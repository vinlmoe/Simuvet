<?php
// Validation ASV sur animal vivant (§9.3) : page publique accessible par lien à jeton, sans
// authentification Moodle, pour un vétérinaire, maître de stage ou encadrant autorisé.
// Niveau de preuve volontairement simple (nom, prénom, date, case de certification,
// signature au doigt) — jamais une signature électronique qualifiée (§14, hors périmètre).

require(__DIR__ . '/../../../config.php');

use local_simhub\record\asv_valanimal;
use local_simhub\persistent\asv_acte;

$token = required_param('token', PARAM_ALPHANUMEXT);

$PAGE->set_context(context_system::instance());
$PAGE->set_url(new moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $token]));
$PAGE->set_pagelayout('login');
$PAGE->set_title(get_string('asv_formulaire_validateur_titre', 'local_simhub'));
$PAGE->set_heading(get_string('asv_formulaire_validateur_titre', 'local_simhub'));

$demande = asv_valanimal::get_par_token($token);

echo $OUTPUT->header();

if (!$demande) {
    echo $OUTPUT->notification(get_string('asv_lien_invalide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
    echo $OUTPUT->footer();
    exit;
}

if ($demande->statut === asv_valanimal::STATUT_VALIDE) {
    echo $OUTPUT->notification(get_string('asv_valide_avec_succes', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS);
    echo $OUTPUT->footer();
    exit;
}

$acte = new asv_acte($demande->acteid);

$submitted = optional_param('submit', 0, PARAM_BOOL);
if ($submitted) {
    $nom = required_param('nom', PARAM_TEXT);
    $prenom = required_param('prenom', PARAM_TEXT);
    $certification = optional_param('certification', 0, PARAM_BOOL);
    $signature = required_param('signature', PARAM_RAW);

    $ok = asv_valanimal::valider($token, $nom, $prenom, (bool) $certification, $signature);
    if ($ok) {
        \local_simhub\event\asv_valide_animal::create([
            'objectid' => $demande->id,
            'context' => context_system::instance(),
            'relateduserid' => $demande->userid,
        ])->trigger();

        redirect($PAGE->url, get_string('asv_valide_avec_succes', 'local_simhub'), null,
            \core\output\notification::NOTIFY_SUCCESS);
    }
    echo $OUTPUT->notification(get_string('asv_validation_incomplete', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
}

echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));

echo html_writer::start_tag('form', ['method' => 'post', 'id' => 'local-simhub-valanimal-form']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'token', 'value' => s($token)]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('asv_champ_nom', 'local_simhub'));
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'nom', 'class' => 'form-control', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('asv_champ_prenom', 'local_simhub'));
echo html_writer::empty_tag('input', ['type' => 'text', 'name' => 'prenom', 'class' => 'form-control', 'required' => 'required']);
echo html_writer::end_div();

echo html_writer::start_div('form-check');
echo html_writer::empty_tag('input', [
    'type' => 'checkbox', 'name' => 'certification', 'value' => 1, 'id' => 'certification', 'class' => 'form-check-input',
    'required' => 'required',
]);
echo html_writer::tag('label', get_string('asv_champ_certification', 'local_simhub'), [
    'for' => 'certification', 'class' => 'form-check-label',
]);
echo html_writer::end_div();

echo html_writer::tag('label', get_string('asv_champ_signature', 'local_simhub'));
echo html_writer::tag('canvas', '', [
    'id' => 'local-simhub-signature-pad', 'width' => 400, 'height' => 150,
    'style' => 'border:1px solid #ccc;touch-action:none;max-width:100%;',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'signature', 'id' => 'local-simhub-signature-data']);
echo html_writer::tag('button', get_string('asv_signature_effacer', 'local_simhub'), ['type' => 'button', 'id' => 'local-simhub-signature-clear', 'class' => 'btn btn-secondary btn-sm ml-2']);

echo html_writer::tag('div', html_writer::tag('button', get_string('asv_valider_acte', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]), ['class' => 'mt-3']);

echo html_writer::end_tag('form');

// Signature au doigt minimaliste (§9.3) : capture souris/tactile sur <canvas>, encodée en
// PNG base64 dans le champ caché avant soumission. Pas de librairie tierce en V1.
echo html_writer::script("
(function() {
    var canvas = document.getElementById('local-simhub-signature-pad');
    var ctx = canvas.getContext('2d');
    var drawing = false;
    var signe = false;

    function pos(e) {
        var rect = canvas.getBoundingClientRect();
        var point = e.touches ? e.touches[0] : e;
        return { x: point.clientX - rect.left, y: point.clientY - rect.top };
    }
    function start(e) { drawing = true; var p = pos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); }
    function move(e) {
        if (!drawing) { return; }
        e.preventDefault();
        var p = pos(e);
        ctx.lineTo(p.x, p.y);
        ctx.stroke();
        signe = true;
    }
    function stop() { drawing = false; }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    canvas.addEventListener('mouseup', stop);
    canvas.addEventListener('touchstart', start);
    canvas.addEventListener('touchmove', move);
    canvas.addEventListener('touchend', stop);

    document.getElementById('local-simhub-signature-clear').addEventListener('click', function() {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        signe = false;
    });

    document.getElementById('local-simhub-valanimal-form').addEventListener('submit', function(e) {
        if (!signe) {
            e.preventDefault();
            window.alert(" . json_encode(get_string('asv_signature_requise', 'local_simhub')) . ");
            return;
        }
        document.getElementById('local-simhub-signature-data').value = canvas.toDataURL('image/png');
    });
})();
");

echo $OUTPUT->footer();
