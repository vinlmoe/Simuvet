<?php
// Pilotage du parcours ASV (§9.4) : progression individuelle (étudiant connecté) ou vue
// d'ensemble par acte/étudiant (encadrant/gestionnaire ASV).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);
$canpilot = has_capability('local/simhub:manageasv', $context) || has_capability('local/simhub:validateasvsimulation', $context);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/asv/index.php'), get_string('asv_parcours', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$actes = asv_acte::get_referentiel($envcode);

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/asv/livret_pdf.php', ['envcode' => $envcode] + ($canpilot ? [] : ['userid' => $USER->id])),
    'Exporter mon livret (PDF)',
    'get'
);

if (!$canpilot) {
    // Vue étudiant : sa propre progression sur le référentiel (§9.4 "état d'avancement individuel").
    $actesvalidessim = asv_valsim::get_actes_valides($USER->id);
    $valanimal = asv_valanimal::get_pour_etudiant($USER->id);
    $actesvalidesanimal = [];
    foreach ($valanimal as $v) {
        if ($v->statut === \local_simhub\record\asv_valanimal::STATUT_VALIDE) {
            $actesvalidesanimal[$v->acteid] = true;
        }
    }

    $table = new html_table();
    $table->head = ['Acte', 'Niveau', 'Simulation', 'Animal vivant', ''];
    foreach ($actes as $acte) {
        $simok = in_array($acte->get('id'), $actesvalidessim, true);
        $animalok = !empty($actesvalidesanimal[$acte->get('id')]);
        $demanderurl = new moodle_url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acte->get('id')]);
        $table->data[] = [
            s($acte->get('nom')),
            s($acte->get('niveau')),
            $simok ? '✔' : '—',
            $animalok ? '✔' : '—',
            (!$animalok && $simok) ? html_writer::link($demanderurl, get_string('asv_demander_validation_animal', 'local_simhub')) : '',
        ];
    }
    echo html_writer::table($table);
} else {
    // Vue pilotage (§9.4) : par acte, nombre d'étudiants validés en simulation / sur animal vivant.
    global $DB;
    $table = new html_table();
    $table->head = ['Acte', 'Niveau', 'Validés en simulation', 'Validés sur animal vivant'];
    foreach ($actes as $acte) {
        $countsim = $DB->count_records('local_simhub_asv_valsim', ['acteid' => $acte->get('id'), 'statut' => 'valide']);
        $countanimal = $DB->count_records('local_simhub_asv_valanimal', ['acteid' => $acte->get('id'), 'statut' => 'valide']);
        $table->data[] = [s($acte->get('nom')), s($acte->get('niveau')), $countsim, $countanimal];
    }
    echo html_writer::table($table);

    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/asv/valider_simulation.php'),
        get_string('asv_valider_simulation', 'local_simhub')
    );

    echo html_writer::tag('h4', 'Certification globale de fin de A3', ['class' => 'mt-4']);
    echo html_writer::start_tag('form', ['method' => 'get', 'action' => new moodle_url('/local/simhub/asv/attestation_pdf.php')]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'envcode', 'value' => $envcode]);
    echo html_writer::empty_tag('input', [
        'type' => 'number', 'name' => 'userid', 'placeholder' => 'Id étudiant', 'class' => 'form-control d-inline-block w-auto mr-2', 'required' => 'required',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'niveau', 'value' => 'A3']);
    echo html_writer::tag('button', 'Générer l\'attestation A3', ['type' => 'submit', 'class' => 'btn btn-primary']);
    echo html_writer::end_tag('form');
}

echo $OUTPUT->footer();
