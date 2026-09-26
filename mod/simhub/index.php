<?php
// Liste des activités SimHub d'un cours.

require(__DIR__ . '/../../config.php');

$id = required_param('id', PARAM_INT);
$course = get_course($id);
require_course_login($course);

$PAGE->set_url(new moodle_url('/mod/simhub/index.php', ['id' => $id]));
$PAGE->set_title(get_string('modulenameplural', 'simhub'));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->navbar->add(get_string('modulenameplural', 'simhub'));

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('modulenameplural', 'simhub'));

$table = new html_table();
$table->head = [get_string('name')];
foreach (get_all_instances_in_course('simhub', $course) as $simhub) {
    $table->data[] = [html_writer::link(new moodle_url('/mod/simhub/view.php', ['id' => $simhub->coursemodule]),
        format_string($simhub->name), $simhub->visible ? [] : ['class' => 'dimmed'])];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
