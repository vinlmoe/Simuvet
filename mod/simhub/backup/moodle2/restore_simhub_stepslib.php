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
 * Restauration : recrée le parcours de l'UC dans le nouveau cours et retrouve ses ateliers.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_simhub\local\parcours_helper;
use local_simhub\persistent\parcours;

/**
 * Restauration : recrée le parcours de l'UC dans le nouveau cours et retrouve ses ateliers
 * par numéro dans le référentiel du site.
 */
class restore_simhub_activity_structure_step extends restore_activity_structure_step {
    /** @var parcours|null Parcours créé pour l'activité restaurée. */
    protected $parcours = null;

    /**
     * Chemins restaurés.
     *
     * @return restore_path_element[]
     */
    protected function define_structure() {
        return $this->prepare_activity_structure([
            new restore_path_element('simhub', '/activity/simhub'),
            new restore_path_element('simhub_atelier', '/activity/simhub/ateliers/atelier'),
        ]);
    }

    /**
     * Crée l'instance et son parcours.
     *
     * @param array $data
     * @return void
     */
    protected function process_simhub($data) {
        global $DB;

        $data = (object) $data;
        $this->parcours = new parcours(0, (object) [
            'nom' => $data->name,
            'type' => 'lie_uc',
            'courseid' => $this->get_courseid(),
            'envcode' => get_config('local_simhub', 'envcode') ?: '',
            'cmid' => $this->task->get_moduleid(),
        ]);
        $this->parcours->create();

        $data->course = $this->get_courseid();
        $data->parcoursid = $this->parcours->get('id');
        $newitemid = $DB->insert_record('simhub', $data);
        $this->apply_activity_instance($newitemid);
    }

    /**
     * Ajoute un atelier au parcours, s'il existe sur ce site.
     *
     * @param array $data
     * @return void
     */
    protected function process_simhub_atelier($data) {
        global $DB;

        $data = (object) $data;
        $atelierid = $DB->get_field('local_simhub_atelier', 'id', ['numero' => $data->numero], IGNORE_MULTIPLE);
        if (!$atelierid) {
            $this->log('SimHub : atelier ' . $data->numero . ' absent du référentiel, non restauré', backup::LOG_WARNING);
            return;
        }
        $echeance = $data->echeance ? $this->apply_date_offset($data->echeance) : null;
        parcours_helper::ajouter_atelier(
            $this->parcours,
            (int) $atelierid,
            (int) $data->ordre,
            !empty($data->obligatoire),
            $echeance ?: null,
            false
        );
    }

    /**
     * Fichiers de la description.
     *
     * @return void
     */
    protected function after_execute() {
        $this->add_related_files('mod_simhub', 'intro', null);
    }
}
