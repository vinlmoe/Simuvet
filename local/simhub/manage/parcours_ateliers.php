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
 * Composition d'un parcours (§8) : ajouter/retirer des ateliers, définir leur ordre,
 * leur caractère obligatoire et une échéance pédagogique optionnelle.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;

require_login();

$parcoursid = required_param('parcoursid', PARAM_INT);
$parcours = new parcours($parcoursid);
if (!\local_simhub\local\droits::peut_gerer_parcours($parcours)) {
    throw new required_capability_exception(
        \local_simhub\local\contexte::racine(),
        'local/simhub:manageparcours',
        'nopermissions',
        ''
    );
}

\local_simhub\local\navigation::preparer(
    $PAGE,
    new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]),
    s($parcours->get('nom')),
    \local_simhub\local\droits::etape_activite($parcours)
    ?: [[get_string('filtre_parcours', 'local_simhub'), new moodle_url('/local/simhub/manage/parcours.php')]]
);
\local_simhub\local\navigation::onglets('parcours', $parcoursid, 'ateliers');

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'ajouter') {
    require_sesskey();
    $atelierid = required_param('atelierid', PARAM_INT);
    $ordre = optional_param('ordre', 0, PARAM_INT);
    $obligatoire = optional_param('obligatoire', 0, PARAM_BOOL);
    $echeancedate = optional_param('echeance', '', PARAM_TEXT);
    $echeance = $echeancedate !== '' ? strtotime($echeancedate) : 0;

    \local_simhub\local\parcours_helper::ajouter_atelier($parcours, $atelierid, $ordre, (bool) $obligatoire, $echeance ?: null);

    redirect(new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]));
} else if ($action === 'retirer') {
    require_sesskey();
    $atelierid = required_param('atelierid', PARAM_INT);

    \local_simhub\local\parcours_helper::retirer_atelier($parcours, $atelierid);

    redirect(new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]));
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('h3', get_string('parcours_ateliers_titre', 'local_simhub'));

$composition = $parcours->get_ateliers();

$table = new html_table();
$table->head = [
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('ordre', 'local_simhub'),
    get_string('champ_obligatoire', 'local_simhub'),
    get_string('champ_echeance', 'local_simhub'),
    '',
];
foreach ($composition as $lien) {
    $atelier = new atelier($lien->atelierid);
    $removeurl = new moodle_url('/local/simhub/manage/parcours_ateliers.php', [
        'parcoursid' => $parcoursid, 'action' => 'retirer', 'atelierid' => $lien->atelierid, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        s($atelier->get('nomcourt')),
        $lien->ordre,
        $lien->obligatoire ? get_string('yes') : get_string('no'),
        $lien->echeance ? userdate($lien->echeance, get_string('strftimedate', 'langconfig')) : '',
        html_writer::link($removeurl, get_string('retirer', 'local_simhub')),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', get_string('parcours_ajouter_atelier', 'local_simhub'));

$ateliers = atelier::get_records([], 'nomcourt');
$dejadans = array_map('intval', array_column($composition, 'atelierid'));

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'parcoursid', 'value' => $parcoursid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'ajouter']);

echo html_writer::start_tag('select', ['name' => 'atelierid', 'class' => 'form-control d-inline-block w-auto mr-2']);
foreach ($ateliers as $atelier) {
    if (in_array((int) $atelier->get('id'), $dejadans, true)) {
        continue;
    }
    echo html_writer::tag('option', s($atelier->get('nomcourt')), ['value' => $atelier->get('id')]);
}
echo html_writer::end_tag('select');

echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'ordre', 'placeholder' => get_string(
        'ordre',
        'local_simhub',
    ), 'class' => 'form-control d-inline-block w-auto mr-2',
]);

echo html_writer::empty_tag('input', [
    'type' => 'date', 'name' => 'echeance', 'title' => get_string('echeance_optionnel', 'local_simhub'),
    'class' => 'form-control d-inline-block w-auto mr-2',
]);

echo html_writer::start_tag('label', ['class' => 'mr-2']);
echo html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'obligatoire', 'value' => 1]);
echo ' ' . get_string('champ_obligatoire', 'local_simhub');
echo html_writer::end_tag('label');

echo html_writer::tag('button', get_string('add'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
