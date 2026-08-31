<?php
// Tableau de bord pédagogique par parcours/cohorte (§12.2), plus riche que la simple liste
// de manage/parcours.php ou le tableau brut de parcours_suivi.php : une vue d'ensemble,
// parcours par parcours, avec des indicateurs agrégés (étudiants n'ayant pas commencé,
// commencé sans terminer, ateliers réalisés mais non validés, à reprendre, échéances
// proches ou dépassées) plutôt qu'un simple pourcentage global.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\session;
use local_simhub\record\ae_reponse;

require_login();

$context = context_system::instance();
require_capability('local/simhub:viewprogression', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/dashboard.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('dashboard_parcours', 'local_simhub'));
$PAGE->set_heading(get_string('dashboard_parcours', 'local_simhub'));

echo $OUTPUT->header();

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
    'Étudiants',
    'Avancement moyen',
    'Pas commencé',
    'Commencé',
    'Terminé',
    'À reprendre',
    'Échéance proche/dépassée',
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

    $cohortid = $parcours->get('cohortid');
    if ($cohortid) {
        $users = $DB->get_records_sql(
            "SELECT u.id
               FROM {cohort_members} cm
               JOIN {user} u ON u.id = cm.userid
              WHERE cm.cohortid = :cohortid",
            ['cohortid' => $cohortid]
        );
    } else {
        [$insql, $sparams] = $DB->get_in_or_equal($atelierids);
        $users = $DB->get_records_sql(
            "SELECT u.id
               FROM {local_simhub_session} s
               JOIN {user} u ON u.id = s.userid
              WHERE s.atelierid $insql
           GROUP BY u.id",
            $sparams
        );
    }

    $nbnoncommence = 0;
    $nbencours = 0;
    $nbtermine = 0;
    $nbareprendre = 0;
    $nbecheance = 0;
    $sommepct = 0;

    foreach ($users as $user) {
        $realises = 0;
        $acommence = false;
        $areprendre = false;
        $echeanceproche = false;

        foreach ($atelierids as $aid) {
            $sessions = session::get_pour_etudiant($user->id, $aid);
            $latest = $sessions ? reset($sessions) : null;

            if ($latest) {
                $acommence = true;
                if (in_array($latest->get('statut'), [session::STATUT_REALISE, session::STATUT_CERTIFIE], true)) {
                    $realises++;
                    if (ae_reponse::get_a_retravailler($latest->get('id'))) {
                        $areprendre = true;
                    }
                }
            }

            if (!$latest || !in_array($latest->get('statut'), [session::STATUT_REALISE, session::STATUT_CERTIFIE], true)) {
                $echeance = $echeancesparatelier[$aid] ?? null;
                if ($echeance && $echeance <= $maintenant + $fenetreechujours * DAYSECS) {
                    $echeanceproche = true;
                }
            }
        }

        $pct = round(100 * $realises / count($atelierids));
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
        html_writer::link($suiviurl, 'Détail'),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
