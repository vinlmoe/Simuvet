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
 * Désinstallation de l'activité.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Supprime les parcours d'UC créés par l'activité et les rattachements qui en découlent ;
 * les ateliers, séances et parcours transversaux de SimHub restent intacts.
 *
 * @return bool
 */
function xmldb_simhub_uninstall() {
    global $DB;

    if (!$DB->get_manager()->table_exists('local_simhub_parcours')) {
        return true;
    }
    foreach (\local_simhub\persistent\parcours::get_records_select('cmid > 0') as $parcours) {
        foreach ($parcours->get_ateliers() as $lien) {
            \local_simhub\local\parcours_helper::retirer_atelier($parcours, (int) $lien->atelierid, false);
        }
        $parcours->delete();
    }
    return true;
}
