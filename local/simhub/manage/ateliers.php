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
 * Liste de gestion des fiches ateliers (§6, profil "Responsable / gestionnaire de salle").
 *
 * Vue volontairement simple (tableau HTML natif) : la carte étudiante et ses filtres
 * riches (§5.2/§5.3) sont une expérience distincte, développée dans index.php /
 * classes/output. Ici, l'enjeu est l'administration : voir tous les ateliers quel que
 * soit leur statut, et accéder rapidement à l'édition ou au changement de statut.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\indispo;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageateliers', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$pageurl = new moodle_url('/local/simhub/manage/ateliers.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('manage_ateliers', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/atelier_edit.php'),
    get_string('atelier_nouveau', 'local_simhub')
);

if (has_capability('local/simhub:exportsuivi', $context)) {
    echo html_writer::div(\local_simhub\local\exporteur::liens(['type' => 'ateliers']), 'mb-3');
}

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$ateliers = atelier::get_records($params, 'nomcourt');

$table = new html_table();
$table->head = [
    get_string('champ_numero', 'local_simhub'),
    get_string('champ_nomcourt', 'local_simhub'),
    get_string('champ_statut', 'local_simhub'),
    get_string('champ_salle', 'local_simhub'),
    '',
];

foreach ($ateliers as $atelier) {
    $statutlabel = get_string('statut_' . $atelier->get('statut'), 'local_simhub');
    if ($atelier->get('statut') === atelier::STATUT_INDISPONIBLE) {
        $encours = indispo::get_en_cours($atelier->get('id'));
        if ($encours && !empty($encours->commentaire)) {
            $statutlabel .= ' — ' . s($encours->commentaire);
        }
    }

    $editurl = new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelier->get('id')]);
    $liens = \local_simhub\local\navigation::menu_actions(
        \local_simhub\local\navigation::liens_atelier($atelier->get('id'))
    );

    $table->data[] = [
        s($atelier->get('numero')),
        html_writer::link($editurl, s($atelier->get('nomcourt'))),
        $statutlabel,
        s($atelier->get('salle')),
        $liens,
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
