<?php
// Validation ASV en simulation par un formateur/encadrant (§9.2). Formulaire minimal :
// étudiant + acte + atelier associé (optionnel). L'auto-évaluation guidée (§5.6) peut être
// une étape préparatoire, mais ne remplace jamais cette validation par un encadrant.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\persistent\atelier;
use local_simhub\record\asv_valsim;
use local_simhub\record\acte_atelier;

require_login();

$context = context_system::instance();
require_capability('local/simhub:validateasvsimulation', $context);

$atelierid = optional_param('atelierid', 0, PARAM_INT);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/asv/valider_simulation.php', $atelierid ? ['atelierid' => $atelierid] : []), get_string('asv_valider_simulation', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);
if ($atelierid) {
    \local_simhub\local\navigation::onglets('atelier', $atelierid, 'asv');
}

$envcode = get_config('local_simhub', 'envcode') ?: '';

// Si on arrive depuis un atelier précis, ne proposer que les actes qui s'y pratiquent
// réellement (liés via manage/asv_acte_edit.php) : ça évite de faire chercher l'acte dans
// tout le référentiel et fiabilise la validation (§9.2). Sans lien connu pour cet atelier,
// on retombe sur le référentiel complet plutôt que de bloquer la validation.
$actesatelier = $atelierid ? acte_atelier::get_actes_pour_atelier($atelierid) : [];
$actesreferentiel = empty($actesatelier) ? asv_acte::get_referentiel($envcode) : [];

// Normalisation en tableaux simples id/nom/niveau : get_actes_pour_atelier() renvoie des
// enregistrements bruts (jointure SQL) tandis que get_referentiel() renvoie des persistent,
// pour un affichage identique quelle que soit la source retenue ci-dessus.
$actes = [];
foreach (!empty($actesatelier) ? $actesatelier : $actesreferentiel as $acte) {
    $actes[] = is_object($acte) && method_exists($acte, 'get')
        ? ['id' => $acte->get('id'), 'nom' => $acte->get('nom'), 'niveau' => $acte->get('niveau')]
        : ['id' => $acte->id, 'nom' => $acte->nom, 'niveau' => $acte->niveau];
}

$submitted = optional_param('submit', 0, PARAM_BOOL);

if ($submitted) {
    require_sesskey();

    $userid = required_param('userid', PARAM_INT);
    $acteid = required_param('acteid', PARAM_INT);
    $atelierid = optional_param('atelierid', 0, PARAM_INT);

    core_user::require_active_user(core_user::get_user($userid, '*', MUST_EXIST));

    $extra = [];
    if ($atelierid) {
        $extra['atelierid'] = $atelierid;
    }

    $id = asv_valsim::valider($userid, $acteid, $USER->id, $extra);
    \local_simhub\event\asv_valide_simulation::create([
        'objectid' => $id,
        'context' => $context,
        'relateduserid' => $userid,
    ])->trigger();

    redirect(
        new moodle_url('/local/simhub/asv/index.php'),
        get_string('asv_validation_enregistree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('etudiant', 'local_simhub'), ['for' => 'id_userid']);
echo \local_simhub\local\selecteurs::etudiants('userid', 0, 'id_userid');
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('asv_acte', 'local_simhub'));
if ($atelierid && empty($actesatelier)) {
    echo html_writer::div(get_string('asv_aucun_acte_lie', 'local_simhub'), 'text-muted small mb-1');
}
echo html_writer::start_tag('select', ['name' => 'acteid', 'class' => 'form-control']);
foreach ($actes as $acte) {
    echo html_writer::tag('option', s($acte['nom']) . ' (' . s($acte['niveau']) . ')', ['value' => $acte['id']]);
}
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('asv_atelier_associe', 'local_simhub'), ['for' => 'id_atelierid']);
echo \local_simhub\local\selecteurs::ateliers('atelierid', $atelierid, 'id_atelierid');
echo html_writer::end_div();

echo html_writer::tag('button', get_string('asv_valider_simulation', 'local_simhub'), [
    'type' => 'submit', 'class' => 'btn btn-primary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
