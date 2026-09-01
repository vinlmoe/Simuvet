<?php
// Création / modification d'un parcours pédagogique (§8).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\form\parcours_form;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageparcours', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/parcours_edit.php', ['id' => $id]));
$PAGE->set_pagelayout('standard');
$title = $id ? get_string('edit') : get_string('parcours_nouveau', 'local_simhub');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$parcours = $id ? new parcours($id) : new parcours();

$form = new parcours_form();
$form->set_data($parcours->to_record());

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/simhub/manage/parcours.php'));
} else if ($data = $form->get_data()) {
    $isnew = empty($data->id);

    foreach ((array) $data as $key => $value) {
        if ($key === 'id' || $key === 'submitbutton') {
            continue;
        }
        if ($parcours->has_property($key)) {
            $parcours->set($key, $value === '' ? null : $value);
        }
    }

    if ($isnew) {
        $parcours->create();
    } else {
        $parcours->update();
    }

    redirect(
        new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcours->get('id')]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$form->display();
echo $OUTPUT->footer();
