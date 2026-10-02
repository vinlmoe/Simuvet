<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Démarrage / fin d'une session d'atelier par un étudiant (§7), avec auto-évaluation
 * guidée à la fin (§5.6, §7.2). Volontairement une seule page à deux étapes plutôt qu'un
 * tunnel complexe : démarrer redirige immédiatement vers la fiche, terminer affiche la
 * grille d'auto-évaluation si l'atelier en a une, sinon clôture directement la session.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\ae_rubrique;
use local_simhub\record\ae_critere;
use local_simhub\record\ae_reponse;
use local_simhub\record\ae_bilan;

require_login();

$context = \local_simhub\local\contexte::racine();

$atelierid = required_param('atelierid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$sessionid = optional_param('sessionid', 0, PARAM_INT);

$atelier = new atelier($atelierid);

$pageurl = new moodle_url('/local/simhub/session.php', ['atelierid' => $atelierid, 'action' => $action]);
$urlfiche = \local_simhub\local\navigation::url('/local/simhub/atelier.php', ['id' => $atelierid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('nav_seance', 'local_simhub'), [
    [s($atelier->get('nomcourt')), $urlfiche],
]);

if ($action === 'demarrer') {
    require_capability('local/simhub:startsession', $context);
    require_sesskey();

    if (session::est_valide($USER->id, $atelierid)) {
        redirect($urlfiche, get_string('atelier_deja_valide', 'local_simhub'), null, \core\output\notification::NOTIFY_INFO);
    }

    // Même contrôle anti-faux-scan que le QR code (§7.3) : sans lui, le bouton « Commencer »
    // permettrait de démarrer depuis n'importe où sans saisir le code de séance.
    if (get_config('local_simhub', 'controlepresenceactif') && !\local_simhub\local\reseau::dans_la_salle()) {
        redirect(\local_simhub\local\navigation::url(
            '/local/simhub/session_code.php',
            ['atelierid' => $atelierid, 'methode' => 'manuel']
        ));
    }
    $controle = get_config('local_simhub', 'controlepresenceactif') ? 'reseau_local' : null;
    session::demarrer_ou_reprendre($USER->id, $atelierid, ['methodescan' => 'manuel', 'controlepresence' => $controle]);

    redirect(
        $urlfiche,
        get_string('session_demarree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

if ($action !== 'terminer') {
    throw new \moodle_exception('invalidaction', 'error');
}

require_capability('local/simhub:startsession', $context);

if (!$sessionid) {
    redirect(\local_simhub\local\navigation::url_retour());
}

$session = new session($sessionid);
if ($session->get('userid') != $USER->id) {
    throw new \moodle_exception('nopermissions', 'error', '', 'session');
}

global $DB;
$modele = $DB->get_record('local_simhub_ae_modele', ['atelierid' => $atelierid, 'actif' => 1]);

$niveaux = [];
foreach ([ae_reponse::NIVEAU_REUSSI, ae_reponse::NIVEAU_A_CONSOLIDER, ae_reponse::NIVEAU_A_REPRENDRE] as $niveau) {
    $niveaux[$niveau] = get_string('niveau_' . $niveau, 'local_simhub');
}
$form = null;
$criteresids = [];
if ($modele) {
    // Présentation verticale, critère par critère, lisible sur téléphone (§7.2).
    $champs = [];
    foreach (ae_rubrique::get_pour_modele($modele->id) as $rubrique) {
        $champs[] = ['header', 'rubrique' . $rubrique->id, format_string($rubrique->titre)];
        foreach (ae_critere::get_pour_rubrique($rubrique->id) as $critere) {
            $criteresids[] = (int) $critere->id;
            $champs[] = ['radio', 'critere_' . $critere->id, format_string($critere->libelle), ['choix' => $niveaux]];
        }
    }
    $champs[] = ['header', 'autobilan', get_string('ae_autobilan', 'local_simhub')];
    foreach (['pointmaitrise', 'pointaretravailler', 'pointattention'] as $champ) {
        $champs[] = ['textarea', $champ, get_string('champ_' . $champ, 'local_simhub'), [
            'attributs' => ['rows' => 2, 'cols' => 40],
        ]];
    }
    $form = new \local_simhub\form\formulaire(
        \local_simhub\local\navigation::url(
            '/local/simhub/session.php',
            ['atelierid' => $atelierid, 'action' => 'terminer', 'sessionid' => $sessionid]
        ),
        ['champs' => $champs, 'bouton' => get_string('bouton_terminer', 'local_simhub')]
    );
}

if ($form && ($data = $form->get_data())) {
    require_capability('local/simhub:submitautoeval', $context);
    foreach ($criteresids as $critereid) {
        $niveau = $data->{'critere_' . $critereid} ?? '';
        if (isset($niveaux[$niveau])) {
            ae_reponse::repondre($sessionid, $critereid, $niveau);
        }
    }
    $bilan = array_map(fn($c) => trim($data->$c ?? ''), ['pointmaitrise', 'pointaretravailler', 'pointattention']);
    if (implode('', $bilan) !== '') {
        ae_bilan::enregistrer($sessionid, ...$bilan);
    }

    $session->terminer();
    \local_simhub\event\session_completed::create([
        'objectid' => $session->get('id'),
        'context' => $context,
    ])->trigger();

    // Retour là d'où l'on vient : l'activité de l'UC, ou l'accueil SimHub.
    redirect(
        \local_simhub\local\navigation::url_retour(),
        get_string('autoeval_enregistree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if (!$modele) {
    // Pas de grille associée à cet atelier : on clôture directement la session (§7.2, la
    // grille est optionnelle selon paramétrage). Ne termine (et ne déclenche l'événement)
    // qu'une seule fois : revisiter cette page ne doit pas réécrire timeend à chaque affichage.
    if ($session->get('statut') === session::STATUT_COMMENCE) {
        $session->terminer();
        \local_simhub\event\session_completed::create([
            'objectid' => $session->get('id'),
            'context' => $context,
        ])->trigger();
    }
    echo $OUTPUT->notification(get_string('session_terminee', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS);
    echo $OUTPUT->continue_button(\local_simhub\local\navigation::url_retour());
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('h3', format_string($modele->titre));
$form->display();

echo $OUTPUT->footer();
