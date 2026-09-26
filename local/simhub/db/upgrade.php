<?php
// Script de mise à jour incrémentale du schéma. Vide en V0 : le schéma
// initial est entièrement décrit dans db/install.xml. À partir de la
// première mise en production, toute évolution de schéma devra passer
// par ce fichier (savepoints), jamais par une modification directe
// d'install.xml après release.

defined('MOODLE_INTERNAL') || die();

function xmldb_local_simhub_upgrade($oldversion) {
    global $CFG, $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026090102) {
        // Plusieurs tables (local_simhub_ae_modele, _ae_rubrique, _ae_critere,
        // _ae_reponse, _ae_bilan, _val_encadrant, les tables ASV...) ont été ajoutées à
        // db/install.xml au fil du développement, après que certaines instances aient déjà
        // installé une version antérieure du plugin. Moodle ne relit jamais install.xml
        // pour un plugin déjà installé : sans ce rattrapage, ces tables n'existent tout
        // simplement pas en base sur ces instances, et toute page qui les utilise (par
        // exemple la composition de la grille d'auto-évaluation) échoue silencieusement ou
        // n'affiche jamais ce qu'on vient d'y ajouter.
        //
        // install_from_xmldb_file() rejoue TOUTES les tables du fichier sans vérifier au
        // préalable lesquelles existent déjà : sur une instance où certaines sont déjà en
        // place, il échoue dès la première (« table already exists »), comme constaté en
        // usage réel. On charge donc la structure du fichier nous-mêmes pour ne créer, une
        // par une, que les tables réellement absentes.
        require_once($CFG->libdir . '/ddllib.php');
        $xmldbfile = new xmldb_file($CFG->dirroot . '/local/simhub/db/install.xml');
        $xmldbfile->loadXMLStructure();
        $structure = $xmldbfile->getStructure();

        foreach ($structure->getTables() as $table) {
            if (!$dbman->table_exists($table)) {
                $dbman->create_table($table);
            }
        }

        upgrade_plugin_savepoint(true, 2026090102, 'local', 'simhub');
    }

    if ($oldversion < 2026090103) {
        // Nouvelle table de liaison N-N acte ASV / atelier (§9.2), pour restreindre la
        // liste des actes proposés à la validation en simulation à ceux réellement
        // pratiqués dans l'atelier concerné. Même logique que le bloc précédent : ne créer
        // que si absente, pour rester sans danger si rejouée.
        $table = new xmldb_table('local_simhub_asv_acte_atelier');
        if (!$dbman->table_exists($table)) {
            require_once($CFG->libdir . '/ddllib.php');
            $xmldbfile = new xmldb_file($CFG->dirroot . '/local/simhub/db/install.xml');
            $xmldbfile->loadXMLStructure();
            $structure = $xmldbfile->getStructure();
            $dbman->create_table($structure->getTable('local_simhub_asv_acte_atelier'));
        }

        upgrade_plugin_savepoint(true, 2026090103, 'local', 'simhub');
    }

    if ($oldversion < 2026092500) {
        $caps = ['local/simhub:view', 'local/simhub:startsession', 'local/simhub:submitautoeval'];
        $syscontext = context_system::instance();
        foreach (get_archetype_roles('user') as $role) {
            foreach ($caps as $cap) {
                assign_capability($cap, CAP_ALLOW, $role->id, $syscontext->id);
            }
        }
        upgrade_plugin_savepoint(true, 2026092500, 'local', 'simhub');
    }

    if ($oldversion < 2026092600) {
        \local_simhub\local\roles::installer();
        upgrade_plugin_savepoint(true, 2026092600, 'local', 'simhub');
    }

    if ($oldversion < 2026092700) {
        $table = new xmldb_table('local_simhub_asv_valsim');
        $field = new xmldb_field('commentaire', XMLDB_TYPE_TEXT, null, null, null, null, null, 'statut');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026092700, 'local', 'simhub');
    }

    if ($oldversion < 2026092900) {
        $table = new xmldb_table('local_simhub_parcours');
        $field = new xmldb_field('cmid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'badgeid');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
            $dbman->add_key($table, new xmldb_key('cmid_fk', XMLDB_KEY_FOREIGN, ['cmid'], 'course_modules', ['id']));
        }

        // Rôles SimHub attribuables dans la catégorie, délégation à l'administrateur fonctionnel.
        \local_simhub\local\roles::installer();
        upgrade_plugin_savepoint(true, 2026092900, 'local', 'simhub');
    }

    return true;
}
