<?php
// Création / activation du modèle d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : un
// modèle par atelier en V1. La composition (rubriques et critères) se gère ensuite sur
// manage/ae_rubriques.php.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ae_modele;

require_login();

$context = \local_simhub\local\contexte::racine();

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);
if (!\local_simhub\local\droits::peut_editer_grille($atelierid)) {
    throw new required_capability_exception($context, 'local/simhub:manageateliers', 'nopermissions', '');
}
$gestionnaire = has_capability('local/simhub:manageateliers', $context);
// Un responsable d'UC n'a pas accès aux pages de gestion de l'atelier : son fil d'Ariane
// passe par la fiche étudiant.
$etapesatelier = $gestionnaire ? [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
] : [[s($atelier->get('nomcourt')), new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])]];

$title = get_string('ae_modele', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid]), $title, $etapesatelier);
if ($gestionnaire) {
    \local_simhub\local\navigation::onglets('atelier', $atelierid, 'ae');
}

$modele = ae_modele::get_pour_atelier($atelierid);

$submitted = optional_param('submit', 0, PARAM_BOOL);
if ($submitted) {
    require_sesskey();

    $titre = required_param('titre', PARAM_TEXT);
    $actif = optional_param('actif', 0, PARAM_BOOL);

    if ($modele) {
        $modele->set('titre', $titre);
        $modele->set('actif', $actif ? 1 : 0);
        $modele->update();
    } else {
        $modele = new ae_modele(0, (object) [
            'atelierid' => $atelierid,
            'titre' => $titre,
            'actif' => $actif ? 1 : 0,
        ]);
        $modele->create();
    }

    redirect(
        new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// La grille est partagée par toutes les UC qui utilisent l'atelier : on montre qui l'a
// modifiée en dernier.
if ($modele && $modele->get('usermodified')) {
    $auteur = \core_user::get_user($modele->get('usermodified'));
    echo $OUTPUT->notification(get_string('ae_derniere_modification', 'local_simhub', [
        'auteur' => $auteur ? fullname($auteur) : '-',
        'date' => userdate($modele->get('timemodified')),
    ]), \core\output\notification::NOTIFY_INFO);
}

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('ae_champ_titre', 'local_simhub'));
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'titre', 'class' => 'form-control d-inline-block w-auto',
    'value' => $modele ? s($modele->get('titre')) : '',
    'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_tag('label', ['class' => 'mr-2']);
echo html_writer::empty_tag('input', array_merge(
    ['type' => 'checkbox', 'name' => 'actif', 'value' => 1],
    (!$modele || $modele->get('actif')) ? ['checked' => 'checked'] : []
));
echo ' ' . get_string('ae_champ_actif', 'local_simhub');
echo html_writer::end_tag('label');

echo html_writer::tag('div', html_writer::tag('button', get_string('savechanges'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]), ['class' => 'mt-3']);
echo html_writer::end_tag('form');

if ($modele) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]),
        get_string('ae_gerer_rubriques', 'local_simhub')
    );
}

echo $OUTPUT->footer();
