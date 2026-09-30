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
 * Code de séance temporaire : un des mécanismes possibles de contrôle anti-faux-scan (§7.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Code de séance temporaire : un des mécanismes possibles de contrôle anti-faux-scan (§7.3).
 * Un encadrant génère un code court pour sa salle, valable une durée limitée ; l'étudiant le
 * saisit en complément du scan QR pour prouver sa présence.
 */
class seancecode {
    /** @var string Table de la base de données. */
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
            // Code court, lisible à voix haute : chiffres/lettres ambigus (0/O, 1/I) et
            // tirets du UUID source retirés avant de tronquer aux 6 premiers caractères.
            'code' => strtoupper(substr(str_replace(['0', 'O', '1', 'I', '-'], '', \core\uuid::generate()), 0, 6)),
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
        // Atelier sans salle renseignée : tout code en cours de validité est accepté, sinon
        // aucun code ne pourrait jamais convenir.
        [$where, $params] = $salle !== '' ? ['salle = ? AND ', [$salle]] : ['', []];
        return $DB->record_exists_select(
            self::TABLE,
            $where . 'code = ? AND validfrom <= ? AND validto >= ?',
            array_merge($params, [strtoupper($code), $now, $now])
        );
    }

    /**
     * Vrai si un code de séance est en cours de validité pour une salle (toutes salles
     * confondues si la salle n'est pas renseignée).
     *
     * @param string $salle
     * @return bool
     */
    public static function existe_actif(string $salle): bool {
        global $DB;

        $now = time();
        [$where, $params] = $salle !== '' ? ['salle = ? AND ', [$salle]] : ['', []];
        return $DB->record_exists_select(
            self::TABLE,
            $where . 'validfrom <= ? AND validto >= ?',
            array_merge($params, [$now, $now])
        );
    }

    /**
     * Le code de séance doit-il être demandé pour démarrer cet atelier (§7.3) ? Oui si le
     * contrôle anti-faux-scan est activé pour tout le site, ou dès qu'un encadrant a généré
     * un code encore valable pour la salle : générer un code n'aurait sinon aucun effet.
     * Jamais depuis le réseau de la salle, où la présence est déjà vérifiée.
     *
     * @param string $salle Salle de l'atelier.
     * @return bool
     */
    public static function a_demander(string $salle): bool {
        if (\local_simhub\local\reseau::dans_la_salle()) {
            return false;
        }
        return get_config('local_simhub', 'controlepresenceactif') || self::existe_actif($salle);
    }
}
