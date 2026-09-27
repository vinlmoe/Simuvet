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
 * Critères d'un atelier fréquemment déclarés à consolider ou à reprendre (§7.1).
 *
 * Depuis l'activité d'une UC (cmid), seuls les étudiants de l'UC sont comptés ; sinon,
 * pour un profil transversal, tous les étudiants.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\local\statistiques;
use local_simhub\persistent\atelier;

$atelierid = required_param('atelierid', PARAM_INT);
$cmid = optional_param('cmid', 0, PARAM_INT);
$atelier = new atelier($atelierid);

if ($cmid) {
    global $CFG;
    require_once($CFG->dirroot . '/mod/simhub/lib.php');
    $cm = get_coursemodule_from_id('simhub', $cmid, 0, false, MUST_EXIST);
    require_login($cm->course, false, $cm);
    require_capability('mod/simhub:viewprogression', context_module::instance($cm->id));
    $simhub = $DB->get_record('simhub', ['id' => $cm->instance], '*', MUST_EXIST);
    if (!$DB->record_exists('local_simhub_parc_atelier', ['parcoursid' => $simhub->parcoursid, 'atelierid' => $atelierid])) {
        throw new moodle_exception('invalidrecord', 'error', '', 'local_simhub_parc_atelier');
    }
    $userids = simhub_etudiants_notes($simhub);
    $etapes = [[format_string($simhub->name), new moodle_url('/mod/simhub/view.php', ['id' => $cm->id])]];
} else {
    require_login();
    require_capability('local/simhub:viewprogression', \local_simhub\local\contexte::racine());
    $userids = null;
    $etapes = [[s($atelier->get('nomcourt')), new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])]];
}

$pageurl = new moodle_url('/local/simhub/manage/ae_stats.php', ['atelierid' => $atelierid, 'cmid' => $cmid]);
if ($cmid) {
    // Dans le cours de l'UC : contexte et fil d'Ariane de l'activité, déjà posés par require_login().
    $PAGE->set_url($pageurl);
    $PAGE->set_title(get_string('stats_titre', 'local_simhub'));
    $PAGE->set_heading(format_string(get_course($cm->course)->fullname));
    $PAGE->navbar->add(get_string('stats_titre', 'local_simhub'));
} else {
    \local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('stats_titre', 'local_simhub'), $etapes);
}
if (!$cmid && has_capability('local/simhub:manageateliers', \local_simhub\local\contexte::racine())) {
    \local_simhub\local\navigation::onglets('atelier', $atelierid, 'stats');
}

echo $OUTPUT->header();
if (!$cmid) {
    echo \local_simhub\local\navigation::barre();
}
echo $OUTPUT->heading(s($atelier->get('numero') . ' — ' . $atelier->get('nomcourt')), 3);
echo html_writer::tag('p', get_string('stats_intro', 'local_simhub'), ['class' => 'text-muted']);

$lignes = statistiques::criteres($atelierid, $userids);
if (!$lignes) {
    echo $OUTPUT->notification(get_string('stats_aucune', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = [
    get_string('stats_rubrique', 'local_simhub'),
    get_string('stats_critere', 'local_simhub'),
    get_string('stats_reponses', 'local_simhub'),
    get_string('niveau_reussi', 'local_simhub'),
    get_string('niveau_a_consolider', 'local_simhub'),
    get_string('niveau_a_reprendre', 'local_simhub'),
];
foreach ($lignes as $l) {
    $row = new html_table_row([
        s($l->rubrique) . ($l->estrisques ? ' ' . html_writer::span(
            get_string('ae_badge_risques', 'local_simhub'),
            'badge badge-warning bg-warning'
        ) : ''),
        s($l->critere),
        $l->total,
        $l->reussi,
        $l->aconsolider . ' (' . $l->pctconsolider . ' %)',
        $l->areprendre . ' (' . $l->pctreprendre . ' %)',
    ]);
    if ($l->pctreprendre + $l->pctconsolider >= 50) {
        $row->attributes['class'] = 'table-warning';
    }
    $table->data[] = $row;
}
echo html_writer::table($table);
echo $OUTPUT->footer();
