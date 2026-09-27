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
 * Paramètres de l'activité SimHub d'une UC.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Paramètres de l'activité SimHub d'une UC.
 */
class mod_simhub_mod_form extends moodleform_mod {
    /**
     * Définition du formulaire.
     *
     * @return void
     */
    public function definition() {
        $mform = $this->_form;

        $mform->addElement('header', 'general', get_string('general', 'form'));
        $mform->addElement('text', 'name', get_string('name'), ['size' => 64]);
        $mform->setType('name', PARAM_TEXT);
        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');

        $this->standard_intro_elements();

        $this->standard_grading_coursemodule_elements();
        $mform->setDefault('grade', 100);
        $mform->addElement('static', 'notecalcul', '', get_string('notecalcul', 'simhub'));

        $this->standard_coursemodule_elements();
        $this->add_action_buttons();
    }

    /**
     * Suffixe des champs d'achèvement (Moodle 4.3+ : réglages par défaut en masse).
     *
     * @return string
     */
    protected function suffixe(): string {
        return method_exists($this, 'get_suffix') ? $this->get_suffix() : '';
    }

    /**
     * Règle d'achèvement : parcours de l'UC terminé.
     *
     * @return string[]
     */
    public function add_completion_rules() {
        $nom = 'completionparcours' . $this->suffixe();
        $this->_form->addElement('checkbox', $nom, '', get_string('completionparcours', 'simhub'));
        return [$nom];
    }

    /**
     * Vrai si la règle est cochée.
     *
     * @param array $data
     * @return bool
     */
    public function completion_rule_enabled($data) {
        return !empty($data['completionparcours' . $this->suffixe()]);
    }

    /**
     * Décocher la règle doit l'enregistrer à 0.
     *
     * @param stdClass $data
     * @return void
     */
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);
        if (!empty($data->completionunlocked)) {
            $suffixe = $this->suffixe();
            $nom = 'completionparcours' . $suffixe;
            $auto = ($data->{'completion' . $suffixe} ?? COMPLETION_TRACKING_NONE) == COMPLETION_TRACKING_AUTOMATIC;
            if (!$auto || empty($data->$nom)) {
                $data->$nom = 0;
            }
        }
    }
}
