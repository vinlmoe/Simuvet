<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Historique des indisponibilités d'un atelier (§6.1).
 *
 * Table volontairement plate (commentaire, référent, échéance), sans
 * ticketing complet (§14) : classe légère par $DB direct plutôt que
 * core\persistent, la table n'ayant pas de timemodified/usermodified.
 */
class indispo {

    const TABLE = 'local_simhub_indispo';

    /**
     * Ouvre une nouvelle indisponibilité pour un atelier.
     *
     * @param int $atelierid
     * @param string $commentaire
     * @param int|null $referentuserid
     * @param int|null $echeanceprevue Timestamp, si connue.
     * @return int Id de l'enregistrement créé.
     */
    public static function ouvrir(int $atelierid, string $commentaire, ?int $referentuserid = null,
            ?int $echeanceprevue = null): int {
        global $DB;

        $now = time();
        return $DB->insert_record(self::TABLE, (object) [
            'atelierid' => $atelierid,
            'commentaire' => $commentaire,
            'referentuserid' => $referentuserid,
            'datemaj' => $now,
            'echeanceprevue' => $echeanceprevue,
            'cloturee' => 0,
            'timecreated' => $now,
        ]);
    }

    /**
     * Clôture l'indisponibilité en cours d'un atelier, s'il y en a une.
     *
     * @param int $atelierid
     * @return void
     */
    public static function cloturer(int $atelierid): void {
        global $DB;

        $DB->set_field(self::TABLE, 'cloturee', 1, ['atelierid' => $atelierid, 'cloturee' => 0]);
    }

    /**
     * Indisponibilité en cours (non clôturée) pour un atelier, s'il y en a une.
     *
     * @param int $atelierid
     * @return \stdClass|false
     */
    public static function get_en_cours(int $atelierid) {
        global $DB;

        $records = $DB->get_records(
            self::TABLE,
            ['atelierid' => $atelierid, 'cloturee' => 0],
            'datemaj DESC',
            '*',
            0,
            1
        );
        return $records ? reset($records) : false;
    }

    /**
     * Historique complet des indisponibilités d'un atelier, la plus récente d'abord.
     *
     * @param int $atelierid
     * @return \stdClass[]
     */
    public static function get_historique(int $atelierid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['atelierid' => $atelierid], 'datemaj DESC');
    }
}
