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
 * Règles communes des parcours (§8) : à qui s'adresse un parcours, quels ateliers
 * comptent pour son achèvement, et avancement d'un étudiant. Une seule implémentation
 * pour l'accueil étudiant, le suivi, le tableau de bord, l'export et l'attestation.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

use local_simhub\persistent\parcours;
use local_simhub\persistent\session;

/**
 * Règles d'affectation et d'avancement des parcours.
 */
class parcours_helper {
    /**
     * Ateliers qui conditionnent l'achèvement : les ateliers marqués obligatoires s'il y
     * en a au moins un, sinon tous les ateliers du parcours (§8.1).
     *
     * @param parcours $parcours
     * @return int[] Identifiants d'ateliers, dans l'ordre du parcours.
     */
    public static function ateliers_requis(parcours $parcours): array {
        $composition = $parcours->get_ateliers();
        $obligatoires = array_filter($composition, fn($l) => !empty($l->obligatoire));
        return array_map('intval', array_column($obligatoires ?: $composition, 'atelierid'));
    }

    /**
     * Statut de chaque étudiant sur chaque atelier, d'après sa séance la plus récente, en une
     * requête par lot plutôt qu'une par étudiant et par atelier.
     *
     * @param int[] $userids
     * @param int[] $atelierids
     * @return array [userid][atelierid] => pascommence|commence|realise|valide|areprendre
     */
    public static function statuts(array $userids, array $atelierids): array {
        $res = [];
        foreach (self::dernieres_seances($userids, $atelierids) as $uid => $parseance) {
            $res[$uid] = array_map(fn($s) => $s ? self::libelle($s->statut) : 'pascommence', $parseance);
        }
        return $res;
    }

    /**
     * Séance la plus récente de chaque étudiant sur chaque atelier, par lot.
     *
     * @param int[] $userids
     * @param int[] $atelierids
     * @return array [userid][atelierid] => \stdClass (id, userid, atelierid, statut) ou null
     */
    public static function dernieres_seances(array $userids, array $atelierids): array {
        global $DB;

        $userids = array_values(array_unique(array_map('intval', $userids)));
        $atelierids = array_values(array_unique(array_map('intval', $atelierids)));
        $res = [];
        foreach ($userids as $uid) {
            $res[$uid] = array_fill_keys($atelierids, null);
        }
        if (!$userids || !$atelierids) {
            return $res;
        }
        [$ainsql, $aparams] = $DB->get_in_or_equal($atelierids, SQL_PARAMS_NAMED, 'a');
        $vus = [];
        foreach (array_chunk($userids, 500) as $lot) {
            [$uinsql, $uparams] = $DB->get_in_or_equal($lot, SQL_PARAMS_NAMED, 'u');
            $rs = $DB->get_recordset_sql(
                "SELECT id, userid, atelierid, statut
                   FROM {local_simhub_session}
                  WHERE userid $uinsql AND atelierid $ainsql
               ORDER BY timestart DESC, id DESC",
                $uparams + $aparams
            );
            foreach ($rs as $s) {
                $cle = $s->userid . '-' . $s->atelierid;
                if (!isset($vus[$cle])) {
                    $vus[$cle] = true;
                    $res[(int) $s->userid][(int) $s->atelierid] = $s;
                }
            }
            $rs->close();
        }
        return $res;
    }

    /**
     * Statut personnel correspondant au statut d'une séance.
     *
     * @param string $statutseance session::STATUT_*
     * @return string
     */
    protected static function libelle(string $statutseance): string {
        switch ($statutseance) {
            case session::STATUT_CERTIFIE:
                return 'valide';
            case session::STATUT_REALISE:
                return 'realise';
            case session::STATUT_NON_TERMINE:
                return 'areprendre';
            default:
                return 'commence';
        }
    }

    /**
     * Statut d'un étudiant sur un atelier, d'après sa séance la plus récente.
     *
     * @param int $userid
     * @param int $atelierid
     * @return string pascommence|commence|realise|valide|areprendre
     */
    public static function statut_atelier(int $userid, int $atelierid): string {
        return self::statuts([$userid], [$atelierid])[$userid][$atelierid];
    }

