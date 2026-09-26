<?php
// Création / modification d'une ressource pédagogique (§6.2), avec upload de fichier via
// l'API filestorage de Moodle. L'itemid de la zone de fichiers 'ressource' est toujours
// l'id de l'enregistrement local_simhub_ressource lui-même (cf. lib.php::local_simhub_pluginfile
// et persistent\ressource::fileitemid), ce qui évite d'avoir à faire correspondre deux
// identifiants différents.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;
use local_simhub\form\ressource_form;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageressources', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$id = optional_param('id', 0, PARAM_INT);
$atelier = new atelier($atelierid);

$fileoptions = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => '*'];

global $DB;
$record = $id ? $DB->get_record(ressource::TABLE, ['id' => $id, 'atelierid' => $atelierid], '*', MUST_EXIST) : (object) [
    'id' => 0,
    'atelierid' => $atelierid,
    'titre' => '',
    'type' => 'fiche_methode',
    'visibilite' => ressource::VISIBILITE_ETUDIANT,
    'url' => '',
    'ordre' => 0,
];

$title = $id ? get_string('edit') : get_string('ressource_nouvelle', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/ressource_edit.php', ['atelierid' => $atelierid, 'id' => $id]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
    [get_string('bouton_ressources', 'local_simhub'), new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid])],
]);
\local_simhub\local\navigation::onglets('atelier', $atelierid, 'ressources');

$draftitemid = file_get_submitted_draft_itemid('fichier');
file_prepare_draft_area($draftitemid, \local_simhub\local\contexte::fichiers()->id, 'local_simhub', 'ressource', $id ?: null, $fileoptions);

$form = new ressource_form();
$data = clone $record;
$data->fichier = $draftitemid;
$form->set_data($data);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]));
} else if ($formdata = $form->get_data()) {
    $record->titre = $formdata->titre;
    $record->type = $formdata->type;
    // Une source éditable reste interne quelle que soit la visibilité choisie (§6.2).
    $record->visibilite = $formdata->type === ressource::TYPE_SOURCE_EDITABLE
        ? ressource::VISIBILITE_INTERNE : $formdata->visibilite;
    $record->url = $formdata->url;
    $record->ordre = (int) $formdata->ordre;
    $record->timemodified = time();
    $record->usermodified = $USER->id;

    if ($record->id) {
        $DB->update_record(ressource::TABLE, $record);
    } else {
        $record->timecreated = time();
        $record->fileitemid = 0;
        $record->id = $DB->insert_record(ressource::TABLE, $record);
    }

    // L'itemid de fichier est fixé à l'id de la ressource elle-même : on sauvegarde donc la
    // zone de brouillon seulement une fois cet id connu.
    file_save_draft_area_files($formdata->fichier, \local_simhub\local\contexte::fichiers()->id, 'local_simhub', 'ressource', $record->id, $fileoptions);

    $fs = get_file_storage();
    $files = $fs->get_area_files(\local_simhub\local\contexte::fichiers()->id, 'local_simhub', 'ressource', $record->id, 'filepath, filename', false);
    $record->fileitemid = !empty($files) ? $record->id : 0;
    $DB->update_record(ressource::TABLE, $record);

    redirect(
        new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();
$form->display();
echo $OUTPUT->footer();
