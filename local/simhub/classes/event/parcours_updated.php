<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : la composition d'un parcours a changé (§8).
 */
class parcours_updated extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_simhub_parcours';
    }

    public static function get_name() {
        return get_string('event_parcours_updated', 'local_simhub');
    }

    public function get_description() {
        return "The user with id '{$this->userid}' changed the workshops of the pathway with id '{$this->objectid}'.";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $this->objectid]);
    }
}
