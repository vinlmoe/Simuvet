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
 * Libellés et options pour l'année d'étude (A1 à A5) du cursus vétérinaire, utilisés par.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Libellés et options pour l'année d'étude (A1 à A5) du cursus vétérinaire, utilisés par
 * les formulaires de rattachement/parcours (§6, §8). La détermination de "à qui" un
 * atelier est recommandé (§5.1) ne repose pas sur une déduction de l'année à partir du nom
 * des groupes Moodle : le gestionnaire sélectionne directement la ou les cohortes
 * concernées (voir record\rattachement et manage/rattachements.php), et la correspondance
 * avec l'étudiant se fait par appartenance réelle à la cohorte, sans reflexion sur le nom
 * du groupe.
 */
class annee_resolver {
    /** Années d'étude possibles, du cursus vétérinaire A1 à A5. */
    const ANNEES = [1, 2, 3, 4, 5];

    /**
     * Libellé affiché pour une année (A1, A2...).
     *
     * @param int $annee
     * @return string
     */
    public static function get_label(int $annee): string {
        return 'A' . $annee;
    }

    /**
     * Options {annee => libellé} pour un élément de formulaire select.
     *
     * @param bool $withempty Ajoute une option vide en tête.
     * @return array
     */
    public static function get_options(bool $withempty = true): array {
        $options = $withempty ? ['' => ''] : [];
        foreach (self::ANNEES as $annee) {
            $options[$annee] = self::get_label($annee);
        }
        return $options;
    }
}
