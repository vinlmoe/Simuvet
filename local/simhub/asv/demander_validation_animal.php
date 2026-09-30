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
 * L'étudiant génère un lien de validation animal vivant pour un acte déjà validé en
 * simulation (§9.3), à transmettre au vétérinaire/maître de stage qui réalisera la
 * validation via valider_animal.php, sans avoir besoin d'un compte Moodle.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$acteid = required_param('acteid', PARAM_INT);
$acte = new asv_acte($acteid);

$pageurl = new moodle_url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acteid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_demander_validation_animal', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// L'ordre du livret (§9.1) est imposé ici et pas seulement par l'affichage du lien : sans
// validation en simulation, aucune demande sur animal vivant ne peut être générée.
if (!in_array($acteid, \local_simhub\record\asv_valsim::get_actes_valides($USER->id))) {
    echo $OUTPUT->notification(get_string('asv_simulation_requise', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}

// Signature déjà recueillie (en contrôle ou acquise) : pas de nouveau lien, sans quoi un
// étudiant pourrait multiplier les signatures en attendant qu'une passe le contrôle.
if ($DB->record_exists_select(
    asv_valanimal::TABLE,
    'userid = :userid AND acteid = :acteid AND statut IN (:signe, :valide)',
    ['userid' => $USER->id, 'acteid' => $acteid, 'signe' => asv_valanimal::STATUT_SIGNE, 'valide' => asv_valanimal::STATUT_VALIDE]
)) {
    echo $OUTPUT->notification(get_string('asv_deja_signe', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}

// Une seule demande active par acte : revenir sur cette page réaffiche le même lien au lieu
// d'en générer un nouveau à chaque visite.
$demande = asv_valanimal::get_ou_creer_demande($USER->id, $acteid);
$lien = new moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $demande->token]);

echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));
echo html_writer::tag('p', get_string('asv_lien_valanimal', 'local_simhub') . ' :');
echo html_writer::tag('p', html_writer::link($lien, $lien->out(false)));
echo html_writer::tag('p', get_string(
    'asv_lien_expire_le',
    'local_simhub',
    userdate($demande->tokenexpire, get_string('strftimedatetimeshort', 'langconfig'))
));
echo html_writer::tag(
    'p',
    get_string('asv_transmettre_lien', 'local_simhub')
);

echo $OUTPUT->footer();
