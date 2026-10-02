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
 * Affichage de l'état ASV d'un étudiant (§9.4), partagé entre sa propre vue (asv/index.php)
 * et la fiche consultée par un encadrant (asv/etudiant.php), pour que les deux montrent
 * exactement la même chose.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

use local_simhub\record\asv_valanimal;
use local_simhub\record\asv_valsim;

/**
 * Rendu HTML de l'état ASV d'un étudiant.
 */
class asv_vue {
    /**
     * Tableau acte par acte : simulation, animal vivant, demande en attente, actions.
     *
     * @param int $userid
     * @param string $envcode
     * @param bool $pilote true pour la vue encadrant (annulations), false pour l'étudiant (demandes).
     * @return string HTML
     */
    public static function tableau(int $userid, string $envcode, bool $pilote): string {
        $context = contexte::racine();
        $format = get_string('strftimedatefullshort', 'langconfig');
        $str = function (string $cle, $a = null): string {
            return get_string($cle, 'local_simhub', $a);
        };

        $table = new \html_table();
        $table->head = [$str('asv_acte'), $str('asv_champ_niveau'), $str('asv_col_simulation'), $str('asv_col_animal'), ''];

        foreach (asv_certification_helper::etat_etudiant($userid, $envcode) as $acteid => $etat) {
            $sim = $etat['sim'];
            $animal = $etat['animal'];
            $attente = $etat['attente'];
            $simok = $sim && $sim->statut === asv_valsim::STATUT_VALIDE;

            if ($simok) {
                $validateur = \core_user::get_user($sim->validateuruserid);
                $simtexte = '✔ ' . userdate($sim->datevalidation, $format)
                    . ($validateur ? ' — ' . s(fullname($validateur)) : '');
            } else if ($sim) {
                $simtexte = '✘ ' . $str('asv_non_valide_le', userdate($sim->datevalidation, $format));
            } else {
                $simtexte = '—';
            }
            if ($sim && !empty($sim->commentaire)) {
                $simtexte .= \html_writer::div(s($sim->commentaire), 'small text-muted');
            }
            if ($etat['annulation']) {
                $simtexte .= \html_writer::div(s($etat['annulation']->commentaire), 'small text-danger');
            }

            $controle = $etat['controle'];
            if ($animal) {
                $animaltexte = '✔ ' . userdate($animal->datevalidation, $format) . ' — '
                    . s($animal->prenomvalidateur . ' ' . $animal->nomvalidateur)
                    . ($animal->emailvalidateur ? \html_writer::div(s($animal->emailvalidateur), 'small text-muted') : '')
                    . (asv_valanimal::signature_valide((string) $animal->signature)
                        ? \html_writer::div(\html_writer::empty_tag('img', [
                            'src' => $animal->signature,
                            'alt' => get_string('valid_signature_de', 'local_simhub',
                                s($animal->prenomvalidateur . ' ' . $animal->nomvalidateur)),
                            'class' => 'local-simhub-signature-apercu',
                        ]))
                        : '');
                $controleur = !empty($animal->controleuruserid) ? \core_user::get_user($animal->controleuruserid) : null;
                if ($controleur) {
                    $animaltexte .= \html_writer::div($str('asv_controle_par', s(fullname($controleur))), 'small text-muted');
                }
            } else if ($controle) {
                $animaltexte = $str('asv_animal_a_controler', (object) [
                    'nom' => s($controle->prenomvalidateur . ' ' . $controle->nomvalidateur),
                    'date' => userdate($controle->datevalidation, $format),
                ]);
            } else if ($attente) {
                $animaltexte = $str('asv_en_attente_jusquau', userdate($attente->tokenexpire, $format))
                    . ($attente->emailvalidateur ? \html_writer::div(s($attente->emailvalidateur), 'small text-muted') : '');
            } else {
                $animaltexte = '—';
            }
            if (!$animal && !$controle && $etat['rejet']) {
                $animaltexte .= \html_writer::div($str('asv_animal_rejete', (object) [
                    'date' => userdate($etat['rejet']->datecontrole, $format),
                    'motif' => s($etat['rejet']->motifcontrole),
                ]), 'small text-danger');
            }

            $actions = [];
            $params = ['userid' => $userid];
            if ($pilote) {
                if ($simok && droits::peut_valider_asv($userid)) {
                    $actions[] = \html_writer::link(
                        new \moodle_url(
                            '/local/simhub/asv/etudiant.php',
                            $params + ['action' => 'annulersim', 'id' => $sim->id]
                        ),
                        $str('asv_annuler_simulation'),
                        ['class' => 'text-danger']
                    );
                }
                if ($controle && droits::peut_valider_asv($userid)) {
                    $actions[] = \html_writer::link(
                        navigation::url('/local/simhub/asv/controle_signatures.php'),
                        $str('asv_controler')
                    );
                }
                if ($animal && has_capability('local/simhub:manageasv', $context)) {
                    $actions[] = \html_writer::link(
                        new \moodle_url(
                            '/local/simhub/asv/etudiant.php',
                            $params + ['action' => 'annuleranimal', 'id' => $animal->id]
                        ),
                        $str('asv_annuler_animal'),
                        ['class' => 'text-danger']
                    );
                }
            } else if ($simok && !$animal && !$controle) {
                $actions[] = \html_writer::link(
                    navigation::url('/local/simhub/asv/demander_validation_animal.php', ['acteid' => $acteid]),
                    $attente ? $str('asv_voir_lien') : $str('asv_demander_validation_animal')
                );
            }

            $table->data[] = [
                s($etat['acte']->get('nom')),
                s($etat['acte']->get('niveau')),
                $simtexte,
                $animaltexte,
                implode(' | ', $actions),
            ];
        }

        return \html_writer::table($table);
    }

    /**
     * Avancement vers chaque certification (A1, A2, globale de fin de A3), avec le lien
     * vers l'attestation quand elle peut être délivrée.
     *
     * @param int $userid
     * @param string $envcode
     * @return string HTML
     */
    public static function certifications(int $userid, string $envcode): string {
        $items = '';
        foreach (['A1', 'A2', 'A3'] as $niveau) {
            if (empty(asv_certification_helper::get_actes_requis($envcode, $niveau))) {
                continue;
            }
            $libelle = $niveau === 'A3'
                ? get_string('asv_certification_a3', 'local_simhub')
                : get_string('asv_certification_niveau', 'local_simhub', $niveau);
            $manquants = asv_certification_helper::get_actes_manquants($userid, $niveau, $envcode);
            if (empty($manquants)) {
                $etat = '✔ ' . \html_writer::link(
                    new \moodle_url(
                        '/local/simhub/asv/attestation_pdf.php',
                        ['userid' => $userid, 'niveau' => $niveau, 'envcode' => $envcode]
                    ),
                    get_string('asv_telecharger_attestation', 'local_simhub')
                );
            } else {
                $etat = get_string('asv_actes_restants', 'local_simhub', count($manquants));
            }
            $items .= \html_writer::tag('li', \html_writer::tag('strong', $libelle) . ' : ' . $etat);
        }
        return $items ? \html_writer::tag('ul', $items, ['class' => 'mb-3']) : '';
    }
}
