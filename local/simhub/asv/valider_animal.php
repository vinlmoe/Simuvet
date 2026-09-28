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
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Validateur externe sans compte Moodle : l'accès est contrôlé par jeton, pas par connexion.
// phpcs:ignore moodle.Files.RequireLogin.Missing
require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

$token = required_param('token', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $token]));
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('asv_formulaire_validateur_titre', 'local_simhub'));
$PAGE->set_heading(get_string('asv_formulaire_validateur_titre', 'local_simhub'));

$demande = asv_valanimal::get_par_token($token);
// Un étudiant ne se valide jamais lui-même, même s'il a obtenu le lien (§9.3). Le contrôle
// ne porte que sur une session Moodle ouverte : le validateur externe n'en a pas.
$connecte = isloggedin() && !isguestuser() ? (int) $USER->id : 0;
$autovalidation = $demande && $connecte && $connecte == $demande->userid;
$form = null;
if ($demande && $demande->statut !== asv_valanimal::STATUT_VALIDE && !$autovalidation) {
    $form = new \local_simhub\form\valanimal_form(
        $PAGE->url,
        ['token' => $token],
        'post',
        '',
        ['id' => 'local-simhub-valanimal-form']
    );
    $data = $form->get_data();
    $valide = $data && asv_valanimal::valider(
        $token,
        $data->nom,
        $data->prenom,
        !empty($data->certification),
        $data->signature,
        $connecte
    );
    if ($valide) {
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

if ($autovalidation) {
    echo $OUTPUT->notification(get_string('asv_autovalidation_interdite', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
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
