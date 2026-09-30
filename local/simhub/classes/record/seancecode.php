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

    /** @var string Code valable pour la salle de l'atelier. */
    const VALIDE = 'valide';
    /** @var string Code connu mais dont la validité est dépassée. */
    const EXPIRE = 'expire';
    /** @var string Code en cours de validité, mais généré pour une autre salle. */
    const AUTRE_SALLE = 'autresalle';
    /** @var string Code inconnu. */
    const INCONNU = 'inconnu';

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
     * Vrai si le code de séance doit être demandé avant de démarrer une séance : contrôle
     * anti-faux-scan activé et étudiant hors du réseau de la salle (§7.3).
     *
     * @return bool
     */
    public static function est_requis(): bool {
        return get_config('local_simhub', 'controlepresenceactif')
            && !\local_simhub\local\reseau::dans_la_salle();
    }

    /**
     * Normalise un code saisi : majuscules, sans espaces ni tirets.
     *
     * @param string $code
     * @return string
     */
    public static function normaliser_code(string $code): string {
        return \core_text::strtoupper(preg_replace('/[\s-]+/u', '', $code));
    }

    /**
     * Normalise un nom de salle pour la comparaison : casse et espaces ignorés.
     *
     * @param string|null $salle
     * @return string
     */
    public static function normaliser_salle(?string $salle): string {
        return \core_text::strtolower(preg_replace('/\s+/u', ' ', trim((string) $salle)));
    }

    /**
     * Vérifie un code pour une salle donnée, au moment présent, en donnant la raison d'un refus.
     * La salle est comparée sans tenir compte de la casse ni des espaces, pour qu'un code généré
     * pour « Salle 2 » soit accepté sur un atelier saisi « salle 2 ».
     *
     * @param string|null $salle Salle de l'atelier.
     * @param string $code Code saisi par l'étudiant.
     * @return string Une des constantes VALIDE, EXPIRE, AUTRE_SALLE, INCONNU.
     */
    public static function verifier(?string $salle, string $code): string {
        global $DB;

        $code = self::normaliser_code($code);
        if ($code === '') {
            return self::INCONNU;
        }
        $now = time();
        $salle = self::normaliser_salle($salle);
        $resultat = self::INCONNU;
        foreach ($DB->get_records(self::TABLE, ['code' => $code]) as $record) {
            $encours = $record->validfrom <= $now && $record->validto >= $now;
            if (self::normaliser_salle($record->salle) === $salle) {
                if ($encours) {
                    return self::VALIDE;
                }
                $resultat = $resultat === self::AUTRE_SALLE ? $resultat : self::EXPIRE;
            } else if ($encours) {
                $resultat = self::AUTRE_SALLE;
            }
        }
        return $resultat;
    }

    /**
     * Vérifie qu'un code est valide pour une salle donnée, au moment présent.
     *
     * @param string|null $salle
     * @param string $code
     * @return bool
     */
    public static function est_valide(?string $salle, string $code): bool {
        return self::verifier($salle, $code) === self::VALIDE;
    }

    /**
     * Codes actuellement valables, le plus récent d'abord.
     *
     * @return \stdClass[]
     */
    public static function get_en_cours(): array {
        global $DB;

        $now = time();
        return $DB->get_records_select(self::TABLE, 'validfrom <= ? AND validto >= ?', [$now, $now], 'timecreated DESC');
    }
}
