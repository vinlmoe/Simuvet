<?php

namespace local_simhub\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;

defined('MOODLE_INTERNAL') || die();

/**
 * Fournisseur de confidentialité (RGPD) pour SimHub.
 *
 * Toutes les données SimHub vivent en contexte système (§10 : pas de rattachement à un
 * contexte de cours), donc chaque utilisateur concerné n'a jamais qu'un seul contexte à
 * traiter : context_system::instance().
 */
class provider implements
        \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider {

    /**
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
        ], 'privacy:metadata:local_simhub_asv_valsim');

        $collection->add_database_table('local_simhub_asv_valanimal', [
            'userid' => 'privacy:metadata:local_simhub_asv_valanimal:userid',
            'acteid' => 'privacy:metadata:local_simhub_asv_valanimal:acteid',
            'nomvalidateur' => 'privacy:metadata:local_simhub_asv_valanimal:nomvalidateur',
            'prenomvalidateur' => 'privacy:metadata:local_simhub_asv_valanimal:prenomvalidateur',
            'signature' => 'privacy:metadata:local_simhub_asv_valanimal:signature',
        ], 'privacy:metadata:local_simhub_asv_valanimal');

        return $collection;
    }

    /**
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
            || $DB->record_exists('local_simhub_asv_valanimal', ['userid' => $userid]);

        if ($hasdata) {
            $contextlist->add_system_context();
        }

        return $contextlist;
    }

    /**
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
    }

    /**
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
     * @param approved_contextlist $contextlist
     * @return void
     */
    public static function delete_data_for_user(approved_contextlist $contextlist): void {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $userid = $contextlist->get_user()->id;

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
