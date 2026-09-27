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
 * Sauvegarde d'une activité SimHub.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/simhub/backup/moodle2/backup_simhub_stepslib.php');

/**
 * Sauvegarde d'une activité SimHub.
 */
class backup_simhub_activity_task extends backup_activity_task {
    /**
     * Aucun réglage propre à l'activité.
     *
     * @return void
     */
    protected function define_my_settings() {
    }

    /**
     * Étapes de sauvegarde.
     *
     * @return void
     */
    protected function define_my_steps() {
        $this->add_step(new backup_simhub_activity_structure_step('simhub_structure', 'simhub.xml'));
    }

    /**
     * Encode les liens vers l'activité pour la restauration.
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content) {
        global $CFG;

        $base = preg_quote($CFG->wwwroot, '/');
        $content = preg_replace("/(" . $base . "\/mod\/simhub\/index.php\?id\=)([0-9]+)/", '$@SIMHUBINDEX*$2@$', $content);
        return preg_replace("/(" . $base . "\/mod\/simhub\/view.php\?id\=)([0-9]+)/", '$@SIMHUBVIEWBYID*$2@$', $content);
    }
}
