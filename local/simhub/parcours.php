<?php
// Parcours côté étudiant (§8) : ateliers dans l'ordre, obligatoires, échéances, statut
// personnel et avancement (§8.1), avec l'attestation une fois le parcours terminé.

require(__DIR__ . '/../../config.php');

use local_simhub\local\parcours_helper;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$id = required_param('id', PARAM_INT);
$parcours = new parcours($id);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/parcours.php', ['id' => $id]),
    s($parcours->get('nom')));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($parcours->get('description')) {
    echo html_writer::tag('p', s($parcours->get('description')));
}

$progression = parcours_helper::progression($parcours, $USER->id);
echo html_writer::tag('p', html_writer::tag('strong', get_string('avancement', 'local_simhub') . ' : ')
    . get_string('parcours_avancement', 'local_simhub', (object) $progression));
echo html_writer::div(html_writer::div('', 'progress-bar', [
    'role' => 'progressbar', 'style' => 'width:' . $progression['pct'] . '%',
    'aria-valuenow' => $progression['pct'], 'aria-valuemin' => 0, 'aria-valuemax' => 100,
]), 'progress mb-3');

if ($progression['total'] && $progression['pct'] >= 100) {
    echo html_writer::div(html_writer::link(
        new moodle_url('/local/simhub/manage/parcours_attestation_pdf.php', ['parcoursid' => $id]),
        get_string('attestation_pdf', 'local_simhub'), ['class' => 'btn btn-primary']), 'mb-3');
}

$requis = parcours_helper::ateliers_requis($parcours);
$composition = $parcours->get_ateliers();
$format = get_string('strftimedatefullshort', 'langconfig');

$table = new html_table();
$table->head = ['#', get_string('atelier', 'local_simhub'), get_string('parcours_col_requis', 'local_simhub'),
    get_string('parcours_col_echeance', 'local_simhub'), get_string('champ_statut', 'local_simhub')];
$rang = 0;
foreach ($composition as $lien) {
    $atelier = new atelier($lien->atelierid);
    $statut = parcours_helper::statut_atelier($USER->id, (int) $lien->atelierid);
    $fait = in_array($statut, ['realise', 'valide'], true);

    $echeance = '—';
    if ($lien->echeance) {
        $echeance = userdate($lien->echeance, $format);
        if (!$fait && $lien->echeance < time()) {
            $echeance = html_writer::span($echeance . ' — ' . get_string('parcours_en_retard', 'local_simhub'), 'text-danger');
        }
    }

    $libelleatelier = html_writer::link(new moodle_url('/local/simhub/atelier.php', ['id' => $lien->atelierid]),
        s($atelier->get('nomcourt')));
    if ($atelier->get('statut') !== atelier::STATUT_ACTIF) {
        $libelleatelier .= ' ' . html_writer::span(get_string('statut_' . $atelier->get('statut'), 'local_simhub'),
            'badge badge-warning bg-warning text-dark');
    }

    $table->data[] = [
        ++$rang,
        $libelleatelier,
        in_array((int) $lien->atelierid, $requis, true) ? get_string('yes') : get_string('no'),
        $echeance,
        get_string('statutperso_' . $statut, 'local_simhub'),
    ];
}
echo html_writer::table($table);

echo $OUTPUT->footer();
