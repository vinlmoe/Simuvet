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
 * Activité SimHub d'une UC : l'étudiant voit son avancement, l'enseignant suit ses
 * étudiants, compose le parcours de l'UC et valide les séances de ses ateliers.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

use local_simhub\local\parcours_helper;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\record\val_encadrant;

$id = required_param('id', PARAM_INT);
$cm = get_coursemodule_from_id('simhub', $id, 0, false, MUST_EXIST);
$course = get_course($cm->course);
$simhub = $DB->get_record('simhub', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = context_module::instance($cm->id);
require_capability('mod/simhub:view', $context);

$parcours = new parcours($simhub->parcoursid);
$url = new moodle_url('/mod/simhub/view.php', ['id' => $cm->id]);

$composition = $parcours->get_ateliers();
$atelierids = array_map('intval', array_column($composition, 'atelierid'));

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'valider' || $action === 'refuser') {
    require_sesskey();
    require_capability('mod/simhub:validatesession', $context);
    $session = $DB->get_record('local_simhub_session', ['id' => required_param('sessionid', PARAM_INT)], '*', MUST_EXIST);
    if (
        !in_array((int) $session->atelierid, $atelierids, true)
            || !in_array((int) $session->userid, simhub_etudiants_notes($simhub, (int) $session->userid), true)
    ) {
        throw new moodle_exception('nopermissions', 'error', '', get_string('simhub:validatesession', 'simhub'));
    }
    parcours_helper::valider_seance(
        $session->id,
        $USER->id,
        $action === 'valider' ? val_encadrant::STATUT_VALIDE : val_encadrant::STATUT_REFUSE
    );
    redirect($url, get_string('changessaved'), null, \core\output\notification::NOTIFY_SUCCESS);
}

$event = \mod_simhub\event\course_module_viewed::create(['context' => $context, 'objectid' => $simhub->id]);
$event->add_record_snapshot('course_modules', $cm);
$event->add_record_snapshot('course', $course);
$event->add_record_snapshot('simhub', $simhub);
$event->trigger();

$completion = new completion_info($course);
$completion->set_module_viewed($cm);

$PAGE->set_url($url);
$PAGE->set_title(format_string($simhub->name));
$PAGE->set_heading(format_string($course->fullname));

echo $OUTPUT->header();

$enseignant = has_capability('mod/simhub:viewprogression', $context);
$ateliers = [];
foreach ($composition as $lien) {
    $ateliers[(int) $lien->atelierid] = new atelier($lien->atelierid);
}
$datefmt = get_string('strftimedate', 'langconfig');

if (!$enseignant) {
    $prog = parcours_helper::progression($parcours, $USER->id);
    echo html_writer::tag('p', get_string('avancement', 'simhub', $prog));
    echo html_writer::div(html_writer::div('', 'progress-bar', ['role' => 'progressbar',
        'style' => 'width:' . $prog['pct'] . '%', 'aria-valuenow' => $prog['pct'], 'aria-valuemin' => 0,
        'aria-valuemax' => 100]), 'progress mb-3');

    $table = new html_table();
    $table->head = [get_string('atelier', 'simhub'), get_string('obligatoire', 'simhub'),
        get_string('echeance', 'simhub'), get_string('etat', 'simhub')];
    foreach ($composition as $lien) {
        $a = $ateliers[(int) $lien->atelierid];
        $statut = parcours_helper::statut_atelier($USER->id, $a->get('id'));
        $table->data[] = [
            html_writer::link(
                new moodle_url('/local/simhub/atelier.php', ['id' => $a->get('id')]),
                s($a->get('numero') . ' — ' . $a->get('nomcourt'))
            ),
            $lien->obligatoire ? get_string('yes') : '',
            $lien->echeance ? userdate($lien->echeance, $datefmt) : '',
            get_string('statutperso_' . $statut, 'local_simhub'),
        ];
    }
    echo $composition ? html_writer::table($table)
        : $OUTPUT->notification(get_string('aucunatelier', 'simhub'), \core\output\notification::NOTIFY_INFO);
    echo html_writer::tag('p', get_string('scaninfo', 'simhub'), ['class' => 'text-muted']);
    echo html_writer::div(\local_simhub\local\exporteur::liens(['type' => 'etudiant']), 'mb-3');
    echo $OUTPUT->footer();
    exit;
}

// Actions de l'enseignant.
$boutons = [];
if (has_capability('mod/simhub:manageparcours', $context)) {
    $boutons[] = html_writer::link(new moodle_url(
        '/local/simhub/manage/parcours_ateliers.php',
        ['parcoursid' => $parcours->get('id')]
    ), get_string('composer', 'simhub'), ['class' => 'btn btn-primary mr-2 me-2']);
}
$boutons[] = html_writer::link(new moodle_url(
    '/local/simhub/manage/parcours_suivi.php',
    ['parcoursid' => $parcours->get('id')]
), get_string('suividetaille', 'simhub'), ['class' => 'btn btn-secondary mr-2 me-2']);
if (has_capability('mod/simhub:validateasvsimulation', $context)) {
    $boutons[] = html_writer::link(
        new moodle_url('/local/simhub/asv/valider_simulation.php'),
        get_string('validerasv', 'simhub'),
        ['class' => 'btn btn-secondary']
    );
}
echo html_writer::div(implode('', $boutons), 'mb-3');
echo html_writer::div(
    html_writer::tag('strong', get_string('exportuc', 'simhub')) . ' '
        . \local_simhub\local\exporteur::liens(['type' => 'uc', 'courseid' => $course->id]),
    'mb-3'
);

