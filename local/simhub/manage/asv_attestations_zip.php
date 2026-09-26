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
 * Téléchargement groupé (ZIP) des attestations de certification ASV pour tous les
 * étudiants éligibles à un niveau (§9.4), au lieu de les télécharger un par un depuis
 * manage/asv_attestations.php.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\asv_acte;
use local_simhub\local\asv_certification_helper;
use local_simhub\local\pdf_helper;
use local_simhub\local\badge_helper;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageasv', $context);

$envcode = '';
$niveau = optional_param('niveau', 'A3', PARAM_ALPHANUM);

$eligibles = asv_certification_helper::get_etudiants_eligibles($niveau, $envcode);
if (empty($eligibles)) {
    redirect(
        new moodle_url('/local/simhub/manage/asv_attestations.php', ['envcode' => $envcode, 'niveau' => $niveau]),
        get_string('asv_aucun_eligible', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_INFO
    );
}

$actes = asv_certification_helper::get_actes_requis($envcode, $niveau);
$badgeid = (int) (get_config('local_simhub', 'badgeasv' . strtolower($niveau)) ?: 0);

$zippath = tempnam(make_temp_directory('local_simhub'), 'asv_attestations_');
$zip = new ZipArchive();
$zip->open($zippath, ZipArchive::OVERWRITE);

foreach ($eligibles as $userid) {
    $user = \core_user::get_user($userid);
    if (!$user) {
        continue;
    }

    // Même certification qu'à l'unité (asv/attestation_pdf.php) : badge délivré (§13) et
    // document identique, pour que les deux chemins de génération restent cohérents.
    badge_helper::delivrer($badgeid ?: null, $userid);

    $pdf = pdf_helper::construire_attestation_asv($user, $niveau, $actes);
    $contenu = $pdf->Output('simhub_certification_' . $niveau . '_' . $userid . '.pdf', 'S');
    $zip->addFromString(
        'certification_' . $niveau . '_' . preg_replace('/[^a-zA-Z0-9_-]/', '_', fullname($user)) . '_' . $userid . '.pdf',
        $contenu,
    );
}

$zip->close();

send_temp_file($zippath, 'simhub_certifications_' . $niveau . '.zip');
