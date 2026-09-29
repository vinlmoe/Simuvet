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

namespace local_simhub\local;

/**
 * Sélection multiple pour les validations en masse : barre « tout sélectionner / tout
 * désélectionner », cases à cocher et boutons d'action groupée (module AMD
 * local_simhub/selection).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class selection {
    /** @var string Classe CSS du conteneur reconnu par le module JS. */
    const CONTENEUR = 'local-simhub-selection';

    /**
     * Charge le module JS de sélection sur la page.
     *
     * @return void
     */
    public static function requerir_js(): void {
        global $PAGE;
        $PAGE->requires->js_call_amd('local_simhub/selection', 'init');
    }

    /**
     * Barre d'outils : tout sélectionner, tout désélectionner, filtre facultatif, compteur et
     * boutons d'envoi groupé (name="action").
     *
     * @param array $actions valeur de l'action => [libellé, classe CSS du bouton] ; vide pour
     *     une barre sans bouton d'envoi (formulaire qui a déjà les siens).
     * @param bool $filtre Afficher un champ de filtre texte sur les lignes.
     * @return string HTML
     */
    public static function barre(array $actions = [], bool $filtre = false): string {
        $html = \html_writer::tag('button', get_string('selection_tout', 'local_simhub'), [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary mr-1 me-1', 'data-selection' => 'tout',
        ]);
        $html .= \html_writer::tag('button', get_string('selection_aucun', 'local_simhub'), [
            'type' => 'button', 'class' => 'btn btn-sm btn-outline-secondary mr-2 me-2', 'data-selection' => 'aucun',
        ]);
        if ($filtre) {
            $html .= \html_writer::empty_tag('input', [
                'type' => 'search', 'class' => 'form-control form-control-sm d-inline-block w-auto mr-2 me-2',
                'placeholder' => get_string('selection_filtre', 'local_simhub'),
                'aria-label' => get_string('selection_filtre', 'local_simhub'), 'data-selection' => 'filtre',
            ]);
        }
        $html .= \html_writer::span(
            get_string('selection_compteur', 'local_simhub', \html_writer::span('0', '', ['data-selection' => 'compteur'])),
            'text-muted mr-2 me-2'
        );
        foreach ($actions as $valeur => [$libelle, $classe]) {
            $html .= \html_writer::tag('button', $libelle, [
                'type' => 'submit', 'name' => 'action', 'value' => $valeur,
                'class' => 'btn btn-sm ' . $classe . ' mr-1 me-1', 'data-selection' => 'action',
            ]);
        }
        return \html_writer::div($html, 'd-flex flex-wrap align-items-center mb-2');
    }

    /**
     * Case à cocher d'une ligne.
     *
     * @param string $nom Nom du paramètre tableau (sans crochets).
     * @param int $valeur
     * @param string $libelle Libellé accessible (texte brut).
     * @return string HTML
     */
    public static function case(string $nom, int $valeur, string $libelle): string {
        return \html_writer::empty_tag('input', [
            'type' => 'checkbox', 'name' => $nom . '[]', 'value' => $valeur, 'data-selection-item' => 1,
            'aria-label' => get_string('selection_case', 'local_simhub', $libelle),
        ]);
    }
}
