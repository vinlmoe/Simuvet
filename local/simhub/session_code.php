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
 * Saisie du code de séance après un scan QR, quand le contrôle anti-faux-scan est activé
 * (§7.3). Volontairement non bloquant : un étudiant qui n'a pas le code (encadrant absent,
 * code non affiché...) peut tout de même démarrer sa session, marquée comme non vérifiée,
 * et sa réalisation sera soumise à une validation manuelle par un encadrant
 * (manage/sessions_a_valider.php) plutôt que d'être refusée.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\seancecode;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:startsession', $context);

$atelierid = required_param('atelierid', PARAM_INT);
$atelier = new atelier($atelierid);

$pageurl = new moodle_url('/local/simhub/session_code.php', ['atelierid' => $atelierid]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('seancecode_champ', 'local_simhub'), [
    [s($atelier->get('nomcourt')), new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])],
]);

if (\local_simhub\local\reseau::dans_la_salle()) {
    session::demarrer_ou_reprendre($USER->id, $atelierid, [
        'methodescan' => 'qr',
        'controlepresence' => 'reseau_local',
    ]);
    redirect(
        new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
        get_string('session_demarree', 'local_simhub'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

$submitted = optional_param('submit', 0, PARAM_BOOL);
$sanscode = optional_param('sanscode', '', PARAM_RAW) !== '';
$erreur = false;

if ($submitted) {
    require_sesskey();

    if ($sanscode) {
        session::demarrer_ou_reprendre($USER->id, $atelierid, [
            'methodescan' => 'qr',
            'controlepresence' => 'non_verifie',
        ]);
        redirect(
            new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
            get_string('seancecode_sansvalidation', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_INFO
        );
    }

    $code = required_param('code', PARAM_ALPHANUMEXT);
    if (seancecode::est_valide($atelier->get('salle'), $code)) {
        session::demarrer_ou_reprendre($USER->id, $atelierid, [
            'methodescan' => 'qr',
            'controlepresence' => 'code_seance',
        ]);
        redirect(
            new moodle_url('/local/simhub/atelier.php', ['id' => $atelierid]),
            get_string('session_demarree', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }

    $erreur = true;
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('p', get_string('seancecode_intro', 'local_simhub'));

if ($erreur) {
    echo $OUTPUT->notification(get_string('seancecode_invalide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
}

echo html_writer::start_tag('form', ['method' => 'post']);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'atelierid', 'value' => $atelierid]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'submit', 'value' => 1]);

echo html_writer::start_div('form-group');
echo html_writer::tag('label', get_string('seancecode_champ', 'local_simhub'));
echo html_writer::empty_tag('input', [
    'type' => 'text', 'name' => 'code', 'class' => 'form-control', 'autocomplete' => 'off',
    'style' => 'text-transform:uppercase;max-width:200px;', 'autofocus' => 'autofocus',
]);
echo html_writer::end_div();

echo html_writer::empty_tag('input', [
    'type' => 'submit', 'name' => 'validercode', 'value' => get_string('seancecode_valider', 'local_simhub'),
    'class' => 'btn btn-primary mr-2',
]);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'name' => 'sanscode', 'value' => get_string('seancecode_pasdecode', 'local_simhub'),
    'class' => 'btn btn-outline-secondary',
]);
echo html_writer::end_tag('form');

echo $OUTPUT->footer();
