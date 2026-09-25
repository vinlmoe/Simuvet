<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : un étudiant a terminé un atelier (§7).
 */
class session_completed extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_PARTICIPATING;
        $this->data['objecttable'] = 'local_simhub_session';
    }

    public static function get_name() {
        return get_string('event_session_completed', 'local_simhub');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' completed the workshop session with id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/index.php');
    }
}
