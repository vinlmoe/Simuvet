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
 * Création / modification d'un acte du référentiel ASV (§9).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\persistent\atelier;
use local_simhub\record\acte_atelier;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageasv', $context);

$id = optional_param('id', 0, PARAM_INT);
$acte = $id ? new asv_acte($id) : new asv_acte();

$title = $id ? get_string('edit') : get_string('asv_acte_nouveau', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/asv_acte_edit.php', ['id' => $id]), $title, [
    [get_string('asv_gerer_actes', 'local_simhub'), new moodle_url('/local/simhub/manage/asv_actes.php')],
]);

$pageurl = new moodle_url('/local/simhub/manage/asv_acte_edit.php', ['id' => $id]);
$niveaux = [
    'A1' => get_string('asv_niveau_a1', 'local_simhub'),
    'A2' => get_string('asv_niveau_a2', 'local_simhub'),
    'A3' => get_string('asv_niveau_a3', 'local_simhub'),
];
$form = new \local_simhub\form\formulaire($pageurl, [
    'id' => 'acte',
    'champs' => [
        ['text', 'code', get_string('asv_champ_code', 'local_simhub'), ['type' => PARAM_ALPHANUMEXT, 'requis' => true,
            'defaut' => $id ? $acte->get('code') : '']],
        ['text', 'nom', get_string('champ_nomcourt', 'local_simhub'), ['requis' => true, 'defaut' => $id ? $acte->get('nom') : '',
            'attributs' => ['size' => 50]]],
        ['text', 'espece', get_string('champ_espece', 'local_simhub'), ['defaut' => $id ? (string) $acte->get('espece') : '']],
        ['autocomplete', 'ucid', get_string('asv_champ_ucid', 'local_simhub'), [
            'choix' => \local_simhub\local\selecteurs::options_cours(), 'type' => PARAM_INT,
            'defaut' => $id ? (int) $acte->get('ucid') : 0,
        ]],
        ['select', 'niveau', get_string('asv_champ_niveau', 'local_simhub'), ['choix' => $niveaux, 'type' => PARAM_ALPHANUM,
            'defaut' => $id ? $acte->get('niveau') : 'A1']],
        ['advcheckbox', 'actif', get_string('ae_champ_actif', 'local_simhub'), ['type' => PARAM_BOOL,
            'defaut' => (!$id || $acte->get('actif')) ? 1 : 0]],
    ],
]);

$lies = $id ? acte_atelier::get_ateliers_pour_acte($id) : [];
$lieids = array_map(fn($a) => (int) $a->id, $lies);
$disponibles = [];
foreach (atelier::get_records([], 'numero') as $a) {
    if (!in_array((int) $a->get('id'), $lieids, true)) {
        $disponibles[$a->get('id')] = $a->get('numero') . ' — ' . $a->get('nomcourt');
    }
}
$formlien = $id && $disponibles ? new \local_simhub\form\formulaire($pageurl, [
    'id' => 'lien',
    'champs' => [['autocomplete', 'atelierid', get_string('atelier', 'local_simhub'), [
        'choix' => $disponibles, 'type' => PARAM_INT, 'requis' => true,
    ]]],
    'bouton' => get_string('asv_lier_atelier', 'local_simhub'),
]) : null;

if ($formlien && ($data = $formlien->get_data()) && isset($disponibles[$data->atelierid])) {
    acte_atelier::lier($id, (int) $data->atelierid);
    redirect($pageurl);
}
if ($id && optional_param('action', '', PARAM_ALPHANUMEXT) === 'delier_atelier') {
    require_sesskey();
    acte_atelier::delier($id, required_param('atelierid', PARAM_INT));
    redirect($pageurl);
}

if ($data = $form->get_data()) {
    // Traçabilité seulement : le code de l'école est conservé, ou pris dans les réglages.
    $envcode = $id ? (string) $acte->get('envcode') : (get_config('local_simhub', 'envcode') ?: '');
    $doublon = $DB->get_field_select(
        'local_simhub_asv_acte',
        'id',
        'envcode = :envcode AND code = :code AND id <> :id',
        ['envcode' => $envcode, 'code' => $data->code, 'id' => $id]
    );
    if ($doublon) {
        redirect(
            $pageurl,
            get_string('asv_code_existe', 'local_simhub', s($data->code)),
            null,
            \core\output\notification::NOTIFY_ERROR
        );
    }

    $acte->set('code', $data->code);
    $acte->set('nom', $data->nom);
    $acte->set('espece', $data->espece ?: null);
    $acte->set('niveau', isset($niveaux[$data->niveau]) ? $data->niveau : 'A1');
    $acte->set('ucid', ($data->ucid ?? 0) ?: null);
    $acte->set('envcode', $envcode);
    $acte->set('actif', $data->actif ? 1 : 0);
    if ($acte->get('id')) {
        $acte->update();
    } else {
        $acte->create();
    }

    redirect(
        new moodle_url('/local/simhub/manage/asv_actes.php'),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$form->display();

if ($id) {
    // Ateliers de simulation où cet acte se pratique (§9.2) : une fois liés, ils
    // restreignent la liste des actes proposés lors d'une validation en simulation lancée
    // depuis cet atelier, au lieu de faire choisir dans tout le référentiel.
    echo html_writer::tag('h4', get_string('asv_ateliers_lies', 'local_simhub'), ['class' => 'mt-4']);

    if (!empty($lies)) {
        echo html_writer::start_tag('ul');
        foreach ($lies as $atelierlie) {
            $delurl = new moodle_url('/local/simhub/manage/asv_acte_edit.php', [
                'id' => $id, 'action' => 'delier_atelier', 'atelierid' => $atelierlie->id, 'sesskey' => sesskey(),
            ]);
            echo html_writer::tag('li', s($atelierlie->numero) . ' — ' . s($atelierlie->nomcourt)
                . ' — ' . html_writer::link($delurl, get_string('delete'), ['class' => 'text-danger']));
        }
        echo html_writer::end_tag('ul');
    }

    if ($formlien) {
        $formlien->display();
    }
}

echo $OUTPUT->footer();
