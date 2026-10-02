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
 * Liste des ressources pédagogiques d'un atelier (§6.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageressources', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'supprimer') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);

    global $DB;
    $fs = get_file_storage();
    $fs->delete_area_files(\local_simhub\local\contexte::fichiers()->id, 'local_simhub', 'ressource', $id);
    $DB->delete_records(ressource::TABLE, ['id' => $id, 'atelierid' => $atelierid]);

    redirect(\local_simhub\local\navigation::url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]));
}

$title = get_string('nav_ressources', 'local_simhub');
$pageurl = \local_simhub\local\navigation::url('/local/simhub/manage/ressources.php', ['atelierid' => $atelierid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, $title, [
    [get_string('manage_ateliers', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
]);
\local_simhub\local\navigation::onglets('atelier', $atelierid, 'ressources');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo $OUTPUT->single_button(
    \local_simhub\local\navigation::url('/local/simhub/manage/ressource_edit.php', ['atelierid' => $atelierid]),
    get_string('ressource_nouvelle', 'local_simhub')
);

global $DB;
$ressources = $DB->get_records(ressource::TABLE, ['atelierid' => $atelierid], 'ordre ASC');

$table = new html_table();
$table->head = [
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('champ_type', 'local_simhub'),
    get_string('champ_visibilite', 'local_simhub'),
    '',
];
foreach ($ressources as $r) {
    $editurl = \local_simhub\local\navigation::url(
        '/local/simhub/manage/ressource_edit.php',
        ['atelierid' => $atelierid, 'id' => $r->id]
    );
    $delurl = \local_simhub\local\navigation::url('/local/simhub/manage/ressources.php', [
        'atelierid' => $atelierid, 'action' => 'supprimer', 'id' => $r->id, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        s($r->titre),
        get_string('ressource_type_' . $r->type, 'local_simhub'),
        get_string('visibilite_' . $r->visibilite, 'local_simhub'),
        html_writer::link($editurl, get_string('edit')) . ' | '
            . html_writer::link($delurl, get_string('delete')),
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
