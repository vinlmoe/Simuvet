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
 * Formulaire Moodle décrit par ses champs, pour les petits formulaires des pages SimHub.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Formulaire générique.
 *
 * Données attendues (customdata) :
 *  - champs : liste de [type, nom, libellé, options] ; options : type (PARAM_*), requis,
 *    defaut, choix (select, autocomplete, radio, cases), attributs, aide (identifiant de chaîne),
 *    filtre (cases) ; le type « cases » est une liste de cases à cocher avec « tout
 *    sélectionner / tout désélectionner », renvoyée en tableau valeur => 0|1 ;
 *  - caches : nom => valeur entière, transmis en champs cachés ;
 *  - bouton : libellé du bouton d'envoi, ou boutons : nom => libellé pour plusieurs ;
 *  - id : identifiant unique quand la page affiche plusieurs formulaires.
 *
 * Plusieurs formulaires d'une même page se distinguent par leur identifiant : Moodle ne
 * considère comme envoyé que celui dont l'identifiant correspond.
 */
class formulaire extends \moodleform {
    /**
     * Identifiant de formulaire propre à chaque instance d'une page.
     *
     * @return string
     */
    protected function get_form_identifier() {
        return parent::get_form_identifier() . '_' . clean_param($this->_customdata['id'] ?? 'principal', PARAM_ALPHANUM);
    }

    /**
     * Définition du formulaire.
     *
     * @return void
     */
    protected function definition() {
        $mform = $this->_form;
        $cd = $this->_customdata;

        foreach ($cd['champs'] as $champ) {
            [$type, $nom, $libelle] = $champ;
            $opts = $champ[3] ?? [];
            if (in_array($type, ['select', 'autocomplete'], true)) {
                $mform->addElement($type, $nom, $libelle, $opts['choix'], $opts['attributs'] ?? []);
            } else if ($type === 'radio') {
                $groupe = [];
                foreach ($opts['choix'] as $valeur => $texte) {
                    $groupe[] = $mform->createElement('radio', $nom, '', $texte, $valeur);
                }
                $mform->addGroup($groupe, $nom . '_groupe', $libelle, \html_writer::empty_tag('br'), false);
            } else if ($type === 'cases') {
                \local_simhub\local\selection::ajouter_cases($mform, $nom, $libelle, $opts);
                continue;
            } else if ($type === 'header') {
                $mform->addElement('header', $nom, $libelle);
                $mform->setExpanded($nom, true);
                continue;
            } else if ($type === 'static') {
                $mform->addElement('static', $nom, $libelle, $opts['texte'] ?? '');
            } else if ($type === 'advcheckbox') {
                $mform->addElement('advcheckbox', $nom, $libelle, $opts['texte'] ?? '', $opts['attributs'] ?? []);
            } else {
                $mform->addElement($type, $nom, $libelle, $opts['attributs'] ?? []);
            }
            if ($type !== 'static' && $type !== 'radio') {
                $mform->setType($nom, $opts['type'] ?? PARAM_TEXT);
            } else if ($type === 'radio') {
                $mform->setType($nom, $opts['type'] ?? PARAM_ALPHANUMEXT);
            }
            if (!empty($opts['requis'])) {
                $mform->addRule($type === 'radio' ? $nom . '_groupe' : $nom, null, 'required', null, 'client');
            }
            if (array_key_exists('defaut', $opts)) {
                $mform->setDefault($nom, $opts['defaut']);
            }
            if (!empty($opts['aide'])) {
                $mform->addHelpButton($nom, $opts['aide'], 'local_simhub');
            }
        }

        foreach (($cd['caches'] ?? []) as $nom => $valeur) {
            $mform->addElement('hidden', $nom, $valeur);
            $mform->setType($nom, PARAM_INT);
        }
        if (!empty($cd['boutons'])) {
            $groupe = [];
            foreach ($cd['boutons'] as $nom => $libelle) {
                $groupe[] = $mform->createElement('submit', $nom, $libelle);
            }
            $mform->addGroup($groupe, 'boutons', '', ' ', false);
        } else {
            $this->add_action_buttons(!empty($cd['annuler']), $cd['bouton'] ?? get_string('savechanges'));
        }
    }
}
