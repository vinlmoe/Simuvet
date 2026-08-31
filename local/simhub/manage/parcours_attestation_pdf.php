<?php
// Attestation de fin de parcours (§8, §12.3) : générée uniquement si l'étudiant a réalisé
// ou validé la totalité des ateliers du parcours (avancement à 100%, §8.1) — sinon la page
// affiche l'avancement actuel plutôt qu'un document à moitié rempli.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\local\pdf_helper;
use local_simhub\local\badge_helper;

require_login();

$context = context_system::instance();

$parcoursid = required_param('parcoursid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);

if ($userid != $USER->id) {
    require_capability('local/simhub:viewprogression', $context);
} else {
    require_capability('local/simhub:view', $context);
}

$parcours = new parcours($parcoursid);
$user = \core_user::get_user($userid, '*', MUST_EXIST);

$composition = $parcours->get_ateliers();
$atelierids = array_column($composition, 'atelierid');

$realises = 0;
$noms = [];
foreach ($atelierids as $aid) {
    $atelier = new atelier($aid);
    $noms[$aid] = $atelier->get('nomcourt');

    $sessions = session::get_pour_etudiant($userid, $aid);
    $latest = $sessions ? reset($sessions) : null;
    if ($latest && in_array($latest->get('statut'), [session::STATUT_REALISE, session::STATUT_CERTIFIE], true)) {
        $realises++;
    }
}

$total = count($atelierids);
$pct = $total > 0 ? round(100 * $realises / $total) : 0;

if ($total === 0 || $pct < 100) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/simhub/manage/parcours_attestation_pdf.php', ['parcoursid' => $parcoursid, 'userid' => $userid]));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(s($parcours->get('nom')));
    $PAGE->set_heading(s($parcours->get('nom')));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        'L\'attestation ne peut pas encore être délivrée : ' . fullname($user) . ' est à ' . $pct . '% '
        . 'du parcours "' . s($parcours->get('nom')) . '" (' . $realises . '/' . $total . ' ateliers réalisés).',
        \core\output\notification::NOTIFY_WARNING
    );
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid]));
    echo $OUTPUT->footer();
    exit;
}

// Le parcours étant effectivement terminé (100%, vérifié ci-dessus), on délivre le badge
// Moodle configuré pour ce parcours, s'il y en a un (§13) — sans jamais faire échouer la
// génération de l'attestation si la délivrance du badge pose problème.
badge_helper::delivrer($parcours->get('badgeid') ?: null, $userid);

$pdf = new pdf();
$pdf->SetCreator('SimHub');
$pdf->SetTitle('Attestation — ' . $parcours->get('nom') . ' — ' . fullname($user));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
pdf_helper::ajouter_entete($pdf);

$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 12, 'Attestation de réalisation', 0, 1, 'C');
$pdf->Ln(6);

$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML(
    '<p>' . pdf_helper::get_etablissement_nom() . ' certifie que</p>'
    . '<p style="text-align:center;font-size:15pt;"><b>' . s(fullname($user)) . '</b></p>'
    . '<p>a réalisé l\'ensemble des ateliers du parcours <b>' . s($parcours->get('nom')) . '</b>.</p>',
    true,
    false,
    true,
    false,
    ''
);

$html = '<table border="1" cellpadding="4"><tr style="font-weight:bold;"><th>Atelier</th></tr>';
foreach ($atelierids as $aid) {
    $html .= '<tr><td>' . s($noms[$aid]) . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(10);
$pdf->Cell(0, 6, 'Délivrée le ' . userdate(time(), get_string('strftimedate', 'langconfig')), 0, 1);

$pdf->Output('simhub_attestation_parcours_' . $parcoursid . '_' . $userid . '.pdf', 'D');
