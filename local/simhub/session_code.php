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
 * Saisie du code de séance après un scan QR, quand le contrôle anti-faux-scan est activé
 * (§7.3). Volontairement non bloquant : un étudiant qui n'a pas le code (encadrant absent,
 * code non affiché...) peut tout de même démarrer sa session, marquée comme non vérifiée,
 * et sa réalisation sera soumise à une validation manuelle par un encadrant
 * (manage/sessions_a_valider.php) plutôt que d'être refusée.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\seancecode;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:startsession', $context);

$atelierid = required_param('atelierid', PARAM_INT);
// Origine du démarrage : scan du QR code ou bouton « Commencer » de la fiche.
$methode = optional_param('methode', 'qr', PARAM_ALPHA) === 'manuel' ? 'manuel' : 'qr';
$atelier = new atelier($atelierid);

$pageurl = new moodle_url('/local/simhub/session_code.php', ['atelierid' => $atelierid, 'methode' => $methode]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('seancecode_champ', 'local_simhub'), [
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])],
]);

if (\local_simhub\local\reseau::dans_la_salle()) {
    session::demarrer_ou_reprendre($USER->id, $atelierid, [
        'methodescan' => $methode,
        'controlepresence' => 'reseau_local',
    ]);
    redirect(
        new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
        get_string('session_demarree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$form = new \local_simhub\form\formulaire($pageurl, [
    'champs' => [
        ['text', 'code', get_string('seancecode_champ', 'local_simhub'), [
            'type' => PARAM_ALPHANUMEXT, 'attributs' => ['autocomplete' => 'off', 'size' => 10, 'autofocus' => 'autofocus'],
        ]],
    ],
    'caches' => ['atelierid' => $atelierid],
    'boutons' => [
        'validercode' => get_string('seancecode_valider', 'local_simhub'),
        'sanscode' => get_string('seancecode_pasdecode', 'local_simhub'),
    ],
]);
$erreur = false;

if ($data = $form->get_data()) {
    // Jamais bloquant (§7.3) : sans code, la séance démarre, marquée non vérifiée.
    if (!empty($data->sanscode)) {
        session::demarrer_ou_reprendre($USER->id, $atelierid, [
            'methodescan' => $methode,
            'controlepresence' => 'non_verifie',
        ]);
        redirect(
            new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
            get_string('seancecode_sansvalidation', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }
    $verification = seancecode::verifier($atelier->get('salle'), (string) $data->code);
    if ($verification === seancecode::VALIDE) {
        session::demarrer_ou_reprendre($USER->id, $atelierid, [
            'methodescan' => $methode,
            'controlepresence' => 'code_seance',
        ]);
        redirect(
            new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
            get_string('session_demarree', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    $erreur = $verification;
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('p', get_string('seancecode_intro', 'local_simhub'));

if ($erreur) {
    $message = get_string('seancecode_' . $erreur, 'local_simhub', s((string) $atelier->get('salle')))
        . ' ' . get_string('seancecode_invalide_suite', 'local_simhub');
    echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_ERROR);
}

$form->display();

echo $OUTPUT->footer();
