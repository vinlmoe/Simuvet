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
 * Positionnement du repère sur le plan de salle par un simple clic sur l'image (§5.4),
 * plutôt que la saisie manuelle des deux champs planrepx/planrepy. Approche pragmatique
 * sans librairie tierce : un clic sur l'image calcule sa position en pourcentage et
 * soumet directement le formulaire.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageateliers', $context);

$id = required_param('id', PARAM_INT);
$atelier = new atelier($id);

$title = get_string('nav_plan', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/atelier_plan.php', ['id' => $id]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), \local_simhub\local\navigation::url('/local/simhub/manage/atelier_edit.php', ['id' => $id])],
]);
\local_simhub\local\navigation::onglets('atelier', $id, 'plan');

$submitted = optional_param('enregistrer', 0, PARAM_BOOL);
if ($submitted) {
    require_sesskey();

    $x = required_param('planrepx', PARAM_FLOAT);
    $y = required_param('planrepy', PARAM_FLOAT);

    $atelier->set('planrepx', $x);
    $atelier->set('planrepy', $y);
    $atelier->update();

    redirect(
        \local_simhub\local\navigation::url('/local/simhub/manage/atelier_plan.php', ['id' => $id]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if (!$atelier->get('planimageitemid')) {
    echo $OUTPUT->notification(
        get_string('plan_absent', 'local_simhub'),
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->continue_button(\local_simhub\local\navigation::url('/local/simhub/manage/atelier_edit.php', ['id' => $id]));
    echo $OUTPUT->footer();
    exit;
}

$fs = get_file_storage();
$planfiles = $fs->get_area_files(
    \local_simhub\local\contexte::fichiers()->id,
    'local_simhub',
    'plan',
    $atelier->get('planimageitemid'),
    'filepath, filename',
    false
);
$planfile = reset($planfiles);

if (!$planfile) {
    echo $OUTPUT->notification(
        get_string('plan_absent', 'local_simhub'),
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->continue_button(\local_simhub\local\navigation::url('/local/simhub/manage/atelier_edit.php', ['id' => $id]));
    echo $OUTPUT->footer();
    exit;
}

$planurl = moodle_url::make_pluginfile_url(
    \local_simhub\local\contexte::fichiers()->id,
    'local_simhub',
    'plan',
    $atelier->get('planimageitemid'),
    '/',
    $planfile->get_filename()
);

echo html_writer::tag('p', get_string('plan_consigne', 'local_simhub'));

echo html_writer::start_tag('form', ['method' => 'post', 'id' => 'local-simhub-plan-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
// Pas de champ nommé « submit » : il masquerait form.submit() utilisé par le module plan.
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'enregistrer', 'value' => 1]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'planrepx']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'planrepy']);
echo html_writer::end_tag('form');

echo html_writer::start_div('local-simhub-plan local-simhub-plan-editable', ['id' => 'local-simhub-plan-container']);
echo html_writer::empty_tag('img', ['src' => $planurl->out(false), 'id' => 'local-simhub-plan-img', 'alt' => '']);
if ($atelier->get('planrepx') !== null && $atelier->get('planrepy') !== null) {
    // Position propre à l'atelier : seule donnée laissée en style en ligne.
    echo html_writer::span('', 'local-simhub-plan-marker', [
        'id' => 'local-simhub-plan-marker',
        'style' => sprintf('left:%s%%;top:%s%%;', (float) $atelier->get('planrepx'), (float) $atelier->get('planrepy')),
    ]);
}
echo html_writer::end_div();
$PAGE->requires->js_call_amd('local_simhub/plan', 'init');

echo $OUTPUT->continue_button(\local_simhub\local\navigation::url('/local/simhub/manage/atelier_edit.php', ['id' => $id]));

echo $OUTPUT->footer();
