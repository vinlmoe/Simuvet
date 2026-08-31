<?php
// Export PDF du livret de compétences ASV (§9.4) : récapitule, pour l'étudiant connecté
// (ou un étudiant choisi par un pilote ASV), les actes du référentiel et leur état de
// validation en simulation / sur animal vivant, avec date et nom du validateur — la
// version numérique du livret papier "acte, espèce, validation simulation, validation
// animal vivant, date et signature" (§9.1).

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$userid = optional_param('userid', $USER->id, PARAM_INT);
if ($userid != $USER->id) {
    require_capability('local/simhub:manageasv', $context);
}

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$user = \core_user::get_user($userid, '*', MUST_EXIST);
$actes = asv_acte::get_referentiel($envcode);

$valsim = [];
foreach (asv_valsim::get_pour_etudiant($userid) as $v) {
    $valsim[$v->acteid] = $v;
}
$valanimal = [];
foreach (asv_valanimal::get_pour_etudiant($userid) as $v) {
    if ($v->statut === asv_valanimal::STATUT_VALIDE) {
        $valanimal[$v->acteid] = $v;
    }
}

$pdf = new pdf();
$pdf->SetCreator('SimHub');
$pdf->SetAuthor(fullname($user));
$pdf->SetTitle(get_string('asv_livret', 'local_simhub') . ' — ' . fullname($user));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, get_string('asv_livret', 'local_simhub'), 0, 1);

$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, fullname($user) . ' — ' . userdate(time(), get_string('strftimedate', 'langconfig')), 0, 1);
$pdf->Ln(4);

$html = '<table border="1" cellpadding="4">';
$html .= '<tr style="font-weight:bold;">'
    . '<th width="30%">Acte</th><th width="10%">Niveau</th><th width="15%">Espèce</th>'
    . '<th width="22%">Validation simulation</th><th width="23%">Validation animal vivant</th></tr>';

foreach ($actes as $acte) {
    $sim = $valsim[$acte->get('id')] ?? null;
    $animal = $valanimal[$acte->get('id')] ?? null;

    $simtext = $sim
        ? userdate($sim->datevalidation, get_string('strftimedatefullshort', 'langconfig'))
        : '—';
    $animaltext = $animal
        ? userdate($animal->datevalidation, get_string('strftimedatefullshort', 'langconfig'))
            . ' (' . s($animal->prenomvalidateur . ' ' . $animal->nomvalidateur) . ')'
        : '—';

    $html .= '<tr>'
        . '<td>' . s($acte->get('nom')) . '</td>'
        . '<td>' . s($acte->get('niveau')) . '</td>'
        . '<td>' . s($acte->get('espece')) . '</td>'
        . '<td>' . $simtext . '</td>'
        . '<td>' . $animaltext . '</td>'
        . '</tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Output('livret_asv_' . $userid . '.pdf', 'D');
