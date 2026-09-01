<?php
// Démarrage / fin d'une session d'atelier par un étudiant (§7), avec auto-évaluation
// guidée à la fin (§5.6, §7.2). Volontairement une seule page à deux étapes plutôt qu'un
// tunnel complexe : démarrer redirige immédiatement vers la fiche, terminer affiche la
// grille d'auto-évaluation si l'atelier en a une, sinon clôture directement la session.

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\ae_rubrique;
use local_simhub\record\ae_critere;
use local_simhub\record\ae_reponse;
use local_simhub\record\ae_bilan;

require_login();

$context = context_system::instance();

$atelierid = required_param('atelierid', PARAM_INT);
$action = required_param('action', PARAM_ALPHA);
$sessionid = optional_param('sessionid', 0, PARAM_INT);

$atelier = new atelier($atelierid);

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/session.php', ['atelierid' => $atelierid, 'action' => $action]));
$PAGE->set_pagelayout('standard');
$PAGE->set_title(s($atelier->get('nomcourt')));
$PAGE->set_heading(s($atelier->get('nomcourt')));

if ($action === 'demarrer') {
    require_capability('local/simhub:startsession', $context);
    require_sesskey();

    $session = session::demarrer($USER->id, $atelierid, ['methodescan' => 'manuel']);
    \local_simhub\event\session_started::create([
        'objectid' => $session->get('id'),
        'context' => $context,
    ])->trigger();

    redirect(new moodle_url('/local/simhub/index.php'));
}

if ($action !== 'terminer') {
    throw new \moodle_exception('invalidaction', 'error');
}

require_capability('local/simhub:startsession', $context);

if (!$sessionid) {
    redirect(new moodle_url('/local/simhub/index.php'));
}

$session = new session($sessionid);
if ($session->get('userid') != $USER->id) {
    throw new \moodle_exception('nopermissions', 'error', '', 'session');
}

global $DB;
$modele = $DB->get_record('local_simhub_ae_modele', ['atelierid' => $atelierid, 'actif' => 1]);

$submitted = optional_param('submit_autoeval', 0, PARAM_BOOL);

if ($submitted) {
    require_sesskey();
    require_capability('local/simhub:submitautoeval', $context);

    if ($modele) {
        $rubriques = ae_rubrique::get_pour_modele($modele->id);
        foreach ($rubriques as $rubrique) {
            $criteres = ae_critere::get_pour_rubrique($rubrique->id);
            foreach ($criteres as $critere) {
                $niveau = optional_param('critere_' . $critere->id, '', PARAM_ALPHA);
                if ($niveau !== '') {
                    ae_reponse::repondre($sessionid, $critere->id, $niveau);
                }
            }
        }
    }

    $pointmaitrise = optional_param('pointmaitrise', '', PARAM_TEXT);
    $pointaretravailler = optional_param('pointaretravailler', '', PARAM_TEXT);
    $pointattention = optional_param('pointattention', '', PARAM_TEXT);
    if ($pointmaitrise !== '' || $pointaretravailler !== '' || $pointattention !== '') {
        ae_bilan::enregistrer($sessionid, $pointmaitrise, $pointaretravailler, $pointattention);
    }

    $session->terminer();
    \local_simhub\event\session_completed::create([
        'objectid' => $session->get('id'),
        'context' => $context,
    ])->trigger();

    redirect(
        new moodle_url('/local/simhub/index.php'),
        get_string('autoeval_enregistree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();

if (!$modele) {
    // Pas de grille associée à cet atelier : on clôture directement la session (§7.2, la
    // grille est optionnelle selon paramétrage). Ne termine (et ne déclenche l'événement)
    // qu'une seule fois : revisiter cette page ne doit pas réécrire timeend à chaque affichage.
    if ($session->get('statut') === session::STATUT_COMMENCE) {
        $session->terminer();
        \local_simhub\event\session_completed::create([
            'objectid' => $session->get('id'),
            'context' => $context,
        ])->trigger();
    }
    echo $OUTPUT->notification(get_string('session_terminee', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS);
    echo $OUTPUT->continue_button(new moodle_url('/local/simhub/index.php'));
    echo $OUTPUT->footer();
    exit;
}

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'terminer']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sessionid', 'value' => $sessionid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit_autoeval', 'value' => 1]);

echo html_writer::tag('h3', s($modele->titre));

$rubriques = ae_rubrique::get_pour_modele($modele->id);
foreach ($rubriques as $rubrique) {
    echo html_writer::start_tag('fieldset', ['class' => 'local-simhub-rubrique']);
    echo html_writer::tag('legend', s($rubrique->titre));

    $criteres = ae_critere::get_pour_rubrique($rubrique->id);
    foreach ($criteres as $critere) {
        echo html_writer::start_div('form-group');
        echo html_writer::tag('label', s($critere->libelle));
        echo html_writer::start_tag('div', ['class' => 'btn-group-toggle']);
        foreach (['reussi', 'a_consolider', 'a_reprendre'] as $niveau) {
            $id = 'critere_' . $critere->id . '_' . $niveau;
            echo html_writer::empty_tag('input', [
                'type' => 'radio', 'name' => 'critere_' . $critere->id, 'value' => $niveau, 'id' => $id,
            ]);
            echo html_writer::tag('label', get_string('niveau_' . $niveau, 'local_simhub'), ['for' => $id]);
        }
        echo html_writer::end_tag('div');
        echo html_writer::end_div();
    }
    echo html_writer::end_tag('fieldset');
}

echo html_writer::tag('h4', 'Auto-bilan');
foreach (['pointmaitrise', 'pointaretravailler', 'pointattention'] as $field) {
    echo html_writer::start_div('form-group');
    echo html_writer::tag('label', get_string('champ_' . $field, 'local_simhub'));
    echo html_writer::tag('textarea', '', ['name' => $field, 'class' => 'form-control', 'rows' => 2]);
    echo html_writer::end_div();
}

echo html_writer::tag('button', get_string('bouton_terminer', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
