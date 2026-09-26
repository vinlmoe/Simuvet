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
 * Classe persistent pour la fiche atelier (§6 du cahier des charges).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

/**
 * Classe persistent pour la fiche atelier (§6 du cahier des charges).
 *
 * S'appuie sur l'API core\persistent de Moodle : gère automatiquement la validation et
 * timecreated/timemodified/usermodified. Le hook after_create() génère le jeton QR de
 * l'atelier (§7) dès sa création.
 */
class atelier extends \core\persistent {
    /** Table associée. */
    const TABLE = 'local_simhub_atelier';

    /** @var string Statut : actif. */
    const STATUT_ACTIF = 'actif';
    /** @var string Statut : non_utilise. */
    const STATUT_NON_UTILISE = 'non_utilise';
    /** @var string Statut : indisponible. */
    const STATUT_INDISPONIBLE = 'indisponible';
    /** @var string Statut : archive. */
    const STATUT_ARCHIVE = 'archive';

    /**
     * Définition des propriétés, alignée sur db/install.xml.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'numero' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'nomcourt' => [
                'type' => PARAM_TEXT,
            ],
            'nomlong' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'descriptioncourte' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'discipline' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'espece' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'niveaudifficulte' => [
                'type' => PARAM_ALPHA,
                'default' => '',
                'null' => NULL_ALLOWED,
                'choices' => ['', 'facile', 'intermediaire', 'avance'],
            ],
            'dureeindicative' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'statut' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUT_NON_UTILISE,
                'choices' => [
                    self::STATUT_ACTIF,
                    self::STATUT_NON_UTILISE,
                    self::STATUT_INDISPONIBLE,
                    self::STATUT_ARCHIVE,
                ],
            ],
            'envcode' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'salle' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'zone' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'codeposte' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'indicationtextuelle' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'planimageitemid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'planrepx' => [
                'type' => PARAM_FLOAT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'planrepy' => [
                'type' => PARAM_FLOAT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'referentuserid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'commentaireadmin' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Hook appelé après la création : génère le jeton QR de l'atelier (§7). Doit être un
     * hook "after" et non "before" : avant la création, l'id de l'atelier — dont le jeton
     * a besoin — n'est pas encore connu.
     */
    protected function after_create() {
        \local_simhub\record\qrtoken::get_ou_creer($this->get('id'));
    }
}
