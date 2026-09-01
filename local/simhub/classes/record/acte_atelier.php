<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Liaison N-N acte ASV / atelier permettant de le pratiquer (§9.2). Sert à restreindre la
 * liste des actes proposés lors d'une validation en simulation à ceux réellement
 * pratiqués dans l'atelier concerné, plutôt que la totalité du référentiel.
 */
class acte_atelier {

    const TABLE = 'local_simhub_asv_acte_atelier';

    /**
     * Lie un acte à un atelier, sans doublon.
     *
     * @param int $acteid
     * @param int $atelierid
     * @return void
     */
    public static function lier(int $acteid, int $atelierid): void {
        global $DB;

        if ($DB->record_exists(self::TABLE, ['acteid' => $acteid, 'atelierid' => $atelierid])) {
            return;
        }

        $DB->insert_record(self::TABLE, (object) [
            'acteid' => $acteid,
            'atelierid' => $atelierid,
        ]);
    }

    /**
     * Retire le lien entre un acte et un atelier.
     *
     * @param int $acteid
     * @param int $atelierid
     * @return void
     */
    public static function delier(int $acteid, int $atelierid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['acteid' => $acteid, 'atelierid' => $atelierid]);
    }

    /**
     * Ateliers liés à un acte, avec leur numéro/nom.
     *
     * @param int $acteid
     * @return \stdClass[]
     */
    public static function get_ateliers_pour_acte(int $acteid): array {
        global $DB;

        return $DB->get_records_sql(
            'SELECT a.id, a.numero, a.nomcourt
               FROM {local_simhub_atelier} a
               JOIN {local_simhub_asv_acte_atelier} l ON l.atelierid = a.id
              WHERE l.acteid = :acteid
           ORDER BY a.numero ASC',
            ['acteid' => $acteid]
        );
    }

    /**
     * Actes liés à un atelier, pour restreindre le formulaire de validation en simulation.
     *
     * @param int $atelierid
     * @return \stdClass[]
     */
    public static function get_actes_pour_atelier(int $atelierid): array {
        global $DB;

        return $DB->get_records_sql(
            'SELECT ac.*
               FROM {local_simhub_asv_acte} ac
               JOIN {local_simhub_asv_acte_atelier} l ON l.acteid = ac.id
              WHERE l.atelierid = :atelierid AND ac.actif = 1
           ORDER BY ac.niveau, ac.code',
            ['atelierid' => $atelierid]
        );
    }
}
