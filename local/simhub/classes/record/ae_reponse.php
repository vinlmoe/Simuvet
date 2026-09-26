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
 * Réponse étudiante à un critère d'auto-évaluation, pour une session donnée (§5.6, §7.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Réponse étudiante à un critère d'auto-évaluation, pour une session donnée (§5.6, §7.2).
 */
class ae_reponse {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ae_reponse';

    /** @var string Niveau d'auto-évaluation : reussi. */
    const NIVEAU_REUSSI = 'reussi';
    /** @var string Niveau d'auto-évaluation : a_consolider. */
    const NIVEAU_A_CONSOLIDER = 'a_consolider';
    /** @var string Niveau d'auto-évaluation : a_reprendre. */
    const NIVEAU_A_REPRENDRE = 'a_reprendre';

    /**
     * Enregistre (ou remplace) la réponse d'un étudiant à un critère pour une session.
     *
     * @param int $sessionid
     * @param int $critereid
     * @param string $niveau reussi|a_consolider|a_reprendre.
     * @return int
     */
    public static function repondre(int $sessionid, int $critereid, string $niveau): int {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['sessionid' => $sessionid, 'critereid' => $critereid]);
        if ($existing) {
            $existing->niveau = $niveau;
            $DB->update_record(self::TABLE, $existing);
            return $existing->id;
        }

        return $DB->insert_record(self::TABLE, (object) [
            'sessionid' => $sessionid,
            'critereid' => $critereid,
            'niveau' => $niveau,
            'timecreated' => time(),
        ]);
    }

    /**
     * Réponses d'une session, par critère.
     *
     * @param int $sessionid
     * @return \stdClass[]
     */
    public static function get_pour_session(int $sessionid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['sessionid' => $sessionid]);
    }

    /**
     * Critères marqués "à reprendre" ou "à consolider" pour une session (§5.6, aide à identifier
     * les ateliers à refaire ou consolider, §7.2).
     *
     * @param int $sessionid
     * @return \stdClass[]
     */
    public static function get_a_retravailler(int $sessionid): array {
        global $DB;

        [$insql, $params] = $DB->get_in_or_equal([self::NIVEAU_A_CONSOLIDER, self::NIVEAU_A_REPRENDRE]);
        $params = array_merge([$sessionid], $params);

        return $DB->get_records_select(self::TABLE, "sessionid = ? AND niveau $insql", $params);
    }
}
