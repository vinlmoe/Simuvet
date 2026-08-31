<?php
// Point d'entrée du QR code d'un atelier (§7) : relie l'objet physique à sa fiche
// numérique et démarre une session de réalisation.
//
// Contrôle anti-faux-scan (§7.3) : si local_simhub/controlepresenceactif est désactivé, la
// session démarre directement (le scan depuis un téléphone connecté avec un vrai compte
// Moodle est déjà un premier filtre). Si activé, l'étudiant est renvoyé vers
// session_code.php pour saisir le code de séance affiché en salle avant que la session ne
// soit réellement créée — jamais bloquant de façon absolue : l'étudiant peut toujours
// continuer sans code et laisser la réalisation en attente de validation par un encadrant
// (§7.3 "le contrôle ne doit pas être bloquant de manière absolue").

require(__DIR__ . '/../../config.php');

use local_simhub\record\qrtoken;
use local_simhub\persistent\session;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$token = required_param('token', PARAM_ALPHANUMEXT);

$qr = qrtoken::get_par_token($token);
if (!$qr) {
    throw new \moodle_exception('invalidtoken', 'error');
}

if (!has_capability('local/simhub:startsession', $context)) {
    redirect(new moodle_url('/local/simhub/atelier.php', ['id' => $qr->atelierid]));
}

if (get_config('local_simhub', 'controlepresenceactif')) {
    redirect(new moodle_url('/local/simhub/session_code.php', ['atelierid' => $qr->atelierid]));
}

session::demarrer($USER->id, $qr->atelierid, [
    'methodescan' => 'qr',
    'controlepresence' => null,
]);

redirect(new moodle_url('/local/simhub/atelier.php', ['id' => $qr->atelierid]));
