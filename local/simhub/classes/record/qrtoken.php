<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Jeton QR code associé à un atelier (§7) : relie l'objet physique à sa fiche numérique.
 */
class qrtoken {

    const TABLE = 'local_simhub_qrtoken';

    /**
     * Crée (ou renvoie) le jeton QR actif d'un atelier.
     *
     * @param int $atelierid
     * @return \stdClass
     */
    public static function get_ou_creer(int $atelierid): \stdClass {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['atelierid' => $atelierid, 'actif' => 1]);
        if ($existing) {
            return $existing;
        }

        $record = (object) [
            'atelierid' => $atelierid,
            'token' => \core\uuid::generate(),
            'actif' => 1,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Retrouve l'atelier associé à un jeton QR actif.
     *
     * @param string $token
     * @return \stdClass|false
     */
    public static function get_par_token(string $token) {
        global $DB;

        return $DB->get_record(self::TABLE, ['token' => $token, 'actif' => 1]);
    }

    /**
     * Régénère le jeton QR d'un atelier (désactive l'ancien, en crée un nouveau).
     *
     * @param int $atelierid
     * @return \stdClass
     */
    public static function regenerer(int $atelierid): \stdClass {
        global $DB;

        $DB->set_field(self::TABLE, 'actif', 0, ['atelierid' => $atelierid, 'actif' => 1]);
        return self::get_ou_creer($atelierid);
    }
}
