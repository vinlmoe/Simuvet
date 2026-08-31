<?php

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_simhub\record\ressource;

/**
 * Formulaire d'ajout/modification d'une ressource pédagogique (§6.2).
 *
 * Une ressource est soit un lien (url), soit un fichier hébergé (filemanager) — les deux
 * peuvent être renseignés, mais rester vide dans les deux cas est refusé côté page.
 */
class ressource_form extends \moodleform {

    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'titre', get_string('champ_nomcourt', 'local_simhub'), ['size' => 60]);
        $mform->setType('titre', PARAM_TEXT);
        $mform->addRule('titre', null, 'required', null, 'client');

        $mform->addElement('select', 'type', get_string('champ_statut', 'local_simhub'), [
            'fiche_methode' => 'Fiche méthode',
            'pdf_etudiant' => 'PDF étudiant',
            'video' => 'Vidéo',
            'consignes' => 'Consignes',
            'criteres_reussite' => 'Critères de réussite',
            'erreurs_frequentes' => 'Erreurs fréquentes',
            'liens_utiles' => 'Liens utiles',
            'complementaire' => 'Complémentaire',
            ressource::TYPE_SOURCE_EDITABLE => 'Source éditable (jamais visible étudiant)',
        ]);

        $mform->addElement('select', 'visibilite', 'Visibilité', [
            ressource::VISIBILITE_ETUDIANT => 'Étudiant',
            ressource::VISIBILITE_INTERNE => 'Interne (gestionnaires uniquement)',
        ]);

        $mform->addElement('text', 'url', 'Lien externe (optionnel si fichier fourni)', ['size' => 60]);
        $mform->setType('url', PARAM_URL);

        $mform->addElement('filemanager', 'fichier', 'Fichier (optionnel si lien fourni)', null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => '*',
        ]);

        $mform->addElement('text', 'ordre', 'Ordre d\'affichage');
        $mform->setType('ordre', PARAM_INT);
        $mform->setDefault('ordre', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        $mform->addElement('hidden', 'atelierid');
        $mform->setType('atelierid', PARAM_INT);

        $this->add_action_buttons();
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $draftitemid = (int) ($data['fichier'] ?? 0);
        $hasfile = false;
        if ($draftitemid) {
            $areafiles = file_get_drafarea_files($draftitemid);
            $hasfile = !empty($areafiles->list);
        }

        if (empty($data['url']) && !$hasfile) {
            $errors['url'] = 'Indiquez un lien ou un fichier.';
        }

        return $errors;
    }
}
