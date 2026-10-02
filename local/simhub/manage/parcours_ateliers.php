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
    \local_simhub\local\navigation::url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]),
    s($parcours->get('nom')),
    \local_simhub\local\droits::etape_activite($parcours)
    ?: [[get_string('filtre_parcours', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/manage/parcours.php')]]
);
\local_simhub\local\navigation::onglets('parcours', $parcoursid, 'ateliers');

$action = optional_param('action', '', PARAM_ALPHA);

$composition = $parcours->get_ateliers();
$dejadans = array_map('intval', array_column($composition, 'atelierid'));
$choix = [];
foreach (atelier::get_records([], 'numero') as $atelier) {
    if (!in_array((int) $atelier->get('id'), $dejadans, true)) {
        $choix[$atelier->get('id')] = $atelier->get('numero') . ' — ' . $atelier->get('nomcourt');
    }
}
$pageurl = \local_simhub\local\navigation::url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]);
$form = new \local_simhub\form\formulaire($pageurl, [
    'champs' => [
        ['autocomplete', 'atelierid', get_string('atelier', 'local_simhub'), [
            'choix' => $choix, 'type' => PARAM_INT, 'requis' => true,
        ]],
        ['text', 'ordre', get_string('ordre', 'local_simhub'), ['type' => PARAM_INT, 'defaut' => count($composition) + 1,
            'attributs' => ['size' => 4]]],
        ['date_selector', 'echeance', get_string('echeance_optionnel', 'local_simhub'), [
            'type' => PARAM_INT, 'attributs' => ['optional' => true],
        ]],
        ['advcheckbox', 'obligatoire', get_string('champ_obligatoire', 'local_simhub'), ['type' => PARAM_BOOL]],
    ],
    'caches' => ['parcoursid' => $parcoursid],
    'bouton' => get_string('add'),
]);

if ($data = $form->get_data()) {
    if (isset($choix[$data->atelierid])) {
        \local_simhub\local\parcours_helper::ajouter_atelier(
            $parcours,
            (int) $data->atelierid,
            (int) $data->ordre,
            (bool) $data->obligatoire,
            $data->echeance ?: null
        );
    }
    redirect($pageurl);
} else if ($action === 'retirer') {
    require_sesskey();
    $atelierid = required_param('atelierid', PARAM_INT);

    \local_simhub\local\parcours_helper::retirer_atelier($parcours, $atelierid);

    redirect(\local_simhub\local\navigation::url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]));
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('h3', get_string('parcours_ateliers_titre', 'local_simhub'));


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
    $removeurl = \local_simhub\local\navigation::url('/local/simhub/manage/parcours_ateliers.php', [
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

$form->display();

echo $OUTPUT->footer();
