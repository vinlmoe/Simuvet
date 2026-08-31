<?php

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulaire de création/modification d'un parcours pédagogique (§8).
 */
class parcours_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'nom', get_string('filtre_parcours', 'local_simhub'), ['size' => 60]);
        $mform->setType('nom', PARAM_TEXT);
        $mform->addRule('nom', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('champ_descriptioncourte', 'local_simhub'));
        $mform->setType('description', PARAM_TEXT);

        $mform->addElement('select', 'type', get_string('champ_statut', 'local_simhub'), [
            'recommande' => 'Recommandé',
            'obligatoire' => 'Obligatoire',
            'lie_uc' => 'Lié à une UC',
            'lie_annee' => 'Lié à une année',
            'certifiant' => 'Certifiant',
            'asv' => 'ASV',
        ]);

        $mform->addElement('text', 'courseid', 'Id de l\'UC Moodle (optionnel)');
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement('text', 'anneeetude', get_string('filtre_annee', 'local_simhub') . ' (optionnel)');
        $mform->setType('anneeetude', PARAM_INT);

        $mform->addElement('text', 'cohortid', 'Id de la cohorte Moodle (optionnel)');
        $mform->setType('cohortid', PARAM_INT);

        $mform->addElement('text', 'envcode', get_string('champ_envcode', 'local_simhub'));
        $mform->setType('envcode', PARAM_ALPHANUMEXT);
        $mform->addRule('envcode', null, 'required', null, 'client');
        $mform->setDefault('envcode', get_config('local_simhub', 'envcode') ?: '');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}
