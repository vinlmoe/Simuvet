<?php
// Rattachement pédagogique d'un atelier à une UC / année d'étude / cohorte (§6), en dehors
// de la logique de parcours (§8, géré séparément dans manage/parcours_ateliers.php).
// C'est ce rattachement que l'accueil étudiant exploite pour la section "À faire pour mes
// UC" (§5.1) et que le filtre par UC/année utilise côté étudiant (§5.2).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\rattachement;
use local_simhub\local\annee_resolver;
use local_simhub\local\cohort_helper;

require_login();

$context = context_system::instance();
require_capability('local/simhub:managerattachement', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'ajouter') {
    require_sesskey();

    $courseid = optional_param('courseid', 0, PARAM_INT);
    $anneeetude = optional_param('anneeetude', 0, PARAM_INT);
    $cohortid = optional_param('cohortid', 0, PARAM_INT);
    $caractere = optional_param('caractere', rattachement::CARACTERE_RECOMMANDE, PARAM_ALPHA);
    $niveauattendu = optional_param('niveauattendu', '', PARAM_TEXT);

    rattachement::creer($atelierid, [
        'courseid' => $courseid ?: null,
        'anneeetude' => $anneeetude ?: null,
        'cohortid' => $cohortid ?: null,
        'caractere' => $caractere,
        'niveauattendu' => $niveauattendu ?: null,
    ]);

    redirect(new moodle_url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelierid]));
} else if ($action === 'supprimer') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);

    rattachement::supprimer($id);

    redirect(new moodle_url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelierid]));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelierid]));
$PAGE->set_pagelayout('admin');
$title = s($atelier->get('nomcourt'));
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

global $DB;

echo html_writer::tag('h3', 'Rattachements existants');

$table = new html_table();
$cohortoptions = cohort_helper::get_options(false);

$table->head = ['UC (id cours)', get_string('filtre_annee', 'local_simhub'), get_string('champ_cohorte', 'local_simhub'), get_string('champ_statut', 'local_simhub'), 'Niveau attendu', ''];
foreach (rattachement::get_pour_atelier($atelierid) as $r) {
    $delurl = new moodle_url('/local/simhub/manage/rattachements.php', [
        'atelierid' => $atelierid, 'action' => 'supprimer', 'id' => $r->id, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        $r->courseid ?: '—',
        $r->anneeetude ? annee_resolver::get_label((int) $r->anneeetude) : '—',
        $r->cohortid ? s($cohortoptions[$r->cohortid] ?? '#' . $r->cohortid) : '—',
        $r->caractere,
        s($r->niveauattendu ?? ''),
        html_writer::link($delurl, 'Retirer'),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', 'Ajouter un rattachement');

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'ajouter']);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Id de l\'UC Moodle (optionnel)');
echo html_writer::empty_tag('input', ['type' => 'number', 'name' => 'courseid', 'class' => 'form-control d-inline-block w-auto mr-2']);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('filtre_annee', 'local_simhub') . ' (optionnel)');
echo html_writer::select(
    annee_resolver::get_options(),
    'anneeetude',
    '',
    false,
    ['class' => 'form-control d-inline-block w-auto mr-2']
);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('champ_cohorte', 'local_simhub') . ' (optionnel — pour recommander directement à ses membres)');
echo html_writer::select(
    cohort_helper::get_options(),
    'cohortid',
    '',
    false,
    ['class' => 'form-control d-inline-block w-auto mr-2']
);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('champ_statut', 'local_simhub'));
echo html_writer::select([
    rattachement::CARACTERE_RECOMMANDE => 'Recommandé',
    rattachement::CARACTERE_OBLIGATOIRE => 'Obligatoire',
], 'caractere', rattachement::CARACTERE_RECOMMANDE, false, ['class' => 'form-control d-inline-block w-auto mr-2']);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', 'Niveau attendu (optionnel)');
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'niveauattendu', 'class' => 'form-control d-inline-block w-auto mr-2']);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('add'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
