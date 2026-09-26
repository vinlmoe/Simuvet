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
 * Met à jour les notes des UC quand l'état d'un atelier change pour un étudiant.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simhub;

/**
 * Met à jour les notes des UC quand l'état d'un atelier change pour un étudiant.
 */
class observer {
    /**
     * Séance terminée ou validée : toutes les UC contenant l'atelier, pour cet étudiant.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function seance_modifiee(\core\event\base $event): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/simhub/lib.php');

        $session = $DB->get_record('local_simhub_session', ['id' => $event->objectid], 'id, userid, atelierid');
        if (!$session) {
            return;
        }
        $instances = $DB->get_records_sql(
            "SELECT s.*
               FROM {simhub} s
              WHERE EXISTS (SELECT 1
                              FROM {local_simhub_parc_atelier} pa
                             WHERE pa.parcoursid = s.parcoursid AND pa.atelierid = ?)",
            [$session->atelierid]
        );
        foreach ($instances as $simhub) {
            simhub_update_grades($simhub, (int) $session->userid);
        }
    }

    /**
     * Composition du parcours modifiée : tous les étudiants de l'UC.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function parcours_modifie(\core\event\base $event): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/simhub/lib.php');

        foreach ($DB->get_records('simhub', ['parcoursid' => $event->objectid]) as $simhub) {
            simhub_update_grades($simhub);
        }
    }
}
