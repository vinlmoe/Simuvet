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
 * Point d'entrée du QR code d'un atelier (§7) : relie l'objet physique à sa fiche
 * numérique et démarre une session de réalisation.
 *
 * Contrôle anti-faux-scan (§7.3) : si local_simhub/controlepresenceactif est désactivé, la
 * session démarre directement (le scan depuis un téléphone connecté avec un vrai compte
 * Moodle est déjà un premier filtre). Si activé, l'étudiant est renvoyé vers
 * session_code.php pour saisir le code de séance affiché en salle avant que la session ne
 * soit réellement créée — jamais bloquant de façon absolue : l'étudiant peut toujours
 * continuer sans code et laisser la réalisation en attente de validation par un encadrant
 * (§7.3 "le contrôle ne doit pas être bloquant de manière absolue").
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\record\qrtoken;
use local_simhub\record\seancecode;
use local_simhub\persistent\session;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$token = required_param('token', PARAM_ALPHANUMEXT);

$qr = qrtoken::get_par_token($token);
if (!$qr) {
    throw new \moodle_exception('invalidtoken', 'error');
}

if (!has_capability('local/simhub:startsession', $context)) {
    redirect(new moodle_url('/local/simhub/atelier.php', ['id' => $qr->atelierid]));
}

// Sur le réseau de la salle, la présence est vérifiée sans code (§7.3).
if (seancecode::est_requis() && !session::en_cours($USER->id, $qr->atelierid)) {
    redirect(new moodle_url('/local/simhub/session_code.php', ['atelierid' => $qr->atelierid]));
}
$dansalle = get_config('local_simhub', 'controlepresenceactif') && \local_simhub\local\reseau::dans_la_salle();

session::demarrer_ou_reprendre($USER->id, $qr->atelierid, [
    'methodescan' => 'qr',
    'controlepresence' => $dansalle ? 'reseau_local' : null,
]);

redirect(new moodle_url('/local/simhub/atelier.php', ['id' => $qr->atelierid]));
