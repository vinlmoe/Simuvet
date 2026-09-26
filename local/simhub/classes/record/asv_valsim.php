<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Validation ASV en simulation par un formateur/encadrant Moodle (§9.2).
 */
class asv_valsim {

    const TABLE = 'local_simhub_asv_valsim';

    const STATUT_VALIDE = 'valide';
    const STATUT_NON_VALIDE = 'non_valide';
    const STATUT_ANNULE = 'annule';

    /**
     * Enregistre une validation ASV en simulation.
     *
     * @param int $userid Étudiant.
     * @param int $acteid
     * @param int $validateuruserid
     * @param array $extra atelierid, sessionid, statut (par défaut "valide").
     * @return int
     */
    public static function valider(int $userid, int $acteid, int $validateuruserid, array $extra = []): int {
        global $DB;

        $now = time();
        $record = (object) array_merge([
            'userid' => $userid,
            'acteid' => $acteid,
            'atelierid' => null,
            'sessionid' => null,
            'datevalidation' => $now,
            'validateuruserid' => $validateuruserid,
            'statut' => self::STATUT_VALIDE,
            'timecreated' => $now,
        ], $extra);

        return $DB->insert_record(self::TABLE, $record);
    }

    /**
     * Validations en simulation d'un étudiant, pour le pilotage du parcours ASV (§9.4).
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function get_pour_etudiant(int $userid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['userid' => $userid, 'statut' => self::STATUT_VALIDE]);
    }

    /**
     * Actes (ids) déjà validés en simulation par un étudiant.
     *
     * @param int $userid
     * @return int[]
     */
    public static function get_actes_valides(int $userid): array {
        global $DB;

        return array_values(array_map('intval', $DB->get_records_menu(
            self::TABLE,
            ['userid' => $userid, 'statut' => self::STATUT_VALIDE],
            '',
            'id, acteid'
        )));
    }

    /**
     * Toutes les décisions d'un encadrant pour un étudiant (validées, refusées, annulées),
     * les plus récentes d'abord : l'historique complet reste consultable.
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function get_historique(int $userid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['userid' => $userid], 'datevalidation DESC, id DESC');
    }

    /**
     * Annule une validation saisie par erreur : l'enregistrement est conservé pour la
     * traçabilité, mais ne compte plus dans le livret ni dans la certification.
     *
     * @param int $id
     * @param string $motif
     * @return void
     */
    public static function annuler(int $id, string $motif): void {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id], '*', MUST_EXIST);
        $record->statut = self::STATUT_ANNULE;
        $record->commentaire = trim(($record->commentaire ?? '') . "\n" . $motif);
        $DB->update_record(self::TABLE, $record);
    }
}
