<?php
// Fiche atelier côté étudiant (§5.4 localisation, §5.5 ressources).
//
// Page volontairement simple : deux blocs (localisation, ressources) affichés l'un après
// l'autre plutôt que des onglets JS, pour rester robuste sur mobile sans dépendance
// supplémentaire. Le paramètre "onglet" ne fait que faire défiler la page vers la bonne
// ancre (via #localisation / #ressources).

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;

require_login();

$context = context_system::instance();
require_capability('local/simhub:view', $context);

$id = required_param('id', PARAM_INT);
$onglet = optional_param('onglet', '', PARAM_ALPHA);

$atelier = new atelier($id);

\local_simhub\local\navigation::preparer($PAGE, new moodle_url('/local/simhub/atelier.php', ['id' => $id]), s($atelier->get('nomcourt')));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('p', s($atelier->get('descriptioncourte')));

echo html_writer::link(
    new moodle_url('/local/simhub/manage/atelier_fiche_pdf.php', ['id' => $id]),
    'Télécharger la fiche (PDF)',
    ['class' => 'btn btn-outline-secondary btn-sm mb-3']
);

echo html_writer::start_div('card mb-3', ['id' => 'localisation']);
echo html_writer::div(get_string('champ_salle', 'local_simhub'), 'card-header');
echo html_writer::start_div('card-body');
echo html_writer::tag('p', html_writer::tag('strong', get_string('champ_salle', 'local_simhub') . ' : ')
    . s($atelier->get('salle')));
if ($atelier->get('zone')) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('champ_zone', 'local_simhub') . ' : ')
        . s($atelier->get('zone')));
}
if ($atelier->get('codeposte')) {
    echo html_writer::tag('p', html_writer::tag('strong', get_string('champ_codeposte', 'local_simhub') . ' : ')
        . s($atelier->get('codeposte')));
}
if ($atelier->get('indicationtextuelle')) {
    echo html_writer::tag('p', s($atelier->get('indicationtextuelle')));
}
if ($atelier->get('planimageitemid')) {
    // Plan de salle sous forme d'image, avec repère éditable (§5.4) : approche pragmatique,
    // sans géolocalisation intérieure sophistiquée (hors périmètre V1, §14).
    $fs = get_file_storage();
    $planfiles = $fs->get_area_files(
        $context->id, 'local_simhub', 'plan', $atelier->get('planimageitemid'), 'filepath, filename', false
    );
    $planfile = reset($planfiles);

    if ($planfile) {
        $planurl = moodle_url::make_pluginfile_url(
            $context->id, 'local_simhub', 'plan', $atelier->get('planimageitemid'), '/', $planfile->get_filename()
        );
        echo html_writer::start_div('local-simhub-plan', ['style' => 'position:relative;display:inline-block;']);
        echo html_writer::empty_tag('img', ['src' => $planurl->out(false), 'style' => 'max-width:100%;']);
        if ($atelier->get('planrepx') !== null && $atelier->get('planrepy') !== null) {
            echo html_writer::span('', 'local-simhub-repere', [
                'style' => sprintf(
                    'position:absolute;left:%s%%;top:%s%%;width:14px;height:14px;border-radius:50%%;'
                    . 'background:red;transform:translate(-50%%,-50%%);',
                    $atelier->get('planrepx'),
                    $atelier->get('planrepy')
                ),
            ]);
        }
        echo html_writer::end_div();
    }
}
echo html_writer::end_div();
echo html_writer::end_div();

echo html_writer::start_div('card mb-3', ['id' => 'ressources']);
echo html_writer::div(get_string('bouton_ressources', 'local_simhub'), 'card-header');
echo html_writer::start_div('card-body');

$ressources = ressource::get_pour_etudiant($id);
if (empty($ressources)) {
    echo $OUTPUT->notification(get_string('aucun_atelier', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
} else {
    $fs = get_file_storage();
    echo html_writer::start_tag('ul', ['class' => 'list-unstyled']);
    foreach ($ressources as $r) {
        $href = $r->get('url');
        if (!$href && $r->get('fileitemid')) {
            $resfiles = $fs->get_area_files(
                $context->id, 'local_simhub', 'ressource', $r->get('fileitemid'), 'filepath, filename', false
            );
            $resfile = reset($resfiles);
            if ($resfile) {
                $href = moodle_url::make_pluginfile_url(
                    $context->id, 'local_simhub', 'ressource', $r->get('fileitemid'), '/', $resfile->get_filename()
                )->out(false);
            }
        }
        echo html_writer::tag('li', $href ? html_writer::link($href, s($r->get('titre'))) : s($r->get('titre')));
    }
    echo html_writer::end_tag('ul');
}
echo html_writer::end_div();
echo html_writer::end_div();

echo $OUTPUT->footer();
