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
 * Générateur de données de test de SimHub.
 *
 * @package    local_simhub
 * @category   test
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use local_simhub\local\parcours_helper;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\persistent\session;

/**
 * Ateliers, ateliers d'une activité d'UC et séances.
 */
class local_simhub_generator extends component_generator_base {
    /**
     * Crée un atelier.
     *
     * @param array|stdClass $record numero, nomcourt, statut, dureeindicative, categorie...
     * @return atelier
     */
    public function create_atelier($record): atelier {
        $record = (array) $record;
        $atelier = new atelier(0, (object) array_merge([
            'statut' => atelier::STATUT_ACTIF,
            'envcode' => '',
        ], $record));
        $atelier->create();
        return $atelier;
    }

    /**
     * Ajoute un atelier au parcours d'une activité SimHub d'UC.
     *
     * @param array|stdClass $record activity (nom de l'activité), atelier (numéro), obligatoire
     * @return void
     */
    public function create_uc_atelier($record): void {
        global $DB;

        $record = (array) $record;
        $parcoursid = $DB->get_field('simhub', 'parcoursid', ['name' => $record['activity']], MUST_EXIST);
        $atelier = atelier::get_record(['numero' => $record['atelier']], MUST_EXIST);
        parcours_helper::ajouter_atelier(
            new parcours($parcoursid),
            (int) $atelier->get('id'),
            (int) ($record['ordre'] ?? 0),
            !empty($record['obligatoire']),
            null
        );
    }

    /**
     * Crée une séance d'un étudiant sur un atelier.
     *
     * @param array|stdClass $record userid, atelier (numéro), statut (commence, realise, certifie)
     * @return session
     */
    public function create_session($record): session {
        $record = (array) $record;
        $atelier = atelier::get_record(['numero' => $record['atelier']], MUST_EXIST);
        $session = session::demarrer((int) $record['userid'], (int) $atelier->get('id'));
        $statut = $record['statut'] ?? session::STATUT_REALISE;
        if ($statut !== session::STATUT_COMMENCE) {
            $session->terminer();
            if ($statut !== session::STATUT_REALISE) {
                $session->set('statut', $statut);
                $session->update();
            }
            \local_simhub\event\session_completed::create([
                'objectid' => $session->get('id'),
                'context' => \local_simhub\local\contexte::racine(),
            ])->trigger();
        }
        return $session;
    }
}
