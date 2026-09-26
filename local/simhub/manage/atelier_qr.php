<?php
// QR code d'un atelier (§7) : affiche le lien de scan, son jeton, et une image QR
// imprimable générée entièrement côté navigateur (bibliothèque JS vendorisée
// js/vendor/qrcode.js, aucun appel réseau externe ni service tiers), avec possibilité de
// régénérer le jeton.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\qrtoken;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:manageqrcodes', $context);

$id = required_param('id', PARAM_INT);
$atelier = new atelier($id);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'regenerer') {
    require_sesskey();
    qrtoken::regenerer($id);
    redirect(new moodle_url('/local/simhub/manage/atelier_qr.php', ['id' => $id]));
}

$title = get_string('nav_qrcode', 'local_simhub');
\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/atelier_qr.php', ['id' => $id]), $title, [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $id])],
]);
\local_simhub\local\navigation::onglets('atelier', $id, 'qr');

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// La bibliothèque est intégrée directement dans la page (plutôt que via
// $PAGE->requires->js()) pour garantir qu'elle est définie avant le script d'utilisation
// ci-dessous, sans dépendre de l'ordre d'injection des scripts du thème Moodle.
echo html_writer::script(file_get_contents(__DIR__ . '/../js/vendor/qrcode.js'));

$qr = qrtoken::get_ou_creer($id);
$scanurl = new moodle_url('/local/simhub/qr.php', ['token' => $qr->token]);

echo html_writer::tag('p', get_string('qr_lien_intro', 'local_simhub'));
echo html_writer::tag('p', html_writer::link($scanurl, $scanurl->out(false)));

echo html_writer::start_div('local-simhub-qr-bloc', ['id' => 'local-simhub-qr-bloc']);
echo html_writer::div('', '', ['id' => 'local-simhub-qr-image']);
echo html_writer::tag('p', s($atelier->get('numero')) . ' — ' . s($atelier->get('nomcourt')), [
    'id' => 'local-simhub-qr-legende', 'style' => 'text-align:center;font-weight:bold;',
]);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('qr_imprimer', 'local_simhub'), [
    'type' => 'button', 'id' => 'local-simhub-qr-imprimer', 'class' => 'btn btn-primary mr-2',
]);
echo html_writer::link('#', get_string('qr_telecharger', 'local_simhub'), [
    'id' => 'local-simhub-qr-telecharger', 'class' => 'btn btn-outline-secondary',
]);

echo html_writer::start_tag('form', ['method' => 'post', 'class' => 'mt-3']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'id', 'value' => $id]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'regenerer']);
echo html_writer::tag('button', get_string('qr_regenerer', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-outline-danger',
]);
echo html_writer::end_tag('form');

// Génération et rendu de l'image QR intégralement côté navigateur : aucune donnée n'est
// envoyée à un service externe, aucune dépendance réseau au moment de l'impression.
echo html_writer::script("
(function() {
    var url = " . json_encode($scanurl->out(false)) . ";
    var qr = qrcode(0, 'M');
    qr.addData(url);
    qr.make();

    var container = document.getElementById('local-simhub-qr-image');
    var svg = qr.createSvgTag(8, 16);
    container.innerHTML = svg;

    document.getElementById('local-simhub-qr-imprimer').addEventListener('click', function() {
        window.print();
    });

    var lien = document.getElementById('local-simhub-qr-telecharger');
    lien.addEventListener('click', function(e) {
        e.preventDefault();
        var blob = new Blob([svg], {type: 'image/svg+xml'});
        var blobUrl = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = blobUrl;
        a.download = 'qr-" . $atelier->get('numero') . ".svg';
        document.body.appendChild(a);
        a.click();
        document.body.removeChild(a);
        URL.revokeObjectURL(blobUrl);
    });
})();
");

echo html_writer::tag('style', '
    @media print {
        body * { visibility: hidden; }
        #local-simhub-qr-bloc, #local-simhub-qr-bloc * { visibility: visible; }
        #local-simhub-qr-bloc { position: absolute; top: 0; left: 0; }
    }
');

echo $OUTPUT->footer();
