<?php
// Composition d'un parcours (§8) : ajouter/retirer des ateliers, définir leur ordre,
// leur caractère obligatoire et une échéance pédagogique optionnelle.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\parcours;
use local_simhub\persistent\atelier;
use local_simhub\record\parc_atelier;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageparcours', $context);

$parcoursid = required_param('parcoursid', PARAM_INT);
$parcours = new parcours($parcoursid);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]), s($parcours->get('nom')), [
    [get_string('filtre_parcours', 'local_simhub'), new moodle_url('/local/simhub/manage/parcours.php')],
]);
\local_simhub\local\navigation::onglets('parcours', $parcoursid, 'ateliers');

$action = optional_param('action', '', PARAM_ALPHA);

if ($action === 'ajouter') {
    require_sesskey();
    $atelierid = required_param('atelierid', PARAM_INT);
    $ordre = optional_param('ordre', 0, PARAM_INT);
    $obligatoire = optional_param('obligatoire', 0, PARAM_BOOL);
    $echeancedate = optional_param('echeance', '', PARAM_TEXT);
    $echeance = $echeancedate !== '' ? strtotime($echeancedate) : 0;

    parc_atelier::ajouter($parcoursid, $atelierid, $ordre, (bool) $obligatoire, $echeance ?: null);

    redirect(new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]));
} else if ($action === 'retirer') {
    require_sesskey();
    $atelierid = required_param('atelierid', PARAM_INT);

    parc_atelier::retirer($parcoursid, $atelierid);

    redirect(new moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid]));
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('h3', get_string('parcours_ateliers_titre', 'local_simhub'));

$composition = $parcours->get_ateliers();

$table = new html_table();
$table->head = [get_string('champ_nomcourt', 'local_simhub'), 'Ordre', 'Obligatoire', 'Échéance', ''];
foreach ($composition as $lien) {
    $atelier = new atelier($lien->atelierid);
    $removeurl = new moodle_url('/local/simhub/manage/parcours_ateliers.php', [
        'parcoursid' => $parcoursid, 'action' => 'retirer', 'atelierid' => $lien->atelierid, 'sesskey' => sesskey(),
    ]);
    $table->data[] = [
        s($atelier->get('nomcourt')),
        $lien->ordre,
        $lien->obligatoire ? get_string('yes') : get_string('no'),
        $lien->echeance ? userdate($lien->echeance, get_string('strftimedate', 'langconfig')) : '',
        html_writer::link($removeurl, get_string('retirer', 'local_simhub')),
    ];
}
echo html_writer::table($table);

echo html_writer::tag('h3', get_string('parcours_ajouter_atelier', 'local_simhub'));

$envcode = get_config('local_simhub', 'envcode') ?: '';
$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$ateliers = atelier::get_records($params, 'nomcourt');
$dejadans = array_column($composition, 'atelierid');

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'parcoursid', 'value' => $parcoursid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => 'ajouter']);

echo html_writer::start_tag('select', ['name' => 'atelierid', 'class' => 'form-control d-inline-block w-auto mr-2']);
foreach ($ateliers as $atelier) {
    if (in_array($atelier->get('id'), $dejadans, true)) {
        continue;
    }
    echo html_writer::tag('option', s($atelier->get('nomcourt')), ['value' => $atelier->get('id')]);
}
echo html_writer::end_tag('select');

echo html_writer::empty_tag('input', [
    'type' => 'number', 'name' => 'ordre', 'placeholder' => get_string('ordre', 'local_simhub'), 'class' => 'form-control d-inline-block w-auto mr-2',
]);

echo html_writer::empty_tag('input', [
    'type' => 'date', 'name' => 'echeance', 'title' => get_string('echeance_optionnel', 'local_simhub'),
    'class' => 'form-control d-inline-block w-auto mr-2',
]);

echo html_writer::start_tag('label', ['class' => 'mr-2']);
echo html_writer::empty_tag('input', ['type' => 'checkbox', 'name' => 'obligatoire', 'value' => 1]);
echo ' Obligatoire';
echo html_writer::end_tag('label');

echo html_writer::tag('button', get_string('add'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
