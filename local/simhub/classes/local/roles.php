<?php
// Rôles système SimHub correspondant aux profils du §11.
//
// SimHub vérifie toutes ses capacités au niveau système : un rôle d'enseignant attribué
// dans un cours n'y donne aucun droit. Ces rôles, attribuables uniquement au niveau
// système, évitent à chaque établissement de les recréer à la main.

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
                set_role_contextlevels($roleid, [CONTEXT_SYSTEM]);
            }
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $roleid, $syscontext->id, true);
            }
        }

        $syscontext->mark_dirty();
    }
}
