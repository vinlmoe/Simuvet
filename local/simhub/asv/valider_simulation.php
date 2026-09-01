<?php
// Validation ASV en simulation par un formateur/encadrant (§9.2). Formulaire minimal :
// étudiant + acte + atelier associé (optionnel). L'auto-évaluation guidée (§5.6) peut être
// une étape préparatoire, mais ne remplace jamais cette validation par un encadrant.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;

require_login();

$context = context_system::instance();
require_capability('local/simhub:validateasvsimulation', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/asv/valider_simulation.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('asv_valider_simulation', 'local_simhub'));
$PAGE->set_heading(get_string('asv_valider_simulation', 'local_simhub'));

$envcode = get_config('local_simhub', 'envcode') ?: '';
$actes = asv_acte::get_referentiel($envcode);

$submitted = optional_param('submit', 0, PARAM_BOOL);

if ($submitted) {
    require_sesskey();

    $userid = required_param('userid', PARAM_INT);
    $acteid = required_param('acteid', PARAM_INT);
    $atelierid = optional_param('atelierid', 0, PARAM_INT);

    core_user::require_active_user(core_user::get_user($userid, '*', MUST_EXIST));

    $extra = [];
    if ($atelierid) {
        $extra['atelierid'] = $atelierid;
    }

    $id = asv_valsim::valider($userid, $acteid, $USER->id, $extra);
    \local_simhub\event\asv_valide_simulation::create([
        'objectid' => $id,
        'context' => $context,
        'relateduserid' => $userid,
    ])->trigger();

    redirect(
        new moodle_url('/local/simhub/asv/index.php'),
        get_string('asv_validation_enregistree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Étudiant (id Moodle)');
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'userid', 'class' => 'form-control', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Acte');
echo html_writer::start_tag('select', ['name' => 'acteid', 'class' => 'form-control']);
foreach ($actes as $acte) {
    echo html_writer::tag('option', s($acte->get('nom')) . ' (' . s($acte->get('niveau')) . ')', ['value' => $acte->get('id')]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Atelier de simulation associé (id, optionnel)');
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'atelierid', 'class' => 'form-control']);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('asv_valider_simulation', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
