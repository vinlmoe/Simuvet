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
 * Rubrique (grande étape du geste) d'un modèle d'auto-évaluation guidée (§5.6, §7.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Rubrique (grande étape du geste) d'un modèle d'auto-évaluation guidée (§5.6, §7.2).
 */
class ae_rubrique {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_ae_rubrique';

    /**
     * Ajoute une rubrique à un modèle.
     *
     * @param int $modeleid
     * @param string $titre
     * @param int $ordre
     * @param bool $estrubriquerisques Rubrique dédiée aux erreurs/risques (§5.6).
     * @return int
     */
    public static function ajouter(
        int $modeleid,
        string $titre,
        int $ordre = 0,
        bool $estrubriquerisques = false
    ): int {
        global $DB;

        return $DB->insert_record(self::TABLE, (object) [
            'modeleid' => $modeleid,
            'titre' => $titre,
            'ordre' => $ordre,
            'estrubriquerisques' => $estrubriquerisques ? 1 : 0,
        ]);
    }

    /**
     * Rubriques d'un modèle, dans l'ordre d'affichage.
     *
     * @param int $modeleid
     * @return \stdClass[]
     */
    public static function get_pour_modele(int $modeleid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['modeleid' => $modeleid], 'ordre ASC');
    }

    /**
     * Supprime une rubrique et ses critères (les réponses déjà enregistrées sur ces
     * critères, historiques, sont conservées : seul le référentiel de la grille change).
     *
     * @param int $id
     * @return void
     */
    public static function supprimer(int $id): void {
        global $DB;

        $DB->delete_records(ae_critere::TABLE, ['rubriqueid' => $id]);
        $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
