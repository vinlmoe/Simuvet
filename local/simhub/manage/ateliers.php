<?php
// Liste de gestion des fiches ateliers (§6, profil "Responsable / gestionnaire de salle").
//
// Vue volontairement simple (tableau HTML natif) : la carte étudiante et ses filtres
// riches (§5.2/§5.3) sont une expérience distincte, développée dans index.php /
// classes/output. Ici, l'enjeu est l'administration : voir tous les ateliers quel que
// soit leur statut, et accéder rapidement à l'édition ou au changement de statut.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\indispo;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageateliers', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/ateliers.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('manage_ateliers', 'local_simhub'));
$PAGE->set_heading(get_string('manage_ateliers', 'local_simhub'));

echo $OUTPUT->header();

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/atelier_edit.php'),
    get_string('atelier_nouveau', 'local_simhub')
);

if (has_capability('local/simhub:exportsuivi', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/export.php', ['type' => 'ateliers', 'envcode' => $envcode]),
        get_string('export_csv', 'local_simhub'),
        'get'
    );
}

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$ateliers = atelier::get_records($params, 'nomcourt');

$table = new html_table();
$table->head = [
    get_string('champ_numero', 'local_simhub'),
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('champ_statut', 'local_simhub'),
    get_string('champ_salle', 'local_simhub'),
    '',
];

foreach ($ateliers as $atelier) {
    $statutlabel = get_string('statut_' . $atelier->get('statut'), 'local_simhub');
    if ($atelier->get('statut') === atelier::STATUT_INDISPONIBLE) {
        $encours = indispo::get_en_cours($atelier->get('id'));
        if ($encours && !empty($encours->commentaire)) {
            $statutlabel .= ' — ' . s($encours->commentaire);
        }
    }

    $editurl = new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelier->get('id')]);
    $ressourcesurl = new moodle_url('/local/simhub/manage/ressources.php', ['atelierid' => $atelier->get('id')]);
    $rattachementsurl = new moodle_url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelier->get('id')]);

    $liens = html_writer::link($editurl, get_string('atelier_modifier', 'local_simhub')) . ' | '
        . html_writer::link($ressourcesurl, get_string('bouton_ressources', 'local_simhub')) . ' | '
        . html_writer::link($rattachementsurl, get_string('rattachements', 'local_simhub')) . ' | '
        . html_writer::link(
            new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelier->get('id')]),
            get_string('ae_modele', 'local_simhub')
        ) . ' | '
        . html_writer::link(
            new moodle_url('/local/simhub/manage/atelier_fiche_pdf.php', ['id' => $atelier->get('id')]),
            'PDF'
        );

    if (has_capability('local/simhub:manageqrcodes', $context)) {
        $liens .= ' | ' . html_writer::link(
            new moodle_url('/local/simhub/manage/atelier_qr.php', ['id' => $atelier->get('id')]),
            'QR'
        );
    }

    $table->data[] = [
        s($atelier->get('numero')),
        s($atelier->get('nomcourt')),
        $statutlabel,
        s($atelier->get('salle')),
        $liens,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
