<?php

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

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'nom', get_string('filtre_parcours', 'local_simhub'), ['size' => 60]);
        $mform->setType('nom', PARAM_TEXT);
        $mform->addRule('nom', null, 'required', null, 'client');

        $mform->addElement('textarea', 'description', get_string('champ_descriptioncourte', 'local_simhub'));
        $mform->setType('description', PARAM_TEXT);

        $types = [];
        foreach (['recommande', 'obligatoire', 'lie_uc', 'lie_annee', 'certifiant', 'asv'] as $type) {
            $types[$type] = get_string('parcours_type_' . $type, 'local_simhub');
        }
        $mform->addElement('select', 'type', get_string('type', 'local_simhub'), $types);

        global $DB;
        $cours = [0 => get_string('aucune_uc', 'local_simhub')];
        foreach ($DB->get_records_select_menu('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname', 'id, fullname') as $cid => $nom) {
            $cours[$cid] = format_string($nom);
        }
        $mform->addElement('autocomplete', 'courseid', get_string('champ_uc_optionnel', 'local_simhub'), $cours);
        $mform->setType('courseid', PARAM_INT);

        $mform->addElement(
            'select',
            'anneeetude',
            get_string('filtre_annee', 'local_simhub') . ' (optionnel)',
            annee_resolver::get_options()
        );
        $mform->setType('anneeetude', PARAM_INT);

        $mform->addElement(
            'select',
            'cohortid',
            get_string('champ_cohorte', 'local_simhub') . ' (optionnel — pour recommander directement à ses membres)',
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

        $mform->addElement('text', 'envcode', get_string('champ_envcode', 'local_simhub'));
        $mform->setType('envcode', PARAM_ALPHANUMEXT);
        $mform->addRule('envcode', null, 'required', null, 'client');
        $mform->setDefault('envcode', get_config('local_simhub', 'envcode') ?: '');

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
}
