<?php
// QR code d'un atelier (§7) : affiche le lien de scan (et son jeton), avec possibilité de
// le régénérer. La génération d'une image QR imprimable dépend d'une bibliothèque externe
// (JS ou PHP) à choisir par l'équipe de développement (aucune n'est fournie par le socle
// Moodle) : cette page fournit en attendant le lien fonctionnel, testable directement et
// encodable dans n'importe quel générateur de QR code du marché.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\qrtoken;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageqrcodes', $context);

$id = required_param('id', PARAM_INT);
$atelier = new atelier($id);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'regenerer') {
    require_sesskey();
    qrtoken::regenerer($id);
    redirect(new moodle_url('/local/simhub/manage/atelier_qr.php', ['id' => $id]));
}

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/atelier_qr.php', ['id' => $id]));
$PAGE->set_pagelayout('admin');
$title = s($atelier->get('nomcourt'));
$PAGE->set_title($title);
$PAGE->set_heading($title);

echo $OUTPUT->header();

$qr = qrtoken::get_ou_creer($id);
$scanurl = new moodle_url('/local/simhub/qr.php', ['token' => $qr->token]);

echo html_writer::tag('p', get_string('qr_lien_intro', 'local_simhub'));
echo html_writer::tag('p', html_writer::link($scanurl, $scanurl->out(false)));

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'regenerer']);
echo html_writer::tag('button', get_string('qr_regenerer', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-outline-danger',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->notification(get_string('qr_pasdimage', 'local_simhub'), \core\output\notification::NOTIFY_INFO);

echo $OUTPUT->footer();
