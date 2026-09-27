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
 * Ressource pédagogique associée à un atelier (§6.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

/**
 * Ressource pédagogique associée à un atelier (§6.2).
 */
class ressource extends \core\persistent {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ressource';

    /** @var string Visibilité : etudiant. */
    const VISIBILITE_ETUDIANT = 'etudiant';
    /** @var string Visibilité : interne. */
    const VISIBILITE_INTERNE = 'interne';

    /** @var string Type de ressource : source_editable. */
    const TYPE_SOURCE_EDITABLE = 'source_editable';

    /**
     * Propriétés persistées.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'atelierid' => ['type' => PARAM_INT],
            'type' => [
                'type' => PARAM_ALPHANUMEXT,
                'choices' => [
                    'fiche_methode', 'pdf_etudiant', 'video', 'consignes',
                    'criteres_reussite', 'erreurs_frequentes', 'liens_utiles',
                    'complementaire', self::TYPE_SOURCE_EDITABLE,
                ],
            ],
            'visibilite' => [
                'type' => PARAM_ALPHA,
                'default' => self::VISIBILITE_ETUDIANT,
                'choices' => [self::VISIBILITE_ETUDIANT, self::VISIBILITE_INTERNE],
            ],
            'titre' => ['type' => PARAM_TEXT],
            'url' => ['type' => PARAM_RAW, 'default' => '', 'null' => NULL_ALLOWED],
            'fileitemid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'ordre' => ['type' => PARAM_INT, 'default' => 0],
        ];
    }

    /**
     * Ressources visibles côté étudiant pour un atelier (jamais les sources éditables, §6.2).
     *
     * @param int $atelierid
     * @return ressource[]
     */
    public static function get_pour_etudiant(int $atelierid): array {
        return array_values(array_filter(
            self::get_records(['atelierid' => $atelierid, 'visibilite' => self::VISIBILITE_ETUDIANT], 'ordre'),
            fn($r) => $r->get('type') !== self::TYPE_SOURCE_EDITABLE
        ));
    }

    /**
     * Une source éditable reste interne quelle que soit la visibilité choisie (§6.2).
     *
     * @return void
     */
    protected function before_validate() {
        if ($this->get('type') === self::TYPE_SOURCE_EDITABLE) {
            $this->set('visibilite', self::VISIBILITE_INTERNE);
        }
    }
}
