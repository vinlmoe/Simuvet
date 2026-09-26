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

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:viewprogression', $context);

$envcode = '';

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/dashboard.php'), get_string('dashboard_parcours', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

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

        $pct = \local_simhub\local\parcours_helper::progression($parcours, $user->id)['pct'];
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
