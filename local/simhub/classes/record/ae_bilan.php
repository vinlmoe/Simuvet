<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Auto-bilan final libre (trois champs) associé à une session (§5.6) :
 * point le mieux maîtrisé, point à retravailler, point d'attention pour le prochain essai.
 */
class ae_bilan {

    const TABLE = 'local_simhub_ae_bilan';

    /**
     * Enregistre (ou remplace) l'auto-bilan d'une session.
     *
     * @param int $sessionid
     * @param string $pointmaitrise
     * @param string $pointaretravailler
     * @param string $pointattention
     * @return int
     */
    public static function enregistrer(int $sessionid, string $pointmaitrise, string $pointaretravailler,
            string $pointattention): int {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['sessionid' => $sessionid]);
        $record = (object) [
            'sessionid' => $sessionid,
            'pointmaitrise' => $pointmaitrise,
            'pointaretravailler' => $pointaretravailler,
            'pointattention' => $pointattention,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return $existing->id;
        }

        $record->timecreated = time();
        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Auto-bilan d'une session, s'il existe.
     *
     * @param int $sessionid
     * @return \stdClass|false
     */
    public static function get_pour_session(int $sessionid) {
        global $DB;

        return $DB->get_record(self::TABLE, ['sessionid' => $sessionid]);
    }
}
