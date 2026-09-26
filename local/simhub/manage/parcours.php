<?php
// Liste de gestion des parcours pédagogiques (§8), profil "Responsable d'UC" /
// "Responsable de salle" selon la capacité manageparcours.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;

require_login();

$context = \local_simhub\local\contexte::racine();
$canmanage = has_capability('local/simhub:manageparcours', $context);
if (!$canmanage && !has_capability('local/simhub:viewprogression', $context)) {
    throw new required_capability_exception($context, 'local/simhub:manageparcours', 'nopermissions', '');
}

$envcode = '';

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/parcours.php'), get_string('filtre_parcours', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($canmanage) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/parcours_edit.php'),
        get_string('parcours_nouveau', 'local_simhub')
    );
}

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$parcourslist = parcours::get_records($params, 'nom');

$table = new html_table();
$table->head = [get_string('filtre_parcours', 'local_simhub'), get_string('type', 'local_simhub'),
    get_string('nb_ateliers', 'local_simhub'), get_string('actions')];

foreach ($parcourslist as $p) {
    $nbateliers = count($p->get_ateliers());
    $suiviurl = new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $p->get('id')]);

    $gestionlinks = \local_simhub\local\navigation::menu_actions(
        \local_simhub\local\navigation::liens_parcours($p->get('id'))
    );

    $table->data[] = [
        html_writer::link($suiviurl, s($p->get('nom'))),
        get_string('parcours_type_' . $p->get('type'), 'local_simhub'),
        $nbateliers,
        $gestionlinks,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
