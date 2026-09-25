<?php
// Attestation de certification globale de fin de A3 (§9.4 "Possibilité d'organiser une
// certification globale de fin de A3"). Un acte n'est considéré comme couvert que si les
// deux niveaux de validation du livret sont acquis — simulation et animal vivant (§9.1) —
// ce qui distingue cette attestation du simple export du livret (asv/livret_pdf.php), qui
// se contente de refléter l'état d'avancement sans se prononcer sur une certification.

require(__DIR__ . '/../../../config.php');
require_once($CFG->libdir . '/pdflib.php');

use local_simhub\persistent\asv_acte;
use local_simhub\local\pdf_helper;
use local_simhub\local\badge_helper;
use local_simhub\local\asv_certification_helper;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$userid = optional_param('userid', $USER->id, PARAM_INT);
if ($userid != $USER->id) {
    require_capability('local/simhub:manageasv', $context);
}

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);
$niveau = optional_param('niveau', 'A3', PARAM_ALPHANUM);

$user = \core_user::get_user($userid, '*', MUST_EXIST);
$actes = asv_acte::get_referentiel($envcode, $niveau);

if (empty($actes)) {
    redirect(
        new moodle_url('/local/simhub/asv/index.php'),
        get_string('asv_aucun_acte_niveau', 'local_simhub', $niveau),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

$manquants = asv_certification_helper::get_actes_manquants($userid, $niveau, $envcode);

if (!empty($manquants)) {
    $PAGE->set_context($context);
    $PAGE->set_url(new moodle_url('/local/simhub/asv/attestation_pdf.php', ['userid' => $userid, 'niveau' => $niveau]));
    $PAGE->set_pagelayout('standard');
    $PAGE->set_title(get_string('asv_certification_niveau', 'local_simhub', $niveau));
    $PAGE->set_heading(get_string('asv_certification_niveau', 'local_simhub', $niveau));

    echo $OUTPUT->header();
    echo $OUTPUT->notification(
        get_string('asv_certification_incomplete', 'local_simhub', (object) ['niveau' => $niveau, 'nom' => fullname($user), 'nb' => count($manquants)]),
        \core\output\notification::NOTIFY_WARNING
    );
    echo html_writer::start_tag('ul');
    foreach ($manquants as $m) {
        echo html_writer::tag('li', s($m));
    }
    echo html_writer::end_tag('ul');
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/asv/index.php'));
    echo $OUTPUT->footer();
    exit;
}

// Le niveau étant effectivement complet (vérifié ci-dessus), on délivre le badge Moodle
// configuré pour ce niveau, s'il y en a un (§13).
$badgeid = (int) (get_config('local_simhub', 'badgeasv' . strtolower($niveau)) ?: 0);
badge_helper::delivrer($badgeid ?: null, $userid);

$pdf = pdf_helper::construire_attestation_asv($user, $niveau, $actes);
$pdf->Output('simhub_certification_' . $niveau . '_' . $userid . '.pdf', 'D');
