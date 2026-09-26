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
 * Auto-bilan final libre (trois champs) associé à une session (§5.6) :
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Auto-bilan final libre (trois champs) associé à une session (§5.6) :
 * point le mieux maîtrisé, point à retravailler, point d'attention pour le prochain essai.
 */
class ae_bilan {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ae_bilan';

    /**
     * Enregistre (ou remplace) l'auto-bilan d'une session.
     *
     * @param int $sessionid
     * @param string $pointmaitrise
     * @param string $pointaretravailler
     * @param string $pointattention
     * @return int
     */
    public static function enregistrer(
        int $sessionid,
        string $pointmaitrise,
        string $pointaretravailler,
        string $pointattention
    ): int {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['sessionid' => $sessionid]);
        $record = (object) [
            'sessionid' => $sessionid,
            'pointmaitrise' => $pointmaitrise,
            'pointaretravailler' => $pointaretravailler,
            'pointattention' => $pointattention,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return $existing->id;
        }

        $record->timecreated = time();
        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Auto-bilan d'une session, s'il existe.
     *
     * @param int $sessionid
     * @return \stdClass|false
     */
    public static function get_pour_session(int $sessionid) {
        global $DB;

        return $DB->get_record(self::TABLE, ['sessionid' => $sessionid]);
    }
}
