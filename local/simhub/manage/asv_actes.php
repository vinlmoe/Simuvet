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
 * Liste de gestion du référentiel des actes vétérinaires délégables ASV (§9), consommé en
 * lecture par asv/index.php, les validations et les exports PDF. Aucune page n'existait
 * jusqu'ici pour créer/modifier ces actes : celle-ci comble ce trou.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageasv', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$title = get_string('asv_gerer_actes', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/asv_actes.php'), $title);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/asv_acte_edit.php'),
    get_string('asv_acte_nouveau', 'local_simhub')
);

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$actes = asv_acte::get_records($params, 'niveau, code');

$table = new html_table();
$table->head = [
    get_string('asv_champ_code', 'local_simhub'),
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('champ_espece', 'local_simhub'),
    get_string('asv_champ_niveau', 'local_simhub'),
    get_string('champ_statut', 'local_simhub'),
    '',
];

foreach ($actes as $acte) {
    $editurl = new moodle_url('/local/simhub/manage/asv_acte_edit.php', ['id' => $acte->get('id')]);

    $table->data[] = [
        s($acte->get('code')),
        s($acte->get('nom')),
        s($acte->get('espece')),
        s($acte->get('niveau')),
        $acte->get('actif') ? get_string('statut_actif', 'local_simhub') : get_string('asv_acte_inactif', 'local_simhub'),
        html_writer::link($editurl, get_string('edit')),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
