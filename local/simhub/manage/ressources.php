<?php
// Liste des ressources pédagogiques d'un atelier (§6.2).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageressources', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'supprimer') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);

    global $DB;
    $fs = get_file_storage();
    $fs->delete_area_files(\local_simhub\local\contexte::fichiers()->id, 'local_simhub', 'ressource', $id);
    $DB->delete_records(ressource::TABLE, ['id' => $id, 'atelierid' => $atelierid]);

    redirect(new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]));
}

$title = get_string('nav_ressources', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
]);
\local_simhub\local\navigation::onglets('atelier', $atelierid, 'ressources');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/ressource_edit.php', ['atelierid' => $atelierid]),
    get_string('ressource_nouvelle', 'local_simhub')
);

global $DB;
$ressources = $DB->get_records(ressource::TABLE, ['atelierid' => $atelierid], 'ordre ASC');

$table = new html_table();
$table->head = [get_string('champ_nomcourt', 'local_simhub'), 'Type', 'Visibilité', ''];
foreach ($ressources as $r) {
    $editurl = new moodle_url('/local/simhub/manage/ressource_edit.php', ['atelierid' => $atelierid, 'id' => $r->id]);
    $delurl = new moodle_url('/local/simhub/manage/ressources.php', [
        'atelierid' => $atelierid, 'action' => 'supprimer', 'id' => $r->id, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        s($r->titre),
        s($r->type),
        s($r->visibilite),
        html_writer::link($editurl, get_string('edit')) . ' | '
            . html_writer::link($delurl, get_string('delete')),
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
