<?php

namespace mod_simhub\privacy;

defined('MOODLE_INTERNAL') || die();

/**
 * L'activité ne stocke aucune donnée personnelle : séances et validations sont déclarées
 * par local_simhub, les notes par le carnet de notes.
 */
class provider implements \core_privacy\local\metadata\null_provider {

    public static function get_reason(): string {
        return 'privacy:metadata';
    }
}
