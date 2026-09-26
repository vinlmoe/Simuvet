<?php
// Génération d'un code de séance temporaire pour une salle (§7.3, mécanisme "code de
// séance" du contrôle anti-faux-scan). L'encadrant génère le code en début de séance et
// le communique aux étudiants (tableau, projection...) ; il reste valable une durée
// limitée (local_simhub/seancecodeduration).

require(__DIR__ . '/../../../config.php');

use local_simhub\record\seancecode;
use local_simhub\persistent\atelier;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:validatesession', $context);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/seancecode_generer.php'), get_string('seancecode_generer', 'local_simhub'));

$submitted = optional_param('submit', 0, PARAM_BOOL);
$genere = null;

if ($submitted) {
    require_sesskey();
    $salle = required_param('salle', PARAM_TEXT);
    if ($salle !== '') {
        $genere = seancecode::generer($salle, $USER->id);
    }
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($genere) {
    echo $OUTPUT->notification(
        get_string('seancecode_genere', 'local_simhub') . ' : ' . html_writer::tag('strong', s($genere->code)),
        \core\output\notification::NOTIFY_SUCCESS
    );
    echo html_writer::tag('p', get_string('seancecode_validite', 'local_simhub',
        userdate($genere->validto, get_string('strftimedatetimeshort', 'langconfig'))));
}

global $DB;
$salles = $DB->get_records_sql(
    "SELECT DISTINCT salle FROM {local_simhub_atelier} WHERE salle IS NOT NULL AND salle <> '' ORDER BY salle"
);

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('seancecode_champ_salle', 'local_simhub'));
if (!empty($salles)) {
    echo html_writer::start_tag('select', ['name' => 'salle', 'class' => 'form-control d-inline-block w-auto']);
    foreach ($salles as $s) {
        echo html_writer::tag('option', s($s->salle), ['value' => s($s->salle)]);
    }
    echo html_writer::end_tag('select');
} else {
    echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'salle', 'class' => 'form-control d-inline-block w-auto']);
}
echo html_writer::end_div();

echo html_writer::tag('button', get_string('seancecode_generer', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
