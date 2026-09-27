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
 * Règle d'achèvement de l'activité SimHub.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_simhub\completion;

use core_completion\activity_custom_completion;

/**
 * Achevée quand l'étudiant a réalisé tous les ateliers requis de l'UC.
 *
 * L'achèvement de l'activité peut alors conditionner l'achèvement du cours et les badges
 * de cours (§10, §13).
 */
class custom_completion extends activity_custom_completion {
    /**
     * État de la règle pour l'étudiant.
     *
     * @param string $rule
     * @return int COMPLETION_COMPLETE ou COMPLETION_INCOMPLETE
     */
    public function get_state(string $rule): int {
        global $DB;

        $this->validate_rule($rule);
        $parcoursid = $DB->get_field('simhub', 'parcoursid', ['id' => $this->cm->instance]);
        $parcours = $parcoursid ? \local_simhub\persistent\parcours::get_record(['id' => $parcoursid]) : false;
        if (!$parcours) {
            return COMPLETION_INCOMPLETE;
        }
        $prog = \local_simhub\local\parcours_helper::progression($parcours, $this->userid);
        return $prog['total'] > 0 && $prog['pct'] >= 100 ? COMPLETION_COMPLETE : COMPLETION_INCOMPLETE;
    }

    /**
     * Règles définies par l'activité.
     *
     * @return string[]
     */
    public static function get_defined_custom_rules(): array {
        return ['completionparcours'];
    }

    /**
     * Descriptions des règles.
     *
     * @return array
     */
    public function get_custom_rule_descriptions(): array {
        return ['completionparcours' => get_string('completiondetail:parcours', 'simhub')];
    }

    /**
     * Ordre d'affichage des conditions.
     *
     * @return string[]
     */
    public function get_sort_order(): array {
        return ['completionview', 'completionparcours', 'completionusegrade', 'completionpassgrade'];
    }
}
