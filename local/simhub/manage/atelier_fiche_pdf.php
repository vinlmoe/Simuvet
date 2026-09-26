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
 * Export PDF imprimable d'une fiche atelier (§6, §12.3) : identification, localisation,
 * statut, et liste des ressources associées (titres uniquement — les fichiers eux-mêmes
 * restent accessibles depuis la fiche numérique, ce PDF sert de pense-bête imprimable).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;
use local_simhub\local\pdf_helper;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$id = required_param('id', PARAM_INT);
$atelier = new atelier($id);

$pdf = new pdf();
$pdf->SetCreator('SimHub');
$pdf->SetTitle(s($atelier->get('nomcourt')));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
pdf_helper::ajouter_entete($pdf);

$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 10, '#' . $atelier->get('numero') . ' — ' . $atelier->get('nomcourt'), 0, 1);

$pdf->SetFont('helvetica', '', 11);
if ($atelier->get('descriptioncourte')) {
    $pdf->MultiCell(0, 6, $atelier->get('descriptioncourte'), 0, 'L');
    $pdf->Ln(2);
}

$html = '<table cellpadding="3">';
$rows = [
    [get_string('champ_discipline', 'local_simhub'), $atelier->get('discipline')],
    [get_string('champ_espece', 'local_simhub'), $atelier->get('espece')],
    [get_string('champ_niveaudifficulte', 'local_simhub'), $atelier->get('niveaudifficulte')],
    [get_string('champ_dureeindicative', 'local_simhub'), $atelier->get('dureeindicative')],
    [get_string('champ_statut', 'local_simhub'), get_string('statut_' . $atelier->get('statut'), 'local_simhub')],
    [get_string('champ_salle', 'local_simhub'), $atelier->get('salle')],
    [get_string('champ_zone', 'local_simhub'), $atelier->get('zone')],
    [get_string('champ_codeposte', 'local_simhub'), $atelier->get('codeposte')],
];
foreach ($rows as [$label, $value]) {
    $html .= '<tr><td width="35%"><b>' . s($label) . '</b></td><td>' . s((string) $value) . '</td></tr>';
}
$html .= '</table>';

$pdf->writeHTML($html, true, false, true, false, '');
$pdf->Ln(4);

$ressources = ressource::get_pour_etudiant($id);
if (!empty($ressources)) {
    $pdf->SetFont('helvetica', 'B', 12);
    $pdf->Cell(0, 8, get_string('bouton_ressources', 'local_simhub'), 0, 1);
    $pdf->SetFont('helvetica', '', 11);
    foreach ($ressources as $r) {
        $pdf->Cell(0, 6, '- ' . $r->get('titre'), 0, 1);
    }
}

$pdf->Output('simhub_atelier_' . $atelier->get('numero') . '.pdf', 'D');
