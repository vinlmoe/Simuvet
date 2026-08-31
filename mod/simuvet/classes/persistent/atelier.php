<?php

namespace local_simhub\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Classe persistent pour la fiche atelier (§6 du cahier des charges).
 *
 * S'appuie sur l'API core\persistent de Moodle : gère automatiquement
 * la validation, timecreated/timemodified/usermodified, et les hooks
 * before_create/before_update pour la logique métier (ex. génération
 * du QR token à la création).
 *
 * Sert de référence pour écrire les autres classes persistent du
 * plugin (parcours, session, asv_acte...), non développées en détail
 * dans ce squelette.
 */
class atelier extends \core\persistent {

    /** Table associée. */
    const TABLE = 'local_simhub_atelier';

    const STATUT_ACTIF = 'actif';
    const STATUT_NON_UTILISE = 'non_utilise';
    const STATUT_INDISPONIBLE = 'indisponible';
    const STATUT_ARCHIVE = 'archive';

    /**
     * Définition des propriétés, alignée sur db/install.xml.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'numero' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'nomcourt' => [
                'type' => PARAM_TEXT,
            ],
            'nomlong' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'descriptioncourte' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'discipline' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'espece' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'niveaudifficulte' => [
                'type' => PARAM_ALPHA,
                'default' => '',
                'null' => NULL_ALLOWED,
                'choices' => ['', 'facile', 'intermediaire', 'avance'],
            ],
            'dureeindicative' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'statut' => [
                'type' => PARAM_ALPHA,
                'default' => self::STATUT_NON_UTILISE,
                'choices' => [
                    self::STATUT_ACTIF,
                    self::STATUT_NON_UTILISE,
                    self::STATUT_INDISPONIBLE,
                    self::STATUT_ARCHIVE,
                ],
            ],
            'envcode' => [
                'type' => PARAM_ALPHANUMEXT,
            ],
            'salle' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'zone' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'codeposte' => [
                'type' => PARAM_TEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'indicationtextuelle' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
            'planimageitemid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'planrepx' => [
                'type' => PARAM_FLOAT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'planrepy' => [
                'type' => PARAM_FLOAT,
                'default' => null,
                'null' => NULL_ALLOWED,
            ],
            'referentuserid' => [
                'type' => PARAM_INT,
                'default' => 0,
                'null' => NULL_ALLOWED,
            ],
            'commentaireadmin' => [
                'type' => PARAM_RAW,
                'default' => '',
                'null' => NULL_ALLOWED,
            ],
        ];
    }

    /**
     * Ateliers effectivement proposés aux étudiants (statut actif),
     * éventuellement filtrés par établissement.
     *
     * TODO : remplacer par une classe de filtres dédiée
     * (local_simhub\local\atelier_filter) une fois les critères de
     * tri/recherche du §5.2 spécifiés côté UI (UC, parcours, année,
     * discipline, espèce, niveau, durée, statut personnel, mot-clé).
     *
     * @param string|null $envcode
     * @return atelier[]
     */
    public static function get_actifs(?string $envcode = null): array {
        $params = ['statut' => self::STATUT_ACTIF];
        if ($envcode !== null) {
            $params['envcode'] = $envcode;
        }
        return self::get_records($params);
    }

    /**
     * Hook appelé avant la création : génère un jeton QR unique pour
     * l'atelier. Implémentation illustrative — à déplacer vers un
     * qrtoken_manager dédié pour gérer la rotation/désactivation des
     * jetons (cf. table local_simhub_qrtoken).
     */
    protected function before_create() {
        // TODO : créer l'enregistrement local_simhub_qrtoken associé
        // ici, ou dans un observer sur l'événement atelier_created
        // (voir classes/event/, non développé dans ce squelette).
    }
}
