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
 * Structure sauvegardée : l'activité et la composition du parcours de l'UC.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Structure sauvegardée : l'activité et la composition du parcours de l'UC.
 *
 * Les ateliers appartiennent au référentiel de l'école, pas au cours : on sauvegarde leur
 * numéro invariant (§5.3) pour les retrouver à la restauration, pas les fiches elles-mêmes.
 * Les séances des étudiants restent dans SimHub et ne font pas partie du cours.
 */
class backup_simhub_activity_structure_step extends backup_activity_structure_step {
    /**
     * Arbre de sauvegarde.
     *
     * @return backup_nested_element
     */
    protected function define_structure() {
        $simhub = new backup_nested_element('simhub', ['id'], [
            'name', 'intro', 'introformat', 'grade', 'completionparcours', 'timecreated', 'timemodified',
        ]);
        $ateliers = new backup_nested_element('ateliers');
        $atelier = new backup_nested_element('atelier', ['id'], ['atelierid', 'numero', 'ordre', 'obligatoire', 'echeance']);

        $simhub->add_child($ateliers);
        $ateliers->add_child($atelier);

        $simhub->set_source_table('simhub', ['id' => backup::VAR_ACTIVITYID]);
        $atelier->set_source_sql(
            "SELECT pa.id, pa.atelierid, a.numero, pa.ordre, pa.obligatoire, pa.echeance
               FROM {local_simhub_parc_atelier} pa
               JOIN {local_simhub_atelier} a ON a.id = pa.atelierid
               JOIN {simhub} s ON s.parcoursid = pa.parcoursid
              WHERE s.id = ?
           ORDER BY pa.ordre, pa.id",
            [backup::VAR_PARENTID]
        );

        $simhub->annotate_files('mod_simhub', 'intro', null);

        return $this->prepare_activity_structure($simhub);
    }
}
