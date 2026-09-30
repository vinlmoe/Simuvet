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
 * Adresse du validateur à qui envoyer le lien de validation animal vivant (§9.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Le lien part directement chez le validateur : l'étudiant ne le voit jamais.
 */
class demande_valanimal_form extends \moodleform {
    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $mform->addElement('text', 'email', get_string('asv_email_validateur', 'local_simhub'), ['size' => 40]);
        $mform->setType('email', PARAM_RAW_TRIMMED);
        $mform->addRule('email', null, 'required', null, 'client');
        $mform->addHelpButton('email', 'asv_email_validateur', 'local_simhub');

        $mform->addElement('hidden', 'acteid', $this->_customdata['acteid']);
        $mform->setType('acteid', PARAM_INT);

        $this->add_action_buttons(false, $this->_customdata['bouton']);
    }

    /**
     * Adresse valide et différente de celle de l'étudiant.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if (!validate_email($data['email'])) {
            $errors['email'] = get_string('invalidemail');
        } else if (!\local_simhub\record\asv_valanimal::email_acceptable($this->_customdata['userid'], $data['email'])) {
            $errors['email'] = get_string('asv_email_etudiant_refuse', 'local_simhub');
        }
        return $errors;
    }
}
