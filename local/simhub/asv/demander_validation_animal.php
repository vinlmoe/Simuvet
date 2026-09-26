<?php
// L'étudiant génère un lien de validation animal vivant pour un acte déjà validé en
// simulation (§9.3), à transmettre au vétérinaire/maître de stage qui réalisera la
// validation via valider_animal.php, sans avoir besoin d'un compte Moodle.

require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$acteid = required_param('acteid', PARAM_INT);
$acte = new asv_acte($acteid);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acteid]), get_string('asv_demander_validation_animal', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// L'ordre du livret (§9.1) est imposé ici et pas seulement par l'affichage du lien : sans
// validation en simulation, aucune demande sur animal vivant ne peut être générée.
if (!in_array($acteid, \local_simhub\record\asv_valsim::get_actes_valides($USER->id))) {
    echo $OUTPUT->notification(get_string('asv_simulation_requise', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}

// Une seule demande active par acte : revenir sur cette page réaffiche le même lien au lieu
// d'en générer un nouveau à chaque visite.
$demande = asv_valanimal::get_ou_creer_demande($USER->id, $acteid);
$lien = new moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $demande->token]);

echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));
echo html_writer::tag('p', get_string('asv_lien_valanimal', 'local_simhub') . ' :');
echo html_writer::tag('p', html_writer::link($lien, $lien->out(false)));
echo html_writer::tag('p', get_string('asv_lien_expire_le', 'local_simhub',
    userdate($demande->tokenexpire, get_string('strftimedatetimeshort', 'langconfig'))));
echo html_writer::tag(
    'p',
    get_string('asv_transmettre_lien', 'local_simhub')
);

echo $OUTPUT->footer();
