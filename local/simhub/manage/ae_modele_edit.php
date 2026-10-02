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
 * Création / activation du modèle d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : un
 * modèle par atelier en V1. La composition (rubriques et critères) se gère ensuite sur
 * manage/ae_rubriques.php.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ae_modele;

require_login();

$context = \local_simhub\local\contexte::racine();

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);
if (!\local_simhub\local\droits::peut_editer_grille($atelierid)) {
    throw new required_capability_exception($context, 'local/simhub:manageateliers', 'nopermissions', '');
}
$gestionnaire = has_capability('local/simhub:manageateliers', $context);
// Un responsable d'UC n'a pas accès aux pages de gestion de l'atelier : son fil d'Ariane
// passe par la fiche étudiant.
$etapesatelier = $gestionnaire ? [
    [get_string('manage_ateliers', 'local_simhub'), new moodle_url('/local/simhub/manage/ateliers.php')],
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $atelierid])],
] : [[s($atelier->get('nomcourt')), new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])]];

$title = get_string('ae_modele', 'local_simhub');
$pageurl = \local_simhub\local\navigation::url('/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, $title, $etapesatelier);
if ($gestionnaire) {
    \local_simhub\local\navigation::onglets('atelier', $atelierid, 'ae');
}

$modele = ae_modele::get_pour_atelier($atelierid);

$form = new \local_simhub\form\formulaire(null, [
    'champs' => [
        ['text', 'titre', get_string('ae_champ_titre', 'local_simhub'), [
            'requis' => true, 'defaut' => $modele ? $modele->get('titre') : '', 'attributs' => ['size' => 50],
        ]],
        ['advcheckbox', 'actif', get_string('ae_champ_actif', 'local_simhub'), [
            'type' => PARAM_BOOL, 'defaut' => (!$modele || $modele->get('actif')) ? 1 : 0,
        ]],
    ],
    'caches' => ['atelierid' => $atelierid],
]);

if ($data = $form->get_data()) {
    if ($modele) {
        $modele->set('titre', $data->titre);
        $modele->set('actif', $data->actif ? 1 : 0);
        $modele->update();
    } else {
        $modele = new ae_modele(0, (object) [
            'atelierid' => $atelierid,
            'titre' => $data->titre,
            'actif' => $data->actif ? 1 : 0,
        ]);
        $modele->create();
    }

    redirect(
        \local_simhub\local\navigation::url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]),
        get_string('changessaved'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

// La grille est partagée par toutes les UC qui utilisent l'atelier : on montre qui l'a
// modifiée en dernier.
if ($modele && $modele->get('usermodified')) {
    $auteur = \core_user::get_user($modele->get('usermodified'));
    echo $OUTPUT->notification(get_string('ae_derniere_modification', 'local_simhub', [
        'auteur' => $auteur ? fullname($auteur) : '-',
        'date' => userdate($modele->get('timemodified')),
    ]), \core\output\notification::NOTIFY_INFO);
}

$form->display();

if ($modele) {
    echo $OUTPUT->single_button(
        \local_simhub\local\navigation::url('/local/simhub/manage/ae_rubriques.php', ['atelierid' => $atelierid]),
        get_string('ae_gerer_rubriques', 'local_simhub')
    );
}

echo $OUTPUT->footer();
