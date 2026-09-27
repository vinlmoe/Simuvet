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
 * Signature au doigt du validateur externe ASV (§9.3), à la souris ou au toucher.
 *
 * @module     local_simhub/signature
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Capture le tracé sur le canvas et le transmet en PNG à l'envoi du formulaire.
 *
 * @param {string} messagerequis Message si l'on envoie sans avoir signé.
 */
export const init = (messagerequis) => {
    const canvas = document.getElementById('local-simhub-signature-pad');
    const formulaire = document.getElementById('local-simhub-valanimal-form');
    if (!canvas || !formulaire) {
        return;
    }
    const ctx = canvas.getContext('2d');
    let trace = false;
    let signe = false;

    const position = (e) => {
        const cadre = canvas.getBoundingClientRect();
        const point = e.touches ? e.touches[0] : e;
        // Le canvas peut être affiché plus petit que sa taille réelle (écran de téléphone).
        return {
            x: (point.clientX - cadre.left) * canvas.width / cadre.width,
            y: (point.clientY - cadre.top) * canvas.height / cadre.height,
        };
    };
    const debut = (e) => {
        trace = true;
        const p = position(e);
        ctx.beginPath();
        ctx.moveTo(p.x, p.y);
    };
    const mouvement = (e) => {
        if (!trace) {
            return;
        }
        e.preventDefault();
        const p = position(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        signe = true;
    };
    const fin = () => {
        trace = false;
    };

    canvas.addEventListener('mousedown', debut);
    canvas.addEventListener('mousemove', mouvement);
    window.addEventListener('mouseup', fin);
    canvas.addEventListener('touchstart', debut, {passive: true});
    canvas.addEventListener('touchmove', mouvement, {passive: false});
    canvas.addEventListener('touchend', fin);

    document.getElementById('local-simhub-signature-clear').addEventListener('click', () => {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signe = false;
    });

    formulaire.addEventListener('submit', (e) => {
        if (!signe) {
            e.preventDefault();
            window.alert(messagerequis);
            return;
        }
        formulaire.querySelector('[name=signature]').value = canvas.toDataURL('image/png');
    });
};
