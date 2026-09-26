<?php

namespace mod_simhub;

defined('MOODLE_INTERNAL') || die();

/**
 * Met à jour les notes des UC quand l'état d'un atelier change pour un étudiant.
 */
class observer {

    /**
     * Séance terminée ou validée : toutes les UC contenant l'atelier, pour cet étudiant.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function seance_modifiee(\core\event\base $event): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/simhub/lib.php');

        $session = $DB->get_record('local_simhub_session', ['id' => $event->objectid], 'id, userid, atelierid');
        if (!$session) {
            return;
        }
        $instances = $DB->get_records_sql(
            "SELECT DISTINCT s.*
               FROM {simhub} s
               JOIN {local_simhub_parc_atelier} pa ON pa.parcoursid = s.parcoursid
              WHERE pa.atelierid = ?", [$session->atelierid]);
        foreach ($instances as $simhub) {
            simhub_update_grades($simhub, (int) $session->userid);
        }
    }

    /**
     * Composition du parcours modifiée : tous les étudiants de l'UC.
     *
     * @param \core\event\base $event
     * @return void
     */
    public static function parcours_modifie(\core\event\base $event): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/simhub/lib.php');

        foreach ($DB->get_records('simhub', ['parcoursid' => $event->objectid]) as $simhub) {
            simhub_update_grades($simhub);
        }
    }
}
