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
 * Jeton QR code associé à un atelier (§7) : relie l'objet physique à sa fiche numérique.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Jeton QR code associé à un atelier (§7) : relie l'objet physique à sa fiche numérique.
 *
 * Un seul enregistrement par atelier (contrainte d'unicité sur atelierid, cf.
 * db/install.xml) : régénérer le jeton met à jour la ligne existante plutôt que d'en créer
 * une nouvelle, pour respecter cette contrainte.
 */
class qrtoken {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_qrtoken';

    /**
     * Crée (ou renvoie) le jeton QR d'un atelier.
     *
     * @param int $atelierid
     * @return \stdClass
     */
    public static function get_ou_creer(int $atelierid): \stdClass {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['atelierid' => $atelierid]);
        if ($existing) {
            // Auto-réparation : une ligne restée à actif=0 (ex. suite à l'ancien bug de
            // régénération, qui violait la contrainte d'unicité sur atelierid avant
            // correction) rendrait le scan impossible sans qu'aucune UI ne l'indique.
            if (!$existing->actif) {
                $existing->actif = 1;
                $DB->update_record(self::TABLE, $existing);
            }
            return $existing;
        }

        $record = (object) [
            'atelierid' => $atelierid,
            'token' => \core\uuid::generate(),
            'actif' => 1,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Retrouve l'atelier associé à un jeton QR actif.
     *
     * @param string $token
     * @return \stdClass|false
     */
    public static function get_par_token(string $token) {
        global $DB;

        return $DB->get_record(self::TABLE, ['token' => $token, 'actif' => 1]);
    }

    /**
     * Régénère le jeton QR d'un atelier : met à jour la ligne existante (un seul
     * enregistrement par atelier), l'ancien QR imprimé devenant invalide.
     *
     * @param int $atelierid
     * @return \stdClass
     */
    public static function regenerer(int $atelierid): \stdClass {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['atelierid' => $atelierid]);
        if (!$existing) {
            return self::get_ou_creer($atelierid);
        }

        $existing->token = \core\uuid::generate();
        $existing->actif = 1;
        $DB->update_record(self::TABLE, $existing);
        return $existing;
    }
}
