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
 * Formulaire d'ajout/modification d'une ressource pédagogique (§6.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

use local_simhub\persistent\ressource;

/**
 * Formulaire d'ajout/modification d'une ressource pédagogique (§6.2).
 *
 * Une ressource est soit un lien (url), soit un fichier hébergé (filemanager) — les deux
 * peuvent être renseignés, mais rester vide dans les deux cas est refusé côté page.
 */
class ressource_form extends \moodleform {
    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'titre', get_string('champ_nomcourt', 'local_simhub'), ['size' => 60]);
        $mform->setType('titre', PARAM_TEXT);
        $mform->addRule('titre', null, 'required', null, 'client');

        $mform->addElement('select', 'type', get_string('champ_statut', 'local_simhub'), [
            'fiche_methode' => get_string('ressource_type_fiche_methode', 'local_simhub'),
            'pdf_etudiant' => get_string('ressource_type_pdf_etudiant', 'local_simhub'),
            'video' => get_string('ressource_type_video', 'local_simhub'),
            'consignes' => get_string('ressource_type_consignes', 'local_simhub'),
            'criteres_reussite' => get_string('ressource_type_criteres_reussite', 'local_simhub'),
            'erreurs_frequentes' => get_string('ressource_type_erreurs_frequentes', 'local_simhub'),
            'liens_utiles' => get_string('ressource_type_liens_utiles', 'local_simhub'),
            'complementaire' => get_string('ressource_type_complementaire', 'local_simhub'),
            ressource::TYPE_SOURCE_EDITABLE => get_string('ressource_type_source_editable', 'local_simhub'),
        ]);

        $mform->addElement('select', 'visibilite', get_string('champ_visibilite', 'local_simhub'), [
            ressource::VISIBILITE_ETUDIANT => get_string('visibilite_etudiant', 'local_simhub'),
            ressource::VISIBILITE_INTERNE => get_string('visibilite_interne', 'local_simhub'),
        ]);

        $mform->addElement('text', 'url', get_string('champ_url_ressource', 'local_simhub'), ['size' => 60]);
        $mform->setType('url', PARAM_URL);

        $mform->addElement('filemanager', 'fichier', get_string('champ_fichier_ressource', 'local_simhub'), null, [
            'subdirs' => 0,
            'maxfiles' => 1,
            'accepted_types' => '*',
        ]);

        $mform->addElement('text', 'ordre', get_string('champ_ordre_affichage', 'local_simhub'));
        $mform->setType('ordre', PARAM_INT);
        $mform->setDefault('ordre', 0);

        $mform->addElement('hidden', 'id');
        $mform->setType('id', PARAM_INT);
        \local_simhub\local\navigation::champ_activite($mform);
        $mform->addElement('hidden', 'atelierid');
        $mform->setType('atelierid', PARAM_INT);

        $this->add_action_buttons();
    }

    /**
     * Validation du formulaire.
     *
     * @param array $data
     * @param array $files
     * @return array Erreurs par champ.
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $draftitemid = (int) ($data['fichier'] ?? 0);
        $hasfile = false;
        if ($draftitemid) {
            $areafiles = file_get_drafarea_files($draftitemid);
            $hasfile = !empty($areafiles->list);
        }

        if (empty($data['url']) && !$hasfile) {
            $errors['url'] = get_string('ressource_lien_ou_fichier', 'local_simhub');
        }

        return $errors;
    }
}