    /**
     * Avancement de plusieurs étudiants dans un parcours.
     *
     * @param parcours $parcours
     * @param int[] $userids
     * @return array userid => ['realises' => int, 'total' => int, 'pct' => int]
     */
    public static function progressions(parcours $parcours, array $userids): array {
        $requis = self::ateliers_requis($parcours);
        $total = count($requis);
        $res = [];
        foreach (self::statuts($userids, $requis) as $uid => $parstatut) {
            $realises = count(array_filter($parstatut, fn($st) => in_array($st, ['realise', 'valide'], true)));
            $res[$uid] = ['realises' => $realises, 'total' => $total,
                'pct' => $total ? (int) round(100 * $realises / $total) : 0];
        }
        return $res;
    }

    /**
     * Avancement d'un étudiant dans un parcours.
     *
     * @param parcours $parcours
     * @param int $userid
     * @return array ['realises' => int, 'total' => int, 'pct' => int]
     */
    public static function progression(parcours $parcours, int $userid): array {
        return self::progressions($parcours, [$userid])[$userid];
    }

    /**
     * Parcours proposés à un étudiant : ceux de ses cohortes, ceux liés à une UC où il est
     * inscrit, et ceux dont il a déjà commencé un atelier.
     *
     * @param int $userid
     * @param string $envcode
     * @return parcours[] indexés par id
     */
    public static function parcours_pour_etudiant(int $userid, string $envcode): array {
        global $DB;

        $params = $envcode !== '' ? ['envcode' => $envcode] : [];
        $cohortids = cohort_helper::get_cohortes_utilisateur($userid);
        $courseids = array_keys(enrol_get_users_courses($userid, true, 'id'));
        $atelierscommences = $DB->get_fieldset_select('local_simhub_session', 'DISTINCT atelierid', 'userid = ?', [$userid]);

        $resultat = [];
        foreach (parcours::get_records($params, 'nom') as $p) {
            $concerne = ($p->get('cohortid') && in_array($p->get('cohortid'), $cohortids))
                || ($p->get('courseid') && in_array($p->get('courseid'), $courseids))
                || array_intersect(array_column($p->get_ateliers(), 'atelierid'), $atelierscommences);
            if ($concerne && $p->get_ateliers()) {
                $resultat[$p->get('id')] = $p;
            }
        }
        return $resultat;
    }

