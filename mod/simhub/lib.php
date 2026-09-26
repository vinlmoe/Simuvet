<?php
// Activité SimHub d'une UC : chaque instance porte un parcours local_simhub lié au cours.
// La note vaut le pourcentage d'avancement de ce parcours.

defined('MOODLE_INTERNAL') || die();

use local_simhub\persistent\parcours;

/**
 * @param string $feature FEATURE_*
 * @return mixed
 */
function simhub_supports($feature) {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_GRADE_HAS_GRADE:
        case FEATURE_COMPLETION_TRACKS_VIEWS:
        case FEATURE_GROUPS:
        case FEATURE_GROUPINGS:
            return true;
        case FEATURE_BACKUP_MOODLE2:
            return false;
        case FEATURE_MOD_PURPOSE:
            return MOD_PURPOSE_ASSESSMENT;
        default:
            return null;
    }
}

/**
 * @param stdClass $simhub
 * @param mod_simhub_mod_form|null $mform
 * @return int
 */
function simhub_add_instance($simhub, $mform = null) {
    global $DB;

    $parcours = new parcours(0, (object) [
        'nom' => $simhub->name,
        'type' => 'lie_uc',
        'courseid' => $simhub->course,
        'envcode' => get_config('local_simhub', 'envcode') ?: '',
        'cmid' => $simhub->coursemodule,
    ]);
    $parcours->create();

    $simhub->parcoursid = $parcours->get('id');
    $simhub->timecreated = time();
    $simhub->timemodified = $simhub->timecreated;
    $simhub->id = $DB->insert_record('simhub', $simhub);

    simhub_grade_item_update($simhub);
    return $simhub->id;
}

/**
 * @param stdClass $simhub
 * @param mod_simhub_mod_form|null $mform
 * @return bool
 */
function simhub_update_instance($simhub, $mform = null) {
    global $DB;

    $simhub->id = $simhub->instance;
    $simhub->timemodified = time();
    $existant = $DB->get_record('simhub', ['id' => $simhub->id], '*', MUST_EXIST);
    $simhub->parcoursid = $existant->parcoursid;
    $DB->update_record('simhub', $simhub);

    $parcours = parcours::get_record(['id' => $existant->parcoursid]);
    if ($parcours) {
        $parcours->set('nom', $simhub->name);
        $parcours->set('cmid', $simhub->coursemodule);
        $parcours->update();
    }

    simhub_grade_item_update($simhub);
    simhub_update_grades($simhub);
    return true;
}

/**
 * @param int $id
 * @return bool
 */
function simhub_delete_instance($id) {
    global $DB;

    $simhub = $DB->get_record('simhub', ['id' => $id]);
    if (!$simhub) {
        return false;
    }
    $parcours = parcours::get_record(['id' => $simhub->parcoursid]);
    if ($parcours) {
        foreach ($parcours->get_ateliers() as $lien) {
            \local_simhub\local\parcours_helper::retirer_atelier($parcours, (int) $lien->atelierid);
        }
        $parcours->delete();
    }
    simhub_grade_item_delete($simhub);
    $DB->delete_records('simhub', ['id' => $id]);
    return true;
}

/**
 * @param stdClass $simhub
 * @param array|string|null $grades
 * @return int GRADE_UPDATE_*
 */
function simhub_grade_item_update($simhub, $grades = null) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    $params = ['itemname' => $simhub->name];
    if ($simhub->grade > 0) {
        $params['gradetype'] = GRADE_TYPE_VALUE;
        $params['grademax'] = $simhub->grade;
        $params['grademin'] = 0;
    } else {
        $params['gradetype'] = GRADE_TYPE_NONE;
    }
    if ($grades === 'reset') {
        $params['reset'] = true;
        $grades = null;
    }
    return grade_update('mod/simhub', $simhub->course, 'mod', 'simhub', $simhub->id, 0, $grades, $params);
}

/**
 * @param stdClass $simhub
 * @return int
 */
function simhub_grade_item_delete($simhub) {
    global $CFG;
    require_once($CFG->libdir . '/gradelib.php');

    return grade_update('mod/simhub', $simhub->course, 'mod', 'simhub', $simhub->id, 0, null, ['deleted' => 1]);
}

/**
 * Étudiants notés : inscrits actifs du cours qui ne suivent pas eux-mêmes l'UC.
 *
 * @param stdClass $simhub
 * @param int $userid 0 pour tous.
 * @return int[]
 */
function simhub_etudiants_notes($simhub, int $userid = 0): array {
    $cm = get_coursemodule_from_instance('simhub', $simhub->id, $simhub->course, false, MUST_EXIST);
    $cmcontext = context_module::instance($cm->id);
    $coursecontext = $cmcontext->get_course_context();
    if ($userid) {
        $ids = is_enrolled($coursecontext, $userid, '', true) ? [$userid] : [];
    } else {
        $ids = array_map('intval', array_keys(get_enrolled_users($coursecontext, '', 0, 'u.id', null, 0, 0, true)));
    }
    return array_values(array_filter($ids, fn($id) => !has_capability('mod/simhub:viewprogression', $cmcontext, $id)));
}

/**
 * @param stdClass $simhub
 * @param int $userid 0 pour tous.
 * @return array userid => (object) ['userid', 'rawgrade']
 */
function simhub_get_user_grades($simhub, $userid = 0) {
    $parcours = parcours::get_record(['id' => $simhub->parcoursid]);
    if (!$parcours || $simhub->grade <= 0) {
        return [];
    }
    $grades = [];
    foreach (simhub_etudiants_notes($simhub, (int) $userid) as $id) {
        $pct = \local_simhub\local\parcours_helper::progression($parcours, $id)['pct'];
        $grades[$id] = (object) ['userid' => $id, 'rawgrade' => $pct * $simhub->grade / 100];
    }
    return $grades;
}

/**
 * @param stdClass $simhub
 * @param int $userid 0 pour tous.
 * @param bool $nullifnone
 * @return void
 */
function simhub_update_grades($simhub, $userid = 0, $nullifnone = true) {
    $grades = simhub_get_user_grades($simhub, $userid);
    if ($grades) {
        simhub_grade_item_update($simhub, $grades);
    } else if ($userid && $nullifnone) {
        simhub_grade_item_update($simhub, (object) ['userid' => $userid, 'rawgrade' => null]);
    } else {
        simhub_grade_item_update($simhub);
    }
}

/**
 * Réinitialisation du cours : efface les notes de l'activité.
 *
 * @param stdClass $data
 * @return array
 */
function simhub_reset_userdata($data) {
    global $DB;

    $status = [];
    if (!empty($data->reset_gradebook_grades)) {
        foreach ($DB->get_records('simhub', ['course' => $data->courseid]) as $simhub) {
            simhub_grade_item_update($simhub, 'reset');
        }
        $status[] = ['component' => get_string('modulenameplural', 'simhub'),
            'item' => get_string('removeallgrades', 'grades'), 'error' => false];
    }
    return $status;
}
