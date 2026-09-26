<?php

defined('MOODLE_INTERNAL') || die();

/**
 * Générateur de données de test de l'activité SimHub.
 */
class mod_simhub_generator extends testing_module_generator {

    public function create_instance($record = null, ?array $options = null) {
        $record = (object) (array) $record;
        if (!isset($record->grade)) {
            $record->grade = 100;
        }
        return parent::create_instance($record, (array) $options);
    }
}
