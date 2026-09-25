<?php
// Suivi de progression d'un parcours (§8.1) : par étudiant, ateliers réalisés/validés,
// pourcentage d'avancement. Vue destinée à l'enseignant/responsable d'UC pour identifier
// rapidement qui n'a pas commencé, qui a commencé sans terminer, et les échéances proches.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;

require_login();

$context = context_system::instance();
require_capability('local/simhub:viewprogression', $context);

$parcoursid = required_param('parcoursid', PARAM_INT);
$parcours = new parcours($parcoursid);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid]), s($parcours->get('nom')), [
    [get_string('filtre_parcours', 'local_simhub'), new moodle_url('/local/simhub/manage/parcours.php')],
]);
\local_simhub\local\navigation::onglets('parcours', $parcoursid, 'suivi');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo $OUTPUT->single_button(
    new moodle_url('/local/simhub/manage/export.php', ['type' => 'parcours', 'parcoursid' => $parcoursid]),
    get_string('export_csv', 'local_simhub'),
    'get'
);

global $DB;

$composition = $parcours->get_ateliers();
$atelierids = array_column($composition, 'atelierid');

if (empty($atelierids)) {
    echo $OUTPUT->notification(get_string('aucun_atelier', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

// Étudiants concernés : membres de la cohorte rattachée au parcours si connue, sinon tout
// étudiant ayant au moins une session sur l'un des ateliers du parcours (§8.1).
$cohortid = $parcours->get('cohortid');
if ($cohortid) {
    $users = $DB->get_records_sql(
        "SELECT u.id, u.firstname, u.lastname
           FROM {cohort_members} cm
           JOIN {user} u ON u.id = cm.userid
          WHERE cm.cohortid = :cohortid
       ORDER BY u.lastname, u.firstname",
        ['cohortid' => $cohortid]
    );
} else {
    [$insql, $params] = $DB->get_in_or_equal($atelierids);
    $users = $DB->get_records_sql(
        "SELECT u.id, u.firstname, u.lastname
           FROM {local_simhub_session} s
           JOIN {user} u ON u.id = s.userid
          WHERE s.atelierid $insql
       GROUP BY u.id, u.firstname, u.lastname
       ORDER BY u.lastname, u.firstname",
        $params
    );
}

$table = new html_table();
$head = ['Étudiant'];
$ateliersbyid = [];
foreach ($atelierids as $aid) {
    $atelier = new atelier($aid);
    $ateliersbyid[$aid] = $atelier;
    $head[] = s($atelier->get('nomcourt'));
}
$head[] = 'Avancement';
$head[] = '';
$table->head = $head;

foreach ($users as $user) {
    $row = [fullname($user)];
    $realises = 0;

    foreach ($atelierids as $aid) {
        $sessions = session::get_pour_etudiant($user->id, $aid);
        $latest = $sessions ? reset($sessions) : null;

        if (!$latest) {
            $row[] = '—';
        } else if ($latest->get('statut') === session::STATUT_CERTIFIE) {
            $row[] = 'Validé';
            $realises++;
        } else if (in_array($latest->get('statut'), [session::STATUT_REALISE], true)) {
            $row[] = 'Réalisé';
            $realises++;
        } else {
            $row[] = 'Commencé';
        }
    }

    $pct = round(100 * $realises / count($atelierids));
    $row[] = $pct . ' %';

    if ($pct >= 100) {
        $attestationurl = new moodle_url('/local/simhub/manage/parcours_attestation_pdf.php', [
            'parcoursid' => $parcoursid, 'userid' => $user->id,
        ]);
        $row[] = html_writer::link($attestationurl, 'Attestation (PDF)');
    } else {
        $row[] = '';
    }

    $table->data[] = $row;
}

echo html_writer::table($table);

echo $OUTPUT->footer();
