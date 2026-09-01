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

    if ($oldversion < 2026090101) {
        // Plusieurs tables (local_simhub_ae_modele, _ae_rubrique, _ae_critere,
        // _ae_reponse, _ae_bilan, _val_encadrant, les tables ASV...) ont été ajoutées à
        // db/install.xml au fil du développement, après que certaines instances aient déjà
        // installé une version antérieure du plugin. Moodle ne relit jamais install.xml
        // pour un plugin déjà installé : sans ce rattrapage, ces tables n'existent tout
        // simplement pas en base sur ces instances, et toute page qui les utilise (par
        // exemple la composition de la grille d'auto-évaluation) échoue silencieusement ou
        // n'affiche jamais ce qu'on vient d'y ajouter.
        //
        // install_from_xmldb_file() vérifie table_exists() avant de créer chaque table du
        // fichier : rejouer tout le schéma ici ne touche pas aux tables déjà en place,
        // et crée uniquement celles qui manquent encore.
        $dbman->install_from_xmldb_file($CFG->dirroot . '/local/simhub/db/install.xml');

        upgrade_plugin_savepoint(true, 2026090101, 'local', 'simhub');
    }

    return true;
}
