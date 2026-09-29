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
 * Sélection multiple pour les validations en masse : boutons « tout sélectionner » et
 * « tout désélectionner », filtre texte, compteur, et actions groupées désactivées tant que
 * rien n'est coché.
 *
 * Balisage attendu dans un conteneur .local-simhub-selection :
 *  - cases à cocher portant l'attribut data-selection-item ;
 *  - boutons data-selection="tout" / "aucun", champ data-selection="filtre" (facultatif) ;
 *  - élément data-selection="compteur" (facultatif) ;
 *  - boutons d'action groupée data-selection="action".
 *
 * @module     local_simhub/selection
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Ligne (tableau ou case de formulaire) portant une case à cocher.
 *
 * @param {HTMLElement} caseacocher
 * @return {HTMLElement}
 */
const ligne = (caseacocher) => caseacocher.closest('[data-selection-ligne], tr, label, .form-check') || caseacocher.parentElement;

/**
 * Cases du conteneur, éventuellement limitées à celles visibles (non masquées par le filtre).
 *
 * @param {HTMLElement} conteneur
 * @param {boolean} visibles
 * @return {HTMLInputElement[]}
 */
const cases = (conteneur, visibles) => Array.from(conteneur.querySelectorAll('input[type="checkbox"][data-selection-item]'))
    .filter((c) => !c.disabled && (!visibles || !ligne(c).hidden));

/**
 * Met à jour le compteur et l'état des boutons d'action groupée.
 *
 * @param {HTMLElement} conteneur
 */
const rafraichir = (conteneur) => {
    const nb = cases(conteneur, false).filter((c) => c.checked).length;
    conteneur.querySelectorAll('[data-selection="compteur"]').forEach((el) => {
        el.textContent = String(nb);
    });
    conteneur.querySelectorAll('[data-selection="action"]').forEach((bouton) => {
        bouton.disabled = nb === 0;
    });
};

/**
 * Coche ou décoche toutes les cases visibles du conteneur.
 *
 * @param {HTMLElement} conteneur
 * @param {boolean} etat
 */
const cocher = (conteneur, etat) => {
    cases(conteneur, true).forEach((c) => {
        c.checked = etat;
    });
    rafraichir(conteneur);
};

/**
 * Masque les lignes dont le texte ne contient pas le filtre saisi.
 *
 * @param {HTMLElement} conteneur
 * @param {string} texte
 */
const filtrer = (conteneur, texte) => {
    const recherche = texte.trim().toLowerCase();
    cases(conteneur, false).forEach((c) => {
        const el = ligne(c);
        el.hidden = recherche !== '' && !el.textContent.toLowerCase().includes(recherche);
    });
};

/**
 * Active la sélection multiple sur tous les conteneurs de la page.
 */
export const init = () => {
    document.querySelectorAll('.local-simhub-selection').forEach((conteneur) => {
        if (conteneur.dataset.selectionInit) {
            return;
        }
        conteneur.dataset.selectionInit = '1';
        conteneur.addEventListener('click', (e) => {
            const bouton = e.target.closest('[data-selection="tout"], [data-selection="aucun"]');
            if (bouton) {
                e.preventDefault();
                cocher(conteneur, bouton.dataset.selection === 'tout');
            }
        });
        conteneur.addEventListener('change', (e) => {
            if (e.target.matches('input[data-selection-item]')) {
                rafraichir(conteneur);
            }
        });
        conteneur.querySelectorAll('[data-selection="filtre"]').forEach((champ) => {
            champ.addEventListener('input', () => filtrer(conteneur, champ.value));
            // Entrée dans le filtre ne doit pas envoyer le formulaire.
            champ.addEventListener('keydown', (e) => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                }
            });
        });
        rafraichir(conteneur);
    });
};
