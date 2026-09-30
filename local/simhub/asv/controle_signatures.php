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
 * Contrôle interne des signatures externes (§9.3, anti-fraude) : une validation sur animal
 * vivant signée par un validateur sans compte Moodle ne compte qu'une fois confirmée ici par
 * un encadrant ou un enseignant de l'UC de l'étudiant. Confirmation ou rejet en masse, avec
 * les indices relevés à la signature (asv_valanimal::indices()).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valanimal;

require_login();

$context = \local_simhub\local\contexte::racine();
if (!\local_simhub\local\droits::peut_valider_asv()) {
    throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
}
// Un enseignant d'UC ne contrôle que les signatures de ses étudiants.
$transversal = has_capability('local/simhub:validateasvsimulation', $context);
$autorises = $transversal ? [] : array_flip(\local_simhub\local\droits::etudiants_asv_autorises());
$courseid = optional_param('courseid', 0, PARAM_INT);
$inscrits = $courseid ? \local_simhub\local\selecteurs::etudiants_du_cours($courseid) : null;
$visible = fn(int $uid) => ($transversal || isset($autorises[$uid])) && ($inscrits === null || isset($inscrits[$uid]));

$pageurl = new moodle_url('/local/simhub/asv/controle_signatures.php', array_filter(['courseid' => $courseid]));
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_controle_titre', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'confirmer' || $action === 'rejeter') {
    require_sesskey();
    $ids = array_unique(optional_param_array('ids', [], PARAM_INT));
    $motif = trim(optional_param('motif', '', PARAM_TEXT));
    if (!$ids) {
        redirect($pageurl, get_string('selection_vide', 'local_simhub'), null, \core\output\notification::NOTIFY_WARNING);
    }
    if ($action === 'rejeter' && $motif === '') {
        redirect($pageurl, get_string('asv_controle_motif_requis', 'local_simhub'), null,
            \core\output\notification::NOTIFY_WARNING);
    }
    // Contrôle complet avant toute écriture : une sélection ne peut pas déborder du périmètre.
    $demandes = $DB->get_records_list(asv_valanimal::TABLE, 'id', $ids);
    foreach ($demandes as $demande) {
        if (!$visible((int) $demande->userid) || !\local_simhub\local\droits::peut_valider_asv((int) $demande->userid)) {
            throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
        }
    }
    $traitees = 0;
    foreach ($demandes as $demande) {
        $controlee = asv_valanimal::controler($demande->id, $USER->id, $action === 'confirmer', $motif);
        if (!$controlee) {
            // Déjà contrôlée entre-temps (double envoi, autre encadrant).
            continue;
        }
        if ($controlee->statut === asv_valanimal::STATUT_VALIDE) {
            \local_simhub\event\asv_valide_animal::create([
                'objectid' => $controlee->id,
                'context' => $context,
                'relateduserid' => $controlee->userid,
            ])->trigger();
        }
        $traitees++;
    }
    redirect($pageurl, get_string('asv_controle_traitees', 'local_simhub', $traitees), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();
echo html_writer::tag('p', get_string('asv_controle_intro', 'local_simhub'));

$demandes = array_filter(asv_valanimal::get_a_controler(), fn($d) => $visible((int) $d->userid));
if (!$demandes) {
    echo $OUTPUT->notification(get_string('asv_controle_aucune', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

\local_simhub\local\selection::requerir_js();
$format = get_string('strftimedatetimeshort', 'langconfig');
$actes = [];
$table = new html_table();
$table->head = [
    '',
    get_string('etudiant', 'local_simhub'),
    get_string('asv_acte', 'local_simhub'),
    get_string('asv_col_signataire', 'local_simhub'),
    get_string('asv_col_signe_le', 'local_simhub'),
    get_string('asv_col_signature', 'local_simhub'),
    get_string('asv_col_indices', 'local_simhub'),
];
foreach ($demandes as $d) {
    $etudiant = core_user::get_user($d->userid);
    $nom = $etudiant ? fullname($etudiant) : '#' . $d->userid;
    $actes[(int) $d->acteid] = $actes[(int) $d->acteid] ?? (new asv_acte($d->acteid))->get('nom');
    $indices = asv_valanimal::indices($d, $etudiant ?: null);
    $table->data[] = [
        \local_simhub\local\selection::case('ids', $d->id, $nom . ' — ' . $actes[(int) $d->acteid]),
        html_writer::link(new moodle_url('/local/simhub/asv/etudiant.php', ['userid' => $d->userid]), s($nom)),
        s($actes[(int) $d->acteid]),
        s($d->prenomvalidateur . ' ' . $d->nomvalidateur),
        userdate($d->datevalidation, $format),
        asv_valanimal::signature_valide((string) $d->signature)
            ? html_writer::empty_tag('img', [
                'src' => $d->signature, 'alt' => get_string('asv_col_signature', 'local_simhub'),
                'class' => 'local-simhub-signature-apercu',
            ])
            : '',
        implode('', array_map(fn($i) => html_writer::div('⚠ ' . s($i), 'text-danger small'), $indices)),
    ];
}

echo html_writer::start_tag('form', [
    'method' => 'post', 'action' => $pageurl->out(false), 'class' => \local_simhub\local\selection::CONTENEUR,
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::div(html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'motif', 'class' => 'form-control form-control-sm',
    'placeholder' => get_string('asv_controle_motif', 'local_simhub'),
    'aria-label' => get_string('asv_controle_motif', 'local_simhub'),
]), 'mb-2');
echo \local_simhub\local\selection::barre([
    'confirmer' => [get_string('asv_controle_confirmer', 'local_simhub'), 'btn-success'],
    'rejeter' => [get_string('asv_controle_rejeter', 'local_simhub'), 'btn-outline-danger'],
], count($demandes) > 10);
echo html_writer::table($table);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
