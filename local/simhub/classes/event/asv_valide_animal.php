<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : un acte ASV a été validé sur animal vivant, potentiellement par un validateur
 * externe sans compte Moodle (§9.3). Déclenché côté anonyme, donc userid peut être 0.
 */
class asv_valide_animal extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'u';
        $this->data['edulevel'] = self::LEVEL_OTHER;
        $this->data['objecttable'] = 'local_simhub_asv_valanimal';
    }

    public static function get_name() {
        return get_string('event_asv_valide_animal', 'local_simhub');
    }

    public function get_description() {
        return "The ASV procedure was validated on a live animal for the student with id "
            . "'{$this->relateduserid}' (record '{$this->objectid}').";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/asv/index.php');
    }
}
