<?php
// This file is part of Moodle - https://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Script de mise à jour incrémentale du schéma. Vide en V0 : le schéma
 * initial est entièrement décrit dans db/install.xml. À partir de la
 * première mise en production, toute évolution de schéma devra passer
 * par ce fichier (savepoints), jamais par une modification directe
 * d'install.xml après release.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Mises à jour du schéma et des rôles.
 *
 * @param int $oldversion
 * @return bool
 */
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

    if ($oldversion < 2026092902) {
        $table = new xmldb_table('local_simhub_atelier');
        $field = new xmldb_field('categorie', XMLDB_TYPE_CHAR, '100', null, null, null, null, 'descriptioncourte');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $table = new xmldb_table('local_simhub_session');
        $field = new xmldb_field('dureesuspecte', XMLDB_TYPE_INTEGER, '1', null, XMLDB_NOTNULL, null, '0', 'controlepresence');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        upgrade_plugin_savepoint(true, 2026092902, 'local', 'simhub');
    }

    if ($oldversion < 2026092905) {
        $table = new xmldb_table('local_simhub_asv_valanimal');
        $field = new xmldb_field('emailvalidateur', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'signature');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        // Les liens des demandes en attente ont été affichés à l'étudiant, qui pourrait s'en
        // servir pour se valider lui-même : ils sont expirés, l'étudiant renvoie la demande
        // à l'adresse de son validateur.
        $DB->set_field_select(
            'local_simhub_asv_valanimal',
            'tokenexpire',
            time() - 1,
            'statut = :statut AND emailvalidateur IS NULL',
            ['statut' => 'en_attente']
        );
        upgrade_plugin_savepoint(true, 2026092905, 'local', 'simhub');
    }

    if ($oldversion < 2026092906) {
        // Lien groupé de validation sur animal vivant : une signature pour plusieurs demandes.
        $table = new xmldb_table('local_simhub_asv_valanimal');
        $field = new xmldb_field('lottoken', XMLDB_TYPE_CHAR, '255', null, null, null, null, 'timecreated');
        if (!$dbman->field_exists($table, $field)) {
            $dbman->add_field($table, $field);
        }
        $index = new xmldb_index('lottoken', XMLDB_INDEX_NOTUNIQUE, ['lottoken']);
        if (!$dbman->index_exists($table, $index)) {
            $dbman->add_index($table, $index);
        }
        upgrade_plugin_savepoint(true, 2026092906, 'local', 'simhub');
    }

    if ($oldversion < 2026092907) {
        // Contrôle interne des signatures externes (anti-fraude) : une signature sur animal
        // vivant ne compte qu'après confirmation par un encadrant. Les validations déjà
        // enregistrées restent acquises.
        $table = new xmldb_table('local_simhub_asv_valanimal');
        $champs = [
            new xmldb_field('demandeuruserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'lottoken'),
            new xmldb_field('demandeip', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'demandeuruserid'),
            new xmldb_field('signatureip', XMLDB_TYPE_CHAR, '45', null, null, null, null, 'demandeip'),
            new xmldb_field('signatureuserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'signatureip'),
            new xmldb_field('controleuruserid', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'signatureuserid'),
            new xmldb_field('datecontrole', XMLDB_TYPE_INTEGER, '10', null, null, null, null, 'controleuruserid'),
            new xmldb_field('motifcontrole', XMLDB_TYPE_TEXT, null, null, null, null, null, 'datecontrole'),
        ];
        foreach ($champs as $field) {
            if (!$dbman->field_exists($table, $field)) {
                $dbman->add_field($table, $field);
            }
        }
        upgrade_plugin_savepoint(true, 2026092907, 'local', 'simhub');
    }

    return true;
}
