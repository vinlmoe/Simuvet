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
 * Génération d'un code de séance temporaire pour une salle (§7.3, mécanisme "code de
 * séance" du contrôle anti-faux-scan). L'encadrant génère le code en début de séance et
 * le communique aux étudiants (tableau, projection...) ; il reste valable une durée
 * limitée (local_simhub/seancecodeduration).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\record\seancecode;
use local_simhub\persistent\atelier;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:validatesession', $context);

$pageurl = new moodle_url('/local/simhub/manage/seancecode_generer.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('seancecode_generer', 'local_simhub'));

$salles = $DB->get_fieldset_sql(
    "SELECT DISTINCT salle FROM {local_simhub_atelier} WHERE salle IS NOT NULL AND salle <> '' ORDER BY salle"
);
$form = new \local_simhub\form\formulaire($pageurl, [
    'champs' => [
        $salles
            ? ['select', 'salle', get_string('seancecode_champ_salle', 'local_simhub'), [
                'choix' => array_combine($salles, $salles),
            ]]
            : ['text', 'salle', get_string('seancecode_champ_salle', 'local_simhub'), ['requis' => true]],
    ],
    'bouton' => get_string('seancecode_generer', 'local_simhub'),
]);
$genere = null;
if (($data = $form->get_data()) && trim($data->salle) !== '') {
    $genere = seancecode::generer(trim($data->salle), $USER->id);
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($genere) {
    echo $OUTPUT->notification(
        get_string('seancecode_genere', 'local_simhub') . ' : ' . html_writer::tag('strong', s($genere->code)),
        \core\output\notification::NOTIFY_SUCCESS
    );
    echo html_writer::tag('p', get_string(
        'seancecode_validite',
        'local_simhub',
        userdate($genere->validto, get_string('strftimedatetimeshort', 'langconfig'))
    ));
}

$form->display();

echo $OUTPUT->footer();
