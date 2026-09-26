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

use local_simhub\local\atelier_importer;
use local_simhub\local\liaison_importer;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:importexport', $context);

$pageurl = new moodle_url('/local/simhub/manage/import.php');
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('import_ateliers', 'local_simhub'));

$submitted = optional_param('submit', 0, PARAM_BOOL);
$result = null;
$type = optional_param('type', 'ateliers', PARAM_ALPHA);

if ($submitted) {
    require_sesskey();

    $delimiter = optional_param('delimiter', ';', PARAM_RAW);
    $delimiter = in_array($delimiter, [',', ';'], true) ? $delimiter : ';';
    $envcode = optional_param('envcode', get_config('local_simhub', 'envcode') ?: '', PARAM_ALPHANUMEXT);

    if (empty($_FILES['csvfile']['tmp_name']) || !is_uploaded_file($_FILES['csvfile']['tmp_name'])) {
        $result = ['crees' => 0, 'majs' => 0, 'erreurs' => [get_string('import_aucun_fichier', 'local_simhub')]];
    } else {
        $content = file_get_contents($_FILES['csvfile']['tmp_name']);
        // Les exports Excel français sont fréquemment encodés en Windows-1252 : on force
        // l'UTF-8 pour éviter des caractères accentués corrompus en base.
        if (!mb_check_encoding($content, 'UTF-8')) {
            $content = mb_convert_encoding($content, 'UTF-8', 'Windows-1252');
        }

        if ($type === 'rattachements') {
            $result = liaison_importer::importer_rattachements($content, $delimiter, $envcode);
        } else if ($type === 'parcours') {
            $result = liaison_importer::importer_parcours($content, $delimiter, $envcode);
        } else {
            $type = 'ateliers';
            $result = atelier_importer::importer($content, $delimiter, $envcode);
        }
    }
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($result !== null) {
    if ($type === 'rattachements') {
        echo html_writer::tag('p', $result['crees'] . ' rattachement(s) créé(s)');
    } else if ($type === 'parcours') {
        echo html_writer::tag(
            'p',
            $result['parcourscrees'] . ' parcours créé(s), ' . $result['crees'] . ' atelier(s) ajouté(s) à un parcours'
        );
    } else {
        echo html_writer::tag(
            'p',
            get_string('import_crees', 'local_simhub', $result['crees']) . ' — '
                . get_string('import_mis_a_jour', 'local_simhub', $result['majs'])
        );
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
echo html_writer::tag(
    'p',
    get_string('import_ordre', 'local_simhub')
);

echo html_writer::start_tag('form', ['method' => 'post', 'enctype' => 'multipart/form-data']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('import_type', 'local_simhub'));
echo html_writer::select([
    'ateliers' => get_string('nav_groupe_ateliers', 'local_simhub'),
    'rattachements' => get_string('import_type_rattachements', 'local_simhub'),
    'parcours' => get_string('import_type_parcours', 'local_simhub'),
], 'type', $type, false, ['class' => 'form-control d-inline-block w-auto']);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('import_fichier', 'local_simhub'));
echo html_writer::empty_tag('input', [
    'type' => 'file', 'name' => 'csvfile', 'accept' => '.csv', 'class' => 'form-control-file', 'required' => 'required',
]);
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('import_separateur', 'local_simhub'));
echo html_writer::start_tag('select', ['name' => 'delimiter', 'class' => 'form-control d-inline-block w-auto']);
echo html_writer::tag('option', get_string('import_sep_pointvirgule', 'local_simhub'), ['value' => ';']);
echo html_writer::tag('option', get_string('import_sep_virgule', 'local_simhub'), ['value' => ',']);
echo html_writer::end_tag('select');
echo html_writer::end_div();

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('champ_envcode', 'local_simhub') . ' (par défaut si absent du fichier)');
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'envcode', 'class' => 'form-control d-inline-block w-auto',
    'value' => get_config('local_simhub', 'envcode') ?: '',
]);
echo html_writer::end_div();

echo html_writer::tag('button', get_string('import_ateliers', 'local_simhub'), ['type' => 'submit', 'class' => 'btn btn-primary']);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
