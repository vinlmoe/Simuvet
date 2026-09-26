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
 * File d'attente des sessions démarrées sans code de séance valide (§7.3), à valider
 * manuellement par un encadrant — le filet de sécurité qui garantit que le contrôle
 * anti-faux-scan n'est jamais bloquant de façon absolue pour l'étudiant.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\val_encadrant;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:validatesession', $context);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'valider' || $action === 'refuser') {
    require_sesskey();
    $sessionid = required_param('sessionid', PARAM_INT);

    $statut = $action === 'valider' ? val_encadrant::STATUT_VALIDE : val_encadrant::STATUT_REFUSE;
    \local_simhub\local\parcours_helper::valider_seance($sessionid, $USER->id, $statut);

    redirect(new moodle_url('/local/simhub/manage/sessions_a_valider.php'));
}

$pageurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('sessions_a_valider', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

global $DB;
$sessions = $DB->get_records_sql(
    "SELECT s.*
       FROM {local_simhub_session} s
      WHERE (s.controlepresence = 'non_verifie' OR s.dureesuspecte = 1)
        AND NOT EXISTS (SELECT 1 FROM {local_simhub_val_encadrant} v WHERE v.sessionid = s.id)
   ORDER BY s.timestart DESC"
);

if (empty($sessions)) {
    echo $OUTPUT->notification(get_string('sessions_aucune_a_valider', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('fullnameuser'),
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('champ_statut', 'local_simhub'),
    get_string('session_demarree_le', 'local_simhub'),
    get_string('session_motif', 'local_simhub'),
    '',
];

foreach ($sessions as $s) {
    $atelier = new atelier($s->atelierid);
    $user = \core_user::get_user($s->userid);

    $validerurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php', [
        'action' => 'valider', 'sessionid' => $s->id, 'sesskey' => sesskey(),
    ]);
    $refuserurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php', [
        'action' => 'refuser', 'sessionid' => $s->id, 'sesskey' => sesskey(),
    ]);

    $table->data[] = [
        $user ? fullname($user) : '#' . $s->userid,
        s($atelier->get('nomcourt')),
        get_string('statutperso_' . ($s->statut === session::STATUT_COMMENCE ? 'commence' : 'realise'), 'local_simhub'),
        userdate($s->timestart, get_string('strftimedatetimeshort', 'langconfig')),
        \local_simhub\local\parcours_helper::motif_a_valider($s),
        html_writer::link($validerurl, get_string('session_valider', 'local_simhub'), ['class' => 'btn btn-sm btn-success mr-1'])
            . html_writer::link(
                $refuserurl,
                get_string('session_refuser', 'local_simhub'),
                ['class' => 'btn btn-sm btn-outline-danger'],
            ),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
