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
 * Liaison N-N parcours / ateliers, avec ordre (§8).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Liaison N-N parcours / ateliers, avec ordre (§8).
 */
class parc_atelier {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_parc_atelier';

    /**
     * Ajoute un atelier à un parcours (ou met à jour son ordre/échéance s'il y est déjà).
     *
     * @param int $parcoursid
     * @param int $atelierid
     * @param int $ordre
     * @param bool $obligatoire
     * @param int|null $echeance
     * @return int Id de l'enregistrement.
     */
    public static function ajouter(
        int $parcoursid,
        int $atelierid,
        int $ordre = 0,
        bool $obligatoire = false,
        ?int $echeance = null
    ): int {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['parcoursid' => $parcoursid, 'atelierid' => $atelierid]);
        $record = (object) [
            'parcoursid' => $parcoursid,
            'atelierid' => $atelierid,
            'ordre' => $ordre,
            'obligatoire' => $obligatoire ? 1 : 0,
            'echeance' => $echeance,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return $existing->id;
        }

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Retire un atelier d'un parcours.
     *
     * @param int $parcoursid
     * @param int $atelierid
     * @return void
     */
    public static function retirer(int $parcoursid, int $atelierid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['parcoursid' => $parcoursid, 'atelierid' => $atelierid]);
    }

    /**
     * Parcours (ids) contenant un atelier donné.
     *
     * @param int $atelierid
     * @return int[]
     */
    public static function get_parcours_pour_atelier(int $atelierid): array {
        global $DB;

        return array_values($DB->get_records_menu(self::TABLE, ['atelierid' => $atelierid], '', 'id, parcoursid'));
    }
}
