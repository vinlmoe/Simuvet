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
 * Tableau de bord pédagogique par parcours/cohorte (§12.2), plus riche que la simple liste
 * de manage/parcours.php ou le tableau brut de parcours_suivi.php : une vue d'ensemble,
 * parcours par parcours, avec des indicateurs agrégés (étudiants n'ayant pas commencé,
 * commencé sans terminer, ateliers réalisés mais non validés, à reprendre, échéances
 * proches ou dépassées) plutôt qu'un simple pourcentage global.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\session;
use local_simhub\record\ae_reponse;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:viewprogression', $context);

$envcode = '';

$pageurl = new moodle_url('/local/simhub/manage/dashboard.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('dashboard_parcours', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// Export du suivi par cohorte (§12.3).
if (has_capability('local/simhub:exportsuivi', $context)) {
    $cohortes = $DB->get_records_menu('cohort', ['visible' => 1], 'name', 'id, name');
    $cohortid = optional_param('cohortid', 0, PARAM_INT);
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3']);
    echo html_writer::label(get_string('export_cohorte', 'local_simhub'), 'id_cohortid', true, ['class' => 'mr-2 me-2']);
    echo html_writer::select(
        array_map('format_string', $cohortes),
        'cohortid',
        $cohortid,
        ['' => 'choosedots'],
        ['id' => 'id_cohortid', 'class' => 'form-control mr-2 me-2']
    );
    echo html_writer::tag('button', get_string('choose'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
    echo html_writer::end_tag('form');
    if ($cohortid && isset($cohortes[$cohortid])) {
        echo html_writer::div(\local_simhub\local\exporteur::liens(['type' => 'cohorte', 'cohortid' => $cohortid]), 'mb-3');
    }
}

global $DB;

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$parcourslist = parcours::get_records($params, 'nom');

if (empty($parcourslist)) {
    echo $OUTPUT->notification(get_string('aucun_atelier', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

// Fenêtre de proximité d'échéance (§8, "échéances pédagogiques proches") : deux semaines,
// volontairement fixe pour rester simple plutôt que d'ajouter un réglage de plus.
$fenetreechujours = 14;
$maintenant = time();

$table = new html_table();
$table->head = [
    get_string('filtre_parcours', 'local_simhub'),
    get_string('dash_etudiants', 'local_simhub'),
    get_string('dash_avancement_moyen', 'local_simhub'),
    get_string('dash_pas_commence', 'local_simhub'),
    get_string('dash_commence', 'local_simhub'),
    get_string('dash_termine', 'local_simhub'),
    get_string('dash_a_reprendre', 'local_simhub'),
    get_string('dash_echeance', 'local_simhub'),
    '',
];

foreach ($parcourslist as $parcours) {
    $composition = $parcours->get_ateliers();
    $atelierids = array_column($composition, 'atelierid');

    if (empty($atelierids)) {
        continue;
    }

    $echeancesparatelier = [];
    foreach ($composition as $lien) {
        $echeancesparatelier[$lien->atelierid] = $lien->echeance ?: null;
    }

    $users = \local_simhub\local\parcours_helper::etudiants($parcours);

    $nbnoncommence = 0;
    $nbencours = 0;
    $nbtermine = 0;
    $nbareprendre = 0;
    $nbecheance = 0;
    $sommepct = 0;

    $userids = array_map('intval', array_keys($users));
    $seances = \local_simhub\local\parcours_helper::dernieres_seances($userids, $atelierids);
    $progressions = \local_simhub\local\parcours_helper::progressions($parcours, $userids);
    // Séances les plus récentes dont l'auto-évaluation comporte un critère à retravailler.
    $aretravailler = [];
    $ids = [];
    foreach ($seances as $parseance) {
        foreach ($parseance as $s) {
            if ($s && in_array($s->statut, [session::STATUT_REALISE, session::STATUT_CERTIFIE], true)) {
                $ids[] = (int) $s->id;
            }
        }
    }
    foreach (array_chunk($ids, 500) as $lot) {
        [$insql, $inparams] = $DB->get_in_or_equal($lot, SQL_PARAMS_NAMED, 's');
        [$nsql, $nparams] = $DB->get_in_or_equal(
            [ae_reponse::NIVEAU_A_CONSOLIDER, ae_reponse::NIVEAU_A_REPRENDRE],
            SQL_PARAMS_NAMED,
            'n'
        );
        $aretravailler += array_flip($DB->get_fieldset_select(
            ae_reponse::TABLE,
            'DISTINCT sessionid',
            "sessionid $insql AND niveau $nsql",
            $inparams + $nparams
        ));
    }

    foreach ($users as $user) {
        $acommence = false;
        $areprendre = false;
        $echeanceproche = false;

        foreach ($atelierids as $aid) {
            $latest = $seances[(int) $user->id][(int) $aid];
            $fait = $latest && in_array($latest->statut, [session::STATUT_REALISE, session::STATUT_CERTIFIE], true);

            if ($latest) {
                $acommence = true;
                if ($fait && isset($aretravailler[(int) $latest->id])) {
                    $areprendre = true;
                }
            }

            if (!$fait) {
                $echeance = $echeancesparatelier[$aid] ?? null;
                if ($echeance && $echeance <= $maintenant + $fenetreechujours * DAYSECS) {
                    $echeanceproche = true;
                }
            }
        }

        $pct = $progressions[(int) $user->id]['pct'];
        $sommepct += $pct;

        if (!$acommence) {
            $nbnoncommence++;
        } else if ($pct >= 100) {
            $nbtermine++;
        } else {
            $nbencours++;
        }
        if ($areprendre) {
            $nbareprendre++;
        }
        if ($echeanceproche) {
            $nbecheance++;
        }
    }

    $nbetudiants = count($users);
    $moyenne = $nbetudiants > 0 ? round($sommepct / $nbetudiants) : 0;

    $suiviurl = new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcours->get('id')]);

    $barre = html_writer::div('', '', [
        'style' => sprintf(
            'height:6px;background:#28a745;width:%d%%;border-radius:3px;',
            $moyenne
        ),
    ]);
    $barrecontainer = html_writer::div($barre, '', [
        'style' => 'background:#e9ecef;border-radius:3px;margin-bottom:2px;',
    ]);

    $table->data[] = [
        s($parcours->get('nom')),
        $nbetudiants,
        $barrecontainer . $moyenne . ' %',
        $nbnoncommence,
        $nbencours,
        $nbtermine,
        $nbareprendre ?: '—',
        $nbecheance ?: '—',
        html_writer::link($suiviurl, get_string('detail', 'local_simhub')),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
