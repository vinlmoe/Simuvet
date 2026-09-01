<?php
// L'étudiant génère un lien de validation animal vivant pour un acte déjà validé en
// simulation (§9.3), à transmettre au vétérinaire/maître de stage qui réalisera la
// validation via valider_animal.php, sans avoir besoin d'un compte Moodle.

require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$acteid = required_param('acteid', PARAM_INT);
$acte = new asv_acte($acteid);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acteid]), get_string('asv_demander_validation_animal', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

$demande = asv_valanimal::creer_demande($USER->id, $acteid);
$lien = new moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $demande->token]);

echo html_writer::tag('p', 'Acte : ' . s($acte->get('nom')));
echo html_writer::tag('p', get_string('asv_lien_valanimal', 'local_simhub') . ' :');
echo html_writer::tag('p', html_writer::link($lien, $lien->out(false)));
echo html_writer::tag(
    'p',
    'Transmettez ce lien au vétérinaire, maître de stage ou encadrant autorisé qui a supervisé le geste sur animal vivant.'
);

echo $OUTPUT->footer();
