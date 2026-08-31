<?php

namespace local_simhub\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Modèle d'auto-évaluation guidée d'un atelier (§5.6, §7.2) : un modèle par atelier en V1
 * (contrainte d'unicité sur atelierid, cf. db/install.xml), composé de rubriques
 * (classes\record\ae_rubrique) elles-mêmes composées de critères
 * (classes\record\ae_critere).
 */
class ae_modele extends \core\persistent {

    const TABLE = 'local_simhub_ae_modele';

    protected static function define_properties() {
        return [
            'atelierid' => ['type' => PARAM_INT],
            'titre' => ['type' => PARAM_TEXT],
            'actif' => ['type' => PARAM_INT, 'default' => 1],
        ];
    }

    /**
     * Modèle d'un atelier, s'il existe (actif ou non).
     *
     * @param int $atelierid
     * @return ae_modele|false
     */
    public static function get_pour_atelier(int $atelierid) {
        return self::get_record(['atelierid' => $atelierid]);
    }
}
