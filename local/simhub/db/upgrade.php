<?php
// Script de mise à jour incrémentale du schéma. Vide en V0 : le schéma
// initial est entièrement décrit dans db/install.xml. À partir de la
// première mise en production, toute évolution de schéma devra passer
// par ce fichier (savepoints), jamais par une modification directe
// d'install.xml après release.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_simhub_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    // Exemple de bloc d'upgrade pour une future version :
    //
    // if ($oldversion < 2026090100) {
    //     $table = new xmldb_table('local_simhub_atelier');
    //     $field = new xmldb_field('nouveauchamp', XMLDB_TYPE_CHAR, '255');
    //     if (!$dbman->field_exists($table, $field)) {
    //         $dbman->add_field($table, $field);
    //     }
    //     upgrade_plugin_savepoint(true, 2026090100, 'local', 'simhub');
    // }

    return true;
}
