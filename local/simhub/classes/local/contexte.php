<?php
// Contextes Moodle utilisés par SimHub.

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Contexte des droits transversaux et contexte de stockage des fichiers.
 */
class contexte {

    /**
     * Contexte où sont vérifiées les capacités transversales : la catégorie SimHub si elle
     * est paramétrée, sinon le système. Un rôle attribué au système reste valable dans la
     * catégorie, qui en hérite.
     *
     * @return \context
     */
    public static function racine(): \context {
        $categoryid = (int) get_config('local_simhub', 'categoryid');
        if ($categoryid) {
            $context = \context_coursecat::instance($categoryid, IGNORE_MISSING);
            if ($context) {
                return $context;
            }
        }
        return \context_system::instance();
    }

    /**
     * Les fichiers restent au contexte système, où ils ont toujours été enregistrés : changer
     * de catégorie SimHub ne doit pas rendre les ressources et plans existants introuvables.
     *
     * @return \context_system
     */
    public static function fichiers(): \context_system {
        return \context_system::instance();
    }
}
