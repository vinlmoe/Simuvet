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
 * Formulaire d'import (§12.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Import d'ateliers, de localisations, de ressources, de rattachements ou de parcours.
 */
class import_form extends \moodleform {
    /** Types d'import proposés. */
    const TYPES = ['ateliers', 'localisation', 'ressources', 'rattachements', 'parcours'];

    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;

        $types = [];
        foreach (self::TYPES as $type) {
            $types[$type] = get_string('import_type_' . $type, 'local_simhub');
        }
        $mform->addElement('select', 'type', get_string('import_type', 'local_simhub'), $types);
        $mform->addHelpButton('type', 'import_type', 'local_simhub');

        $mform->addElement('filepicker', 'fichier', get_string('import_fichier', 'local_simhub'), null, [
            'accepted_types' => array_map(fn($e) => '.' . $e, \local_simhub\local\tableur::EXTENSIONS),
            'maxfiles' => 1,
        ]);
        $mform->addRule('fichier', null, 'required', null, 'client');

        $mform->addElement('select', 'delimiter', get_string('import_separateur', 'local_simhub'), [
            ';' => get_string('import_sep_pointvirgule', 'local_simhub'),
            ',' => get_string('import_sep_virgule', 'local_simhub'),
        ]);

        $this->add_action_buttons(false, get_string('import_lancer', 'local_simhub'));
    }
}
