<?php
// Droits SimHub : capacité transversale (contexte::racine()) OU capacité de l'activité
// mod_simhub d'une UC, limitée aux ateliers et aux étudiants de cette UC.

namespace local_simhub\local;

use local_simhub\persistent\parcours;

defined('MOODLE_INTERNAL') || die();

/**
 * Contrôles d'accès combinant les droits transversaux et ceux des activités d'UC.
 */
class droits {

    /**
     * Activités d'UC (parcours liés à une activité mod_simhub) contenant un atelier.
     *
     * @param int $atelierid
     * @return array cmid => courseid
     */
    public static function activites_pour_atelier(int $atelierid): array {
        global $DB;

        return $DB->get_records_sql_menu(
            "SELECT DISTINCT p.cmid, p.courseid
               FROM {local_simhub_parcours} p
               JOIN {local_simhub_parc_atelier} pa ON pa.parcoursid = p.id
               JOIN {course_modules} cm ON cm.id = p.cmid
              WHERE pa.atelierid = ? AND p.cmid > 0 AND cm.deletioninprogress = 0",
            [$atelierid]);
    }

    /**
     * Toutes les activités d'UC existantes.
     *
     * @return array cmid => courseid
     */
    public static function activites(): array {
        global $DB;

        return $DB->get_records_sql_menu(
            "SELECT p.cmid, p.courseid
               FROM {local_simhub_parcours} p
               JOIN {course_modules} cm ON cm.id = p.cmid
              WHERE p.cmid > 0 AND cm.deletioninprogress = 0");
    }

    /**
     * Vrai si l'utilisateur a la capacité dans au moins une des activités, en exigeant,
     * si $etudiantid est fourni, que l'étudiant soit inscrit au cours de l'activité.
     *
     * @param array $activites cmid => courseid
     * @param string $cap Capacité mod/simhub:*
     * @param int $etudiantid
     * @return bool
     */
    protected static function dans_une_activite(array $activites, string $cap, int $etudiantid = 0): bool {
        foreach ($activites as $cmid => $courseid) {
            $cmcontext = \context_module::instance($cmid, IGNORE_MISSING);
            if (!$cmcontext || !has_capability($cap, $cmcontext)) {
                continue;
            }
            if ($etudiantid && !is_enrolled(\context_course::instance($courseid), $etudiantid, '', true)) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Grille d'auto-évaluation : gestionnaire de salle, ou responsable d'une UC contenant l'atelier.
     *
     * @param int $atelierid
     * @return bool
     */
    public static function peut_editer_grille(int $atelierid): bool {
        return has_capability('local/simhub:manageateliers', contexte::racine())
            || self::dans_une_activite(self::activites_pour_atelier($atelierid), 'mod/simhub:manageparcours');
    }

    /**
     * Validation d'une séance : encadrant transversal, ou enseignant d'une UC qui contient
     * l'atelier et où l'étudiant est inscrit, où que la séance ait été lancée.
     *
     * @param int $etudiantid
     * @param int $atelierid
     * @return bool
     */
    public static function peut_valider_seance(int $etudiantid, int $atelierid): bool {
        return has_capability('local/simhub:validatesession', contexte::racine())
            || self::dans_une_activite(self::activites_pour_atelier($atelierid), 'mod/simhub:validatesession',
                $etudiantid);
    }

    /**
     * Validation ASV en simulation : formateur désigné dans la catégorie SimHub, ou
     * enseignant d'une UC où l'étudiant est inscrit.
     *
     * @param int $etudiantid 0 : a-t-il ce droit pour au moins un étudiant ?
     * @return bool
     */
    public static function peut_valider_asv(int $etudiantid = 0): bool {
        return has_capability('local/simhub:validateasvsimulation', contexte::racine())
            || self::dans_une_activite(self::activites(), 'mod/simhub:validateasvsimulation', $etudiantid);
    }

    /**
     * Suivi d'un parcours.
     *
     * @param parcours $parcours
     * @return bool
     */
    public static function peut_suivre_parcours(parcours $parcours): bool {
        return has_capability('local/simhub:viewprogression', contexte::racine())
            || self::dans_activite_du_parcours($parcours, 'mod/simhub:viewprogression');
    }

    /**
     * Composition et fiche d'un parcours.
     *
     * @param parcours $parcours
     * @return bool
     */
    public static function peut_gerer_parcours(parcours $parcours): bool {
        return has_capability('local/simhub:manageparcours', contexte::racine())
            || self::dans_activite_du_parcours($parcours, 'mod/simhub:manageparcours');
    }

    /**
     * @param parcours $parcours
     * @param string $cap
     * @return bool
     */
    protected static function dans_activite_du_parcours(parcours $parcours, string $cap): bool {
        $cmid = (int) $parcours->get('cmid');
        if (!$cmid) {
            return false;
        }
        $cmcontext = \context_module::instance($cmid, IGNORE_MISSING);
        return $cmcontext && has_capability($cap, $cmcontext);
    }

    /**
     * Fil d'Ariane vers l'activité d'UC d'un parcours, pour revenir au cours.
     *
     * @param parcours $parcours
     * @return array [[libellé, \moodle_url]] ou []
     */
    public static function etape_activite(parcours $parcours): array {
        $cmid = (int) $parcours->get('cmid');
        if (!$cmid || !\context_module::instance($cmid, IGNORE_MISSING)) {
            return [];
        }
        return [[format_string($parcours->get('nom')), new \moodle_url('/mod/simhub/view.php', ['id' => $cmid])]];
    }

    /**
     * Étudiants dont l'utilisateur peut voir le suivi dans ce parcours : tous pour un profil
     * transversal, sinon seulement les inscrits du cours de l'UC (hors enseignants).
     *
     * @param parcours $parcours
     * @return array Enregistrements utilisateur.
     */
    public static function etudiants_du_parcours(parcours $parcours): array {
        $users = parcours_helper::etudiants($parcours);
        if (has_capability('local/simhub:viewprogression', contexte::racine())) {
            return $users;
        }
        $cmcontext = \context_module::instance((int) $parcours->get('cmid'), IGNORE_MISSING);
        if (!$cmcontext) {
            return [];
        }
        $coursecontext = $cmcontext->get_course_context();
        return array_filter($users, fn($u) => is_enrolled($coursecontext, $u->id, '', true)
            && !has_capability('mod/simhub:viewprogression', $cmcontext, $u->id));
    }

    /**
     * Étudiants des UC où l'utilisateur peut valider des actes ASV en simulation.
     *
     * @return int[]
     */
    public static function etudiants_asv_autorises(): array {
        $userids = [];
        foreach (self::activites() as $cmid => $courseid) {
            $cmcontext = \context_module::instance($cmid, IGNORE_MISSING);
            if ($cmcontext && has_capability('mod/simhub:validateasvsimulation', $cmcontext)) {
                $enrolled = get_enrolled_users(\context_course::instance($courseid), '', 0, 'u.id', null, 0, 0, true);
                $userids = array_merge($userids, array_map('intval', array_keys($enrolled)));
            }
        }
        return array_values(array_unique($userids));
    }
}
