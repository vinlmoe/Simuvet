<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Import souple (§12.1) : les tableaux de suivi existants des quatre ENV peuvent être
 * hétérogènes et imparfaits. Cette page alimente une première fois la base à partir d'un
 * CSV — ateliers, rattachements UC/année/cohorte, ou composition de parcours — à charge
 * pour le gestionnaire de corriger/enrichir ensuite via les pages de gestion dédiées.
 * Les rattachements et parcours référencent les ateliers par (envcode, numero) : importer
 * d'abord les ateliers avant d'importer le reste.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\form\import_form;
use local_simhub\local\atelier_importer;
use local_simhub\local\liaison_importer;
use local_simhub\local\tableur;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:importexport', $context);

$pageurl = new moodle_url('/local/simhub/manage/import.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('import_ateliers', 'local_simhub'));

$form = new import_form($pageurl);
$result = null;
$type = 'ateliers';

if ($data = $form->get_data()) {
    $type = in_array($data->type, import_form::TYPES, true) ? $data->type : 'ateliers';
    $delimiter = in_array($data->delimiter, [',', ';'], true) ? $data->delimiter : ';';
    // Traçabilité seulement : code de l'école pris dans les réglages si absent du fichier.
    $envcode = get_config('local_simhub', 'envcode') ?: '';

    $nom = $form->get_new_filename('fichier');
    $chemin = make_request_directory() . '/import';
    if (!$nom || !$form->save_file('fichier', $chemin, true)) {
        $result = ['erreurs' => [get_string('import_aucun_fichier', 'local_simhub')]];
    } else if (!in_array(strtolower(pathinfo($nom, PATHINFO_EXTENSION)), tableur::EXTENSIONS, true)) {
        $result = ['erreurs' => [get_string('import_format_refuse', 'local_simhub')]];
    } else {
        $content = tableur::vers_csv($chemin, $nom, $delimiter);
        switch ($type) {
            case 'rattachements':
                $result = liaison_importer::importer_rattachements($content, $delimiter, $envcode);
                break;
            case 'parcours':
                $result = liaison_importer::importer_parcours($content, $delimiter, $envcode);
                break;
            case 'localisation':
                $result = liaison_importer::importer_localisation($content, $delimiter, $envcode);
                break;
            case 'ressources':
                $result = liaison_importer::importer_ressources($content, $delimiter, $envcode);
                break;
            default:
                $result = atelier_importer::importer($content, $delimiter, $envcode);
        }
    }
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($result !== null) {
    $compteurs = [];
    $chaines = ['crees' => 'import_bilan_crees', 'majs' => 'import_bilan_majs', 'parcourscrees' => 'import_bilan_parcours'];
    foreach ($chaines as $cle => $chaine) {
        if (isset($result[$cle])) {
            $compteurs[] = get_string($chaine, 'local_simhub', $result[$cle]);
        }
    }
    if ($compteurs) {
        echo $OUTPUT->notification(implode(' — ', $compteurs), \core\output\notification::NOTIFY_SUCCESS);
    }
    if (!empty($result['erreurs'])) {
        echo html_writer::start_tag('ul', ['class' => 'text-warning']);
        foreach ($result['erreurs'] as $erreur) {
            echo html_writer::tag('li', s($erreur));
        }
        echo html_writer::end_tag('ul');
    }
    echo $OUTPUT->single_button(new moodle_url('/local/simhub/manage/ateliers.php'), get_string('manage_ateliers', 'local_simhub'));
}

echo html_writer::tag('p', get_string('import_description', 'local_simhub'));
echo html_writer::tag('p', get_string('import_ordre', 'local_simhub'));
$form->display();

echo $OUTPUT->footer();
