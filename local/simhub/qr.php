<?php
// Point d'entrée du QR code d'un atelier (§7) : relie l'objet physique à sa fiche
// numérique et démarre directement une session de réalisation.
//
// Contrôle anti-faux-scan (§7.3) : en V1, ce point d'entrée se contente d'exiger une
// authentification Moodle (donc un vrai scan depuis le téléphone connecté de l'étudiant,
// pas un lien transmis à distance sans compte). Le renforcement par réseau local / code de
// séance / validation encadrant reste paramétrable via local_simhub/controlepresenceactif
// et sera branché ici une fois l'infrastructure réseau des salles confirmée par les ENV.

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

if (has_capability('local/simhub:startsession', $context)) {
    $controlepresence = get_config('local_simhub', 'controlepresenceactif') ? 'non_verifie' : null;
    session::demarrer($USER->id, $qr->atelierid, [
        'methodescan' => 'qr',
        'controlepresence' => $controlepresence,
    ]);
}

redirect(new moodle_url('/local/simhub/atelier.php', ['id' => $qr->atelierid]));
