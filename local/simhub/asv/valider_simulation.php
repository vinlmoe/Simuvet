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
 * Validation ASV en simulation par un formateur/encadrant (§9.2). Formulaire minimal :
 * étudiants + acte + atelier associé (optionnel). Plusieurs étudiants peuvent être cochés
 * pour enregistrer la même décision en une fois (validation en masse d'un groupe). L'auto-évaluation guidée (§5.6) peut être
 * une étape préparatoire, mais ne remplace jamais cette validation par un encadrant.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../../config.php');

use local_simhub\persistent\asv_acte;
use local_simhub\persistent\atelier;
use local_simhub\record\asv_valsim;
use local_simhub\record\acte_atelier;

require_login();

$context = \local_simhub\local\contexte::racine();
if (!\local_simhub\local\droits::peut_valider_asv()) {
    throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
}
// Un enseignant d'UC ne valide que les étudiants inscrits à l'une de ses UC.
$transversal = has_capability('local/simhub:validateasvsimulation', $context);
$autorises = $transversal ? [] : array_flip(\local_simhub\local\droits::etudiants_asv_autorises());

$atelierid = optional_param('atelierid', 0, PARAM_INT);
$preselection = optional_param('userid', 0, PARAM_INT);
// Depuis l'activité d'une UC : liste restreinte aux inscrits du cours.
$courseid = optional_param('courseid', 0, PARAM_INT);

$pageurl = new moodle_url('/local/simhub/asv/valider_simulation.php', array_filter([
    'atelierid' => $atelierid, 'courseid' => $courseid,
]));
\local_simhub\local\navigation::preparer($PAGE, $pageurl, get_string('asv_valider_simulation', 'local_simhub'), [
    [get_string('asv_parcours', 'local_simhub'), new moodle_url('/local/simhub/asv/index.php')],
]);
if ($atelierid) {
    \local_simhub\local\navigation::onglets('atelier', $atelierid, 'asv');
}

$envcode = '';

// Si on arrive depuis un atelier précis, ne proposer que les actes qui s'y pratiquent
// réellement (liés via manage/asv_acte_edit.php) : ça évite de faire chercher l'acte dans
// tout le référentiel et fiabilise la validation (§9.2). Sans lien connu pour cet atelier,
// on retombe sur le référentiel complet plutôt que de bloquer la validation.
$actesatelier = $atelierid ? acte_atelier::get_actes_pour_atelier($atelierid) : [];
$actesreferentiel = empty($actesatelier) ? asv_acte::get_referentiel($envcode) : [];

// Normalisation en tableaux simples id/nom/niveau : get_actes_pour_atelier() renvoie des
// enregistrements bruts (jointure SQL) tandis que get_referentiel() renvoie des persistent,
// pour un affichage identique quelle que soit la source retenue ci-dessus.
$actes = [];
foreach (!empty($actesatelier) ? $actesatelier : $actesreferentiel as $acte) {
    $actes[] = is_object($acte) && method_exists($acte, 'get')
        ? ['id' => $acte->get('id'), 'nom' => $acte->get('nom'), 'niveau' => $acte->get('niveau')]
        : ['id' => $acte->id, 'nom' => $acte->nom, 'niveau' => $acte->niveau];
}

$choixactes = [];
foreach ($actes as $acte) {
    $choixactes[$acte['id']] = $acte['nom'] . ' (' . $acte['niveau'] . ')';
}
$inscrits = $courseid ? array_flip(array_map('intval', array_keys(get_enrolled_users(
    context_course::instance($courseid),
    '',
    0,
    'u.id',
    null,
    0,
    0,
    true
)))) : null;
$choixetudiants = \local_simhub\local\selecteurs::options_etudiants(
    fn(int $uid) => ($transversal || isset($autorises[$uid])) && ($inscrits === null || isset($inscrits[$uid]))
);
unset($choixetudiants['']);
$form = new \local_simhub\form\formulaire($PAGE->url, [
    'champs' => array_merge(
        [['cases', 'userids', get_string('asv_etudiants', 'local_simhub'), [
            'choix' => $choixetudiants, 'defaut' => $preselection ? [$preselection] : [], 'filtre' => true,
        ]]],
        $atelierid && empty($actesatelier)
            ? [['static', 'aucunacte', '', ['texte' => get_string('asv_aucun_acte_lie', 'local_simhub')]]] : [],
        [
            ['select', 'acteid', get_string('asv_acte', 'local_simhub'), ['choix' => $choixactes, 'type' => PARAM_INT,
                'requis' => true]],
            ['autocomplete', 'atelierid', get_string('asv_atelier_associe', 'local_simhub'), [
                'choix' => \local_simhub\local\selecteurs::options_ateliers(), 'type' => PARAM_INT, 'defaut' => $atelierid,
            ]],
            ['select', 'resultat', get_string('asv_resultat', 'local_simhub'), [
                'choix' => [
                    asv_valsim::STATUT_VALIDE => get_string('asv_resultat_valide', 'local_simhub'),
                    asv_valsim::STATUT_NON_VALIDE => get_string('asv_resultat_non_valide', 'local_simhub'),
                ],
                'type' => PARAM_ALPHANUMEXT,
            ]],
            ['textarea', 'commentaire', get_string('asv_commentaire', 'local_simhub'), [
                'attributs' => ['rows' => 2, 'cols' => 50],
            ]],
        ]
    ),
    'bouton' => get_string('asv_enregistrer_decision', 'local_simhub'),
]);

$erreur = false;
if ($data = $form->get_data()) {
    $userids = array_values(array_intersect(
        array_map('intval', array_keys(array_filter((array) ($data->userids ?? [])))),
        array_map('intval', array_keys($choixetudiants))
    ));
    $acteid = (int) $data->acteid;
    if (!isset($choixactes[$acteid])) {
        throw new moodle_exception('invalidrecord', 'error', '', 'local_simhub_asv_acte');
    }
    // Contrôle de chaque étudiant avant toute écriture : une sélection est enregistrée en entier ou pas du tout.
    foreach ($userids as $userid) {
        core_user::require_active_user(core_user::get_user($userid, '*', MUST_EXIST));
        if (!\local_simhub\local\droits::peut_valider_asv($userid)) {
            throw new required_capability_exception($context, 'local/simhub:validateasvsimulation', 'nopermissions', '');
        }
    }

    $extra = [];
    if (!empty($data->atelierid)) {
        $extra['atelierid'] = (int) $data->atelierid;
    }
    $extra['statut'] = $data->resultat === asv_valsim::STATUT_NON_VALIDE
        ? asv_valsim::STATUT_NON_VALIDE : asv_valsim::STATUT_VALIDE;
    $commentaire = trim($data->commentaire ?? '');
    if ($commentaire !== '') {
        $extra['commentaire'] = $commentaire;
    }

    foreach ($userids as $userid) {
        $id = asv_valsim::valider($userid, $acteid, $USER->id, $extra);
        if ($extra['statut'] === asv_valsim::STATUT_VALIDE) {
            \local_simhub\event\asv_valide_simulation::create([
                'objectid' => $id,
                'context' => $context,
                'relateduserid' => $userid,
            ])->trigger();
        }
    }

    if (count($userids) === 1) {
        redirect(
            new moodle_url('/local/simhub/asv/etudiant.php', ['userid' => $userids[0]]),
            get_string('asv_decision_enregistree', 'local_simhub'),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    } else if ($userids) {
        redirect(
            $pageurl,
            get_string('asv_decisions_enregistrees', 'local_simhub', count($userids)),
            null,
            \core\output\notification::NOTIFY_SUCCESS
        );
    }
    $erreur = true;
}

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

if ($erreur) {
    echo $OUTPUT->notification(get_string('selection_vide', 'local_simhub'), \core\output\notification::NOTIFY_ERROR);
}
$form->display();

echo $OUTPUT->footer();
