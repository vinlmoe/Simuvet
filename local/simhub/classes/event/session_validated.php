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
 * Événement : un encadrant a validé ou refusé une séance d'atelier (§7.3, §11).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\event;

/**
 * Événement : un encadrant a validé ou refusé une séance d'atelier (§7.3, §11).
 */
class session_validated extends \core\event\base {
    /**
     * Initialisation de l'événement.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_simhub_session';
    }

    /**
     * Nom de l'événement.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_session_validated', 'local_simhub');
    }

    /**
     * Description de l'événement pour les journaux.
     *
     * @return string
     */
    public function get_description() {
        return "The user with id '{$this->userid}' reviewed the workshop session with id '{$this->objectid}' "
            . "of the user with id '{$this->relateduserid}'.";
    }

    /**
     * Page liée à l'événement.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/simhub/index.php');
    }
}
