<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Sélection directe d'une cohorte Moodle existante ("groupe" au sens du cahier des
 * charges), pour recommander un atelier ou un parcours à ses membres (§5.1, §6, §8).
 *
 * Le gestionnaire choisit la cohorte dans une liste réelle plutôt que de saisir un
 * identifiant numérique ou de laisser SimHub déduire un groupe par inférence : la
 * correspondance avec l'étudiant se fait ensuite par appartenance effective
 * (table Moodle cohort_members), sans reflexion sur un nom de groupe.
 */
class cohort_helper {

    /**
     * Options {cohortid => libellé} pour un élément de formulaire select, triées par nom.
     *
     * @param bool $withempty Ajoute une option vide en tête.
     * @return array
     */
    public static function get_options(bool $withempty = true): array {
        global $DB;

        $options = $withempty ? ['' => ''] : [];
        $cohorts = $DB->get_records('cohort', null, 'name ASC', 'id, name, idnumber');
        foreach ($cohorts as $cohort) {
            $label = $cohort->name;
            if (!empty($cohort->idnumber)) {
                $label .= ' (' . $cohort->idnumber . ')';
            }
            $options[$cohort->id] = $label;
        }
        return $options;
    }

    /**
     * Cohortes (ids) dont l'utilisateur est membre.
     *
     * @param int $userid
     * @return int[]
     */
    public static function get_cohortes_utilisateur(int $userid): array {
        global $DB;

        return array_values($DB->get_records_menu(
            'cohort_members',
            ['userid' => $userid],
            '',
            'id, cohortid'
        ));
    }
}
