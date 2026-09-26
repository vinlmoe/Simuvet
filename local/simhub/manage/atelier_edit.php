<?php
// Création / modification d'une fiche atelier (§6), avec gestion du statut
// "indisponible" (§6.1) : ouverture/clôture d'une entrée dans local_simhub_indispo.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/filelib.php');

use local_simhub\persistent\atelier;
use local_simhub\form\atelier_form;
use local_simhub\record\indispo;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageateliers', $context);

$id = optional_param('id', 0, PARAM_INT);

$title = $id ? get_string('atelier_modifier', 'local_simhub') : get_string('atelier_nouveau', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $id]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
]);
if ($id) {
    \local_simhub\local\navigation::onglets('atelier', $id, 'fiche');
}

$atelier = $id ? new atelier($id) : new atelier();
$oldstatut = $atelier->get('id') ? $atelier->get('statut') : null;

$planoptions = ['subdirs' => 0, 'maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg']];
$plandraftid = file_get_submitted_draft_itemid('planimage');
file_prepare_draft_area($plandraftid, $context->id, 'local_simhub', 'plan', $id ?: null, $planoptions);

$form = new atelier_form();
$formdata = $atelier->to_record();
$formdata->planimage = $plandraftid;
$indispoencours = $id ? indispo::get_en_cours($id) : false;
if ($indispoencours) {
    $formdata->indispo_motif = $indispoencours->commentaire;
    $formdata->indispo_echeance = $indispoencours->echeanceprevue ?: 0;
}
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
        // Le jeton QR (§7) est généré automatiquement par atelier::after_create().
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

    // Ouverture, mise à jour ou clôture de l'indisponibilité selon le statut (§6.1).
    $motif = trim($data->indispo_motif ?? '');
    $echeance = !empty($data->indispo_echeance) ? (int) $data->indispo_echeance : null;
    if ($data->statut === atelier::STATUT_INDISPONIBLE && $oldstatut !== atelier::STATUT_INDISPONIBLE) {
        indispo::ouvrir($atelier->get('id'), $motif, $USER->id, $echeance);
    } else if ($data->statut === atelier::STATUT_INDISPONIBLE) {
        indispo::mettre_a_jour($atelier->get('id'), $motif, $USER->id, $echeance);
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
echo \local_simhub\local\navigation::barre();
$form->display();

if ($id && $atelier->get('planimageitemid')) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/atelier_plan.php', ['id' => $id]),
        get_string('plan_positionner', 'local_simhub')
    );
}

$historique = $id ? indispo::get_historique($id) : [];
if ($historique) {
    $format = get_string('strftimedatefullshort', 'langconfig');
    echo html_writer::tag('h3', get_string('indispo_historique', 'local_simhub'), ['class' => 'mt-4']);
    $table = new html_table();
    $table->head = [get_string('indispo_debut', 'local_simhub'), get_string('indispo_motif', 'local_simhub'),
        get_string('indispo_echeance', 'local_simhub'), get_string('indispo_referent', 'local_simhub'),
        get_string('champ_statut', 'local_simhub')];
    foreach ($historique as $h) {
        $referent = $h->referentuserid ? \core_user::get_user($h->referentuserid) : null;
        $table->data[] = [
            userdate($h->timecreated, $format),
            s($h->commentaire),
            $h->echeanceprevue ? userdate($h->echeanceprevue, $format) : '—',
            $referent ? s(fullname($referent)) : '—',
            $h->cloturee ? get_string('indispo_cloturee', 'local_simhub') : get_string('indispo_en_cours', 'local_simhub'),
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
