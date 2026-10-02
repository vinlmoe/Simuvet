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
 * Suivi de progression d'un parcours (§8.1) : par étudiant, ateliers réalisés/validés,
 * pourcentage d'avancement. Vue destinée à l'enseignant/responsable d'UC pour identifier
 * rapidement qui n'a pas commencé, qui a commencé sans terminer, et les échéances proches.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;

require_login();

$context = \local_simhub\local\contexte::racine();

$parcoursid = required_param('parcoursid', PARAM_INT);
$parcours = new parcours($parcoursid);
if (!\local_simhub\local\droits::peut_suivre_parcours($parcours)) {
    throw new required_capability_exception(
        \local_simhub\local\contexte::racine(),
        'local/simhub:viewprogression',
        'nopermissions',
        '',
    );
}

\local_simhub\local\navigation::preparer(
    $PAGE,
    \local_simhub\local\navigation::url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid]),
    s($parcours->get('nom')),
    \local_simhub\local\droits::etape_activite($parcours)
    ?: [[get_string('filtre_parcours', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/manage/parcours.php')]]
);
\local_simhub\local\navigation::onglets('parcours', $parcoursid, 'suivi');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::div(\local_simhub\local\exporteur::liens(['type' => 'parcours', 'parcoursid' => $parcoursid]), 'mb-3');

global $DB;

$composition = $parcours->get_ateliers();
$atelierids = array_column($composition, 'atelierid');

if (empty($atelierids)) {
    echo $OUTPUT->notification(get_string('aucun_atelier', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

// Les étudiants concernés sont : membres de la cohorte rattachée au parcours si connue, sinon tout
// étudiant ayant au moins une session sur l'un des ateliers du parcours (§8.1).
$users = \local_simhub\local\droits::etudiants_du_parcours($parcours);

// Parcours d'UC : les groupes de l'activité filtrent le suivi (un encadrant suit son groupe de TP).
$cm = $parcours->get('cmid') ? get_coursemodule_from_id('simhub', $parcours->get('cmid')) : false;
if ($cm && groups_get_activity_groupmode($cm)) {
    groups_print_activity_menu(
        $cm,
        \local_simhub\local\navigation::url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid])
    );
    $groupid = groups_get_activity_group($cm, true);
    if ($groupid) {
        $membres = array_map('intval', array_keys(groups_get_members($groupid, 'u.id')));
        $users = array_filter($users, fn($u) => in_array((int) $u->id, $membres, true));
    }
}
$requis = \local_simhub\local\parcours_helper::ateliers_requis($parcours);

$table = new html_table();
$head = [get_string('etudiant', 'local_simhub')];
$ateliersbyid = [];
foreach ($atelierids as $aid) {
    $atelier = new atelier($aid);
    $ateliersbyid[$aid] = $atelier;
    $head[] = s($atelier->get('nomcourt')) . (in_array((int) $aid, $requis, true) && count($requis) < count($atelierids)
        ? ' *' : '');
}
$head[] = get_string('avancement', 'local_simhub');
$head[] = '';
$table->head = $head;

$userids = array_map('intval', array_keys($users));
$statuts = \local_simhub\local\parcours_helper::statuts($userids, $atelierids);
$progressions = \local_simhub\local\parcours_helper::progressions($parcours, $userids);
foreach ($users as $user) {
    $row = [fullname($user)];
    foreach ($atelierids as $aid) {
        $statut = $statuts[(int) $user->id][(int) $aid];
        $row[] = $statut === 'pascommence' ? '—' : get_string('statutperso_' . $statut, 'local_simhub');
    }

    $pct = $progressions[(int) $user->id]['pct'];
    $row[] = $pct . ' %';

    if ($pct >= 100) {
        $attestationurl = \local_simhub\local\navigation::url('/local/simhub/manage/parcours_attestation_pdf.php', [
            'parcoursid' => $parcoursid, 'userid' => $user->id,
        ]);
        $row[] = html_writer::link($attestationurl, get_string('attestation_pdf', 'local_simhub'));
    } else {
        $row[] = '';
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
