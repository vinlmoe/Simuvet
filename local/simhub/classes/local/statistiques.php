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
 * Critères d'auto-évaluation fréquemment déclarés à consolider ou à reprendre (§7.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

use local_simhub\record\ae_reponse;

/**
 * Agrégats des réponses d'auto-évaluation.
 */
class statistiques {
    /**
     * Répartition des réponses par critère de la grille d'un atelier.
     *
     * @param int $atelierid
     * @param int[]|null $userids Restreindre à ces étudiants (étudiants d'une UC), null pour tous.
     * @return array Lignes triées par taux « à reprendre » puis « à consolider » décroissants :
     *               [rubrique, critere, estrisques, total, reussi, a_consolider, a_reprendre]
     */
    public static function criteres(int $atelierid, ?array $userids = null): array {
        global $DB;

        if ($userids === []) {
            return [];
        }
        $params = ['atelierid' => $atelierid];
        $filtre = '';
        if ($userids !== null) {
            [$insql, $inparams] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED, 'u');
            $filtre = "AND s.userid $insql";
            $params += $inparams;
        }
        $sql = "SELECT c.id, rb.titre AS rubrique, rb.ordre AS rordre, rb.estrubriquerisques, c.libelle, c.ordre,
                       COUNT(r.id) AS total,
                       SUM(CASE WHEN r.niveau = :reussi THEN 1 ELSE 0 END) AS reussi,
                       SUM(CASE WHEN r.niveau = :consolider THEN 1 ELSE 0 END) AS aconsolider,
                       SUM(CASE WHEN r.niveau = :reprendre THEN 1 ELSE 0 END) AS areprendre
                  FROM {local_simhub_ae_modele} m
                  JOIN {local_simhub_ae_rubrique} rb ON rb.modeleid = m.id
                  JOIN {local_simhub_ae_critere} c ON c.rubriqueid = rb.id
                  JOIN {local_simhub_ae_reponse} r ON r.critereid = c.id
                  JOIN {local_simhub_session} s ON s.id = r.sessionid
                 WHERE m.atelierid = :atelierid $filtre
              GROUP BY c.id, rb.titre, rb.ordre, rb.estrubriquerisques, c.libelle, c.ordre";
        $params += ['reussi' => ae_reponse::NIVEAU_REUSSI, 'consolider' => ae_reponse::NIVEAU_A_CONSOLIDER,
            'reprendre' => ae_reponse::NIVEAU_A_REPRENDRE];

        $lignes = [];
        foreach ($DB->get_records_sql($sql, $params) as $r) {
            $total = (int) $r->total;
            $lignes[] = (object) [
                'rubrique' => $r->rubrique,
                'critere' => $r->libelle,
                'estrisques' => (bool) $r->estrubriquerisques,
                'total' => $total,
                'reussi' => (int) $r->reussi,
                'aconsolider' => (int) $r->aconsolider,
                'areprendre' => (int) $r->areprendre,
                'pctconsolider' => $total ? (int) round(100 * $r->aconsolider / $total) : 0,
                'pctreprendre' => $total ? (int) round(100 * $r->areprendre / $total) : 0,
            ];
        }
        usort($lignes, fn($a, $b) => [$b->pctreprendre, $b->pctconsolider] <=> [$a->pctreprendre, $a->pctconsolider]);
        return $lignes;
    }

    /**
     * Synthèse par atelier : part des réponses « à consolider » et « à reprendre ».
     *
     * @param int[] $atelierids
     * @param int[]|null $userids
     * @return array atelierid => (object) [total, pctconsolider, pctreprendre]
     */
    public static function ateliers(array $atelierids, ?array $userids = null): array {
        $res = [];
        foreach ($atelierids as $aid) {
            $lignes = self::criteres((int) $aid, $userids);
            $total = array_sum(array_column($lignes, 'total'));
            $res[$aid] = (object) [
                'total' => $total,
                'pctconsolider' => $total ? (int) round(100 * array_sum(array_column($lignes, 'aconsolider')) / $total) : 0,
                'pctreprendre' => $total ? (int) round(100 * array_sum(array_column($lignes, 'areprendre')) / $total) : 0,
            ];
        }
        return $res;
    }
}
