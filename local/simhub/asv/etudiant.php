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
 * Fiche ASV d'un étudiant côté encadrant (§9.4) : état acte par acte, avancement vers les
 * certifications, livret, et annulation d'une validation saisie par erreur.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\local\asv_vue;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

require_login();

$context = \local_simhub\local\contexte::racine();
$userid = required_param('userid', PARAM_INT);
if (!\local_simhub\local\droits::peut_valider_asv($userid)) {
    require_capability('local/simhub:manageasv', $context);
}
$action = optional_param('action', '', PARAM_ALPHA);
$envcode = '';

$user = \core_user::get_user($userid, '*', MUST_EXIST);
$url = \local_simhub\local\navigation::url('/local/simhub/asv/etudiant.php', ['userid' => $userid]);

\local_simhub\local\navigation::preparer($PAGE, $url, s(fullname($user)), [
    [get_string('asv_parcours', 'local_simhub'), \local_simhub\local\navigation::url('/local/simhub/asv/index.php')],
]);

if ($action === 'annulersim' || $action === 'annuleranimal') {
    $id = required_param('id', PARAM_INT);
    $table = $action === 'annulersim' ? asv_valsim::TABLE : asv_valanimal::TABLE;
    if ($action !== 'annulersim' || !\local_simhub\local\droits::peut_valider_asv($userid)) {
        require_capability('local/simhub:manageasv', $context);
    }
    $validation = $DB->get_record($table, ['id' => $id, 'userid' => $userid, 'statut' => 'valide'], '*', MUST_EXIST);

    $form = new \local_simhub\form\formulaire(new moodle_url($url, ['action' => $action, 'id' => $id]), [
        'champs' => [
            ['textarea', 'motif', get_string('asv_motif', 'local_simhub'), ['requis' => true,
                'attributs' => ['rows' => 2, 'cols' => 50]]],
        ],
        'bouton' => get_string('asv_confirmer', 'local_simhub'),
        'annuler' => true,
    ]);
    if ($form->is_cancelled()) {
        redirect($url);
    } else if (($data = $form->get_data()) && trim($data->motif) !== '') {
        $motif = get_string('asv_annule_par', 'local_simhub', (object) [
            'nom' => fullname($USER), 'date' => userdate(time(), get_string('strftimedatefullshort', 'langconfig')),
            'motif' => trim($data->motif),
        ]);
        if ($action === 'annulersim') {
            asv_valsim::annuler($id, $motif);
        } else {
            asv_valanimal::annuler($id);
        }
        redirect($url, get_string('asv_validation_annulee', 'local_simhub'), null, \core\output\notification::NOTIFY_SUCCESS);
    }

    echo $OUTPUT->header();
    echo \local_simhub\local\navigation::barre();
    $acte = new \local_simhub\persistent\asv_acte($validation->acteid);
    echo $OUTPUT->notification(get_string('asv_confirmer_annulation', 'local_simhub', (object) [
        'acte' => s($acte->get('nom')), 'etudiant' => s(fullname($user)),
    ]), \core\output\notification::NOTIFY_WARNING);

    $form->display();
    echo $OUTPUT->footer();
    exit;
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::div(
    html_writer::link(
        \local_simhub\local\navigation::url('/local/simhub/asv/livret_pdf.php', ['userid' => $userid, 'envcode' => $envcode]),
        get_string('asv_exporter_livret', 'local_simhub'),
        ['class' => 'btn btn-outline-secondary btn-sm mr-2 me-2']
    )
    . (\local_simhub\local\droits::peut_valider_asv($userid)
        ? html_writer::link(
            \local_simhub\local\navigation::url('/local/simhub/asv/valider_simulation.php', ['userid' => $userid]),
            get_string('asv_valider_simulation', 'local_simhub'),
            ['class' => 'btn btn-primary btn-sm']
        )
        : ''),
    'mb-3'
);

echo asv_vue::certifications($userid, $envcode);
echo asv_vue::tableau($userid, $envcode, true);

echo $OUTPUT->footer();
