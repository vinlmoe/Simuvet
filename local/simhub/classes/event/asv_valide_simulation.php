<?php

namespace local_simhub\event;

defined('MOODLE_INTERNAL') || die();

/**
 * Événement : un acte ASV a été validé en simulation (§9.2).
 */
class asv_valide_simulation extends \core\event\base {

    protected function init() {
        $this->data['crud'] = 'c';
        $this->data['edulevel'] = self::LEVEL_TEACHING;
        $this->data['objecttable'] = 'local_simhub_asv_valsim';
    }

    public static function get_name() {
        return get_string('event_asv_valide_simulation', 'local_simhub');
    }

    public function get_description() {
        return "L'utilisateur avec l'id '{$this->userid}' a validé en simulation l'acte ASV "
            . "pour l'étudiant avec l'id '{$this->relateduserid}' (enregistrement '{$this->objectid}').";
    }

    public function get_url() {
        return new \moodle_url('/local/simhub/asv/index.php');
    }
}
