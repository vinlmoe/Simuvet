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
 * Recherche/filtrage des ateliers pour l'accueil étudiant (§5.2).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Recherche/filtrage des ateliers pour l'accueil étudiant (§5.2).
 *
 * Regroupe les critères de tri/recherche listés au cahier des charges : UC, parcours,
 * année d'étude, discipline, espèce, niveau de difficulté, durée indicative, statut
 * personnel, statut atelier, mot-clé. Reste volontairement un simple filtre SQL : pas de
 * moteur de recherche dédié en V1.
 */
class atelier_filter {
    /** @var string */
    public $envcode = '';
    /** @var int */
    public $courseid = 0;
    /** @var int */
    public $parcoursid = 0;
    /** @var int */
    public $anneeetude = 0;
    /** @var string */
    public $discipline = '';
    /** @var string */
    public $espece = '';
    /** @var string */
    public $niveaudifficulte = '';
    /** @var int */
    public $dureemax = 0;
    /** @var string */
    public $motcle = '';
    /** @var string Statut personnel filtré (pascommence, commence, realise, valide, areprendre), vide pour tous. */
    public $statutperso = '';

    /** Statuts personnels proposés au filtre (§5.2). */
    const STATUTS_PERSO = ['pascommence', 'commence', 'realise', 'valide', 'areprendre'];

    /** Valeur de filtre : ateliers montrés aux étudiants, actifs ou momentanément indisponibles (§5.3, §6.1). */
    const STATUT_VISIBLES = 'visibles';

    /** @var string Statut d'atelier filtré, STATUT_VISIBLES par défaut. */
    public $statut = self::STATUT_VISIBLES;

    /**
     * Construit un filtre à partir des paramètres GET de la requête courante.
     *
     * @return atelier_filter
     */
    public static function from_request(): atelier_filter {
        $filter = new self();
        $filter->envcode = '';
        $filter->courseid = optional_param('courseid', 0, PARAM_INT);
        $filter->parcoursid = optional_param('parcoursid', 0, PARAM_INT);
        $filter->anneeetude = optional_param('anneeetude', 0, PARAM_INT);
        $filter->discipline = optional_param('discipline', '', PARAM_TEXT);
        $filter->espece = optional_param('espece', '', PARAM_TEXT);
        $filter->niveaudifficulte = optional_param('niveaudifficulte', '', PARAM_ALPHA);
        $filter->dureemax = optional_param('dureemax', 0, PARAM_INT);
        $filter->motcle = optional_param('motcle', '', PARAM_TEXT);
        // Un étudiant ne peut pas afficher les ateliers archivés ou non utilisés (§5.2).
        $statut = optional_param('statut', self::STATUT_VISIBLES, PARAM_ALPHAEXT);
        $filter->statut = in_array($statut, [self::STATUT_VISIBLES, \local_simhub\persistent\atelier::STATUT_ACTIF,
            \local_simhub\persistent\atelier::STATUT_INDISPONIBLE], true) ? $statut : self::STATUT_VISIBLES;
        $statutperso = optional_param('statutperso', '', PARAM_ALPHA);
        $filter->statutperso = in_array($statutperso, self::STATUTS_PERSO, true) ? $statutperso : '';
        return $filter;
    }

    /**
     * Exécute la recherche et retourne les ateliers correspondants.
     *
     * @return \stdClass[] Enregistrements bruts d'atelier (pas de persistent, pour rester
     *                      léger côté rendu : la carte étudiante n'a besoin que de lire).
     */
    public function get_ateliers(): array {
        global $DB;

        [$where, $params] = $this->build_where();
        $sql = "SELECT a.*
                  FROM {local_simhub_atelier} a
                 WHERE $where
              ORDER BY a.nomcourt ASC";

        return $DB->get_records_sql($sql, $params);
    }

    /**
     * Clause WHERE et paramètres correspondant aux filtres.
     *
     * @return array [string $where, array $params]
     */
    private function build_where(): array {
        $conditions = ['1=1'];
        $params = [];

        if ($this->envcode !== '') {
            $conditions[] = 'a.envcode = :envcode';
            $params['envcode'] = $this->envcode;
        }
        if ($this->statut === self::STATUT_VISIBLES) {
            $conditions[] = 'a.statut IN (:statutactif, :statutindispo)';
            $params['statutactif'] = \local_simhub\persistent\atelier::STATUT_ACTIF;
            $params['statutindispo'] = \local_simhub\persistent\atelier::STATUT_INDISPONIBLE;
        } else if ($this->statut !== '') {
            $conditions[] = 'a.statut = :statut';
            $params['statut'] = $this->statut;
        }
        if ($this->discipline !== '') {
            $conditions[] = $this->like_condition('a.discipline', 'discipline');
            $params['discipline'] = $this->discipline;
        }
        if ($this->espece !== '') {
            $conditions[] = $this->like_condition('a.espece', 'espece');
            $params['espece'] = '%' . $this->espece . '%';
        }
        if ($this->niveaudifficulte !== '') {
            $conditions[] = 'a.niveaudifficulte = :niveaudifficulte';
            $params['niveaudifficulte'] = $this->niveaudifficulte;
        }
        if ($this->dureemax > 0) {
            $conditions[] = 'a.dureeindicative <= :dureemax';
            $params['dureemax'] = $this->dureemax;
        }
        if ($this->motcle !== '') {
            $conditions[] = '(' . $this->like_condition('a.nomcourt', 'motcle1')
                . ' OR ' . $this->like_condition('a.nomlong', 'motcle2') . ')';
            $params['motcle1'] = '%' . $this->motcle . '%';
            $params['motcle2'] = '%' . $this->motcle . '%';
        }
        if ($this->courseid > 0) {
            $conditions[] = 'a.id IN (SELECT r.atelierid FROM {local_simhub_rattachement} r WHERE r.courseid = :courseid)';
            $params['courseid'] = $this->courseid;
        }
        if ($this->parcoursid > 0) {
            $conditions[] = 'a.id IN (SELECT pa.atelierid FROM {local_simhub_parc_atelier} pa WHERE pa.parcoursid = :parcoursid)';
            $params['parcoursid'] = $this->parcoursid;
        }
        if ($this->anneeetude > 0) {
            $conditions[] = 'a.id IN (SELECT r2.atelierid FROM {local_simhub_rattachement} r2 WHERE r2.anneeetude = :anneeetude)';
            $params['anneeetude'] = $this->anneeetude;
        }

        return [implode(' AND ', $conditions), $params];
    }

    /**
     * Condition LIKE insensible à la casse et aux accents.
     *
     * @param string $field
     * @param string $paramname
     * @return string
     */
    private function like_condition(string $field, string $paramname): string {
        global $DB;

        return $DB->sql_like($field, ":$paramname", false, false);
    }
}
