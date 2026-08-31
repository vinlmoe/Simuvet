<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Validation générique d'une session par un encadrant Moodle (hors ASV, §7.1).
 */
class val_encadrant {

    const TABLE = 'local_simhub_val_encadrant';

    const STATUT_VALIDE = 'valide';
    const STATUT_REFUSE = 'refuse';

    /**
     * Enregistre la validation (ou le refus) d'une session par un encadrant.
     *
     * @param int $sessionid
     * @param int $validateuruserid
     * @param string $statut valide|refuse.
     * @param string $commentaire
     * @return int
     */
    public static function valider(int $sessionid, int $validateuruserid, string $statut,
            string $commentaire = ''): int {
        global $DB;

        $now = time();
        return $DB->insert_record(self::TABLE, (object) [
            'sessionid' => $sessionid,
            'validateuruserid' => $validateuruserid,
            'datevalidation' => $now,
            'statut' => $statut,
            'commentaire' => $commentaire,
            'timecreated' => $now,
        ]);
    }

    /**
     * Validations d'une session, la plus récente d'abord.
     *
     * @param int $sessionid
     * @return \stdClass[]
     */
    public static function get_pour_session(int $sessionid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['sessionid' => $sessionid], 'datevalidation DESC');
    }
}
