<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : un encadrant a validé ou refusé une séance d'atelier (§7.3, §11).
 */
class session_validated extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_simhub_session';
    }

    public static function get_name() {
        return get_string('event_session_validated', 'local_simhub');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' reviewed the workshop session with id '{$this->objectid}' "
            . "of the user with id '{$this->relateduserid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/index.php');
    }
}
