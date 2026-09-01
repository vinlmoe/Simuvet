<?php
// File d'attente des sessions démarrées sans code de séance valide (§7.3), à valider
// manuellement par un encadrant — le filet de sécurité qui garantit que le contrôle
// anti-faux-scan n'est jamais bloquant de façon absolue pour l'étudiant.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\val_encadrant;

require_login();

$context = context_system::instance();
require_capability('local/simhub:validatesession', $context);

$action = optional_param('action', '', PARAM_ALPHA);
if ($action === 'valider' || $action === 'refuser') {
    require_sesskey();
    $sessionid = required_param('sessionid', PARAM_INT);

    $statut = $action === 'valider' ? val_encadrant::STATUT_VALIDE : val_encadrant::STATUT_REFUSE;
    val_encadrant::valider($sessionid, $USER->id, $statut);

    if ($statut === val_encadrant::STATUT_VALIDE) {
        $session = new session($sessionid);
        $session->set('statut', session::STATUT_CERTIFIE);
        $session->update();
    }

    redirect(new moodle_url('/local/simhub/manage/sessions_a_valider.php'));
}

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/sessions_a_valider.php'), get_string('sessions_a_valider', 'local_simhub'));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

global $DB;
$sessions = $DB->get_records_sql(
    "SELECT s.*
       FROM {local_simhub_session} s
      WHERE s.controlepresence = 'non_verifie'
        AND NOT EXISTS (SELECT 1 FROM {local_simhub_val_encadrant} v WHERE v.sessionid = s.id)
   ORDER BY s.timestart DESC"
);

if (empty($sessions)) {
    echo $OUTPUT->notification(get_string('sessions_aucune_a_valider', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
    echo $OUTPUT->footer();
    exit;
}

$table = new html_table();
$table->head = ['Étudiant', get_string('champ_nomcourt', 'local_simhub'), get_string('champ_statut', 'local_simhub'), 'Démarrée le', ''];

foreach ($sessions as $s) {
    $atelier = new atelier($s->atelierid);
    $user = \core_user::get_user($s->userid);

    $validerurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php', [
        'action' => 'valider', 'sessionid' => $s->id, 'sesskey' => sesskey(),
    ]);
    $refuserurl = new moodle_url('/local/simhub/manage/sessions_a_valider.php', [
        'action' => 'refuser', 'sessionid' => $s->id, 'sesskey' => sesskey(),
    ]);

    $table->data[] = [
        $user ? fullname($user) : '#' . $s->userid,
        s($atelier->get('nomcourt')),
        get_string('statutperso_' . ($s->statut === session::STATUT_COMMENCE ? 'commence' : 'realise'), 'local_simhub'),
        userdate($s->timestart, get_string('strftimedatetimeshort', 'langconfig')),
        html_writer::link($validerurl, get_string('session_valider', 'local_simhub'), ['class' => 'btn btn-sm btn-success mr-1'])
            . html_writer::link($refuserurl, get_string('session_refuser', 'local_simhub'), ['class' => 'btn btn-sm btn-outline-danger']),
    ];
}

echo html_writer::table($table);

echo $OUTPUT->footer();
