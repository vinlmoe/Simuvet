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
 * Historique des indisponibilités d'un atelier (§6.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Historique des indisponibilités d'un atelier (§6.1).
 *
 * Table volontairement plate (commentaire, référent, échéance), sans
 * ticketing complet (§14) : classe légère par $DB direct plutôt que
 * core\persistent, la table n'ayant pas de timemodified/usermodified.
 */
class indispo {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_indispo';

    /**
     * Ouvre une nouvelle indisponibilité pour un atelier.
     *
     * @param int $atelierid
     * @param string $commentaire
     * @param int|null $referentuserid
     * @param int|null $echeanceprevue Timestamp, si connue.
     * @return int Id de l'enregistrement créé.
     */
    public static function ouvrir(
        int $atelierid,
        string $commentaire,
        ?int $referentuserid = null,
        ?int $echeanceprevue = null
    ): int {
        global $DB;

        $now = time();
        return $DB->insert_record(self::TABLE, (object) [
            'atelierid' => $atelierid,
            'commentaire' => $commentaire,
            'referentuserid' => $referentuserid,
            'datemaj' => $now,
            'echeanceprevue' => $echeanceprevue,
            'cloturee' => 0,
            'timecreated' => $now,
        ]);
    }

    /**
     * Clôture l'indisponibilité en cours d'un atelier, s'il y en a une.
     *
     * @param int $atelierid
     * @return void
     */
    public static function cloturer(int $atelierid): void {
        global $DB;

        $DB->set_field(self::TABLE, 'cloturee', 1, ['atelierid' => $atelierid, 'cloturee' => 0]);
    }

    /**
     * Indisponibilité en cours (non clôturée) pour un atelier, s'il y en a une.
     *
     * @param int $atelierid
     * @return \stdClass|false
     */
    public static function get_en_cours(int $atelierid) {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            ['atelierid' => $atelierid, 'cloturee' => 0],
            'datemaj DESC',
            '*',
            0,
            1
        );
        return $records ? reset($records) : false;
    }

    /**
     * Historique complet des indisponibilités d'un atelier, la plus récente d'abord.
     *
     * @param int $atelierid
     * @return \stdClass[]
     */
    public static function get_historique(int $atelierid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['atelierid' => $atelierid], 'datemaj DESC');
    }

    /**
     * Met à jour le motif, le référent et l'échéance de l'indisponibilité en cours.
     *
     * @param int $atelierid
     * @param string $commentaire
     * @param int|null $referentuserid
     * @param int|null $echeanceprevue
     * @return void
     */
    public static function mettre_a_jour(
        int $atelierid,
        string $commentaire,
        ?int $referentuserid,
        ?int $echeanceprevue
    ): void {
        global $DB;

        $encours = self::get_en_cours($atelierid);
        if (!$encours) {
            self::ouvrir($atelierid, $commentaire, $referentuserid, $echeanceprevue);
            return;
        }
        $encours->commentaire = $commentaire;
        $encours->referentuserid = $referentuserid;
        $encours->echeanceprevue = $echeanceprevue;
        $encours->datemaj = time();
        $DB->update_record(self::TABLE, $encours);
    }
}
