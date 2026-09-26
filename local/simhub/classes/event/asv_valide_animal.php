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
 * Événement : un acte ASV a été validé sur animal vivant, potentiellement par un validateur.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\event;

/**
 * Événement : un acte ASV a été validé sur animal vivant, potentiellement par un validateur
 * externe sans compte Moodle (§9.3). Déclenché côté anonyme, donc userid peut être 0.
 */
class asv_valide_animal extends \core\event\base {
    /**
     * Initialisation de l'événement.
     *
     * @return void
     */
    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_simhub_asv_valanimal';
    }

    /**
     * Nom de l'événement.
     *
     * @return string
     */
    public static function get_name() {
        return get_string('event_asv_valide_animal', 'local_simhub');
    }

    /**
     * Description de l'événement pour les journaux.
     *
     * @return string
     */
    public function get_description() {
        return "The ASV procedure was validated on a live animal for the student with id "
            . "'{$this->relateduserid}' (record '{$this->objectid}').";
    }

    /**
     * Page liée à l'événement.
     *
     * @return \moodle_url
     */
    public function get_url() {
        return new \moodle_url('/local/simhub/asv/index.php');
    }
}
