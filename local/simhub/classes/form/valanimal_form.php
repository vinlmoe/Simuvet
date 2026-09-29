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
 * Formulaire du validateur externe ASV (§9.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Nom, prénom, certification et signature au doigt : volontairement court (§9.3).
 *
 * Lien groupé (customdata lot + demandes) : le validateur coche les étudiants qu'il a vus
 * réaliser l'acte et signe une seule fois pour tous.
 */
class valanimal_form extends \moodleform {
    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        if (!empty($this->_customdata['demandes'])) {
            \local_simhub\local\selection::ajouter_cases($mform, 'demandes', get_string('asv_etudiants', 'local_simhub'), [
                'choix' => $this->_customdata['demandes'],
                'defaut' => array_keys($this->_customdata['demandes']),
                'filtre' => count($this->_customdata['demandes']) > 10,
            ]);
        }

        $mform->addElement('text', 'nom', get_string('asv_champ_nom', 'local_simhub'), ['autocomplete' => 'family-name']);
        $mform->setType('nom', PARAM_TEXT);
        $mform->addRule('nom', null, 'required', null, 'client');

        $mform->addElement('text', 'prenom', get_string('asv_champ_prenom', 'local_simhub'), ['autocomplete' => 'given-name']);
        $mform->setType('prenom', PARAM_TEXT);
        $mform->addRule('prenom', null, 'required', null, 'client');

        $mform->addElement('checkbox', 'certification', '', get_string('asv_champ_certification', 'local_simhub'));
        $mform->addRule('certification', null, 'required', null, 'client');

        $mform->addElement(
            'static',
            'zonesignature',
            get_string('asv_champ_signature', 'local_simhub'),
            \html_writer::tag('canvas', '', [
                'id' => 'local-simhub-signature-pad', 'class' => 'local-simhub-signature', 'width' => 400, 'height' => 150,
            ]) . \html_writer::tag('button', get_string('asv_signature_effacer', 'local_simhub'), [
                'type' => 'button', 'id' => 'local-simhub-signature-clear', 'class' => 'btn btn-secondary btn-sm d-block mt-1',
            ])
        );
        $mform->addElement('hidden', 'signature', '');
        $mform->setType('signature', PARAM_RAW);

        $jeton = !empty($this->_customdata['lot']) ? 'lot' : 'token';
        $mform->addElement('hidden', $jeton, $this->_customdata[$jeton]);
        $mform->setType($jeton, PARAM_ALPHANUMEXT);

        $this->add_action_buttons(false, get_string('asv_valider_acte', 'local_simhub'));
    }
}
