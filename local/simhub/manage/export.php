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
 * Exports CSV de base (§12.3) : liste des ateliers, ou suivi de progression d'un parcours.
 * Reste volontairement simple (CSV natif, pas de XLSX) : un tableur ouvre un CSV sans
 * dépendance supplémentaire, et l'export sert surtout d'échange ponctuel entre équipes.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\local\droits;
use local_simhub\local\exporteur;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;

require_login();

$context = \local_simhub\local\contexte::racine();

$type = required_param('type', PARAM_ALPHA);
$format = optional_param('format', 'csv', PARAM_ALPHA);
if (!array_key_exists($format, exporteur::FORMATS)) {
    $format = 'csv';
}
$refus = fn(string $cap) => new required_capability_exception($context, $cap, 'nopermissions', '');

if ($type === 'ateliers') {
    require_capability('local/simhub:exportsuivi', $context);
    $entetes = ['numero', 'nomcourt', 'categorie', 'discipline', 'espece', 'niveaudifficulte', 'dureeindicative',
        'statut', 'envcode', 'salle', 'zone', 'codeposte'];
    $lignes = [];
    foreach (atelier::get_records([], 'nomcourt') as $a) {
        $lignes[] = array_map(fn($champ) => $a->get($champ), $entetes);
    }
    exporteur::envoyer('simhub_ateliers', $format, $entetes, $lignes);
} else if ($type === 'parcours') {
    $parcours = new parcours(required_param('parcoursid', PARAM_INT));
    if (!droits::peut_suivre_parcours($parcours)) {
        throw $refus('local/simhub:viewprogression');
    }
    $atelierids = array_map('intval', array_column($parcours->get_ateliers(), 'atelierid'));
    [$entetes, $lignes] = exporteur::tableau_suivi(
        droits::etudiants_du_parcours($parcours),
        $atelierids,
        fn($uid) => \local_simhub\local\parcours_helper::progression($parcours, $uid)['pct']
    );
    exporteur::envoyer('simhub_parcours_' . $parcours->get('id'), $format, $entetes, $lignes);
} else if ($type === 'uc') {
    $courseid = required_param('courseid', PARAM_INT);
    get_course($courseid);
    if (!droits::peut_suivre_uc($courseid)) {
        throw $refus('local/simhub:exportsuivi');
    }
    [$entetes, $lignes] = exporteur::suivi_uc($courseid);
    exporteur::envoyer('simhub_uc_' . $courseid, $format, $entetes, $lignes);
} else if ($type === 'cohorte') {
    require_capability('local/simhub:exportsuivi', $context);
    $cohortid = required_param('cohortid', PARAM_INT);
    $DB->get_record('cohort', ['id' => $cohortid], 'id', MUST_EXIST);
    [$entetes, $lignes] = exporteur::suivi_cohorte($cohortid);
    exporteur::envoyer('simhub_cohorte_' . $cohortid, $format, $entetes, $lignes);
} else if ($type === 'etudiant') {
    $userid = optional_param('userid', $USER->id, PARAM_INT);
    core_user::get_user($userid, 'id', MUST_EXIST);
    if (!droits::peut_suivre_etudiant($userid)) {
        throw $refus('local/simhub:viewprogression');
    }
    [$entetes, $lignes] = exporteur::historique_etudiant($userid);
    exporteur::envoyer('simhub_historique_' . $userid, $format, $entetes, $lignes);
} else {
    throw new \moodle_exception('invalidaction', 'error');
}
