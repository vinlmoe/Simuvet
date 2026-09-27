<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Accueil SimHub (§5) : filtres + carte atelier pour l'étudiant.
 *
 * Les sept sections personnalisées du §5.1 ("À faire pour mes UC", "Mes parcours en
 * cours", "Parcours ASV"...) demandent de connaître les rattachements réels une fois les
 * données importées (§12.1) : elles restent à construire au-dessus de cette liste
 * filtrée, qui couvre déjà "Tous les ateliers disponibles" et la recherche par critère.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\local\atelier_filter;
use local_simhub\output\student_home_page;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$pageurl = new moodle_url('/local/simhub/index.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('pluginname', 'local_simhub'));

$filter = atelier_filter::from_request();
$page = new student_home_page($USER->id, $filter);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$renderer = $PAGE->get_renderer('local_simhub');
echo $renderer->render_student_home_page($page);

echo $OUTPUT->footer();
