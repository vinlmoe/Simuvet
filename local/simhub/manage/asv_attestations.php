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
 * Génération groupée des attestations de certification ASV (§9.4) : liste tous les
 * étudiants ayant entièrement validé un niveau (simulation + animal vivant sur tous les
 * actes), avec téléchargement individuel ou en une fois (ZIP), plutôt que de devoir
 * deviner un par un qui a fini son parcours.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\local\asv_certification_helper;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageasv', $context);

$envcode = '';
$niveau = optional_param('niveau', 'A3', PARAM_ALPHANUM);

$pageurl = new moodle_url('/local/simhub/manage/asv_attestations.php', ['envcode' => $envcode, 'niveau' => $niveau]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_attestations_groupees', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3']);
echo html_writer::tag('label', get_string('asv_champ_niveau', 'local_simhub'), ['class' => 'mr-2']);
echo html_writer::select(
    [
        'A1' => get_string('asv_niveau_a1', 'local_simhub'),
        'A2' => get_string('asv_niveau_a2', 'local_simhub'),
        'A3' => get_string('asv_niveau_a3', 'local_simhub'),
    ],
    'niveau',
    $niveau,
    false,
    ['class' => 'form-control mr-2']
);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'envcode', 'value' => $envcode]);
echo html_writer::tag('button', get_string('filtrer', 'local_simhub'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
echo html_writer::end_tag('form');

$eligibles = asv_certification_helper::get_etudiants_eligibles($niveau, $envcode);

if (empty($eligibles)) {
    echo $OUTPUT->notification(get_string('asv_aucun_eligible', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::tag('p', get_string('asv_nb_eligibles', 'local_simhub', count($eligibles)));

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/asv_attestations_zip.php', ['envcode' => $envcode, 'niveau' => $niveau]),
    get_string('asv_telecharger_tout', 'local_simhub'),
    'get'
);

$table = new html_table();
$table->head = [get_string('champ_nomcourt', 'local_simhub'), ''];
foreach ($eligibles as $userid) {
    $user = \core_user::get_user($userid);
    if (!$user) {
        continue;
    }
    $dlurl = new moodle_url(
        '/local/simhub/asv/attestation_pdf.php',
        ['userid' => $userid, 'niveau' => $niveau, 'envcode' => $envcode],
    );
    $table->data[] = [
        s(fullname($user)),
        html_writer::link($dlurl, get_string('asv_telecharger', 'local_simhub'), ['class' => 'btn btn-outline-primary btn-sm']),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
