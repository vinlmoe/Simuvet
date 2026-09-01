<?php
// Création / modification d'un acte du référentiel ASV (§9).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageasv', $context);

$id = optional_param('id', 0, PARAM_INT);
$acte = $id ? new asv_acte($id) : new asv_acte();

$title = $id ? get_string('edit') : get_string('asv_acte_nouveau', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/asv_acte_edit.php', ['id' => $id]), $title, [
    [get_string('asv_gerer_actes', 'local_simhub'), new moodle_url('/local/simhub/manage/asv_actes.php')],
]);

$submitted = optional_param('submit', 0, PARAM_BOOL);
if ($submitted) {
    require_sesskey();

    $code = required_param('code', PARAM_ALPHANUMEXT);
    $nom = required_param('nom', PARAM_TEXT);
    $espece = optional_param('espece', '', PARAM_TEXT);
    $niveau = required_param('niveau', PARAM_ALPHANUM);
    $ucid = optional_param('ucid', 0, PARAM_INT);
    $envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);
    $actif = optional_param('actif', 0, PARAM_BOOL);

    $acte->set('code', $code);
    $acte->set('nom', $nom);
    $acte->set('espece', $espece ?: null);
    $acte->set('niveau', $niveau);
    $acte->set('ucid', $ucid ?: null);
    $acte->set('envcode', $envcode);
    $acte->set('actif', $actif ? 1 : 0);

    if ($acte->get('id')) {
        $acte->update();
    } else {
        $acte->create();
    }

    redirect(
        new moodle_url('/local/simhub/manage/asv_actes.php'),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

$champs = [
    'code' => [get_string('asv_champ_code', 'local_simhub'), 'text', true],
    'nom' => [get_string('champ_nomcourt', 'local_simhub'), 'text', true],
    'espece' => [get_string('champ_espece', 'local_simhub'), 'text', false],
    'envcode' => [get_string('champ_envcode', 'local_simhub'), 'text', false],
    'ucid' => [get_string('asv_champ_ucid', 'local_simhub'), 'number', false],
];

foreach ($champs as $name => [$label, $type, $required]) {
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', $label, ['for' => 'id_' . $name]);
    $attrs = [
        'type' => $type, 'name' => $name, 'id' => 'id_' . $name, 'class' => 'form-control d-inline-block w-auto ml-2',
        'value' => $id ? s($acte->get($name)) : ($name === 'envcode' ? s(get_config('local_simhub', 'envcode') ?: '') : ''),
    ];
    if ($required) {
        $attrs['required'] = 'required';
    }
    echo html_writer::empty_tag('input', $attrs);
    echo html_writer::end_div();
}

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('asv_champ_niveau', 'local_simhub'), ['for' => 'id_niveau']);
echo html_writer::select(
    ['A1' => get_string('asv_niveau_a1', 'local_simhub'), 'A2' => get_string('asv_niveau_a2', 'local_simhub'), 'A3' => get_string('asv_niveau_a3', 'local_simhub')],
    'niveau',
    $id ? $acte->get('niveau') : 'A1',
    false,
    ['id' => 'id_niveau', 'class' => 'form-control d-inline-block w-auto ml-2']
);
echo html_writer::end_div();

echo html_writer::start_tag('label', ['class' => 'mr-2']);
echo html_writer::empty_tag('input', array_merge(
    ['type' => 'checkbox', 'name' => 'actif', 'value' => 1],
    (!$id || $acte->get('actif')) ? ['checked' => 'checked'] : []
));
echo ' ' . get_string('ae_champ_actif', 'local_simhub');
echo html_writer::end_tag('label');

echo html_writer::tag('div', html_writer::tag('button', get_string('savechanges'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]), ['class' => 'mt-3']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
