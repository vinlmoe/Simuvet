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
 * Modèle d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : un modèle par atelier en V1.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

/**
 * Modèle d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : un modèle par atelier en V1
 * (contrainte d'unicité sur atelierid, cf. db/install.xml), composé de rubriques
 * (classes\record\ae_rubrique) elles-mêmes composées de critères
 * (classes\record\ae_critere).
 */
class ae_modele extends \core\persistent {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ae_modele';

    /**
     * Propriétés persistées.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'atelierid' => ['type' => PARAM_INT],
            'titre' => ['type' => PARAM_TEXT],
            'actif' => ['type' => PARAM_INT, 'default' => 1],
        ];
    }

    /**
     * Modèle d'un atelier, s'il existe (actif ou non).
     *
     * @param int $atelierid
     * @return ae_modele|false
     */
    public static function get_pour_atelier(int $atelierid) {
        return self::get_record(['atelierid' => $atelierid]);
    }
}
