<?php
// Tableau de bord "responsable de salle" (§12.2) : ateliers actifs, en maintenance, sans
// ressource, sans rattachement UC, peu utilisés — au-delà de la simple liste de
// manage/ateliers.php, pour repérer d'un coup d'œil ce qui mérite une action (compléter
// une fiche, relancer une maintenance, retirer un atelier qui ne sert jamais).

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\record\indispo;

require_login();

$context = context_system::instance();
require_capability('local/simhub:manageateliers', $context);

$envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

// Seuil arbitraire mais assumé : un atelier actif avec moins de ce nombre de sessions
// enregistrées est considéré comme peu utilisé, à discuter avec les responsables de salle
// une fois de premières données réelles disponibles (§15).
$seuilpeuutilise = 3;

$PAGE->set_context($context);
$PAGE->set_url(new moodle_url('/local/simhub/manage/dashboard_salle.php'));
$PAGE->set_pagelayout('admin');
$PAGE->set_title(get_string('dashboard_salle', 'local_simhub'));
$PAGE->set_heading(get_string('dashboard_salle', 'local_simhub'));

echo $OUTPUT->header();

global $DB;

$params = $envcode !== '' ? ['envcode' => $envcode] : [];
$ateliers = atelier::get_records($params);

$parstatut = [
    atelier::STATUT_ACTIF => [],
    atelier::STATUT_INDISPONIBLE => [],
    atelier::STATUT_NON_UTILISE => [],
    atelier::STATUT_ARCHIVE => [],
];
$sansressource = [];
$sansuc = [];
$peuutilises = [];

$atelierids = array_map(fn($a) => $a->get('id'), $ateliers);
$ressourcecounts = [];
$rattachementuc = [];
$sessioncounts = [];

if (!empty($atelierids)) {
    [$insql, $inparams] = $DB->get_in_or_equal($atelierids);

    $ressourcecounts = $DB->get_records_sql(
        "SELECT atelierid, COUNT(*) AS nb FROM {local_simhub_ressource} WHERE atelierid $insql GROUP BY atelierid",
        $inparams
    );

    $rattachementuc = $DB->get_records_sql(
        "SELECT DISTINCT atelierid, 1 AS present
           FROM {local_simhub_rattachement}
          WHERE courseid IS NOT NULL AND atelierid $insql",
        $inparams
    );

    $sessioncounts = $DB->get_records_sql(
        "SELECT atelierid, COUNT(*) AS nb FROM {local_simhub_session} WHERE atelierid $insql GROUP BY atelierid",
        $inparams
    );
}

foreach ($ateliers as $atelier) {
    $id = $atelier->get('id');
    $parstatut[$atelier->get('statut')][] = $atelier;

    if (empty($ressourcecounts[$id])) {
        $sansressource[] = $atelier;
    }
    if (empty($rattachementuc[$id])) {
        $sansuc[] = $atelier;
    }
    if ($atelier->get('statut') === atelier::STATUT_ACTIF && (int) ($sessioncounts[$id]->nb ?? 0) < $seuilpeuutilise) {
        $peuutilises[] = $atelier;
    }
}

/**
 * Affiche une tuile de statistique simple.
 *
 * @param string $label
 * @param int $valeur
 * @param string $couleur Couleur Bootstrap (bg-*).
 * @return string
 */
function local_simhub_dashboard_tuile(string $label, int $valeur, string $couleur = 'bg-light'): string {
    return html_writer::div(
        html_writer::tag('div', $valeur, ['style' => 'font-size:1.8rem;font-weight:bold;'])
        . html_writer::tag('div', $label, ['style' => 'font-size:0.85rem;']),
        $couleur,
        ['style' => 'display:inline-block;min-width:150px;padding:12px;margin:0 8px 8px 0;border-radius:6px;text-align:center;']
    );
}

echo html_writer::start_div('mb-4');
echo local_simhub_dashboard_tuile(get_string('statut_actif', 'local_simhub'), count($parstatut[atelier::STATUT_ACTIF]), 'bg-success text-white');
echo local_simhub_dashboard_tuile(get_string('statut_indisponible', 'local_simhub'), count($parstatut[atelier::STATUT_INDISPONIBLE]), 'bg-warning');
echo local_simhub_dashboard_tuile(get_string('statut_non_utilise', 'local_simhub'), count($parstatut[atelier::STATUT_NON_UTILISE]), 'bg-light');
echo local_simhub_dashboard_tuile(get_string('statut_archive', 'local_simhub'), count($parstatut[atelier::STATUT_ARCHIVE]), 'bg-light');
echo local_simhub_dashboard_tuile(get_string('dashboard_sansressource', 'local_simhub'), count($sansressource), 'bg-info text-white');
echo local_simhub_dashboard_tuile(get_string('dashboard_sansuc', 'local_simhub'), count($sansuc), 'bg-info text-white');
echo local_simhub_dashboard_tuile(get_string('dashboard_peuutilise', 'local_simhub'), count($peuutilises), 'bg-secondary text-white');
echo html_writer::end_div();

/**
 * Affiche une section listant des ateliers, avec lien direct vers leur gestion.
 *
 * @param string $titre
 * @param atelier[] $liste
 * @return void
 */
function local_simhub_dashboard_section(string $titre, array $liste): void {
    if (empty($liste)) {
        return;
    }
    echo html_writer::tag('h4', $titre);
    echo html_writer::start_tag('ul');
    foreach ($liste as $atelier) {
        $editurl = new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelier->get('id')]);
        echo html_writer::tag('li', html_writer::link($editurl, '#' . s($atelier->get('numero')) . ' — ' . s($atelier->get('nomcourt'))));
    }
    echo html_writer::end_tag('ul');
}

local_simhub_dashboard_section(get_string('statut_indisponible', 'local_simhub'), $parstatut[atelier::STATUT_INDISPONIBLE]);
local_simhub_dashboard_section(get_string('dashboard_sansressource', 'local_simhub'), $sansressource);
local_simhub_dashboard_section(get_string('dashboard_sansuc', 'local_simhub'), $sansuc);
local_simhub_dashboard_section(get_string('dashboard_peuutilise', 'local_simhub'), $peuutilises);

echo $OUTPUT->footer();
