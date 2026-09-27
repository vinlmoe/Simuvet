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
 * Export PDF du livret de compétences ASV (§9.4) : récapitule, pour l'étudiant connecté
 * (ou un étudiant choisi par un pilote ASV), les actes du référentiel et leur état de
 * validation en simulation / sur animal vivant, avec date et nom du validateur — la
 * version numérique du livret papier "acte, espèce, validation simulation, validation
 * animal vivant, date et signature" (§9.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;
use local_simhub\local\pdf_helper;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$userid = optional_param('userid', $USER->id, PARAM_INT);
if ($userid != $USER->id && !\local_simhub\local\droits::peut_valider_asv($userid)) {
    require_capability('local/simhub:manageasv', $context);
}

$envcode = '';

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
pdf_helper::ajouter_entete($pdf);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, get_string('asv_livret', 'local_simhub'), 0, 1);

$pdf->SetFont('helvetica', '', 11);
$pdf->Cell(0, 8, fullname($user) . ' — ' . userdate(time(), get_string('strftimedate', 'langconfig')), 0, 1);
$pdf->Ln(4);

$html = '<table border="1" cellpadding="4">';
$html .= '<tr style="font-weight:bold;">'
    . '<th width="30%">' . get_string(
        'asv_acte',
        'local_simhub',
    ) . '</th><th width="10%">' . get_string('asv_champ_niveau', 'local_simhub') . '</th>'
    . '<th width="15%">' . get_string('champ_espece', 'local_simhub') . '</th>'
    . '<th width="22%">' . get_string('asv_col_validation_simulation', 'local_simhub') . '</th>'
    . '<th width="23%">' . get_string('asv_col_validation_animal', 'local_simhub') . '</th></tr>';

foreach ($actes as $acte) {
    $sim = $valsim[$acte->get('id')] ?? null;
    $animal = $valanimal[$acte->get('id')] ?? null;

    $simtext = '—';
    if ($sim) {
        $encadrant = \core_user::get_user($sim->validateuruserid);
        $simtext = userdate($sim->datevalidation, get_string('strftimedatefullshort', 'langconfig'))
            . ($encadrant ? '<br>' . s(fullname($encadrant)) : '');
    }
    $animaltext = '—';
    if ($animal) {
        $animaltext = userdate($animal->datevalidation, get_string('strftimedatefullshort', 'langconfig'))
            . '<br>' . s($animal->prenomvalidateur . ' ' . $animal->nomvalidateur);
        // Tracé de signature du validateur (§9.1 « date et signature »), passé à TCPDF en
        // données brutes via le préfixe « @ » plutôt qu'en URL.
        if (asv_valanimal::signature_valide((string) $animal->signature)) {
            $png = substr($animal->signature, strlen('data:image/png;base64,'));
            $animaltext .= '<br><img src="@' . $png . '" height="28">';
        }
    }

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
