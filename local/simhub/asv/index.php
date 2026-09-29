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
 * Pilotage du parcours ASV (§9.4) : progression individuelle (étudiant connecté) ou vue
 * d'ensemble par acte/étudiant (encadrant/gestionnaire ASV).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$envcode = '';
$canpilot = has_capability('local/simhub:manageasv', $context) || has_capability('local/simhub:validateasvsimulation', $context);

$pageurl = new moodle_url('/local/simhub/asv/index.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_parcours', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$actes = asv_acte::get_referentiel($envcode);

// Côté encadrant, le livret d'un étudiant s'exporte depuis sa fiche (asv/etudiant.php).
if (!$canpilot) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/asv/livret_pdf.php', ['envcode' => $envcode]),
        get_string('asv_exporter_livret', 'local_simhub'),
        'get'
    );
}

if (has_capability('local/simhub:manageasv', $context)) {
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/asv_actes.php', ['envcode' => $envcode]),
        get_string('asv_gerer_actes', 'local_simhub'),
        'get'
    );
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/manage/asv_attestations.php', ['envcode' => $envcode]),
        get_string('asv_attestations_groupees', 'local_simhub'),
        'get'
    );
}

if (!$canpilot) {
    // Vue étudiant (§9.4 « état d'avancement individuel ») : avancement vers chaque
    // certification, puis détail acte par acte avec les demandes sur animal vivant en attente.
    echo \local_simhub\local\asv_vue::certifications($USER->id, $envcode);
    echo \local_simhub\local\asv_vue::tableau($USER->id, $envcode, false);
} else {
    // Vue pilotage (§9.4) : étudiant par étudiant, filtrable par cohorte (promotion).
    global $DB;

    $cohortid = optional_param('cohortid', 0, PARAM_INT);
    echo html_writer::start_tag('form', ['method' => 'get', 'class' => 'form-inline mb-3']);
    echo html_writer::tag('label', get_string('champ_cohorte', 'local_simhub'), ['for' => 'id_cohortid', 'class' => 'mr-2 me-2']);
    echo html_writer::select(
        \local_simhub\local\cohort_helper::get_options(false),
        'cohortid',
        $cohortid,
        ['0' => get_string('asv_tous_etudiants', 'local_simhub')],
        ['id' => 'id_cohortid', 'class' => 'form-control mr-2 me-2']
    );
    echo html_writer::tag('button', get_string('filtrer', 'local_simhub'), ['type' => 'submit', 'class' => 'btn btn-secondary']);
    echo html_writer::end_tag('form');

    if ($cohortid) {
        $userids = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$cohortid]);
    } else {
        // Sans cohorte choisie : tous les étudiants ayant au moins une trace ASV.
        $userids = array_unique(array_merge(
            $DB->get_fieldset_select('local_simhub_asv_valsim', 'DISTINCT userid', '1 = 1'),
            $DB->get_fieldset_select('local_simhub_asv_valanimal', 'DISTINCT userid', '1 = 1')
        ));
    }

    $total = count($actes);
    $table = new html_table();
    $table->head = [
        get_string('etudiant', 'local_simhub'),
        get_string('asv_col_valides_simulation', 'local_simhub'),
        get_string('asv_col_valides_animal', 'local_simhub'),
        get_string('asv_col_en_attente', 'local_simhub'),
        get_string('asv_col_certifications', 'local_simhub'),
    ];
    $lignes = [];
    foreach ($userids as $uid) {
        $user = \core_user::get_user($uid);
        if (!$user || $user->deleted) {
            continue;
        }
        $etat = \local_simhub\local\asv_certification_helper::etat_etudiant($uid, $envcode);
        $nbsim = $nbanimal = $nbattente = 0;
        foreach ($etat as $e) {
            $nbsim += ($e['sim'] && $e['sim']->statut === asv_valsim::STATUT_VALIDE) ? 1 : 0;
            $nbanimal += $e['animal'] ? 1 : 0;
            $nbattente += $e['attente'] ? 1 : 0;
        }
        $certifs = [];
        foreach (['A1', 'A2', 'A3'] as $niveau) {
            if (
                !empty(\local_simhub\local\asv_certification_helper::get_actes_requis($envcode, $niveau))
                    && empty(\local_simhub\local\asv_certification_helper::get_actes_manquants($uid, $niveau, $envcode))
            ) {
                $certifs[] = $niveau === 'A3' ? get_string('asv_certif_globale_courte', 'local_simhub') : $niveau;
            }
        }
        $lignes[fullname($user) . $uid] = [
            html_writer::link(new moodle_url('/local/simhub/asv/etudiant.php', ['userid' => $uid]), s(fullname($user))),
            $nbsim . ' / ' . $total,
            $nbanimal . ' / ' . $total,
            $nbattente ?: '',
            $certifs ? '✔ ' . implode(', ', $certifs) : '—',
        ];
    }
    ksort($lignes);
    $table->data = array_values($lignes);

    if (empty($table->data)) {
        echo $OUTPUT->notification(get_string('asv_aucun_etudiant', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    } else {
        echo html_writer::table($table);
    }

    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/asv/valider_simulation.php'),
        get_string('asv_valider_simulation', 'local_simhub')
    );
    echo $OUTPUT->single_button(
        new moodle_url('/local/simhub/asv/demande_lot.php'),
        get_string('asv_lot_titre', 'local_simhub'),
        'get'
    );

    // Synthèse par acte, pour repérer les actes rarement validés.
    echo html_writer::tag('h4', get_string('asv_synthese_par_acte', 'local_simhub'), ['class' => 'mt-4']);
    $table = new html_table();
    $table->head = [
        get_string('asv_acte', 'local_simhub'),
        get_string('asv_champ_niveau', 'local_simhub'),
        get_string('asv_col_valides_simulation', 'local_simhub'),
        get_string('asv_col_valides_animal', 'local_simhub'),
    ];
    foreach ($actes as $acte) {
        $countsim = $DB->count_records_select(
            'local_simhub_asv_valsim',
            'acteid = ? AND statut = ?',
            [$acte->get('id'), asv_valsim::STATUT_VALIDE],
            'COUNT(DISTINCT userid)'
        );
        $countanimal = $DB->count_records_select(
            'local_simhub_asv_valanimal',
            'acteid = ? AND statut = ?',
            [$acte->get('id'), asv_valanimal::STATUT_VALIDE],
            'COUNT(DISTINCT userid)'
        );
        $table->data[] = [s($acte->get('nom')), s($acte->get('niveau')), $countsim, $countanimal];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
