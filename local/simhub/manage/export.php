<?php
// Exports CSV de base (§12.3) : liste des ateliers, ou suivi de progression d'un parcours.
// Reste volontairement simple (CSV natif, pas de XLSX) : un tableur ouvre un CSV sans
// dépendance supplémentaire, et l'export sert surtout d'échange ponctuel entre équipes.

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\persistent\session;

require_login();

$context = context_system::instance();

$type = required_param('type', PARAM_ALPHA);

/**
 * Écrit un tableau de lignes en CSV directement vers la sortie, puis termine la requête.
 *
 * @param string $filename
 * @param array $rows Première ligne = en-têtes.
 * @return void
 */
function local_simhub_export_csv(string $filename, array $rows): void {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');

    // BOM UTF-8 : Excel (souvent utilisé par les ENV, §12.1) n'affiche correctement les
    // accents sans ticket que si le fichier commence par cette marque.
    echo "\xEF\xBB\xBF";

    $out = fopen('php://output', 'w');
    foreach ($rows as $row) {
        fputcsv($out, $row, ';', '"', '');
    }
    fclose($out);
    exit;
}

if ($type === 'ateliers') {
    require_capability('local/simhub:exportsuivi', $context);

    $envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);
    $params = $envcode !== '' ? ['envcode' => $envcode] : [];
    $ateliers = atelier::get_records($params, 'nomcourt');

    $rows = [[
        'numero', 'nomcourt', 'discipline', 'espece', 'niveaudifficulte', 'dureeindicative',
        'statut', 'envcode', 'salle', 'zone', 'codeposte',
    ]];
    foreach ($ateliers as $a) {
        $rows[] = [
            $a->get('numero'), $a->get('nomcourt'), $a->get('discipline'), $a->get('espece'),
            $a->get('niveaudifficulte'), $a->get('dureeindicative'), $a->get('statut'), $a->get('envcode'),
            $a->get('salle'), $a->get('zone'), $a->get('codeposte'),
        ];
    }

    local_simhub_export_csv('simhub_ateliers.csv', $rows);
} else if ($type === 'parcours') {
    require_capability('local/simhub:viewprogression', $context);

    $parcoursid = required_param('parcoursid', PARAM_INT);
    $parcours = new parcours($parcoursid);

    global $DB;
    $composition = $parcours->get_ateliers();
    $atelierids = array_column($composition, 'atelierid');

    $users = \local_simhub\local\parcours_helper::etudiants($parcours);

    $head = ['etudiant'];
    $ateliernoms = [];
    foreach ($atelierids as $aid) {
        $a = new atelier($aid);
        $ateliernoms[$aid] = $a->get('nomcourt');
        $head[] = $a->get('nomcourt');
    }
    $head[] = 'avancement_pct';
    $rows = [$head];

    foreach ($users as $user) {
        $row = [fullname($user)];
        $realises = 0;
        foreach ($atelierids as $aid) {
            $sessions = session::get_pour_etudiant($user->id, $aid);
            $latest = $sessions ? reset($sessions) : null;
            if (!$latest) {
                $row[] = '';
            } else if ($latest->get('statut') === session::STATUT_CERTIFIE) {
                $row[] = 'valide';
                $realises++;
            } else if ($latest->get('statut') === session::STATUT_REALISE) {
                $row[] = 'realise';
                $realises++;
            } else {
                $row[] = 'commence';
            }
        }
        $row[] = \local_simhub\local\parcours_helper::progression($parcours, $user->id)['pct'];
        $rows[] = $row;
    }

    local_simhub_export_csv('simhub_parcours_' . $parcoursid . '.csv', $rows);
} else {
    throw new \moodle_exception('invalidaction', 'error');
}
