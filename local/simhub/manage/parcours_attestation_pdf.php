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
 * Attestation de fin de parcours (§8, §12.3) : générée uniquement si l'étudiant a réalisé
 * ou validé la totalité des ateliers du parcours (avancement à 100%, §8.1) — sinon la page
 * affiche l'avancement actuel plutôt qu'un document à moitié rempli.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\local\pdf_helper;
use local_simhub\local\badge_helper;

require_login();

$context = \local_simhub\local\contexte::racine();

$parcoursid = required_param('parcoursid', PARAM_INT);
$userid = optional_param('userid', $USER->id, PARAM_INT);

$parcours = new parcours($parcoursid);
if ($userid != $USER->id) {
    if (
        !array_key_exists($userid, \local_simhub\local\droits::etudiants_du_parcours($parcours))
            || !\local_simhub\local\droits::peut_suivre_parcours($parcours)
    ) {
        throw new required_capability_exception(
            \local_simhub\local\contexte::racine(),
            'local/simhub:viewprogression',
            'nopermissions',
            '',
        );
    }
} else {
    require_capability('local/simhub:view', $context);
}
$user = \core_user::get_user($userid, '*', MUST_EXIST);

$composition = $parcours->get_ateliers();
$atelierids = array_column($composition, 'atelierid');

$noms = [];
foreach ($atelierids as $aid) {
    $noms[$aid] = (new atelier($aid))->get('nomcourt');
}
// L'attestation liste les ateliers effectivement réalisés parmi ceux du parcours.
$atelierids = array_values(array_filter(
    $atelierids,
    fn($aid) => in_array(\local_simhub\local\parcours_helper::statut_atelier($userid, (int) $aid), ['realise', 'valide'], true)
));

['realises' => $realises, 'total' => $total, 'pct' => $pct] = \local_simhub\local\parcours_helper::progression($parcours, $userid);

if ($total === 0 || $pct < 100) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url(
        '/local/simhub/manage/parcours_attestation_pdf.php',
        ['parcoursid' => $parcoursid, 'userid' => $userid]
    ));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(s($parcours->get('nom')));
    $PAGE->set_heading(s($parcours->get('nom')));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('attestation_incomplete', 'local_simhub', (object) [
            'nom' => fullname($user), 'pct' => $pct, 'parcours' => s($parcours->get('nom')),
            'realises' => $realises, 'total' => $total,
        ]),
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
$pdf->SetTitle(get_string('attestation_titre', 'local_simhub') . ' — ' . $parcours->get('nom') . ' — ' . fullname($user));
$pdf->setPrintHeader(false);
$pdf->setPrintFooter(false);
$pdf->AddPage();
pdf_helper::ajouter_entete($pdf);

$pdf->Ln(15);
$pdf->SetFont('helvetica', 'B', 20);
$pdf->Cell(0, 12, get_string('attestation_titre', 'local_simhub'), 0, 1, 'C');
$pdf->Ln(6);

$pdf->SetFont('helvetica', '', 12);
$pdf->writeHTML(
    '<p>' . get_string('pdf_certifie_que', 'local_simhub', pdf_helper::get_etablissement_nom()) . '</p>'
    . '<p style="text-align:center;font-size:15pt;"><b>' . s(fullname($user)) . '</b></p>'
    . '<p>' . get_string('attestation_parcours_realise', 'local_simhub', s($parcours->get('nom'))) . '</p>',
    true,
    false,
    true,
    false,
    ''
);

$html = '<table border="1" cellpadding="4"><tr style="font-weight:bold;"><th>' . get_string(
    'atelier',
    'local_simhub',
) . '</th></tr>';
foreach ($atelierids as $aid) {
    $html .= '<tr><td>' . s($noms[$aid]) . '</td></tr>';
}
$html .= '</table>';
$pdf->writeHTML($html, true, false, true, false, '');

$pdf->Ln(10);
$pdf->Cell(0, 6, get_string('pdf_delivree_le', 'local_simhub', userdate(time(), get_string('strftimedate', 'langconfig'))), 0, 1);

$pdf->Output('simhub_attestation_parcours_' . $parcoursid . '_' . $userid . '.pdf', 'D');
