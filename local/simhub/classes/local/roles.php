<?php
// Rôles SimHub transversaux (§11), attribuables au système ou dans la catégorie SimHub
// (contexte::racine()). Les enseignants et responsables d'UC n'en ont pas besoin : leurs
// droits viennent de leur rôle dans le cours de l'UC, via l'activité mod_simhub.

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Création et mise à jour des rôles SimHub.
 */
class roles {

    /**
     * Capacités accordées par rôle (nom court => capacités).
     *
     * @return array
     */
    public static function definitions(): array {
        $encadrant = [
            'local/simhub:view',
            'local/simhub:viewprogression',
            'local/simhub:validatesession',
            'local/simhub:exportsuivi',
            'local/simhub:validateasvsimulation',
        ];
        $responsableuc = array_merge($encadrant, [
            'local/simhub:manageparcours',
            'local/simhub:managerattachement',
        ]);
        $gestionnairesalle = [
            'local/simhub:view',
            'local/simhub:viewprogression',
            'local/simhub:validatesession',
            'local/simhub:manageateliers',
            'local/simhub:manageressources',
            'local/simhub:managestatuts',
            'local/simhub:manageqrcodes',
            'local/simhub:importexport',
            'local/simhub:managerattachement',
        ];
        $adminfonctionnel = array_values(array_unique(array_merge($responsableuc, $gestionnairesalle, [
            'local/simhub:manageasv',
            'local/simhub:configure',
            // Attribue lui-même les autres rôles SimHub dans la catégorie, sans compte admin.
            'moodle/role:assign',
            'moodle/role:review',
        ])));

        return [
            'simhubencadrant' => $encadrant,
            'simhubresponsableuc' => $responsableuc,
            'simhubgestionnairesalle' => $gestionnairesalle,
            'simhubadminfonctionnel' => $adminfonctionnel,
        ];
    }

    /**
     * Crée les rôles manquants et leur accorde leurs capacités. Sans danger si rejoué :
     * un rôle existant est complété, jamais recréé, et les réglages ajoutés à la main par
     * l'établissement sur d'autres capacités sont conservés.
     *
     * @return void
     */
    public static function installer(): void {
        global $DB;

        $syscontext = \context_system::instance();

        // À l'installation, db/install.php s'exécute avant que Moodle n'enregistre les
        // capacités de db/access.php : assign_capability() les refuserait.
        update_capabilities('local_simhub');

        foreach (self::definitions() as $shortname => $caps) {
            $roleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            if (!$roleid) {
                $roleid = create_role(
                    get_string('role_' . $shortname, 'local_simhub'),
                    $shortname,
                    get_string('role_' . $shortname . '_desc', 'local_simhub')
                );
            }
            $niveaux = array_values(array_unique(array_merge(
                array_values(get_role_contextlevels($roleid)), [CONTEXT_SYSTEM, CONTEXT_COURSECAT])));
            set_role_contextlevels($roleid, $niveaux);
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $roleid, $syscontext->id, true);
            }
        }

        // L'administrateur fonctionnel ne peut attribuer que les rôles SimHub.
        $adminid = $DB->get_field('role', 'id', ['shortname' => 'simhubadminfonctionnel']);
        foreach (array_keys(self::definitions()) as $shortname) {
            $cibleid = $DB->get_field('role', 'id', ['shortname' => $shortname]);
            if ($adminid && $cibleid
                    && !$DB->record_exists('role_allow_assign', ['roleid' => $adminid, 'allowassign' => $cibleid])) {
                core_role_set_assign_allowed($adminid, $cibleid);
            }
        }

        $syscontext->mark_dirty();
    }
}
