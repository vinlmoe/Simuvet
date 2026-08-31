<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Intégration avec les badges Moodle (§13 "V1+ souhaitable") : SimHub ne gère pas ses
 * propres badges, il délivre un badge de site existant, choisi par le gestionnaire, quand
 * un étudiant termine effectivement un parcours (§8) ou valide un niveau ASV complet
 * (§9.4). La création des badges eux-mêmes (image, critères d'affichage...) reste une
 * tâche d'administration Moodle standard, hors périmètre de ce plugin.
 */
class badge_helper {

    /**
     * Options {badgeid => nom} pour un élément de formulaire select, limitées aux badges de
     * site actifs (les seuls que ce plugin, transverse aux cours, peut délivrer de façon
     * cohérente).
     *
     * @param bool $withempty Ajoute une option "aucun" en tête.
     * @return array
     */
    public static function get_options(bool $withempty = true): array {
        global $CFG, $DB;

        $options = $withempty ? ['' => get_string('badge_aucun', 'local_simhub')] : [];

        if (!$DB->get_manager()->table_exists('badge')) {
            return $options;
        }

        require_once($CFG->libdir . '/badgeslib.php');

        $badges = $DB->get_records_select(
            'badge',
            'type = :type AND status <> :inactive',
            ['type' => BADGE_TYPE_SITE, 'inactive' => BADGE_STATUS_INACTIVE],
            'name ASC',
            'id, name'
        );
        foreach ($badges as $badge) {
            $options[$badge->id] = $badge->name;
        }

        return $options;
    }

    /**
     * Délivre un badge de site à un utilisateur, si les badges sont activés sur le site,
     * que le badge existe et est actif, et qu'il n'a pas déjà été délivré.
     *
     * @param int|null $badgeid
     * @param int $userid
     * @return void
     */
    public static function delivrer(?int $badgeid, int $userid): void {
        global $CFG;

        if (empty($badgeid) || empty($CFG->enablebadges)) {
            return;
        }

        require_once($CFG->libdir . '/badgeslib.php');

        if (!class_exists('\badge')) {
            return;
        }

        try {
            $badge = new \badge($badgeid);
            if ($badge->status != BADGE_STATUS_INACTIVE && !$badge->is_issued($userid)) {
                $badge->issue($userid, true);
            }
        } catch (\Exception $e) {
            // Badge introuvable, désactivé, ou API badges indisponible sur cette instance :
            // ne jamais faire échouer la délivrance d'une attestation pour cette raison.
            debugging('local_simhub: échec de la délivrance du badge ' . $badgeid . ' : ' . $e->getMessage());
        }
    }
}
