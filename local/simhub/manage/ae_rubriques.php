<?php
// Composition de la grille d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : rubriques
// (grandes étapes du geste, avec une rubrique optionnelle dédiée aux erreurs/risques) et,
// pour chacune, ses critères observables.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ae_modele;
use local_simhub\record\ae_rubrique;
use local_simhub\record\ae_critere;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageateliers', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$modele = ae_modele::get_pour_atelier($atelierid);
if (!$modele) {
    redirect(new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid]));
}

// PARAM_ALPHA n'autorise que les lettres a-z/A-Z : il aurait silencieusement supprimé le
// « _ » des valeurs d'action ci-dessous (ajouter_rubrique devenant ajouterrubrique), qui
// n'aurait alors plus jamais matché aucune branche - postée sans erreur ni redirection,
// juste un réaffichage silencieux de la page. PARAM_ALPHANUMEXT autorise aussi le « _ ».
$action = optional_param('action', '', PARAM_ALPHANUMEXT);

if ($action === 'ajouter_rubrique') {
    require_sesskey();
    $titre = required_param('titre', PARAM_TEXT);
    $estrisques = optional_param('estrubriquerisques', 0, PARAM_BOOL);
    $ordre = optional_param('ordre', 0, PARAM_INT);

    ae_rubrique::ajouter($modele->get('id'), $titre, $ordre, (bool) $estrisques);

    redirect(new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]));
} else if ($action === 'supprimer_rubrique') {
    require_sesskey();
    ae_rubrique::supprimer(required_param('rubriqueid', PARAM_INT));

    redirect(new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]));
} else if ($action === 'ajouter_critere') {
    require_sesskey();
    $rubriqueid = required_param('rubriqueid', PARAM_INT);
    $libelle = required_param('libelle', PARAM_TEXT);
    $ordre = optional_param('ordre', 0, PARAM_INT);

    ae_critere::ajouter($rubriqueid, $libelle, $ordre);

    redirect(new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]));
} else if ($action === 'supprimer_critere') {
    require_sesskey();
    ae_critere::supprimer(required_param('critereid', PARAM_INT));

    redirect(new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]));
}

$title = get_string('ae_gerer_rubriques', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
    [get_string('ae_modele', 'local_simhub'), new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid])],
]);
\local_simhub\local\navigation::onglets('atelier', $atelierid, 'ae');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('p', s($modele->get('titre')));
echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid]),
    get_string('edit')
);

$rubriques = ae_rubrique::get_pour_modele($modele->get('id'));

foreach ($rubriques as $rubrique) {
    echo html_writer::start_div('card mb-3');
    echo html_writer::start_div('card-header d-flex justify-content-between align-items-center');
    echo html_writer::tag('span', s($rubrique->titre)
        . ($rubrique->estrubriquerisques ? ' ' . html_writer::tag('span', get_string('ae_badge_risques', 'local_simhub'), ['class' => 'badge badge-warning']) : ''));

    $delrubriqueurl = new moodle_url('/local/simhub/manage/ae_rubriques.php', [
        'atelierid' => $atelierid, 'action' => 'supprimer_rubrique', 'rubriqueid' => $rubrique->id, 'sesskey' => sesskey(),
    ]);
    echo html_writer::link($delrubriqueurl, get_string('retirer', 'local_simhub'), ['class' => 'text-danger']);
    echo html_writer::end_div();

    echo html_writer::start_div('card-body');

    $criteres = ae_critere::get_pour_rubrique($rubrique->id);
    if (!empty($criteres)) {
        echo html_writer::start_tag('ul');
        foreach ($criteres as $critere) {
            $delcritereurl = new moodle_url('/local/simhub/manage/ae_rubriques.php', [
                'atelierid' => $atelierid, 'action' => 'supprimer_critere', 'critereid' => $critere->id, 'sesskey' => sesskey(),
            ]);
            echo html_writer::tag('li', s($critere->libelle) . ' — ' . html_writer::link($delcritereurl, get_string('retirer', 'local_simhub'), ['class' => 'text-danger']));
        }
        echo html_writer::end_tag('ul');
    }

    echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'form-inline']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'ajouter_critere']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'rubriqueid', 'value' => $rubrique->id]);
    echo html_writer::empty_tag('input', [
        'type' => 'text', 'name' => 'libelle', 'class' => 'form-control mr-2',
        'placeholder' => get_string('ae_champ_critere', 'local_simhub'), 'required' => 'required',
    ]);
    echo html_writer::tag('button', get_string('ae_ajouter_critere', 'local_simhub'), [
        'type' => 'submit', 'class' => 'btn btn-outline-primary btn-sm',
    ]);
    echo html_writer::end_tag('form');

    echo html_writer::end_div();
    echo html_writer::end_div();
}

echo html_writer::tag('h4', get_string('ae_ajouter_rubrique', 'local_simhub'));

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'form-inline']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'ajouter_rubrique']);
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'titre', 'class' => 'form-control mr-2',
    'placeholder' => get_string('ae_champ_titre', 'local_simhub'), 'required' => 'required',
]);
echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'ordre', 'class' => 'form-control mr-2', 'placeholder' => get_string('ordre', 'local_simhub'),
]);
echo html_writer::start_tag('label', ['class' => 'mr-2']);
echo html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'estrubriquerisques', 'value' => 1]);
echo ' ' . get_string('ae_champ_risques', 'local_simhub');
echo html_writer::end_tag('label');
echo html_writer::tag('button', get_string('ae_ajouter_rubrique', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
