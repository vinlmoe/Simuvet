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
 * Formulaire de création/modification d'un parcours pédagogique (§8).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_simhub\local\annee_resolver;
use local_simhub\local\cohort_helper;
use local_simhub\local\badge_helper;

/**
 * Formulaire de création/modification d'un parcours pédagogique (§8).
 */
class parcours_form extends \moodleform {
    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        // Parcours d'une activité d'UC : nom, type et cours sont ceux de l'activité, affichés
        // sans être modifiables.
        $uc = $this->_customdata['uc'] ?? null;

        if ($uc) {
            $mform->addElement('static', 'infouc', '', get_string('parcours_uc_verrouille', 'local_simhub'));
            $mform->addElement('static', 'nomuc', get_string('filtre_parcours', 'local_simhub'), s($uc['nom']));
            $mform->addElement('static', 'coursuc', get_string('col_uc', 'local_simhub'), s($uc['cours']));
        } else {
            $mform->addElement('text', 'nom', get_string('filtre_parcours', 'local_simhub'), ['size' => 60]);
            $mform->setType('nom', PARAM_TEXT);
            $mform->addRule('nom', null, 'required', null, 'client');
        }

        $mform->addElement('textarea', 'description', get_string('champ_descriptioncourte', 'local_simhub'));
        $mform->setType('description', PARAM_TEXT);

        $types = [];
        foreach (['recommande', 'obligatoire', 'lie_uc', 'lie_annee', 'certifiant', 'asv'] as $type) {
            $types[$type] = get_string('parcours_type_' . $type, 'local_simhub');
        }
        if (!$uc) {
            $mform->addElement('select', 'type', get_string('type', 'local_simhub'), $types);
        }

        global $DB;
        $cours = [0 => get_string('aucune_uc', 'local_simhub')];
        $listecours = $DB->get_records_select_menu('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname', 'id, fullname');
        foreach ($listecours as $cid => $nom) {
            $cours[$cid] = format_string($nom);
        }
        if (!$uc) {
            $mform->addElement('autocomplete', 'courseid', get_string('champ_uc_optionnel', 'local_simhub'), $cours);
            $mform->setType('courseid', PARAM_INT);
        }

        $mform->addElement(
            'select',
            'anneeetude',
            get_string('filtre_annee_optionnel', 'local_simhub'),
            annee_resolver::get_options()
        );
        $mform->setType('anneeetude', PARAM_INT);

        $mform->addElement(
            'select',
            'cohortid',
            get_string('champ_cohorte_optionnel', 'local_simhub'),
            cohort_helper::get_options()
        );
        $mform->setType('cohortid', PARAM_INT);

        $mform->addElement(
            'select',
            'badgeid',
            get_string('champ_badge', 'local_simhub'),
            badge_helper::get_options()
        );
        $mform->setType('badgeid', PARAM_INT);

        // Chaque école a son propre Moodle : le code établissement ne sert qu'à la traçabilité.
        $mform->addElement('hidden', 'envcode');
        $mform->setType('envcode', PARAM_ALPHANUMEXT);
        $mform->setDefault('envcode', get_config('local_simhub', 'envcode') ?: '');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}
