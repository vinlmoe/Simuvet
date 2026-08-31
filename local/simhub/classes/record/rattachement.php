<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Rattachement pédagogique d'un atelier à une UC / année / cohorte (§6, hors parcours).
 */
class rattachement {

    const TABLE = 'local_simhub_rattachement';

    const CARACTERE_RECOMMANDE = 'recommande';
    const CARACTERE_OBLIGATOIRE = 'obligatoire';

    /**
     * Crée un rattachement pédagogique.
     *
     * @param int $atelierid
     * @param array $data courseid, anneeetude, cohortid, caractere, niveauattendu.
     * @return int Id créé.
     */
    public static function creer(int $atelierid, array $data): int {
        global $DB;

        $record = (object) array_merge([
            'atelierid' => $atelierid,
            'courseid' => null,
            'anneeetude' => null,
            'cohortid' => null,
            'caractere' => self::CARACTERE_RECOMMANDE,
            'niveauattendu' => null,
            'timecreated' => time(),
        ], $data);

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Rattachements d'un atelier.
     *
     * @param int $atelierid
     * @return \stdClass[]
     */
    public static function get_pour_atelier(int $atelierid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['atelierid' => $atelierid]);
    }

    /**
     * Ateliers rattachés à une UC Moodle donnée (utile à l'accueil étudiant "À faire pour mes UC", §5.1).
     *
     * @param int $courseid
     * @return \stdClass[]
     */
    public static function get_pour_uc(int $courseid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['courseid' => $courseid]);
    }

    /**
     * Rattachements pour une année d'étude donnée (A1 à A5).
     *
     * @param int $anneeetude
     * @return \stdClass[]
     */
    public static function get_pour_annee(int $anneeetude): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['anneeetude' => $anneeetude]);
    }

    /**
     * Rattachements pointant vers l'une des cohortes données, utilisés par l'accueil
     * étudiant pour la section "Recommandés pour mon groupe" (§5.1) : le gestionnaire
     * choisit directement la cohorte concernée (manage/rattachements.php), la
     * correspondance avec l'étudiant se faisant ensuite par appartenance réelle plutôt que
     * par déduction.
     *
     * @param int[] $cohortids
     * @return \stdClass[]
     */
    public static function get_pour_cohortes(array $cohortids): array {
        global $DB;

        if (empty($cohortids)) {
            return [];
        }

        [$insql, $params] = $DB->get_in_or_equal($cohortids);
        return $DB->get_records_select(self::TABLE, "cohortid $insql", $params);
    }

    /**
     * Supprime un rattachement.
     *
     * @param int $id
     * @return void
     */
    public static function supprimer(int $id): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['id' => $id]);
    }
}
