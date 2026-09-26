<?php

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Paramètres de l'activité SimHub d'une UC.
 */
class mod_simhub_mod_form extends moodleform_mod {

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
}
