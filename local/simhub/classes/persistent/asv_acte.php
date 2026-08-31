<?php

namespace local_simhub\persistent;

defined('MOODLE_INTERNAL') || die();

/**
 * Référentiel des actes vétérinaires délégables ASV (§9).
 */
class asv_acte extends \core\persistent {

    const TABLE = 'local_simhub_asv_acte';

    protected static function define_properties() {
        return [
            'code' => ['type' => PARAM_ALPHANUMEXT],
            'nom' => ['type' => PARAM_TEXT],
            'espece' => ['type' => PARAM_TEXT, 'default' => '', 'null' => NULL_ALLOWED],
            'niveau' => ['type' => PARAM_ALPHANUM, 'choices' => ['A1', 'A2', 'A3']],
            'ucid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'envcode' => ['type' => PARAM_ALPHANUMEXT],
            'actif' => ['type' => PARAM_INT, 'default' => 1],
        ];
    }

    /**
     * Référentiel actif d'un établissement, éventuellement filtré par niveau A1-A3 (§9.4).
     *
     * @param string $envcode
     * @param string|null $niveau
     * @return asv_acte[]
     */
    public static function get_referentiel(string $envcode, ?string $niveau = null): array {
        $params = ['envcode' => $envcode, 'actif' => 1];
        if ($niveau !== null) {
            $params['niveau'] = $niveau;
        }
        return self::get_records($params, 'niveau');
    }
}
