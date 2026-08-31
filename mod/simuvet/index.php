<?php
// Point d'entrée SimHub. En V0, redirige simplement vers une page
// listant les ateliers actifs, sans encore le tri/filtres/carte décrits
// au §5. Sert de point de départ pour construire :
//   - l'accueil étudiant personnalisé (§5.1)
//   - les filtres (§5.2)
//   - la carte atelier (§5.3)
//
// Architecture cible : ce fichier ne devrait contenir que la
// résolution de contexte + délégation à un contrôleur
// (classes/local/page_controller.php) qui choisit le renderer et le
// template mustache appropriés selon le rôle (étudiant / gestionnaire).

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/adminlib.php');

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_simhub'));
$PAGE->set_heading(get_string('pluginname', 'local_simhub'));

echo $OUTPUT->header();

$ateliers = \local_simhub\persistent\atelier::get_actifs(
    get_config('local_simhub', 'envcode') ?: null
);

echo html_writer::tag('p', get_string('simhub:studenthome', 'local_simhub'));

if (empty($ateliers)) {
    echo $OUTPUT->notification(
        'Aucun atelier actif pour le moment. (Écran de liste minimal - '
        . 'à remplacer par la carte atelier et les filtres du §5.2/§5.3.)',
        \core\output\notification::NOTIFY_INFO
    );
} else {
    echo html_writer::start_tag('ul');
    foreach ($ateliers as $atelier) {
        echo html_writer::tag('li', s($atelier->get('nomcourt')) . ' — ' . s($atelier->get('salle')));
    }
    echo html_writer::end_tag('ul');
}

// TODO : remplacer ce rendu minimal par un template mustache
// (templates/student_home.mustache) alimenté par un renderer dédié,
// avec les sections "À faire pour mes UC", "Mes parcours en cours",
// "Parcours ASV", etc. listées au §5.1.

echo $OUTPUT->footer();
