<?php
// Création / modification d'un parcours pédagogique (§8).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\form\parcours_form;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageparcours', $context);

$id = optional_param('id', 0, PARAM_INT);

$title = $id ? get_string('edit') : get_string('parcours_nouveau', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/parcours_edit.php', ['id' => $id]), $title, [
    [get_string('filtre_parcours', 'local_simhub'), new moodle_url('/local/simhub/manage/parcours.php')],
]);
if ($id) {
    \local_simhub\local\navigation::onglets('parcours', $id, 'fiche');
}

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
echo \local_simhub\local\navigation::barre();
$form->display();
echo $OUTPUT->footer();
