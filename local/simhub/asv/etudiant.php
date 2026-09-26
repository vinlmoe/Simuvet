<?php
// Fiche ASV d'un étudiant côté encadrant (§9.4) : état acte par acte, avancement vers les
// certifications, livret, et annulation d'une validation saisie par erreur.

require(__DIR__ . '/../../../config.php');

use local_simhub\local\asv_vue;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

require_login();

$context = context_system::instance();
if (!has_capability('local/simhub:validateasvsimulation', $context)) {
    require_capability('local/simhub:manageasv', $context);
}

$userid = required_param('userid', PARAM_INT);
$action = optional_param('action', '', PARAM_ALPHA);
$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

$user = \core_user::get_user($userid, '*', MUST_EXIST);
$url = new moodle_url('/local/simhub/asv/etudiant.php', ['userid' => $userid]);

\local_simhub\local\navigation::preparer($PAGE, $url, s(fullname($user)), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

if ($action === 'annulersim' || $action === 'annuleranimal') {
    $id = required_param('id', PARAM_INT);
    $table = $action === 'annulersim' ? asv_valsim::TABLE : asv_valanimal::TABLE;
    require_capability($action === 'annulersim' ? 'local/simhub:validateasvsimulation' : 'local/simhub:manageasv', $context);
    $validation = $DB->get_record($table, ['id' => $id, 'userid' => $userid, 'statut' => 'valide'], '*', MUST_EXIST);

    if (optional_param('confirmer', 0, PARAM_BOOL)) {
        require_sesskey();
        $motif = trim(optional_param('motif', '', PARAM_TEXT));
        if ($motif === '') {
            redirect(new moodle_url($url, ['action' => $action, 'id' => $id]),
                get_string('asv_motif_obligatoire', 'local_simhub'), null, \core\output\notification::NOTIFY_ERROR);
        }
        $motif = get_string('asv_annule_par', 'local_simhub', (object) [
            'nom' => fullname($USER), 'date' => userdate(time(), get_string('strftimedatefullshort', 'langconfig')),
            'motif' => $motif,
        ]);
        if ($action === 'annulersim') {
            asv_valsim::annuler($id, $motif);
        } else {
            asv_valanimal::annuler($id);
        }
        redirect($url, get_string('asv_validation_annulee', 'local_simhub'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo \local_simhub\local\navigation::barre();
    $acte = new \local_simhub\persistent\asv_acte($validation->acteid);
    echo $OUTPUT->notification(get_string('asv_confirmer_annulation', 'local_simhub', (object) [
        'acte' => s($acte->get('nom')), 'etudiant' => s(fullname($user)),
    ]), \core\output\notification::NOTIFY_WARNING);

    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $url->out(false)]);
    foreach (['sesskey' => sesskey(), 'action' => $action, 'id' => $id, 'confirmer' => 1] as $name => $value) {
        echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
    }
    echo html_writer::tag('label', get_string('asv_motif', 'local_simhub'), ['for' => 'id_motif']);
    echo html_writer::tag('textarea', '', ['name' => 'motif', 'id' => 'id_motif', 'class' => 'form-control mb-2',
        'required' => 'required', 'rows' => 2]);
    echo html_writer::tag('button', get_string('asv_confirmer', 'local_simhub'), ['type' => 'submit', 'class' => 'btn btn-danger mr-2 me-2']);
    echo html_writer::link($url, get_string('cancel'), ['class' => 'btn btn-secondary']);
    echo html_writer::end_tag('form');
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::div(
    html_writer::link(new moodle_url('/local/simhub/asv/livret_pdf.php', ['userid' => $userid, 'envcode' => $envcode]),
        get_string('asv_exporter_livret', 'local_simhub'), ['class' => 'btn btn-outline-secondary btn-sm mr-2 me-2'])
    . (has_capability('local/simhub:validateasvsimulation', $context)
        ? html_writer::link(new moodle_url('/local/simhub/asv/valider_simulation.php', ['userid' => $userid]),
            get_string('asv_valider_simulation', 'local_simhub'), ['class' => 'btn btn-primary btn-sm'])
        : ''),
    'mb-3'
);

echo asv_vue::certifications($userid, $envcode);
echo asv_vue::tableau($userid, $envcode, true);

echo $OUTPUT->footer();
