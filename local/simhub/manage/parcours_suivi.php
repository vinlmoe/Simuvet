<?php
// Suivi de progression d'un parcours (§8.1) : par étudiant, ateliers réalisés/validés,
// pourcentage d'avancement. Vue destinée à l'enseignant/responsable d'UC pour identifier
// rapidement qui n'a pas commencé, qui a commencé sans terminer, et les échéances proches.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;

require_login();

$context = \local_simhub\local\contexte::racine();

$parcoursid = required_param('parcoursid', PARAM_INT);
$parcours = new parcours($parcoursid);
if (!\local_simhub\local\droits::peut_suivre_parcours($parcours)) {
    throw new required_capability_exception(\local_simhub\local\contexte::racine(), 'local/simhub:viewprogression', 'nopermissions', '');
}

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid]), s($parcours->get('nom')),
    \local_simhub\local\droits::etape_activite($parcours)
        ?: [[get_string('filtre_parcours', 'local_simhub'), new moodle_url('/local/simhub/manage/parcours.php')]]);
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
$users = \local_simhub\local\droits::etudiants_du_parcours($parcours);
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

foreach ($users as $user) {
    $row = [fullname($user)];
    $realises = 0;

    foreach ($atelierids as $aid) {
        $sessions = session::get_pour_etudiant($user->id, $aid);
        $latest = $sessions ? reset($sessions) : null;

        if (!$latest) {
            $row[] = '—';
        } else if ($latest->get('statut') === session::STATUT_CERTIFIE) {
            $row[] = get_string('suivi_valide', 'local_simhub');
            $realises++;
        } else if (in_array($latest->get('statut'), [session::STATUT_REALISE], true)) {
            $row[] = get_string('suivi_realise', 'local_simhub');
            $realises++;
        } else {
            $row[] = get_string('suivi_commence', 'local_simhub');
        }
    }

    $pct = \local_simhub\local\parcours_helper::progression($parcours, $user->id)['pct'];
    $row[] = $pct . ' %';

    if ($pct >= 100) {
        $attestationurl = new moodle_url('/local/simhub/manage/parcours_attestation_pdf.php', [
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
