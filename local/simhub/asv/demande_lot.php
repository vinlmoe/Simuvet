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
 * Lien de signature groupé (§9.3) : un encadrant choisit un acte et les étudiants qui l'ont
 * réalisé sur animal vivant, puis transmet un seul lien (ou QR code) au vétérinaire ou maître
 * de stage, qui coche les étudiants qu'il a vus et signe une fois pour tous
 * (valider_animal.php?lot=...). L'ordre du livret est respecté : seuls les étudiants déjà
 * validés en simulation pour l'acte sont proposés.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valanimal;
use local_simhub\record\asv_valsim;

require_login();

$context = \local_simhub\local\contexte::racine();
if (!\local_simhub\local\droits::peut_valider_asv()) {
    throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
}
// Un enseignant d'UC ne regroupe que les étudiants inscrits à l'une de ses UC.
$transversal = has_capability('local/simhub:validateasvsimulation', $context);
$autorises = $transversal ? [] : array_flip(\local_simhub\local\droits::etudiants_asv_autorises());

$acteid = optional_param('acteid', 0, PARAM_INT);
$courseid = optional_param('courseid', 0, PARAM_INT);
$lot = optional_param('lot', '', PARAM_ALPHANUMEXT);

$pageurl = new moodle_url('/local/simhub/asv/demande_lot.php', array_filter([
    'acteid' => $acteid, 'courseid' => $courseid, 'lot' => $lot,
]));
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_lot_titre', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);

// Lien généré : adresse, QR code et état des demandes regroupées.
if ($lot !== '') {
    $demandes = $DB->get_records('local_simhub_asv_valanimal', ['lottoken' => $lot], 'id');
    $demandes = array_filter($demandes, fn($d) => $transversal || isset($autorises[(int) $d->userid]));
    if (!$demandes) {
        throw new moodle_exception('invalidrecord', 'error', '', 'local_simhub_asv_valanimal');
    }
    $lien = new moodle_url('/local/simhub/asv/valider_animal.php', ['lot' => $lot]);
    $acte = new asv_acte(reset($demandes)->acteid);

    echo $OUTPUT->header();
    echo \local_simhub\local\navigation::barre();
    echo html_writer::tag('p', get_string('asv_acte_libelle', 'local_simhub', s($acte->get('nom'))));
    echo html_writer::tag('p', get_string('asv_lot_lien', 'local_simhub') . ' :');
    echo html_writer::tag('p', html_writer::link($lien, $lien->out(false)));
    echo html_writer::tag('p', get_string('asv_lot_expire', 'local_simhub', userdate(
        min(array_map(fn($d) => (int) $d->tokenexpire, $demandes)),
        get_string('strftimedatetimeshort', 'langconfig')
    )));

    echo html_writer::start_div('local-simhub-qr-bloc', ['id' => 'local-simhub-qr-bloc']);
    echo html_writer::div('', '', ['id' => 'local-simhub-qr-image']);
    echo html_writer::tag('p', s($acte->get('nom')), ['class' => 'local-simhub-qr-legende']);
    echo html_writer::end_div();
    echo html_writer::div(
        html_writer::tag('button', get_string('qr_imprimer', 'local_simhub'), [
            'type' => 'button', 'id' => 'local-simhub-qr-imprimer', 'class' => 'btn btn-primary mr-2 me-2',
        ]) . html_writer::link('#', get_string('qr_telecharger', 'local_simhub'), [
            'id' => 'local-simhub-qr-telecharger', 'class' => 'btn btn-outline-secondary',
        ]),
        'mb-3'
    );
    $PAGE->requires->js_call_amd('local_simhub/qrcode', 'init', [$lien->out(false), 'signature-asv.svg']);

    $table = new html_table();
    $table->head = [get_string('etudiant', 'local_simhub'), get_string('asv_lot_etat', 'local_simhub')];
    foreach ($demandes as $d) {
        $etudiant = core_user::get_user($d->userid);
        $table->data[] = [
            $etudiant ? fullname($etudiant) : '#' . $d->userid,
            match ($d->statut) {
                asv_valanimal::STATUT_VALIDE => get_string('asv_lot_signe', 'local_simhub',
                    s($d->prenomvalidateur . ' ' . $d->nomvalidateur)),
                asv_valanimal::STATUT_SIGNE => get_string('asv_lot_a_controler', 'local_simhub',
                    s($d->prenomvalidateur . ' ' . $d->nomvalidateur)),
                asv_valanimal::STATUT_REJETE => get_string('asv_lot_rejete', 'local_simhub'),
                asv_valanimal::STATUT_ANNULE => get_string('asv_lot_annule', 'local_simhub'),
                default => get_string('asv_lot_en_attente', 'local_simhub'),
            },
        ];
    }
    echo html_writer::table($table);
    echo $OUTPUT->footer();
    exit;
}