// Ateliers de l'UC, avec accès à leur grille d'auto-évaluation.
echo $OUTPUT->heading(get_string('ateliersuc', 'simhub'), 3);
if ($composition) {
    $table = new html_table();
    $table->head = [get_string('atelier', 'simhub'), get_string('obligatoire', 'simhub'),
        get_string('echeance', 'simhub'), ''];
    foreach ($composition as $lien) {
        $a = $ateliers[(int) $lien->atelierid];
        $table->data[] = [
            html_writer::link(
                new moodle_url('/local/simhub/atelier.php', ['id' => $a->get('id')]),
                s($a->get('numero') . ' — ' . $a->get('nomcourt'))
            ),
            $lien->obligatoire ? get_string('yes') : '',
            $lien->echeance ? userdate($lien->echeance, $datefmt) : '',
            has_capability('mod/simhub:manageparcours', $context)
                ? html_writer::link(
                    new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $a->get('id')]),
                    get_string('grille', 'simhub')
                )
                : '',
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('aucunatelier', 'simhub'), \core\output\notification::NOTIFY_INFO);
}

// Avancement des étudiants, par groupe si l'activité est en mode groupes.
$groupmode = groups_get_activity_groupmode($cm);
echo $OUTPUT->heading(get_string('avancementetudiants', 'simhub'), 3);
groups_print_activity_menu($cm, $url);
$groupid = $groupmode ? groups_get_activity_group($cm, true) : 0;

$etudiants = simhub_etudiants_notes($simhub);
if ($groupid) {
    $membres = array_map('intval', array_keys(groups_get_members($groupid, 'u.id')));
    $etudiants = array_values(array_intersect($etudiants, $membres));
}

if ($etudiants) {
    $table = new html_table();
    $table->head = [get_string('fullnameuser'), get_string('avancementcol', 'simhub'), get_string('historique', 'simhub')];
    foreach ($etudiants as $uid) {
        $prog = parcours_helper::progression($parcours, $uid);
        $table->data[] = [
            fullname(core_user::get_user($uid)),
            $prog['pct'] . ' % (' . $prog['realises'] . '/' . $prog['total'] . ')',
            html_writer::link(
                new moodle_url('/local/simhub/manage/export.php', ['type' => 'etudiant', 'userid' => $uid, 'format' => 'xlsx']),
                get_string('export_format_xlsx', 'local_simhub')
            ),
        ];
    }
    echo html_writer::table($table);
} else {
    echo $OUTPUT->notification(get_string('aucunetudiant', 'simhub'), \core\output\notification::NOTIFY_INFO);
}

// Séances à valider : réalisées ou non vérifiées, sur un atelier de l'UC, par un étudiant
// de l'UC, où que la séance ait été lancée.
if (has_capability('mod/simhub:validatesession', $context) && $atelierids && $etudiants) {
    [$ainsql, $aparams] = $DB->get_in_or_equal($atelierids, SQL_PARAMS_NAMED, 'a');
    [$uinsql, $uparams] = $DB->get_in_or_equal($etudiants, SQL_PARAMS_NAMED, 'u');
    $seances = $DB->get_records_sql(
        "SELECT s.*
           FROM {local_simhub_session} s
          WHERE s.atelierid $ainsql AND s.userid $uinsql
            AND (s.statut = :realise OR s.controlepresence = :nonverifie)
            AND NOT EXISTS (SELECT 1 FROM {local_simhub_val_encadrant} v WHERE v.sessionid = s.id)
       ORDER BY s.timestart DESC",
        $aparams + $uparams + ['realise' => \local_simhub\persistent\session::STATUT_REALISE, 'nonverifie' => 'non_verifie']
    );

    echo $OUTPUT->heading(get_string('seancesavalider', 'simhub'), 3);
    if ($seances) {
        $table = new html_table();
        $table->head = [get_string('fullnameuser'), get_string('atelier', 'simhub'), get_string('date'),
            get_string('etat', 'simhub'), ''];
        foreach ($seances as $s) {
            $params = ['id' => $cm->id, 'sessionid' => $s->id, 'sesskey' => sesskey()];
            $table->data[] = [
                fullname(core_user::get_user($s->userid)),
                s($ateliers[(int) $s->atelierid]->get('nomcourt')),
                userdate($s->timestart, get_string('strftimedatetimeshort', 'langconfig')),
                get_string('statutperso_' . ($s->statut === 'commence' ? 'commence' : 'realise'), 'local_simhub')
                    . (($motif = parcours_helper::motif_a_valider($s)) !== '' ? ' — ' . $motif : ''),
                html_writer::link(
                    new moodle_url($url, $params + ['action' => 'valider']),
                    get_string('session_valider', 'local_simhub'),
                    ['class' => 'btn btn-sm btn-success mr-1 me-1']
                )
                . html_writer::link(
                    new moodle_url($url, $params + ['action' => 'refuser']),
                    get_string('session_refuser', 'local_simhub'),
                    ['class' => 'btn btn-sm btn-outline-danger']
                ),
            ];
        }
        echo html_writer::table($table);
    } else {
        echo $OUTPUT->notification(get_string('aucuneseance', 'simhub'), \core\output\notification::NOTIFY_INFO);
    }
}

echo $OUTPUT->footer();
