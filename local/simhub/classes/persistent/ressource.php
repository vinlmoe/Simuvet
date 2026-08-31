<?php

namespace local_simhub\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Ressource pédagogique associée à un atelier (§6.2).
 */
class ressource extends \core\persistent {

    const TABLE = 'local_simhub_ressource';

    const VISIBILITE_ETUDIANT = 'etudiant';
    const VISIBILITE_INTERNE = 'interne';

    const TYPE_SOURCE_EDITABLE = 'source_editable';

    protected static function define_properties() {
        return [
            'atelierid' => ['type' => PARAM_INT],
            'type' => [
                'type' => PARAM_ALPHANUMEXT,
                'choices' => [
                    'fiche_methode', 'pdf_etudiant', 'video', 'consignes',
                    'criteres_reussite', 'erreurs_frequentes', 'liens_utiles',
                    'complementaire', self::TYPE_SOURCE_EDITABLE,
                ],
            ],
            'visibilite' => [
                'type' => PARAM_ALPHA,
                'default' => self::VISIBILITE_ETUDIANT,
                'choices' => [self::VISIBILITE_ETUDIANT, self::VISIBILITE_INTERNE],
            ],
            'titre' => ['type' => PARAM_TEXT],
            'url' => ['type' => PARAM_RAW, 'default' => '', 'null' => NULL_ALLOWED],
            'fileitemid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'ordre' => ['type' => PARAM_INT, 'default' => 0],
        ];
    }

    /**
     * Ressources visibles côté étudiant pour un atelier (jamais les sources éditables, §6.2).
     *
     * @param int $atelierid
     * @return ressource[]
     */
    public static function get_pour_etudiant(int $atelierid): array {
        return self::get_records(
            ['atelierid' => $atelierid, 'visibilite' => self::VISIBILITE_ETUDIANT],
            'ordre'
        );
    }
}
