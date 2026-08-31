<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Rubrique (grande étape du geste) d'un modèle d'auto-évaluation guidée (§5.6, §7.2).
 */
class ae_rubrique {

    const TABLE = 'local_simhub_ae_rubrique';

    /**
     * Ajoute une rubrique à un modèle.
     *
     * @param int $modeleid
     * @param string $titre
     * @param int $ordre
     * @param bool $estrubriquerisques Rubrique dédiée aux erreurs/risques (§5.6).
     * @return int
     */
    public static function ajouter(int $modeleid, string $titre, int $ordre = 0,
            bool $estrubriquerisques = false): int {
        global $DB;

        return $DB->insert_record(self::TABLE, (object) [
            'modeleid' => $modeleid,
            'titre' => $titre,
            'ordre' => $ordre,
            'estrubriquerisques' => $estrubriquerisques ? 1 : 0,
        ]);
    }

    /**
     * Rubriques d'un modèle, dans l'ordre d'affichage.
     *
     * @param int $modeleid
     * @return \stdClass[]
     */
    public static function get_pour_modele(int $modeleid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['modeleid' => $modeleid], 'ordre ASC');
    }
}
