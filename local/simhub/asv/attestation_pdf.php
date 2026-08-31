<?php
// Attestation de certification globale de fin de A3 (§9.4 "Possibilité d'organiser une
// certification globale de fin de A3"). Un acte n'est considéré comme couvert que si les
// deux niveaux de validation du livret sont acquis — simulation et animal vivant (§9.1) —
// ce qui distingue cette attestation du simple export du livret (asv/livret_pdf.php), qui
// se contente de refléter l'état d'avancement sans se prononcer sur une certification.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;
use local_simhub\local\pdf_helper;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$userid = optional_param('userid', $USER->id, PARAM_INT);
if ($userid != $USER->id) {
    require_capability('local/simhub:manageasv', $context);
}

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);
$niveau = optional_param('niveau', 'A3', PARAM_ALPHANUM);

$user = \core_user::get_user($userid, '*', MUST_EXIST);
$actes = asv_acte::get_referentiel($envcode, $niveau);

if (empty($actes)) {
    redirect(
        new moodle_url('/local/simhub/asv/index.php'),
        'Aucun acte ' . $niveau . ' dans le référentiel de cet établissement.',
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$actesvalidessim = asv_valsim::get_actes_valides($userid);
$actesvalidesanimal = [];
foreach (asv_valanimal::get_pour_etudiant($userid) as $v) {
    if ($v->statut === asv_valanimal::STATUT_VALIDE) {
        $actesvalidesanimal[$v->acteid] = true;
    }
}

$manquants = [];
foreach ($actes as $acte) {
    $simok = in_array($acte->get('id'), $actesvalidessim, true);
    $animalok = !empty($actesvalidesanimal[$acte->get('id')]);
    if (!$simok || !$animalok) {
        $manquants[] = $acte->get('nom') . ($simok ? '' : ' (simulation manquante)')
            . ($animalok ? '' : ' (animal vivant manquant)');
    }
}

if (!empty($manquants)) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/simhub/asv/attestation_pdf.php', ['userid' => $userid, 'niveau' => $niveau]));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title('Certification ' . $niveau);
    $PAGE->set_heading('Certification ' . $niveau);

    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        'La certification ' . $niveau . ' de ' . fullname($user) . ' ne peut pas encore être délivrée : '
        . count($manquants) . ' acte(s) restent à valider.',
        \core\output\notification::NOTIFY_WARNING
    );
    echo html_writer::start_tag('ul');
    foreach ($manquants as $m) {
        echo html_writer::tag('li', s($m));
    }
    echo html_writer::end_tag('ul');
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}

$pdf = new pdf();
$pdf->SetCreator('SimHub');
$pdf->SetTitle('Certification ' . $niveau . ' — ' . fullname($user));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
pdf_helper::ajouter_entete($pdf);

$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 12, 'Attestation de certification', 0, 1, 'C');
$pdf->SetFont('helvetica', '', 13);
$pdf->Cell(0, 8, 'Actes vétérinaires délégables ASV — niveau ' . $niveau, 0, 1, 'C');
$pdf->Ln(10);

$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML(
    '<p>' . pdf_helper::get_etablissement_nom() . ' certifie que</p>'
    . '<p style="text-align:center;font-size:15pt;"><b>' . s(fullname($user)) . '</b></p>'
    . '<p>a validé, en simulation puis sur animal vivant, l\'ensemble des actes vétérinaires '
    . 'délégables du niveau ' . s($niveau) . ' listés ci-dessous :</p>',
    true,
    false,
    true,
    false,
    ''
);

$html = '<table border="1" cellpadding="4"><tr style="font-weight:bold;">'
    . '<th width="70%">Acte</th><th width="30%">Espèce</th></tr>';
foreach ($actes as $acte) {
    $html .= '<tr><td>' . s($acte->get('nom')) . '</td><td>' . s($acte->get('espece')) . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(10);
$pdf->Cell(0, 6, 'Délivrée le ' . userdate(time(), get_string('strftimedate', 'langconfig')), 0, 1);

$pdf->Output('simhub_certification_' . $niveau . '_' . $userid . '.pdf', 'D');
