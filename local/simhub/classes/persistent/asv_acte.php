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
 * Référentiel des actes vétérinaires délégables ASV (§9).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

/**
 * Référentiel des actes vétérinaires délégables ASV (§9).
 */
class asv_acte extends \core\persistent {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_asv_acte';

    /**
     * Propriétés persistées.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'code' => ['type' => PARAM_ALPHANUMEXT],
            'nom' => ['type' => PARAM_TEXT],
            'espece' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED],
            'niveau' => ['type' => PARAM_ALPHANUM, 'choices' => ['A1', 'A2', 'A3']],
            'ucid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'envcode' => ['type' => PARAM_ALPHANUMEXT],
            'actif' => ['type' => PARAM_INT, 'default' => 1],
        ];
    }

    /**
     * Référentiel actif d'un établissement, éventuellement filtré par niveau A1-A3 (§9.4).
     *
     * @param string $envcode
     * @param string|null $niveau
     * @return asv_acte[]
     */
    public static function get_referentiel(string $envcode, ?string $niveau = null): array {
        $params = ['actif' => 1];
        if ($envcode !== '') {
            $params['envcode'] = $envcode;
        }
        if ($niveau !== null) {
            $params['niveau'] = $niveau;
        }
        return self::get_records($params, 'niveau');
    }
}
