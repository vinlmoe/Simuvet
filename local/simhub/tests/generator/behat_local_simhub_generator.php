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
 * Générateur Behat de SimHub.
 *
 * @package    local_simhub
 * @category   test
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Entités créables par l'étape « the following "local_simhub > …" exist ».
 */
class behat_local_simhub_generator extends behat_generator_base {
    /**
     * Entités créables.
     *
     * @return array
     */
    protected function get_creatable_entities(): array {
        return [
            'ateliers' => [
                'singular' => 'atelier',
                'datagenerator' => 'atelier',
                'required' => ['numero', 'nomcourt'],
            ],
            'uc ateliers' => [
                'singular' => 'uc atelier',
                'datagenerator' => 'uc_atelier',
                'required' => ['activity', 'atelier'],
            ],
            'sessions' => [
                'singular' => 'session',
                'datagenerator' => 'session',
                'required' => ['user', 'atelier'],
                'switchids' => ['user' => 'userid'],
            ],
        ];
    }
}
