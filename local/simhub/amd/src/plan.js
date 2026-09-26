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
 * Positionnement du repère d'un atelier sur le plan de salle, par un clic (§5.4).
 *
 * @module     local_simhub/plan
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Au clic sur le plan, place le repère et enregistre sa position en pourcentage.
 */
export const init = () => {
    const conteneur = document.getElementById('local-simhub-plan-container');
    const image = document.getElementById('local-simhub-plan-img');
    const formulaire = document.getElementById('local-simhub-plan-form');
    if (!conteneur || !image || !formulaire) {
        return;
    }
    conteneur.addEventListener('click', (e) => {
        const cadre = image.getBoundingClientRect();
        const x = ((e.clientX - cadre.left) / cadre.width) * 100;
        const y = ((e.clientY - cadre.top) / cadre.height) * 100;

        let repere = document.getElementById('local-simhub-plan-marker');
        if (!repere) {
            repere = document.createElement('span');
            repere.id = 'local-simhub-plan-marker';
            repere.className = 'local-simhub-plan-marker';
            conteneur.appendChild(repere);
        }
        repere.style.left = x + '%';
        repere.style.top = y + '%';

        formulaire.querySelector('[name=planrepx]').value = x.toFixed(2);
        formulaire.querySelector('[name=planrepy]').value = y.toFixed(2);
        formulaire.submit();
    });
};
