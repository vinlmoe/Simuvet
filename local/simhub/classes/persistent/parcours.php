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
 * Parcours pédagogique (§8).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

use local_simhub\record\parc_atelier;

/**
 * Parcours pédagogique (§8).
 */
class parcours extends \core\persistent {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_parcours';

    /**
     * Propriétés persistées.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'nom' => ['type' => PARAM_TEXT],
            'description' => ['type' => PARAM_RAW, 'default' => '', 'null' => NULL_ALLOWED],
            // PARAM_ALPHA n'autorise pas le « _ » présent dans lie_uc/lie_annee : core\persistent
            // nettoie la valeur et la compare à l'original lors de la validation, ce qui aurait
            // rejeté tout parcours créé avec l'un de ces deux types (invalid_persistent_exception).
            'type' => [
                'type' => PARAM_ALPHANUMEXT,
                'choices' => ['recommande', 'obligatoire', 'lie_uc', 'lie_annee', 'certifiant', 'asv'],
            ],
            'courseid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'anneeetude' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'cohortid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'envcode' => ['type' => PARAM_ALPHANUMEXT],
            'badgeid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'cmid' => ['type' => PARAM_INT, 'default' => null, 'null' => NULL_ALLOWED],
        ];
    }

    /**
     * Ateliers du parcours dans leur ordre, avec caractère obligatoire/échéance (§8).
     *
     * @return array Enregistrements de local_simhub_parc_atelier, triés par ordre.
     */
    public function get_ateliers(): array {
        global $DB;
        return $DB->get_records(
            parc_atelier::TABLE,
            ['parcoursid' => $this->get('id')],
            'ordre ASC'
        );
    }
}
