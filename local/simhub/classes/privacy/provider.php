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
 * Fournisseur de confidentialité (RGPD) pour SimHub.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

/**
 * Fournisseur de confidentialité (RGPD) pour SimHub.
 *
 * Toutes les données SimHub vivent en contexte système (§10 : pas de rattachement à un
 * contexte de cours), donc chaque utilisateur concerné n'a jamais qu'un seul contexte à
 * traiter : context_system::instance().
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {
    /**
     * Champs du personnel sur le référentiel partagé (ateliers, ressources, parcours...) :
     * à la suppression d'un utilisateur, la fiche reste et la référence est effacée.
     *
     * @return array table => [champ => valeur de remplacement]
     */
    protected static function champs_personnel(): array {
        return [
            'local_simhub_atelier' => ['referentuserid' => null, 'usermodified' => 0],
            'local_simhub_indispo' => ['referentuserid' => null],
            'local_simhub_ressource' => ['usermodified' => 0],
            'local_simhub_parcours' => ['usermodified' => 0],
            'local_simhub_ae_modele' => ['usermodified' => 0],
            'local_simhub_asv_acte' => ['usermodified' => 0],
            'local_simhub_seancecode' => ['createuruserid' => 0],
        ];
    }

    /**
     * Données personnelles stockées par SimHub.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table('local_simhub_session', [
            'userid' => 'privacy:metadata:local_simhub_session:userid',
            'atelierid' => 'privacy:metadata:local_simhub_session:atelierid',
            'timestart' => 'privacy:metadata:local_simhub_session:timestart',
            'timeend' => 'privacy:metadata:local_simhub_session:timeend',
            'statut' => 'privacy:metadata:local_simhub_session:statut',
            'usermodified' => 'privacy:metadata:personnel:usermodified',
        ], 'privacy:metadata:local_simhub_session');

        $collection->add_database_table('local_simhub_ae_reponse', [
            'sessionid' => 'privacy:metadata:local_simhub_ae_reponse:sessionid',
            'critereid' => 'privacy:metadata:local_simhub_ae_reponse:critereid',
            'niveau' => 'privacy:metadata:local_simhub_ae_reponse:niveau',
        ], 'privacy:metadata:local_simhub_ae_reponse');

        $collection->add_database_table('local_simhub_ae_bilan', [
            'sessionid' => 'privacy:metadata:local_simhub_ae_bilan:sessionid',
            'pointmaitrise' => 'privacy:metadata:local_simhub_ae_bilan:pointmaitrise',
            'pointaretravailler' => 'privacy:metadata:local_simhub_ae_bilan:pointaretravailler',
            'pointattention' => 'privacy:metadata:local_simhub_ae_bilan:pointattention',
        ], 'privacy:metadata:local_simhub_ae_bilan');

        $collection->add_database_table('local_simhub_val_encadrant', [
            'sessionid' => 'privacy:metadata:local_simhub_val_encadrant:sessionid',
            'validateuruserid' => 'privacy:metadata:local_simhub_val_encadrant:validateuruserid',
            'statut' => 'privacy:metadata:local_simhub_val_encadrant:statut',
            'commentaire' => 'privacy:metadata:local_simhub_val_encadrant:commentaire',
        ], 'privacy:metadata:local_simhub_val_encadrant');

        $collection->add_database_table('local_simhub_asv_valsim', [
            'userid' => 'privacy:metadata:local_simhub_asv_valsim:userid',
            'acteid' => 'privacy:metadata:local_simhub_asv_valsim:acteid',
            'validateuruserid' => 'privacy:metadata:local_simhub_asv_valsim:validateuruserid',
            'statut' => 'privacy:metadata:local_simhub_asv_valsim:statut',
            'commentaire' => 'privacy:metadata:local_simhub_asv_valsim:commentaire',
        ], 'privacy:metadata:local_simhub_asv_valsim');

        $collection->add_database_table('local_simhub_asv_valanimal', [
            'userid' => 'privacy:metadata:local_simhub_asv_valanimal:userid',
            'acteid' => 'privacy:metadata:local_simhub_asv_valanimal:acteid',
            'nomvalidateur' => 'privacy:metadata:local_simhub_asv_valanimal:nomvalidateur',
            'prenomvalidateur' => 'privacy:metadata:local_simhub_asv_valanimal:prenomvalidateur',
            'signature' => 'privacy:metadata:local_simhub_asv_valanimal:signature',
        ], 'privacy:metadata:local_simhub_asv_valanimal');

        foreach (self::champs_personnel() as $table => $champs) {
            $collection->add_database_table(
                $table,
                array_combine(
                    array_keys($champs),
                    array_map(fn($c) => 'privacy:metadata:personnel:' . $c, array_keys($champs))
                ),
                'privacy:metadata:personnel'
            );
        }

        return $collection;
    }

    /**
     * Contextes contenant des données de l'utilisateur.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();

        $hasdata = $DB->record_exists('local_simhub_session', ['userid' => $userid])
            || $DB->record_exists('local_simhub_val_encadrant', ['validateuruserid' => $userid])
            || $DB->record_exists('local_simhub_asv_valsim', ['userid' => $userid])
            || $DB->record_exists('local_simhub_asv_valsim', ['validateuruserid' => $userid])
            || $DB->record_exists('local_simhub_asv_valanimal', ['userid' => $userid])
            || self::est_reference_personnel($userid);

        if ($hasdata) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
     * Vrai si l'utilisateur figure comme référent, auteur ou créateur dans le référentiel.
     *
     * @param int $userid
     * @return bool
     */
    protected static function est_reference_personnel(int $userid): bool {
        global $DB;

        foreach (self::champs_personnel() as $table => $champs) {
            foreach (array_keys($champs) as $champ) {
                if ($DB->record_exists($table, [$champ => $userid])) {
                    return true;
                }
            }
        }
        return false;
    }

    /**
     * Utilisateurs ayant des données dans le contexte.
     *
     * @param userlist $userlist
     * @return void
     */
    public static function get_users_in_context(userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $sources = [
            'local_simhub_session' => ['userid', 'usermodified'],
            'local_simhub_val_encadrant' => ['validateuruserid'],
            'local_simhub_asv_valsim' => ['userid', 'validateuruserid'],
            'local_simhub_asv_valanimal' => ['userid'],
        ];
        foreach (self::champs_personnel() as $table => $champs) {
            $sources[$table] = array_keys($champs);
        }
        foreach ($sources as $table => $champs) {
            foreach ($champs as $champ) {
                $userlist->add_from_sql('userid', "SELECT $champ AS userid FROM {" . $table . "} WHERE $champ > 0", []);
            }
        }
    }

    /**
     * Suppression des données des utilisateurs approuvés.
     *
     * @param approved_userlist $userlist
     * @return void
     */
    public static function delete_data_for_users(approved_userlist $userlist): void {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        foreach ($userlist->get_userids() as $userid) {
            self::supprimer_utilisateur((int) $userid);
        }
    }

    /**
     * Export des données de l'utilisateur.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function export_user_data(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $context = \context_system::instance();
        $userid = $contextlist->get_user()->id;

        $sessions = $DB->get_records('local_simhub_session', ['userid' => $userid]);
        $export = [];
        foreach ($sessions as $session) {
            $bilan = $DB->get_record('local_simhub_ae_bilan', ['sessionid' => $session->id]);
            $reponses = $DB->get_records('local_simhub_ae_reponse', ['sessionid' => $session->id]);
            $export[] = [
                'atelierid' => $session->atelierid,
                'statut' => $session->statut,
                'timestart' => transform::datetime($session->timestart),
                'timeend' => $session->timeend ? transform::datetime($session->timeend) : null,
                'autoevaluation' => array_map(fn($r) => ['critereid' => $r->critereid, 'niveau' => $r->niveau], $reponses),
                'autobilan' => $bilan ? [
                    'pointmaitrise' => $bilan->pointmaitrise,
                    'pointaretravailler' => $bilan->pointaretravailler,
                    'pointattention' => $bilan->pointattention,
                ] : null,
            ];
        }
        if ($export) {
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:local_simhub_session', 'local_simhub')],
                (object) ['sessions' => $export]
            );
        }

        $valsim = $DB->get_records('local_simhub_asv_valsim', ['userid' => $userid]);
        if ($valsim) {
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:local_simhub_asv_valsim', 'local_simhub')],
                (object) ['validations' => array_values($valsim)]
            );
        }

        $valanimal = $DB->get_records('local_simhub_asv_valanimal', ['userid' => $userid]);
        if ($valanimal) {
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:local_simhub_asv_valanimal', 'local_simhub')],
                (object) ['validations' => array_values($valanimal)]
            );
        }

        $references = [];
        foreach (self::champs_personnel() as $table => $champs) {
            foreach (array_keys($champs) as $champ) {
                $ids = $DB->get_fieldset_select($table, 'id', "$champ = ?", [$userid]);
                if ($ids) {
                    $references[$table][$champ] = array_map('intval', $ids);
                }
            }
        }
        if ($references) {
            writer::with_context($context)->export_data(
                [get_string('privacy:metadata:personnel', 'local_simhub')],
                (object) ['references' => $references]
            );
        }
    }

    /**
     * Suppression des données de tous les utilisateurs du contexte.
     *
     * @param \context $context
     * @return void
     */
    public static function delete_data_for_all_users_in_context(\context $context): void {
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        self::delete_all_simhub_data();
    }

    /**
     * Suppression des données de l'utilisateur.
     *
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        self::supprimer_utilisateur((int) $contextlist->get_user()->id);
    }

    /**
     * Supprime les données personnelles d'un utilisateur et efface ses références dans le
     * référentiel partagé.
     *
     * @param int $userid
     * @return void
     */
    protected static function supprimer_utilisateur(int $userid): void {
        global $DB;

        $sessionids = $DB->get_fieldset_select('local_simhub_session', 'id', 'userid = ?', [$userid]);
        if ($sessionids) {
            [$insql, $params] = $DB->get_in_or_equal($sessionids);
            $DB->delete_records_select('local_simhub_ae_reponse', "sessionid $insql", $params);
            $DB->delete_records_select('local_simhub_ae_bilan', "sessionid $insql", $params);
            $DB->delete_records_select('local_simhub_val_encadrant', "sessionid $insql", $params);
        }
        $DB->delete_records('local_simhub_session', ['userid' => $userid]);
        $DB->delete_records('local_simhub_asv_valsim', ['userid' => $userid]);
        $DB->delete_records('local_simhub_asv_valanimal', ['userid' => $userid]);

        foreach (self::champs_personnel() as $table => $champs) {
            foreach ($champs as $champ => $remplacement) {
                $DB->set_field($table, $champ, $remplacement, [$champ => $userid]);
            }
        }
        $DB->set_field('local_simhub_session', 'usermodified', 0, ['usermodified' => $userid]);
    }

    /**
     * Purge complète (utilisée uniquement lors d'une suppression de contexte système entière,
     * ce qui n'arrive en pratique jamais hors désinstallation du plugin).
     *
     * @return void
     */
    private static function delete_all_simhub_data(): void {
        global $DB;

        $DB->delete_records('local_simhub_ae_reponse');
        $DB->delete_records('local_simhub_ae_bilan');
        $DB->delete_records('local_simhub_val_encadrant');
        $DB->delete_records('local_simhub_session');
        $DB->delete_records('local_simhub_asv_valsim');
        $DB->delete_records('local_simhub_asv_valanimal');
    }
}
