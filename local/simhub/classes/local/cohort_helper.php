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
 * Sélection directe d'une cohorte Moodle existante ("groupe" au sens du cahier des.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Sélection directe d'une cohorte Moodle existante ("groupe" au sens du cahier des
 * charges), pour recommander un atelier ou un parcours à ses membres (§5.1, §6, §8).
 *
 * Le gestionnaire choisit la cohorte dans une liste réelle plutôt que de saisir un
 * identifiant numérique ou de laisser SimHub déduire un groupe par inférence : la
 * correspondance avec l'étudiant se fait ensuite par appartenance effective
 * (table Moodle cohort_members), sans reflexion sur un nom de groupe.
 */
class cohort_helper {
    /**
     * Options {cohortid => libellé} pour un élément de formulaire select, triées par nom.
     *
     * @param bool $withempty Ajoute une option vide en tête.
     * @return array
     */
    public static function get_options(bool $withempty = true): array {
        global $DB;

        $options = $withempty ? ['' => ''] : [];
        $cohorts = $DB->get_records('cohort', null, 'name ASC', 'id, name, idnumber');
        foreach ($cohorts as $cohort) {
            $label = $cohort->name;
            if (!empty($cohort->idnumber)) {
                $label .= ' (' . $cohort->idnumber . ')';
            }
            $options[$cohort->id] = $label;
        }
        return $options;
    }

    /**
     * Cohortes (ids) dont l'utilisateur est membre.
     *
     * @param int $userid
     * @return int[]
     */
    public static function get_cohortes_utilisateur(int $userid): array {
        global $DB;

        return array_values($DB->get_records_menu(
            'cohort_members',
            ['userid' => $userid],
            '',
            'id, cohortid'
        ));
    }
}
