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

namespace local_simhub\local;

use local_simhub\persistent\asv_acte;
use local_simhub\persistent\atelier;
use local_simhub\persistent\session;
use local_simhub\record\asv_valanimal;

/**
 * Listes de validation partagées par l'activité d'UC et les pages SimHub : séances à
 * valider par un encadrant (§7.3) et signatures animal vivant à contrôler (§9.3).
 *
 * Chaque ligne donne de quoi décider sans ouvrir d'autre page (date, horaires, durée,
 * présence, auto-évaluation ; signataire, date, signature, indices), et un bouton valide
 * d'un coup tout ce qui ne présente aucune anomalie : seules les lignes signalées restent
 * à examiner une par une.
 *
 * Les formulaires envoient action=valider|refuser (séances) ou confirmer|rejeter
 * (signatures), avec sessionids[] ou ids[], sesskey et un commentaire facultatif.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class validation {
    /**
     * Séances sans anomalie (présence vérifiée, durée plausible, séance terminée).
     *
     * @param \stdClass[] $sessions Enregistrements de local_simhub_session.
     * @return int[]
     */
    public static function seances_sans_anomalie(array $sessions): array {
        $ids = [];
        foreach ($sessions as $s) {
            if ($s->statut === session::STATUT_REALISE && parcours_helper::motif_a_valider($s) === '') {
                $ids[] = (int) $s->id;
            }
        }
        return $ids;
    }

    /**
     * Liste des séances à valider, avec validation groupée.
     *
     * @param \stdClass[] $sessions Enregistrements de local_simhub_session.
     * @param \moodle_url $url Page qui traite les actions.
     * @return string HTML
     */
    public static function seances(array $sessions, \moodle_url $url): string {
        global $DB, $OUTPUT;

        if (!$sessions) {
            return $OUTPUT->notification(get_string('sessions_aucune_a_valider', 'local_simhub'),
                \core\output\notification::NOTIFY_INFO);
        }
        selection::requerir_js();

        $ids = array_map('intval', array_keys($sessions));
        $autoevals = self::autoevals($ids);
        $bilans = $DB->get_records_list('local_simhub_ae_bilan', 'sessionid', $ids, '', 'sessionid, pointaretravailler');
        $ateliers = [];
        $jour = get_string('strftimedaydate', 'langconfig');
        $heure = get_string('strftimetime', 'langconfig');

        $table = new \html_table();
        $table->attributes['class'] = 'generaltable table-sm';
        $table->head = [
            '',
            get_string('etudiant', 'local_simhub'),
            get_string('atelier', 'local_simhub'),
            get_string('valid_col_seance', 'local_simhub'),
            get_string('valid_col_presence', 'local_simhub'),
            get_string('valid_col_autoeval', 'local_simhub'),
            '',
        ];
        foreach ($sessions as $s) {
            $aid = (int) $s->atelierid;
            $ateliers[$aid] = $ateliers[$aid] ?? new atelier($aid);
            $atelier = $ateliers[$aid];
            $user = \core_user::get_user($s->userid);
            $nom = $user ? fullname($user) : '#' . $s->userid;

            // Séance : jour, horaires, durée réelle face à la durée indicative.
            $horaires = userdate($s->timestart, $heure) . ($s->timeend ? ' – ' . userdate($s->timeend, $heure) : '');
            $indicative = (int) $atelier->get('dureeindicative');
            if ($s->timeend) {
                $duree = get_string('valid_duree', 'local_simhub', (int) round(($s->timeend - $s->timestart) / MINSECS));
                if ($indicative) {
                    $duree .= ' ' . get_string('valid_duree_indicative', 'local_simhub', $indicative);
                }
                if (!empty($s->dureesuspecte)) {
                    $duree .= ' ' . \html_writer::span(get_string('valid_duree_courte', 'local_simhub'),
                        'badge badge-danger bg-danger');
                }
            } else {
                $duree = \html_writer::span(get_string('valid_encours', 'local_simhub'), 'badge badge-info bg-info');
            }
            $seance = \html_writer::div(userdate($s->timestart, $jour)) . \html_writer::div($horaires, 'small')
                . \html_writer::div($duree, 'small');

            // Présence : comment la séance a été lancée et vérifiée.
            $methode = $s->methodescan ? get_string('valid_methode_' . $s->methodescan, 'local_simhub') : '';
            $controle = $s->controlepresence ?: 'aucun';
            $presence = \html_writer::div($methode, 'small text-muted')
                . ($controle === 'non_verifie'
                    ? \html_writer::span(get_string('valid_presence_non_verifie', 'local_simhub'), 'badge badge-danger bg-danger')
                    : \html_writer::div(get_string('valid_presence_' . $controle, 'local_simhub'), 'small'));

            // Auto-évaluation : répartition des niveaux et point à retravailler.
            $ae = $autoevals[(int) $s->id] ?? null;
            $autoeval = $ae ? \html_writer::div(get_string('valid_autoeval', 'local_simhub', (object) $ae), 'small')
                : \html_writer::div(get_string('valid_autoeval_aucune', 'local_simhub'), 'small text-muted');
            if (!empty($bilans[$s->id]->pointaretravailler)) {
                $autoeval .= \html_writer::div(get_string('champ_pointaretravailler', 'local_simhub') . ' : '
                    . s($bilans[$s->id]->pointaretravailler), 'small text-muted');
            }

            $params = ['sessionids[]' => $s->id, 'sesskey' => sesskey()];
            $table->data[] = [
                selection::case('sessionids', $s->id, $nom . ' — ' . $atelier->get('nomcourt')),
                s($nom),
                s($atelier->get('numero') . ' — ' . $atelier->get('nomcourt')),
                $seance,
                $presence,
                $autoeval,
                \html_writer::link(new \moodle_url($url, $params + ['action' => 'valider']),
                    get_string('session_valider', 'local_simhub'), ['class' => 'btn btn-sm btn-success mb-1 mr-1 me-1'])
                . \html_writer::link(new \moodle_url($url, $params + ['action' => 'refuser']),
                    get_string('session_refuser', 'local_simhub'), ['class' => 'btn btn-sm btn-outline-danger mb-1']),
            ];
        }

        return self::raccourci($url, 'sessionids', self::seances_sans_anomalie($sessions), 'valider',
                'valid_sans_anomalie')
            . self::formulaire($url, $table, [
                'valider' => [get_string('selection_valider', 'local_simhub'), 'btn-success'],
                'refuser' => [get_string('selection_refuser', 'local_simhub'), 'btn-outline-danger'],
            ], 'valid_commentaire', count($sessions) > 10);
    }

    /**
     * Signatures sans indice de fraude.
     *
     * @param \stdClass[] $demandes Enregistrements de local_simhub_asv_valanimal.
     * @return int[]
     */
    public static function signatures_sans_indice(array $demandes): array {
        $ids = [];
        foreach ($demandes as $d) {
            if (!asv_valanimal::indices($d, \core_user::get_user($d->userid) ?: null)
                    && asv_valanimal::signature_valide((string) $d->signature)) {
                $ids[] = (int) $d->id;
            }
        }
        return $ids;
    }

    /**
     * Liste des signatures animal vivant à contrôler, avec confirmation groupée.
     *
     * @param \stdClass[] $demandes Enregistrements de local_simhub_asv_valanimal signés.
     * @param \moodle_url $url Page qui traite les actions.
     * @return string HTML
     */
    public static function signatures(array $demandes, \moodle_url $url): string {
        global $OUTPUT;

        if (!$demandes) {
            return $OUTPUT->notification(get_string('asv_controle_aucune', 'local_simhub'),
                \core\output\notification::NOTIFY_INFO);
        }
        selection::requerir_js();

        $format = get_string('strftimedatetimeshort', 'langconfig');
        $actes = [];
        $table = new \html_table();
        $table->attributes['class'] = 'generaltable table-sm';
        $table->head = [
            '',
            get_string('etudiant', 'local_simhub'),
            get_string('asv_acte', 'local_simhub'),
            get_string('asv_col_signataire', 'local_simhub'),
            get_string('asv_col_signe_le', 'local_simhub'),
            get_string('asv_col_signature', 'local_simhub'),
            get_string('asv_col_indices', 'local_simhub'),
            '',
        ];
        foreach ($demandes as $d) {
            $etudiant = \core_user::get_user($d->userid);
            $nom = $etudiant ? fullname($etudiant) : '#' . $d->userid;
            $acteid = (int) $d->acteid;
            $actes[$acteid] = $actes[$acteid] ?? new asv_acte($acteid);
            $acte = $actes[$acteid]->get('nom') . ' (' . $actes[$acteid]->get('niveau') . ')';

            // Signataire : identité saisie, adresse à laquelle le lien a été envoyé, engagement.
            $signataire = \html_writer::div(s(trim($d->prenomvalidateur . ' ' . \core_text::strtoupper($d->nomvalidateur))));
            if (!empty($d->emailvalidateur)) {
                $signataire .= \html_writer::div(s($d->emailvalidateur), 'small text-muted');
            }
            $signataire .= \html_writer::div(get_string(
                $d->certificationcochee ? 'valid_certification_oui' : 'valid_certification_non',
                'local_simhub'
            ), 'small' . ($d->certificationcochee ? '' : ' text-danger'));

            // Dates : signature, demande, et mode (lien individuel ou groupé).
            $dates = \html_writer::div(userdate($d->datevalidation, $format))
                . \html_writer::div(get_string('valid_demande_le', 'local_simhub', userdate($d->timecreated, $format)),
                    'small text-muted')
                . \html_writer::div(get_string(empty($d->lottoken) ? 'valid_lien_individuel' : 'valid_lien_groupe',
                    'local_simhub'), 'small text-muted');

            $signature = '';
            if (asv_valanimal::signature_valide((string) $d->signature)) {
                $signature = \html_writer::empty_tag('img', [
                    'src' => $d->signature,
                    'alt' => get_string('valid_signature_de', 'local_simhub', s($d->prenomvalidateur . ' ' . $d->nomvalidateur)),
                    'class' => 'local-simhub-signature-apercu',
                ]);
            }

            $indices = asv_valanimal::indices($d, $etudiant ?: null);
            $params = ['ids[]' => $d->id, 'sesskey' => sesskey()];
            $table->data[] = [
                selection::case('ids', $d->id, $nom . ' — ' . $acte),
                \html_writer::link(navigation::url('/local/simhub/asv/etudiant.php', ['userid' => $d->userid]), s($nom)),
                s($acte),
                $signataire,
                $dates,
                $signature,
                $indices
                    ? implode('', array_map(fn($i) => \html_writer::div('⚠ ' . s($i), 'text-danger small'), $indices))
                    : \html_writer::span(get_string('valid_aucun_indice', 'local_simhub'), 'small text-muted'),
                \html_writer::link(new \moodle_url($url, $params + ['action' => 'confirmer']),
                    get_string('valid_confirmer', 'local_simhub'), ['class' => 'btn btn-sm btn-success']),
            ];
        }

        return self::raccourci($url, 'ids', self::signatures_sans_indice($demandes), 'confirmer',
                'valid_sans_indice')
            . self::formulaire($url, $table, [
                'confirmer' => [get_string('asv_controle_confirmer', 'local_simhub'), 'btn-success'],
                'rejeter' => [get_string('asv_controle_rejeter', 'local_simhub'), 'btn-outline-danger'],
            ], 'asv_controle_motif', count($demandes) > 10);
    }

    /**
     * Répartition des niveaux d'auto-évaluation par séance.
     *
     * @param int[] $sessionids
     * @return array sessionid => ['reussi' => int, 'consolider' => int, 'reprendre' => int]
     */
    protected static function autoevals(array $sessionids): array {
        global $DB;

        if (!$sessionids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED);
        $rs = $DB->get_recordset_sql(
            "SELECT sessionid, niveau, COUNT(1) AS nb
               FROM {local_simhub_ae_reponse}
              WHERE sessionid $insql
           GROUP BY sessionid, niveau",
            $params
        );
        $cles = ['reussi' => 'reussi', 'a_consolider' => 'consolider', 'a_reprendre' => 'reprendre'];
        $res = [];
        foreach ($rs as $r) {
            $sid = (int) $r->sessionid;
            $res[$sid] = $res[$sid] ?? ['reussi' => 0, 'consolider' => 0, 'reprendre' => 0];
            if (isset($cles[$r->niveau])) {
                $res[$sid][$cles[$r->niveau]] += (int) $r->nb;
            }
        }
        $rs->close();
        return $res;
    }

    /**
     * Bouton qui traite en une fois toutes les lignes sans anomalie.
     *
     * @param \moodle_url $url
     * @param string $champ Nom du paramètre tableau des identifiants.
     * @param int[] $ids
     * @param string $action
     * @param string $libelle Chaîne local_simhub, reçoit le nombre de lignes.
     * @return string HTML
     */
    protected static function raccourci(\moodle_url $url, string $champ, array $ids, string $action, string $libelle): string {
        if (!$ids) {
            return '';
        }
        $html = \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()])
            . \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'action', 'value' => $action]);
        foreach ($ids as $id) {
            $html .= \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $champ . '[]', 'value' => $id]);
        }
        $html .= \html_writer::tag('button', get_string($libelle, 'local_simhub', count($ids)), [
            'type' => 'submit', 'class' => 'btn btn-success',
        ]);
        return \html_writer::tag('form', $html, ['method' => 'post', 'action' => $url->out(false), 'class' => 'mb-3']);
    }

    /**
     * Formulaire de sélection : commentaire, barre d'actions groupées et tableau.
     *
     * @param \moodle_url $url
     * @param \html_table $table
     * @param array $actions Voir selection::barre().
     * @param string $commentaire Chaîne local_simhub du libellé du commentaire.
     * @param bool $filtre
     * @return string HTML
     */
    protected static function formulaire(\moodle_url $url, \html_table $table, array $actions, string $commentaire,
            bool $filtre): string {
        $html = \html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
        $html .= \html_writer::div(\html_writer::empty_tag('input', [
            'type' => 'text', 'name' => 'commentaire', 'class' => 'form-control form-control-sm',
            'placeholder' => get_string($commentaire, 'local_simhub'),
            'aria-label' => get_string($commentaire, 'local_simhub'),
        ]), 'mb-2');
        $html .= selection::barre($actions, $filtre);
        $html .= \html_writer::div(\html_writer::table($table), 'table-responsive');
        return \html_writer::tag('form', $html, [
            'method' => 'post', 'action' => $url->out(false), 'class' => selection::CONTENEUR,
        ]);
    }
}
