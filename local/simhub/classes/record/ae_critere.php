<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Critère observable d'une rubrique d'auto-évaluation guidée (§5.6, §7.2).
 */
class ae_critere {

    const TABLE = 'local_simhub_ae_critere';

    /**
     * Ajoute un critère à une rubrique.
     *
     * @param int $rubriqueid
     * @param string $libelle
     * @param int $ordre
     * @return int
     */
    public static function ajouter(int $rubriqueid, string $libelle, int $ordre = 0): int {
        global $DB;

        return $DB->insert_record(self::TABLE, (object) [
            'rubriqueid' => $rubriqueid,
            'libelle' => $libelle,
            'ordre' => $ordre,
        ]);
    }

    /**
     * Critères d'une rubrique, dans l'ordre d'affichage.
     *
     * @param int $rubriqueid
     * @return \stdClass[]
     */
    public static function get_pour_rubrique(int $rubriqueid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['rubriqueid' => $rubriqueid], 'ordre ASC');
    }
}
