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
 * Fiche atelier côté étudiant (§5.4 localisation, §5.5 ressources).
 *
 * Page volontairement simple : deux blocs (localisation, ressources) affichés l'un après
 * l'autre plutôt que des onglets JS, pour rester robuste sur mobile sans dépendance
 * supplémentaire. Le paramètre "onglet" ne fait que faire défiler la page vers la bonne
 * ancre (via #localisation / #ressources).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use local_simhub\persistent\atelier;
use local_simhub\persistent\ressource;

require_login();

$context = \local_simhub\local\contexte::racine();
require_capability('local/simhub:view', $context);

$id = required_param('id', PARAM_INT);
$onglet = optional_param('onglet', '', PARAM_ALPHA);

$atelier = new atelier($id);

$pageurl = \local_simhub\local\navigation::url('/local/simhub/atelier.php', ['id' => $id]);
\local_simhub\local\navigation::preparer($PAGE, $pageurl, s($atelier->get('nomcourt')));

echo $OUTPUT->header();
echo \local_simhub\local\navigation::barre();

echo html_writer::tag('p', s($atelier->get('descriptioncourte')));

// Actions principales directement sur la fiche : c'est la page où arrive l'étudiant après
// un scan QR ou depuis une recherche, il doit pouvoir y démarrer ou terminer sa séance.
$actions = '';
if ($atelier->get('statut') !== atelier::STATUT_ACTIF) {
    $indispo = \local_simhub\record\indispo::get_en_cours($id);
    $message = get_string('atelier_non_demarrable', 'local_simhub');
    if ($indispo && $indispo->commentaire) {
        $message .= ' ' . s($indispo->commentaire);
    }
    if ($indispo && $indispo->echeanceprevue) {
        $message .= ' ' . get_string(
            'indispo_retour_prevu',
            'local_simhub',
            userdate($indispo->echeanceprevue, get_string('strftimedatefullshort', 'langconfig'))
        );
    }
    echo $OUTPUT->notification($message, \core\output\notification::NOTIFY_WARNING);
}
$dejavalide = has_capability('local/simhub:startsession', $context)
    && \local_simhub\persistent\session::est_valide($USER->id, $id);
if ($dejavalide) {
    echo $OUTPUT->notification(get_string('atelier_deja_valide', 'local_simhub'), \core\output\notification::NOTIFY_SUCCESS);
} else if (has_capability('local/simhub:startsession', $context)) {
    $encours = null;
    foreach (\local_simhub\persistent\session::get_pour_etudiant($USER->id, $id) as $session) {
        if ($session->get('statut') === \local_simhub\persistent\session::STATUT_COMMENCE) {
            $encours = $session;
            break;
        }
    }
    if ($encours) {
        $actions .= html_writer::link(
            \local_simhub\local\navigation::url('/local/simhub/session.php', ['atelierid' => $id, 'action' => 'terminer',
                'sessionid' => $encours->get('id')]),
            get_string('bouton_terminer', 'local_simhub'),
            ['class' => 'btn btn-primary btn-sm mr-2 me-2']
        );
    } else if ($atelier->get('statut') === atelier::STATUT_ACTIF) {
        $actions .= html_writer::link(
            \local_simhub\local\navigation::url(
                '/local/simhub/session.php',
                ['atelierid' => $id, 'action' => 'demarrer', 'sesskey' => sesskey()]
            ),
            get_string('bouton_commencer', 'local_simhub'),
            ['class' => 'btn btn-primary btn-sm mr-2 me-2']
        );
    }
}
$actions .= html_writer::link(
    new moodle_url('/local/simhub/manage/atelier_fiche_pdf.php', ['id' => $id]),
    get_string('telecharger_fiche_pdf', 'local_simhub'),
    ['class' => 'btn btn-outline-secondary btn-sm mr-2 me-2']
);
if (has_capability('local/simhub:manageateliers', $context)) {
    $actions .= html_writer::link(
        new moodle_url('/local/simhub/manage/atelier_edit.php', ['id' => $id]),
        get_string('gerer_atelier', 'local_simhub'),
        ['class' => 'btn btn-outline-secondary btn-sm']
    );
}
echo html_writer::div($actions, 'mb-3');

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
        \local_simhub\local\contexte::fichiers()->id,
        'local_simhub',
        'plan',
        $atelier->get('planimageitemid'),
        'filepath, filename',
        false
    );
    $planfile = reset($planfiles);

    if ($planfile) {
        $planurl = moodle_url::make_pluginfile_url(
            \local_simhub\local\contexte::fichiers()->id,
            'local_simhub',
            'plan',
            $atelier->get('planimageitemid'),
            '/',
            $planfile->get_filename()
        );
        echo html_writer::start_div('local-simhub-plan');
        echo html_writer::empty_tag('img', ['src' => $planurl->out(false), 'alt' => get_string('nav_plan', 'local_simhub')]);
        if ($atelier->get('planrepx') !== null && $atelier->get('planrepy') !== null) {
            echo html_writer::span('', 'local-simhub-plan-marker', [
                'style' => sprintf('left:%s%%;top:%s%%;', (float) $atelier->get('planrepx'), (float) $atelier->get('planrepy')),
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
                \local_simhub\local\contexte::fichiers()->id,
                'local_simhub',
                'ressource',
                $r->get('fileitemid'),
                'filepath, filename',
                false
            );
            $resfile = reset($resfiles);
            if ($resfile) {
                $href = moodle_url::make_pluginfile_url(
                    \local_simhub\local\contexte::fichiers()->id,
                    'local_simhub',
                    'ressource',
                    $r->get('fileitemid'),
                    '/',
                    $resfile->get_filename()
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
