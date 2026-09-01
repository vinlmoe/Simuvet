<?php
// Liste de gestion des parcours pédagogiques (§8), profil "Responsable d'UC" /
// "Responsable de salle" selon la capacité manageparcours.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;

require_login();

$context = context_system::instance();
$canmanage = has_capability('local/simhub:manageparcours', $context);
if (!$canmanage && !has_capability('local/simhub:viewprogression', $context)) {
    throw new required_capability_exception($context, 'local/simhub:manageparcours', 'nopermissions', '');
}

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/parcours.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('filtre_parcours', 'local_simhub'));
$PAGE->set_heading(get_string('filtre_parcours', 'local_simhub'));

echo $OUTPUT->header();

if ($canmanage) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/parcours_edit.php'),
        get_string('parcours_nouveau', 'local_simhub')
    );
}

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$parcourslist = parcours::get_records($params, 'nom');

$table = new html_table();
$table->head = [get_string('filtre_parcours', 'local_simhub'), get_string('champ_statut', 'local_simhub'), 'Ateliers', '', ''];

foreach ($parcourslist as $p) {
    $nbateliers = count($p->get_ateliers());
    $suiviurl = new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $p->get('id')]);

    $gestionlinks = '';
    if ($canmanage) {
        $editurl = new moodle_url('/local/simhub/manage/parcours_edit.php', ['id' => $p->get('id')]);
        $ateliersurl = new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $p->get('id')]);
        $gestionlinks = html_writer::link($editurl, get_string('edit')) . ' | '
            . html_writer::link($ateliersurl, 'Composition');
    }

    $table->data[] = [
        s($p->get('nom')),
        s($p->get('type')),
        $nbateliers,
        $gestionlinks,
        html_writer::link($suiviurl, 'Suivi'),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
