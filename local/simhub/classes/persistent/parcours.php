<?php

namespace local_simhub\persistent;

use local_simhub\record\parc_atelier;

defined('MOODLE_INTERNAL') || die();

/**
 * Parcours pédagogique (§8).
 */
class parcours extends \core\persistent {

    const TABLE = 'local_simhub_parcours';

    protected static function define_properties() {
        return [
            'nom' => ['type' => PARAM_TEXT],
            'description' => ['type' => PARAM_RAW, 'default' => '', 'null' => NULL_ALLOWED],
            'type' => [
                'type' => PARAM_ALPHA,
                'choices' => ['recommande', 'obligatoire', 'lie_uc', 'lie_annee', 'certifiant', 'asv'],
            ],
            'courseid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'anneeetude' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'cohortid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'envcode' => ['type' => PARAM_ALPHANUMEXT],
            'badgeid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
        ];
    }

    /**
     * Ateliers du parcours dans leur ordre, avec caractère obligatoire/échéance (§8).
     *
     * @return array Enregistrements de local_simhub_parc_atelier, triés par ordre.
     */
    public function get_ateliers(): array {
        global $DB;
        return $DB->get_records(
            parc_atelier::TABLE,
            ['parcoursid' => $this->get('id')],
            'ordre ASC'
        );
    }
}
