<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Détermine l'année d'étude (A1 à A5) d'un étudiant à partir de ses groupes Moodle, faute
 * de champ standard dédié dans Moodle pour cette information (§5.1 "recommandés pour mon
 * année").
 *
 * Chaque ENV nomme ses groupes différemment ; plutôt qu'imposer une convention, l'année est
 * extraite par une expression régulière paramétrable (local_simhub/groupeanneeregex),
 * appliquée au nom de chacun des groupes Moodle de l'étudiant (tous cours confondus, les
 * ateliers étant transverses aux UC, §4). Le premier groupe dont le nom correspond et dont
 * le chiffre capturé est compris entre 1 et 5 détermine l'année retenue.
 */
class annee_resolver {

    /** Motif par défaut : reconnaît "A1".."A5", "Année 1".."Année 5", insensible à la casse. */
    const DEFAULT_PATTERN = '/A(?:nn[ée]e)?\s*([1-5])\b/i';

    /** Années d'étude possibles, du cursus vétérinaire A1 à A5. */
    const ANNEES = [1, 2, 3, 4, 5];

    /**
     * Année d'étude déduite des groupes Moodle de l'étudiant, si trouvée.
     *
     * @param int $userid
     * @return int|null Un entier entre 1 et 5, ou null si aucun groupe ne correspond.
     */
    public static function get_annee_etudiant(int $userid): ?int {
        global $DB;

        $pattern = get_config('local_simhub', 'groupeanneeregex');
        $pattern = $pattern !== false && $pattern !== '' ? $pattern : self::DEFAULT_PATTERN;

        $groups = $DB->get_records_sql(
            "SELECT g.id, g.name
               FROM {groups} g
               JOIN {groups_members} gm ON gm.groupid = g.id
              WHERE gm.userid = :userid",
            ['userid' => $userid]
        );

        foreach ($groups as $group) {
            $matches = [];
            if (@preg_match($pattern, $group->name, $matches) && !empty($matches[1])) {
                $annee = (int) $matches[1];
                if (in_array($annee, self::ANNEES, true)) {
                    return $annee;
                }
            }
        }

        return null;
    }

    /**
     * Libellé affiché pour une année (A1, A2...).
     *
     * @param int $annee
     * @return string
     */
    public static function get_label(int $annee): string {
        return 'A' . $annee;
    }

    /**
     * Options {annee => libellé} pour un élément de formulaire select.
     *
     * @param bool $withempty Ajoute une option vide en tête.
     * @return array
     */
    public static function get_options(bool $withempty = true): array {
        $options = $withempty ? ['' => ''] : [];
        foreach (self::ANNEES as $annee) {
            $options[$annee] = self::get_label($annee);
        }
        return $options;
    }
}
