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
 * Liste des activités SimHub d'un cours.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$event = \mod_simhub\event\course_module_instance_list_viewed::create(['context' => context_course::instance($course->id)]);
$event->add_record_snapshot('course', $course);
$event->trigger();

$PAGE->set_url(new moodle_url('/mod/simhub/index.php', ['id' => $id]));
$PAGE->set_title(get_string('modulenameplural', 'simhub'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('modulenameplural', 'simhub'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'simhub'));

$table = new html_table();
$table->head = [get_string('name')];
foreach (get_all_instances_in_course('simhub', $course) as $simhub) {
    $table->data[] = [html_writer::link(
        new moodle_url('/mod/simhub/view.php', ['id' => $simhub->coursemodule]),
        format_string($simhub->name),
        $simhub->visible ? [] : ['class' => 'dimmed']
    )];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
