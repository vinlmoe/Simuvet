<?php
// Création / modification d'une fiche atelier (§6), avec gestion du statut
// "indisponible" (§6.1) : ouverture/clôture d'une entrée dans local_simhub_indispo.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\form\atelier_form;
use local_simhub\record\indispo;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageateliers', $context);

$id = optional_param('id', 0, PARAM_INT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$title = $id ? get_string('atelier_modifier', 'local_simhub') : get_string('atelier_nouveau', 'local_simhub');
$PAGE->set_title($title);
$PAGE->set_heading($title);

$atelier = $id ? new atelier($id) : new atelier();
$oldstatut = $atelier->get('id') ? $atelier->get('statut') : null;

$planoptions = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg']];
$plandraftid = file_get_submitted_draft_itemid('planimage');
file_prepare_draft_area($plandraftid, $context->id, 'local_simhub', 'plan', $id ?: null, $planoptions);

$form = new atelier_form();
$formdata = $atelier->to_record();
$formdata->planimage = $plandraftid;
$form->set_data($formdata);

if ($form->is_cancelled()) {
    redirect(new moodle_url('/local/simhub/manage/ateliers.php'));
} else if ($data = $form->get_data()) {
    $isnew = empty($data->id);

    foreach ((array) $data as $key => $value) {
        if ($key === 'id' || $key === 'submitbutton') {
            continue;
        }
        if ($atelier->has_property($key)) {
            $atelier->set($key, $value);
        }
    }

    if ($isnew) {
        $atelier->create();
        \local_simhub\event\atelier_created::create([
            'objectid' => $atelier->get('id'),
            'context' => $context,
        ])->trigger();
    } else {
        $atelier->update();
    }

    // L'itemid de la zone de fichiers 'plan' est l'id de l'atelier lui-même (§5.4), une fois
    // celui-ci connu (création comprise).
    file_save_draft_area_files($data->planimage, $context->id, 'local_simhub', 'plan', $atelier->get('id'), $planoptions);
    $fs = get_file_storage();
    $planfiles = $fs->get_area_files($context->id, 'local_simhub', 'plan', $atelier->get('id'), 'filepath, filename', false);
    $atelier->set('planimageitemid', !empty($planfiles) ? $atelier->get('id') : 0);
    $atelier->update();

    // Ouverture/clôture automatique de l'indisponibilité selon le changement de statut (§6.1) :
    // le formulaire ne demande pas explicitement le commentaire pour rester simple ; un
    // gestionnaire souhaitant en documenter un utilisera la page dédiée (à développer),
    // cette page se contentant de garantir la cohérence de l'historique.
    if ($data->statut === atelier::STATUT_INDISPONIBLE && $oldstatut !== atelier::STATUT_INDISPONIBLE) {
        indispo::ouvrir($atelier->get('id'), '', $USER->id);
    } else if ($oldstatut === atelier::STATUT_INDISPONIBLE && $data->statut !== atelier::STATUT_INDISPONIBLE) {
        indispo::cloturer($atelier->get('id'));
    }

    redirect(
        new moodle_url('/local/simhub/manage/ateliers.php'),
        get_string('atelier_enregistre', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
$form->display();

if ($id && $atelier->get('planimageitemid')) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/atelier_plan.php', ['id' => $id]),
        'Positionner le repère sur le plan'
    );
}

echo $OUTPUT->footer();
