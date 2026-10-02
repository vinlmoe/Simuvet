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
 * Rattachement pédagogique d'un atelier à une UC / année d'étude / cohorte (§6), en dehors
 * de la logique de parcours (§8, géré séparément dans manage/parcours_ateliers.php).
 * C'est ce rattachement que l'accueil étudiant exploite pour la section "À faire pour mes
 * UC" (§5.1) et que le filtre par UC/année utilise côté étudiant (§5.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\rattachement;
use local_simhub\local\annee_resolver;
use local_simhub\local\cohort_helper;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:managerattachement', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$title = get_string('rattachements', 'local_simhub');
$pageurl = \local_simhub\local\navigation::url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelierid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, $title, [
    [get_string('manage_ateliers', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
]);
\local_simhub\local\navigation::onglets('atelier', $atelierid, 'rattachements');

$form = new \local_simhub\form\formulaire($pageurl, [
    'champs' => [
        ['autocomplete', 'courseid', get_string('champ_uc_optionnel', 'local_simhub'), [
            'choix' => \local_simhub\local\selecteurs::options_cours(), 'type' => PARAM_INT,
        ]],
        ['select', 'anneeetude', get_string('filtre_annee_optionnel', 'local_simhub'), [
            'choix' => annee_resolver::get_options(), 'type' => PARAM_INT,
        ]],
        ['select', 'cohortid', get_string('champ_cohorte_recommandation', 'local_simhub'), [
            'choix' => cohort_helper::get_options(), 'type' => PARAM_INT,
        ]],
        ['select', 'caractere', get_string('champ_statut', 'local_simhub'), [
            'choix' => [
                rattachement::CARACTERE_RECOMMANDE => get_string('parcours_type_recommande', 'local_simhub'),
                rattachement::CARACTERE_OBLIGATOIRE => get_string('parcours_type_obligatoire', 'local_simhub'),
            ],
            'type' => PARAM_ALPHA,
        ]],
        ['text', 'niveauattendu', get_string('niveau_attendu_optionnel', 'local_simhub')],
    ],
    'caches' => ['atelierid' => $atelierid],
    'bouton' => get_string('add'),
]);

$action = optional_param('action', '', PARAM_ALPHA);

if ($data = $form->get_data()) {
    rattachement::creer($atelierid, [
        // Une liste laissée sur son choix vide n'est pas renvoyée par le formulaire.
        'courseid' => ($data->courseid ?? 0) ?: null,
        'anneeetude' => ($data->anneeetude ?? 0) ?: null,
        'cohortid' => ($data->cohortid ?? 0) ?: null,
        'caractere' => $data->caractere === rattachement::CARACTERE_OBLIGATOIRE
            ? rattachement::CARACTERE_OBLIGATOIRE : rattachement::CARACTERE_RECOMMANDE,
        'niveauattendu' => $data->niveauattendu ?: null,
    ]);
    redirect($pageurl);
} else if ($action === 'supprimer') {
    require_sesskey();
    $id = required_param('id', PARAM_INT);
    // Seul un rattachement de cet atelier peut être supprimé depuis sa page.
    $DB->get_record(rattachement::TABLE, ['id' => $id, 'atelierid' => $atelierid], 'id', MUST_EXIST);
    rattachement::supprimer($id);

    redirect(\local_simhub\local\navigation::url('/local/simhub/manage/rattachements.php', ['atelierid' => $atelierid]));
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

global $DB;

echo html_writer::tag('h3', get_string('rattachements_existants', 'local_simhub'));

$table = new html_table();
$cohortoptions = cohort_helper::get_options(false);

$table->head = [
    get_string('col_uc', 'local_simhub'),
    get_string('filtre_annee', 'local_simhub'),
    get_string('champ_cohorte', 'local_simhub'),
    get_string('champ_statut', 'local_simhub'),
    get_string('champ_niveauattendu', 'local_simhub'),
    '',
];
foreach (rattachement::get_pour_atelier($atelierid) as $r) {
    $delurl = \local_simhub\local\navigation::url('/local/simhub/manage/rattachements.php', [
        'atelierid' => $atelierid, 'action' => 'supprimer', 'id' => $r->id, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        $r->courseid ?: '—',
        $r->anneeetude ? annee_resolver::get_label((int) $r->anneeetude) : '—',
        $r->cohortid ? s($cohortoptions[$r->cohortid] ?? '#' . $r->cohortid) : '—',
        $r->caractere,
        s($r->niveauattendu ?? ''),
        html_writer::link($delurl, get_string('retirer', 'local_simhub')),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', get_string('rattachement_ajouter', 'local_simhub'));

$form->display();

echo $OUTPUT->footer();
