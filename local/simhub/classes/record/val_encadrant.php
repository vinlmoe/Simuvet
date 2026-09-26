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
 * Validation générique d'une session par un encadrant Moodle (hors ASV, §7.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Validation générique d'une session par un encadrant Moodle (hors ASV, §7.1).
 */
class val_encadrant {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_val_encadrant';

    /** @var string Statut : valide. */
    const STATUT_VALIDE = 'valide';
    /** @var string Statut : refuse. */
    const STATUT_REFUSE = 'refuse';

    /**
     * Enregistre la validation (ou le refus) d'une session par un encadrant.
     *
     * @param int $sessionid
     * @param int $validateuruserid
     * @param string $statut valide|refuse.
     * @param string $commentaire
     * @return int
     */
    public static function valider(
        int $sessionid,
        int $validateuruserid,
        string $statut,
        string $commentaire = ''
    ): int {
        global $DB;

        $now = time();
        return $DB->insert_record(self::TABLE, (object) [
            'sessionid' => $sessionid,
            'validateuruserid' => $validateuruserid,
            'datevalidation' => $now,
            'statut' => $statut,
            'commentaire' => $commentaire,
            'timecreated' => $now,
        ]);
    }

    /**
     * Validations d'une session, la plus récente d'abord.
     *
     * @param int $sessionid
     * @return \stdClass[]
     */
    public static function get_pour_session(int $sessionid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['sessionid' => $sessionid], 'datevalidation DESC');
    }
}
