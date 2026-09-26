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
 * Contextes Moodle utilisés par SimHub.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Contexte des droits transversaux et contexte de stockage des fichiers.
 */
class contexte {
    /**
     * Contexte où sont vérifiées les capacités transversales : la catégorie SimHub si elle
     * est paramétrée, sinon le système. Un rôle attribué au système reste valable dans la
     * catégorie, qui en hérite.
     *
     * @return \context
     */
    public static function racine(): \context {
        $categoryid = (int) get_config('local_simhub', 'categoryid');
        if ($categoryid) {
            $context = \context_coursecat::instance($categoryid, IGNORE_MISSING);
            if ($context) {
                return $context;
            }
        }
        return \context_system::instance();
    }

    /**
     * Les fichiers restent au contexte système, où ils ont toujours été enregistrés : changer
     * de catégorie SimHub ne doit pas rendre les ressources et plans existants introuvables.
     *
     * @return \context_system
     */
    public static function fichiers(): \context_system {
        return \context_system::instance();
    }
}
