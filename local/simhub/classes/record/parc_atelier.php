<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Liaison N-N parcours / ateliers, avec ordre (§8).
 */
class parc_atelier {

    const TABLE = 'local_simhub_parc_atelier';

    /**
     * Ajoute un atelier à un parcours (ou met à jour son ordre/échéance s'il y est déjà).
     *
     * @param int $parcoursid
     * @param int $atelierid
     * @param int $ordre
     * @param bool $obligatoire
     * @param int|null $echeance
     * @return int Id de l'enregistrement.
     */
    public static function ajouter(int $parcoursid, int $atelierid, int $ordre = 0,
            bool $obligatoire = false, ?int $echeance = null): int {
        global $DB;

        $existing = $DB->get_record(self::TABLE, ['parcoursid' => $parcoursid, 'atelierid' => $atelierid]);
        $record = (object) [
            'parcoursid' => $parcoursid,
            'atelierid' => $atelierid,
            'ordre' => $ordre,
            'obligatoire' => $obligatoire ? 1 : 0,
            'echeance' => $echeance,
        ];

        if ($existing) {
            $record->id = $existing->id;
            $DB->update_record(self::TABLE, $record);
            return $existing->id;
        }

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Retire un atelier d'un parcours.
     *
     * @param int $parcoursid
     * @param int $atelierid
     * @return void
     */
    public static function retirer(int $parcoursid, int $atelierid): void {
        global $DB;

        $DB->delete_records(self::TABLE, ['parcoursid' => $parcoursid, 'atelierid' => $atelierid]);
    }

    /**
     * Parcours (ids) contenant un atelier donné.
     *
     * @param int $atelierid
     * @return int[]
     */
    public static function get_parcours_pour_atelier(int $atelierid): array {
        global $DB;

        return array_values($DB->get_records_menu(self::TABLE, ['atelierid' => $atelierid], '', 'id, parcoursid'));
    }
}
