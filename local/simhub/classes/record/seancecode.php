<?php

namespace local_simhub\record;

defined('MOODLE_INTERNAL') || die();

/**
 * Code de séance temporaire : un des mécanismes possibles de contrôle anti-faux-scan (§7.3).
 * Un encadrant génère un code court pour sa salle, valable une durée limitée ; l'étudiant le
 * saisit en complément du scan QR pour prouver sa présence.
 */
class seancecode {

    const TABLE = 'local_simhub_seancecode';

    /**
     * Génère un nouveau code de séance pour une salle.
     *
     * @param string $salle
     * @param int $createuruserid
     * @param int|null $dureesecondes Durée de validité ; par défaut le réglage local_simhub/seancecodeduration.
     * @return \stdClass
     */
    public static function generer(string $salle, int $createuruserid, ?int $dureesecondes = null): \stdClass {
        global $DB;

        $duree = $dureesecondes ?? (int) (get_config('local_simhub', 'seancecodeduration') ?: HOURSECS);
        $now = time();

        $record = (object) [
            'salle' => $salle,
            'code' => strtoupper(substr(str_replace(['0', 'O', '1', 'I'], '', \core\uuid::generate()), 0, 6)),
            'validfrom' => $now,
            'validto' => $now + $duree,
            'createuruserid' => $createuruserid,
            'timecreated' => $now,
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Vérifie qu'un code est valide pour une salle donnée, au moment présent.
     *
     * @param string $salle
     * @param string $code
     * @return bool
     */
    public static function est_valide(string $salle, string $code): bool {
        global $DB;

        $now = time();
        return $DB->record_exists_select(
            self::TABLE,
            'salle = ? AND code = ? AND validfrom <= ? AND validto >= ?',
            [$salle, strtoupper($code), $now, $now]
        );
    }
}
