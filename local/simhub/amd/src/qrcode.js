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
 * QR code d'un atelier, généré entièrement dans le navigateur (§7) : aucune donnée n'est
 * envoyée à un service externe.
 *
 * @module     local_simhub/qrcode
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import qrcode from 'local_simhub/qrcode_generator';

/**
 * Dessine le QR code et branche l'impression et le téléchargement.
 *
 * @param {string} url Adresse de scan de l'atelier.
 * @param {string} nomfichier Nom du fichier SVG téléchargé.
 */
export const init = (url, nomfichier) => {
    const qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();
    const svg = qr.createSvgTag(8, 16);
    document.getElementById('local-simhub-qr-image').innerHTML = svg;

    document.getElementById('local-simhub-qr-imprimer').addEventListener('click', () => window.print());

    document.getElementById('local-simhub-qr-telecharger').addEventListener('click', (e) => {
        e.preventDefault();
        const blobUrl = URL.createObjectURL(new Blob([svg], {type: 'image/svg+xml'}));
        const lien = document.createElement('a');
        lien.href = blobUrl;
        lien.download = nomfichier;
        document.body.appendChild(lien);
        lien.click();
        document.body.removeChild(lien);
        URL.revokeObjectURL(blobUrl);
    });
};
