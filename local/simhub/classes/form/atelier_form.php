<?php

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_simhub\persistent\atelier;

/**
 * Formulaire de création/modification d'une fiche atelier (§6).
 *
 * Le statut "indisponible" (§6.1) est géré à part, dans manage/atelier_edit.php, via
 * classes\record\indispo : ce formulaire ne porte que les champs propres à la fiche.
 */
class atelier_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'numero', get_string('champ_numero', 'local_simhub'));
        $mform->setType('numero', PARAM_ALPHANUMEXT);
        $mform->addRule('numero', null, 'required', null, 'client');

        $mform->addElement('text', 'nomcourt', get_string('champ_nomcourt', 'local_simhub'), ['size' => 60]);
        $mform->setType('nomcourt', PARAM_TEXT);
        $mform->addRule('nomcourt', null, 'required', null, 'client');

        $mform->addElement('textarea', 'nomlong', get_string('champ_nomlong', 'local_simhub'));
        $mform->setType('nomlong', PARAM_TEXT);

        $mform->addElement('textarea', 'descriptioncourte', get_string('champ_descriptioncourte', 'local_simhub'));
        $mform->setType('descriptioncourte', PARAM_TEXT);

        $mform->addElement('text', 'discipline', get_string('champ_discipline', 'local_simhub'));
        $mform->setType('discipline', PARAM_TEXT);

        $mform->addElement('text', 'espece', get_string('champ_espece', 'local_simhub'));
        $mform->setType('espece', PARAM_TEXT);

        $mform->addElement('select', 'niveaudifficulte', get_string('champ_niveaudifficulte', 'local_simhub'), [
            '' => '',
            'facile' => get_string('niveau_facile', 'local_simhub'),
            'intermediaire' => get_string('niveau_intermediaire', 'local_simhub'),
            'avance' => get_string('niveau_avance', 'local_simhub'),
        ]);

        $mform->addElement('text', 'dureeindicative', get_string('champ_dureeindicative', 'local_simhub'));
        $mform->setType('dureeindicative', PARAM_INT);

        $mform->addElement('select', 'statut', get_string('champ_statut', 'local_simhub'), [
            atelier::STATUT_ACTIF => get_string('statut_actif', 'local_simhub'),
            atelier::STATUT_NON_UTILISE => get_string('statut_non_utilise', 'local_simhub'),
            atelier::STATUT_INDISPONIBLE => get_string('statut_indisponible', 'local_simhub'),
            atelier::STATUT_ARCHIVE => get_string('statut_archive', 'local_simhub'),
        ]);

        $mform->addElement('text', 'envcode', get_string('champ_envcode', 'local_simhub'));
        $mform->setType('envcode', PARAM_ALPHANUMEXT);
        $mform->addRule('envcode', null, 'required', null, 'client');
        $mform->setDefault('envcode', get_config('local_simhub', 'envcode') ?: '');

        $mform->addElement('header', 'localisation', get_string('champ_salle', 'local_simhub'));

        $mform->addElement('text', 'salle', get_string('champ_salle', 'local_simhub'));
        $mform->setType('salle', PARAM_TEXT);

        $mform->addElement('text', 'zone', get_string('champ_zone', 'local_simhub'));
        $mform->setType('zone', PARAM_TEXT);

        $mform->addElement('text', 'codeposte', get_string('champ_codeposte', 'local_simhub'));
        $mform->setType('codeposte', PARAM_TEXT);

        $mform->addElement('textarea', 'indicationtextuelle', get_string('champ_indicationtextuelle', 'local_simhub'));
        $mform->setType('indicationtextuelle', PARAM_TEXT);

        // Repère sur le plan de salle (§5.4) : coordonnées en % de l'image, conservées ici en
        // champs cachés — leur valeur est fixée par un clic sur l'image dans
        // manage/atelier_plan.php plutôt que saisie à la main, une fois l'atelier enregistré.
        $mform->addElement('hidden', 'planrepx');
        $mform->setType('planrepx', PARAM_FLOAT);

        $mform->addElement('hidden', 'planrepy');
        $mform->setType('planrepy', PARAM_FLOAT);

        $mform->addElement('filemanager', 'planimage', get_string('champ_planimage', 'local_simhub'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => ['.png', '.jpg', '.jpeg'],
        ]);

        $mform->addElement('header', 'administration', get_string('champ_commentaireadmin', 'local_simhub'));

        $mform->addElement('textarea', 'commentaireadmin', get_string('champ_commentaireadmin', 'local_simhub'));
        $mform->setType('commentaireadmin', PARAM_TEXT);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);

        $this->add_action_buttons();
    }
    /**
     * Le numéro d'atelier est unique par établissement (§5.3) : le signaler dans le
     * formulaire plutôt que de laisser la base refuser l'enregistrement.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        global $DB;

        $errors = parent::validation($data, $files);
        $doublon = $DB->record_exists_select('local_simhub_atelier',
            'envcode = :envcode AND numero = :numero AND id <> :id',
            ['envcode' => $data['envcode'], 'numero' => $data['numero'], 'id' => (int) ($data['id'] ?? 0)]);
        if ($doublon) {
            $errors['numero'] = get_string('numero_existe', 'local_simhub');
        }
        return $errors;
    }
}
