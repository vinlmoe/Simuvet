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
 * Validation ASV sur animal vivant (§9.3) : page publique accessible par lien à jeton, sans
 * authentification Moodle, pour un vétérinaire, maître de stage ou encadrant autorisé.
 * Niveau de preuve volontairement simple (nom, prénom, date, case de certification,
 * signature au doigt) — jamais une signature électronique qualifiée (§14, hors périmètre).
 *
 * Deux sortes de liens : token (une demande, générée par l'étudiant) ou lot (lien groupé
 * généré par un encadrant, asv/demande_lot.php) où le validateur coche les étudiants qu'il a
 * vus réaliser l'acte et signe une seule fois pour tous.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Validateur externe sans compte Moodle : l'accès est contrôlé par jeton, pas par connexion.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

$lot = optional_param('lot', '', PARAM_ALPHANUMEXT);
$token = $lot === '' ? required_param('token', PARAM_ALPHANUMEXT) : '';

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/simhub/asv/valider_animal.php', $lot !== '' ? ['lot' => $lot] : ['token' => $token]));
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('asv_formulaire_validateur_titre', 'local_simhub'));
$PAGE->set_heading(get_string('asv_formulaire_validateur_titre', 'local_simhub'));

if ($lot !== '') {
    // Lien groupé : une signature pour les étudiants cochés.
    $demandes = asv_valanimal::get_lot($lot);
    $actes = [];
    $choix = [];
    foreach ($demandes as $demande) {
        $actes[(int) $demande->acteid] = $actes[(int) $demande->acteid] ?? (new asv_acte($demande->acteid))->get('nom');
        $etudiant = core_user::get_user($demande->userid);
        $choix[$demande->id] = ($etudiant ? fullname($etudiant) : '#' . $demande->userid);
    }
    if (count($actes) > 1) {
        foreach ($demandes as $demande) {
            $choix[$demande->id] .= ' — ' . $actes[(int) $demande->acteid];
        }
    }

    $erreur = '';
    if ($demandes) {
        $form = new \local_simhub\form\valanimal_form(
            $PAGE->url,
            ['lot' => $lot, 'demandes' => $choix],
            'post',
            '',
            ['id' => 'local-simhub-valanimal-form']
        );
        if ($data = $form->get_data()) {
            $ids = \local_simhub\local\selection::cochees($data->demandes ?? [], $choix);
            $validees = $ids ? asv_valanimal::valider_lot(
                $lot,
                $ids,
                $data->nom,
                $data->prenom,
                !empty($data->certification),
                $data->signature
            ) : [];
            foreach ($validees as $demande) {
                \local_simhub\event\asv_valide_animal::create([
                    'objectid' => $demande->id,
                    'context' => context_system::instance(),
                    'relateduserid' => $demande->userid,
                ])->trigger();
            }
            if ($validees) {
                redirect(
                    $PAGE->url,
                    get_string('asv_lot_valides', 'local_simhub', count($validees)),
                    null,
                    \core\output\notification::NOTIFY_SUCCESS
                );
            }
            $erreur = get_string($ids ? 'asv_validation_incomplete' : 'selection_vide', 'local_simhub');
        }
    }

    echo $OUTPUT->header();
    if (!$demandes) {
        // Plus rien à signer : soit tout a été validé, soit le lien est invalide ou expiré.
        $signe = $DB->record_exists('local_simhub_asv_valanimal', ['lottoken' => $lot, 'statut' => asv_valanimal::STATUT_VALIDE]);
        echo $signe
            ? $OUTPUT->notification(get_string('asv_lot_termine', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS)
            : $OUTPUT->notification(get_string('asv_lien_invalide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
        echo $OUTPUT->footer();
        exit;
    }
    if ($erreur !== '') {
        echo $OUTPUT->notification($erreur, \core\output\notification::NOTIFY_ERROR);
    }
    if (count($actes) === 1) {
        echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s(reset($actes))));
    }
    echo html_writer::tag('p', get_string('asv_lot_consigne', 'local_simhub'));
    $form->display();
    $PAGE->requires->js_call_amd('local_simhub/signature', 'init', [get_string('asv_signature_requise', 'local_simhub')]);
    echo $OUTPUT->footer();
    exit;
}

$demande = asv_valanimal::get_par_token($token);
$form = null;
if ($demande && $demande->statut !== asv_valanimal::STATUT_VALIDE) {
    $form = new \local_simhub\form\valanimal_form(
        $PAGE->url,
        ['token' => $token],
        'post',
        '',
        ['id' => 'local-simhub-valanimal-form']
    );
    $data = $form->get_data();
    if ($data && asv_valanimal::valider($token, $data->nom, $data->prenom, !empty($data->certification), $data->signature)) {
        \local_simhub\event\asv_valide_animal::create([
            'objectid' => $demande->id,
            'context' => context_system::instance(),
            'relateduserid' => $demande->userid,
        ])->trigger();
        redirect(
            $PAGE->url,
            get_string('asv_valide_avec_succes', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
}

echo $OUTPUT->header();

if (!$demande) {
    echo $OUTPUT->notification(get_string('asv_lien_invalide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

if ($demande->statut === asv_valanimal::STATUT_VALIDE) {
    echo $OUTPUT->notification(get_string('asv_valide_avec_succes', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS);
    echo $OUTPUT->footer();
    exit;
}

if (!empty($data)) {
    echo $OUTPUT->notification(get_string('asv_validation_incomplete', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
}

$acte = new asv_acte($demande->acteid);
echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));

$form->display();
$PAGE->requires->js_call_amd('local_simhub/signature', 'init', [get_string('asv_signature_requise', 'local_simhub')]);

echo $OUTPUT->footer();
