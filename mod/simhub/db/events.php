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
 * Une séance appartient à l'étudiant et à l'atelier : toutes les UC qui contiennent
 * l'atelier recalculent leur note.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$observers = [
    ['eventname' => '\local_simhub\event\session_completed', 'callback' => '\mod_simhub\observer::seance_modifiee'],
    ['eventname' => '\local_simhub\event\session_validated', 'callback' => '\mod_simhub\observer::seance_modifiee'],
    ['eventname' => '\local_simhub\event\parcours_updated', 'callback' => '\mod_simhub\observer::parcours_modifie'],
];
