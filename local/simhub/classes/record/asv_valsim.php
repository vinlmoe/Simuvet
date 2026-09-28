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
 * Validation ASV en simulation par un formateur/encadrant Moodle (§9.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Validation ASV en simulation par un formateur/encadrant Moodle (§9.2).
 */
class asv_valsim {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_asv_valsim';

    /** @var string Statut : valide. */
    const STATUT_VALIDE = 'valide';
    /** @var string Statut : non_valide. */
    const STATUT_NON_VALIDE = 'non_valide';
    /** @var string Statut : annule. */
    const STATUT_ANNULE = 'annule';

    /**
     * Enregistre une validation ASV en simulation.
     *
     * @param int $userid Étudiant.
     * @param int $acteid
     * @param int $validateuruserid
     * @param array $extra atelierid, sessionid, statut (par défaut "valide").
     * @return int
     */
    public static function valider(int $userid, int $acteid, int $validateuruserid, array $extra = []): int {
        global $DB;

        if ($userid == $validateuruserid) {
            throw new \moodle_exception('asv_autovalidation_interdite', 'local_simhub');
        }
        $now = time();
        $record = (object) array_merge([
            'userid' => $userid,
            'acteid' => $acteid,
            'atelierid' => null,
            'sessionid' => null,
            'datevalidation' => $now,
            'validateuruserid' => $validateuruserid,
            'statut' => self::STATUT_VALIDE,
            'timecreated' => $now,
        ], $extra);

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Validations en simulation d'un étudiant, pour le pilotage du parcours ASV (§9.4).
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function get_pour_etudiant(int $userid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['userid' => $userid, 'statut' => self::STATUT_VALIDE]);
    }

    /**
     * Actes (ids) déjà validés en simulation par un étudiant.
     *
     * @param int $userid
     * @return int[]
     */
    public static function get_actes_valides(int $userid): array {
        global $DB;

        return array_values(array_map('intval', $DB->get_records_menu(
            self::TABLE,
            ['userid' => $userid, 'statut' => self::STATUT_VALIDE],
            '',
            'id, acteid'
        )));
    }

    /**
     * Toutes les décisions d'un encadrant pour un étudiant (validées, refusées, annulées),
     * les plus récentes d'abord : l'historique complet reste consultable.
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function get_historique(int $userid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['userid' => $userid], 'datevalidation DESC, id DESC');
    }

    /**
     * Annule une validation saisie par erreur : l'enregistrement est conservé pour la
     * traçabilité, mais ne compte plus dans le livret ni dans la certification.
     *
     * @param int $id
     * @param string $motif
     * @return void
     */
    public static function annuler(int $id, string $motif): void {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $record->statut = self::STATUT_ANNULE;
        $record->commentaire = trim(($record->commentaire ?? '') . "\n" . $motif);
        $DB->update_record(self::TABLE, $record);
    }
}
