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
 * L'étudiant demande une validation animal vivant pour un acte déjà validé en simulation
 * (§9.3). Il indique l'adresse du vétérinaire/maître de stage : le lien de validation lui est
 * envoyé par e-mail et n'est jamais affiché à l'étudiant, qui ne peut donc pas se valider
 * lui-même. Le validateur n'a pas besoin de compte Moodle (valider_animal.php).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$acteid = required_param('acteid', PARAM_INT);
$acte = new asv_acte($acteid);

$pageurl = new moodle_url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acteid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_demander_validation_animal', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

// L'ordre du livret (§9.1) est imposé ici et pas seulement par l'affichage du lien : sans
// validation en simulation, aucune demande sur animal vivant ne peut être générée.
if (!in_array($acteid, \local_simhub\record\asv_valsim::get_actes_valides($USER->id))) {
    echo $OUTPUT->header();
    echo \local_simhub\local\navigation::barre();
    echo $OUTPUT->notification(get_string('asv_simulation_requise', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}
if (asv_valanimal::est_valide($USER->id, $acteid)) {
    redirect(new moodle_url('/local/simhub/asv/index.php'), get_string('asv_deja_valide_animal', 'local_simhub'));
}

$demande = asv_valanimal::get_demande_en_attente($USER->id, $acteid);

// Renvoi du même lien, par exemple si le validateur ne retrouve plus l'e-mail.
if ($demande && optional_param('renvoyer', 0, PARAM_BOOL) && confirm_sesskey()) {
    $envoye = asv_valanimal::envoyer_lien($demande);
    redirect(
        $pageurl,
        get_string($envoye ? 'asv_lien_envoye' : 'asv_lien_non_envoye', 'local_simhub', s($demande->emailvalidateur)),
        null,
        $envoye ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR
    );
}

// Une seule demande active par acte : envoyer à une autre adresse remplace le lien précédent.
$form = new \local_simhub\form\demande_valanimal_form($pageurl, [
    'acteid' => $acteid,
    'userid' => (int) $USER->id,
    'bouton' => get_string($demande ? 'asv_envoyer_autre_adresse' : 'asv_envoyer_lien', 'local_simhub'),
]);
if ($data = $form->get_data()) {
    $demande = asv_valanimal::creer_demande($USER->id, $acteid, $data->email);
    redirect(
        $pageurl,
        get_string($demande->envoye ? 'asv_lien_envoye' : 'asv_lien_non_envoye', 'local_simhub', s($demande->emailvalidateur)),
        null,
        $demande->envoye ? \core\output\notification::NOTIFY_SUCCESS : \core\output\notification::NOTIFY_ERROR
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();
echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));

if ($demande) {
    echo $OUTPUT->notification(get_string('asv_demande_envoyee', 'local_simhub', (object) [
        'email' => s($demande->emailvalidateur),
        'date' => userdate($demande->tokenexpire, get_string('strftimedatetimeshort', 'langconfig')),
    ]), \core\output\notification::NOTIFY_INFO, false);
    echo $OUTPUT->single_button(
        new moodle_url($pageurl, ['renvoyer' => 1, 'sesskey' => sesskey()]),
        get_string('asv_renvoyer_lien', 'local_simhub'),
        'post'
    );
    echo $OUTPUT->heading(get_string('asv_envoyer_autre_adresse', 'local_simhub'), 4, 'mt-4');
} else {
    echo html_writer::tag('p', get_string('asv_transmettre_lien', 'local_simhub'));
}
$form->display();

echo $OUTPUT->footer();