// Étape 1 : l'acte.
$choixactes = [];
foreach (asv_acte::get_referentiel('') as $a) {
    $choixactes[$a->get('id')] = $a->get('nom') . ' (' . $a->get('niveau') . ')';
}
$formacte = new \local_simhub\form\formulaire(
    new moodle_url('/local/simhub/asv/demande_lot.php', array_filter(['courseid' => $courseid])),
    [
        'champs' => [['select', 'acteid', get_string('asv_acte', 'local_simhub'), [
            'choix' => $choixactes, 'type' => PARAM_INT, 'defaut' => $acteid,
        ]]],
        'bouton' => get_string('asv_lot_afficher', 'local_simhub'),
        'id' => 'acte',
    ]
);
if ($data = $formacte->get_data()) {
    redirect(new moodle_url('/local/simhub/asv/demande_lot.php', array_filter([
        'acteid' => (int) $data->acteid, 'courseid' => $courseid,
    ])));
}

// Étape 2 : les étudiants validés en simulation pour cet acte, pas encore sur animal vivant.
$formlot = null;
$choixetudiants = [];
$erreur = false;
if ($acteid && isset($choixactes[$acteid])) {
    $simulation = array_flip(array_map('intval', $DB->get_fieldset_select(
        'local_simhub_asv_valsim',
        'DISTINCT userid',
        'acteid = :acteid AND statut = :statut',
        ['acteid' => $acteid, 'statut' => asv_valsim::STATUT_VALIDE]
    )));
    // Déjà signés (acquis ou en cours de contrôle) : rien à redemander.
    $animal = array_flip(array_map('intval', $DB->get_fieldset_select(
        'local_simhub_asv_valanimal',
        'DISTINCT userid',
        'acteid = :acteid AND statut IN (:valide, :signe)',
        ['acteid' => $acteid, 'valide' => asv_valanimal::STATUT_VALIDE, 'signe' => asv_valanimal::STATUT_SIGNE]
    )));
    $inscrits = $courseid ? \local_simhub\local\selecteurs::etudiants_du_cours($courseid) : null;
    $choixetudiants = \local_simhub\local\selecteurs::options_etudiants(
        fn(int $uid) => isset($simulation[$uid]) && !isset($animal[$uid])
            && ($transversal || isset($autorises[$uid])) && ($inscrits === null || isset($inscrits[$uid]))
    );
    unset($choixetudiants['']);

    if ($choixetudiants) {
        $formlot = new \local_simhub\form\formulaire($pageurl, [
            'champs' => [['cases', 'userids', get_string('asv_etudiants', 'local_simhub'), [
                'choix' => $choixetudiants, 'filtre' => count($choixetudiants) > 10,
            ]]],
            'bouton' => get_string('asv_lot_generer', 'local_simhub'),
            'id' => 'lot',
        ]);
        if ($data = $formlot->get_data()) {
            $userids = \local_simhub\local\selection::cochees($data->userids ?? [], $choixetudiants);
            foreach ($userids as $userid) {
                if (!\local_simhub\local\droits::peut_valider_asv($userid)) {
                    throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
                }
            }
            if ($userids) {
                $cree = asv_valanimal::creer_lot($acteid, $userids);
                redirect(new moodle_url('/local/simhub/asv/demande_lot.php', ['lot' => $cree->lottoken]));
            }
            $erreur = true;
        }
    }
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();
echo html_writer::tag('p', get_string('asv_lot_intro', 'local_simhub'));
$formacte->display();
if ($erreur) {
    echo $OUTPUT->notification(get_string('selection_vide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
}
if ($formlot) {
    $formlot->display();
} else if ($acteid) {
    echo $OUTPUT->notification(get_string('asv_lot_aucun_etudiant', 'local_simhub'), \core\output\notification::NOTIFY_INFO);
}
echo $OUTPUT->footer();