    /**
     * Étudiants suivis dans un parcours : membres de sa cohorte, inscrits de son UC, et
     * étudiants ayant commencé l'un de ses ateliers.
     *
     * @param parcours $parcours
     * @return \stdClass[] id, firstname, lastname... triés par nom.
     */
    public static function etudiants(parcours $parcours): array {
        global $DB;

        $userids = [];
        if ($parcours->get('cohortid')) {
            $userids = $DB->get_fieldset_select('cohort_members', 'userid', 'cohortid = ?', [$parcours->get('cohortid')]);
        }
        if ($parcours->get('courseid') && $DB->record_exists('course', ['id' => $parcours->get('courseid')])) {
            $coursecontext = \context_course::instance($parcours->get('courseid'));
            $userids = array_merge($userids, array_keys(get_enrolled_users($coursecontext, '', 0, 'u.id', null, 0, 0, true)));
        }
        $atelierids = array_column($parcours->get_ateliers(), 'atelierid');
        if ($atelierids) {
            [$insql, $inparams] = $DB->get_in_or_equal($atelierids);
            $userids = array_merge(
                $userids,
                $DB->get_fieldset_select('local_simhub_session', 'DISTINCT userid', "atelierid $insql", $inparams)
            );
        }
        $userids = array_unique(array_map('intval', $userids));
        if (!$userids) {
            return [];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($userids);
        return $DB->get_records_select(
            'user',
            "id $insql AND deleted = 0",
            $inparams,
            'lastname, firstname',
            'id, ' . implode(', ', \core_user\fields::get_name_fields())
        );
    }

    /**
     * Ajoute ou met à jour un atelier du parcours. Pour un parcours d'UC, l'atelier est
     * aussi rattaché au cours, pour apparaître dans « À faire pour mes UC » et le filtre UC.
     *
     * @param parcours $parcours
     * @param int $atelierid
     * @param int $ordre
     * @param bool $obligatoire
     * @param int|null $echeance
     * @param bool $signaler Faux pendant une restauration : les notes sont recalculées à la fin.
     * @return void
     */
    public static function ajouter_atelier(
        parcours $parcours,
        int $atelierid,
        int $ordre,
        bool $obligatoire,
        ?int $echeance,
        bool $signaler = true
    ): void {
        global $DB;

        \local_simhub\record\parc_atelier::ajouter($parcours->get('id'), $atelierid, $ordre, $obligatoire, $echeance);
        $courseid = (int) $parcours->get('courseid');
        if ($courseid && !$DB->record_exists('local_simhub_rattachement', ['atelierid' => $atelierid, 'courseid' => $courseid])) {
            \local_simhub\record\rattachement::creer($atelierid, [
                'courseid' => $courseid,
                'caractere' => $obligatoire ? \local_simhub\record\rattachement::CARACTERE_OBLIGATOIRE
                    : \local_simhub\record\rattachement::CARACTERE_RECOMMANDE,
            ]);
        }
        if ($signaler) {
            self::signaler_modification($parcours);
        }
    }

    /**
     * Retire un atelier du parcours, et son rattachement à l'UC si plus aucun parcours du
     * cours ne le contient.
     *
     * @param parcours $parcours
     * @param int $atelierid
     * @param bool $signaler Faux à la suppression de l'activité ou du plugin.
     * @return void
     */
    public static function retirer_atelier(parcours $parcours, int $atelierid, bool $signaler = true): void {
        global $DB;

        \local_simhub\record\parc_atelier::retirer($parcours->get('id'), $atelierid);
        $courseid = (int) $parcours->get('courseid');
        if ($courseid) {
            $encore = $DB->record_exists_sql(
                "SELECT 1
                   FROM {local_simhub_parc_atelier} pa
                   JOIN {local_simhub_parcours} p ON p.id = pa.parcoursid
                  WHERE p.courseid = ? AND pa.atelierid = ?",
                [$courseid, $atelierid]
            );
            if (!$encore) {
                $DB->delete_records('local_simhub_rattachement', ['atelierid' => $atelierid, 'courseid' => $courseid]);
            }
        }
        if ($signaler) {
            self::signaler_modification($parcours);
        }
    }

    /**
     * Déclenche l'événement de modification du parcours.
     *
     * @param parcours $parcours
     * @return void
     */
    protected static function signaler_modification(parcours $parcours): void {
        \local_simhub\event\parcours_updated::create([
            'objectid' => $parcours->get('id'),
            'context' => contexte::racine(),
        ])->trigger();
    }

    /**
     * Décision d'un encadrant sur une séance : une validation la certifie (§7.3).
     *
     * @param int $sessionid
     * @param int $validateurid
     * @param string $statut val_encadrant::STATUT_*
     * @param string $commentaire
     * @return void
     */
    public static function valider_seance(int $sessionid, int $validateurid, string $statut, string $commentaire = ''): void {
        \local_simhub\record\val_encadrant::valider($sessionid, $validateurid, $statut, $commentaire);
        $session = new session($sessionid);
        // Une séance refusée ne compte plus comme réalisée : l'atelier est à refaire.
        $session->set('statut', $statut === \local_simhub\record\val_encadrant::STATUT_VALIDE
            ? session::STATUT_CERTIFIE : session::STATUT_NON_TERMINE);
        $session->update();
        \local_simhub\event\session_validated::create([
            'objectid' => $sessionid,
            'relateduserid' => $session->get('userid'),
            'context' => contexte::racine(),
            'other' => ['atelierid' => (int) $session->get('atelierid'), 'statut' => $statut],
        ])->trigger();
    }

    /**
     * Raison pour laquelle une séance attend l'avis d'un encadrant.
     *
     * @param \stdClass $session Enregistrement de local_simhub_session.
     * @return string Texte affichable, vide si rien à signaler.
     */
    public static function motif_a_valider(\stdClass $session): string {
        $motifs = [];
        if ($session->controlepresence === 'non_verifie') {
            $motifs[] = get_string('session_motif_nonverifie', 'local_simhub');
        }
        if (!empty($session->dureesuspecte)) {
            $motifs[] = get_string('session_motif_duree', 'local_simhub', (object) [
                'duree' => (int) round(($session->timeend - $session->timestart) / MINSECS),
                'indicative' => (int) (new \local_simhub\persistent\atelier($session->atelierid))->get('dureeindicative'),
            ]);
        }
        return implode(' ; ', $motifs);
    }
}
