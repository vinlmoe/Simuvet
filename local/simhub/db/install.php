<?php
// Actions post-installation de SimHub.

defined('MOODLE_INTERNAL') || die();

/**
 * Crée les rôles système SimHub (§11).
 *
 * @return bool
 */
function xmldb_local_simhub_install() {
    \local_simhub\local\roles::installer();
    return true;
}
