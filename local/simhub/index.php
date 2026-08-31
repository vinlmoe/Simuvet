<?php
// Accueil SimHub (§5) : filtres + carte atelier pour l'étudiant.
//
// Les sept sections personnalisées du §5.1 ("À faire pour mes UC", "Mes parcours en
// cours", "Parcours ASV"...) demandent de connaître les rattachements réels une fois les
// données importées (§12.1) : elles restent à construire au-dessus de cette liste
// filtrée, qui couvre déjà "Tous les ateliers disponibles" et la recherche par critère.

require(__DIR__ . '/../../config.php');

use local_simhub\local\atelier_filter;
use local_simhub\output\student_home_page;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/index.php'));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(get_string('pluginname', 'local_simhub'));
$PAGE->set_heading(get_string('pluginname', 'local_simhub'));

$filter = atelier_filter::from_request();
$page = new student_home_page($USER->id, $filter);

echo $OUTPUT->header();

$renderer = $PAGE->get_renderer('local_simhub');
echo $renderer->render_student_home_page($page);

echo $OUTPUT->footer();
