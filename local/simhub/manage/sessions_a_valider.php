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
 * File d'attente des sessions démarrées sans code de séance valide (§7.3), à valider
 * manuellement par un encadrant — le filet de sécurité qui garantit que le contrôle
 * anti-faux-scan n'est jamais bloquant de façon absolue pour l'étudiant.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\record\val_encadrant;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:validatesession', $context);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'valider' || $action === 'refuser') {
    require_sesskey();
    // Une séance (bouton de ligne) ou une sélection (validation en masse).
    $sessionids = optional_param_array('sessionids', [], PARAM_INT);
    if (!$sessionids) {
        $sessionids = [optional_param('sessionid', 0, PARAM_INT)];
    }
    $sessionids = array_filter(array_unique($sessionids));
    $commentaire = trim(optional_param('commentaire', '', PARAM_TEXT));
    $pageurl = \local_simhub\local\navigation::url('/local/simhub/manage/sessions_a_valider.php');
    if (!$sessionids) {
        redirect($pageurl, get_string('selection_vide', 'local_simhub'), null, \core\output\notification::NOTIFY_WARNING);
    }

    $statut = $action === 'valider' ? val_encadrant::STATUT_VALIDE : val_encadrant::STATUT_REFUSE;
    $traitees = 0;
    foreach ($sessionids as $sessionid) {
        // Déjà traitée (double envoi, autre encadrant) : on n'empile pas une seconde décision.
        if (!$DB->record_exists('local_simhub_session', ['id' => $sessionid])
                || $DB->record_exists('local_simhub_val_encadrant', ['sessionid' => $sessionid])) {
            continue;
        }
        \local_simhub\local\parcours_helper::valider_seance($sessionid, $USER->id, $statut, $commentaire);
        $traitees++;
    }

    redirect($pageurl, get_string('sessions_traitees', 'local_simhub', $traitees), null,
        \core\output\notification::NOTIFY_SUCCESS);
}

$pageurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('sessions_a_valider', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$sessions = $DB->get_records_sql(
    "SELECT s.*
       FROM {local_simhub_session} s
      WHERE (s.controlepresence = 'non_verifie' OR s.dureesuspecte = 1)
        AND NOT EXISTS (SELECT 1 FROM {local_simhub_val_encadrant} v WHERE v.sessionid = s.id)
   ORDER BY s.timestart DESC"
);
echo \local_simhub\local\validation::seances($sessions, $pageurl);

echo $OUTPUT->footer();
