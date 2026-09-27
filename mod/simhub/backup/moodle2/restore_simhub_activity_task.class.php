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
 * Restauration d'une activité SimHub.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simhub/backup/moodle2/restore_simhub_stepslib.php');

/**
 * Restauration d'une activité SimHub.
 */
class restore_simhub_activity_task extends restore_activity_task {
    /**
     * Aucun réglage propre à l'activité.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Étapes de restauration.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new restore_simhub_activity_structure_step('simhub_structure', 'simhub.xml'));
    }

    /**
     * Les séances des étudiants sont dans SimHub, pas dans le cours : une fois l'élément
     * d'évaluation restauré, on recalcule les notes des inscrits du nouveau cours.
     *
     * @return void
     */
    public function after_restore() {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/simhub/lib.php');

        $simhub = $DB->get_record('simhub', ['id' => $this->get_activityid()]);
        if ($simhub) {
            simhub_update_grades($simhub);
        }
    }

    /**
     * Contenus dont les liens sont à décoder.
     *
     * @return restore_decode_content[]
     */
    public static function define_decode_contents() {
        return [new restore_decode_content('simhub', ['intro'], 'simhub')];
    }

    /**
     * Règles de décodage des liens.
     *
     * @return restore_decode_rule[]
     */
    public static function define_decode_rules() {
        return [
            new restore_decode_rule('SIMHUBVIEWBYID', '/mod/simhub/view.php?id=$1', 'course_module'),
            new restore_decode_rule('SIMHUBINDEX', '/mod/simhub/index.php?id=$1', 'course'),
        ];
    }

    /**
     * Règles de restauration des journaux.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules() {
        return [];
    }

    /**
     * Règles de restauration des journaux du cours.
     *
     * @return restore_log_rule[]
     */
    public static function define_restore_log_rules_for_course() {
        return [];
    }
}
