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
 * Critère observable d'une rubrique d'auto-évaluation guidée (§5.6, §7.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Critère observable d'une rubrique d'auto-évaluation guidée (§5.6, §7.2).
 */
class ae_critere {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ae_critere';

    /**
     * Ajoute un critère à une rubrique.
     *
     * @param int $rubriqueid
     * @param string $libelle
     * @param int $ordre
     * @return int
     */
    public static function ajouter(int $rubriqueid, string $libelle, int $ordre = 0): int {
        global $DB;

        return $DB->insert_record(self::TABLE, (object) [
            'rubriqueid' => $rubriqueid,
            'libelle' => $libelle,
            'ordre' => $ordre,
        ]);
    }

    /**
     * Critères d'une rubrique, dans l'ordre d'affichage.
     *
     * @param int $rubriqueid
     * @return \stdClass[]
     */
    public static function get_pour_rubrique(int $rubriqueid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['rubriqueid' => $rubriqueid], 'ordre ASC');
    }

    /**
     * Supprime un critère (les réponses déjà enregistrées dessus, historiques, sont
     * conservées : seul le référentiel de la grille change).
     *
     * @param int $id
     * @return void
     */
    public static function supprimer(int $id): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
