<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : un atelier a été créé.
 */
class atelier_created extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_simhub_atelier';
    }

    public static function get_name() {
        return get_string('event_atelier_created', 'local_simhub');
    }

    public function get_description() {
        return "L'utilisateur avec l'id '{$this->userid}' a créé l'atelier avec l'id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/index.php');
    }
}
