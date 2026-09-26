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
 * Validation ASV sur animal vivant, potentiellement par un validateur externe sans compte.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\record;

/**
 * Validation ASV sur animal vivant, potentiellement par un validateur externe sans compte
 * Moodle, via un lien à jeton (§9.3). Niveau de preuve volontairement simple : nom, prénom,
 * date, case de certification, signature au doigt — pas de signature électronique qualifiée.
 */
class asv_valanimal {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_asv_valanimal';

    /** @var string Statut : en_attente. */
    const STATUT_EN_ATTENTE = 'en_attente';
    /** @var string Statut : valide. */
    const STATUT_VALIDE = 'valide';
    /** @var string Statut : annule. */
    const STATUT_ANNULE = 'annule';

    /**
     * Crée une demande de validation animal vivant et son lien à jeton, pour un étudiant/acte.
     *
     * @param int $userid Étudiant.
     * @param int $acteid
     * @return \stdClass Enregistrement créé (contient le token).
     */
    public static function get_ou_creer_demande(int $userid, int $acteid): \stdClass {
        return self::get_demande_en_attente($userid, $acteid) ?: self::creer_demande($userid, $acteid);
    }

    /**
     * Demande encore utilisable (en attente, lien non expiré) pour un acte, s'il y en a une.
     *
     * @param int $userid
     * @param int $acteid
     * @return \stdClass|false
     */
    public static function get_demande_en_attente(int $userid, int $acteid) {
        global $DB;

        $records = $DB->get_records_select(
            self::TABLE,
            'userid = :userid AND acteid = :acteid AND statut = :statut AND tokenexpire > :now',
            ['userid' => $userid, 'acteid' => $acteid, 'statut' => self::STATUT_EN_ATTENTE, 'now' => time()],
            'tokenexpire DESC',
            '*',
            0,
            1
        );
        return reset($records);
    }

    /**
     * Une signature n'est acceptée que si un tracé a réellement été dessiné : image PNG
     * encodée en base64, produite par le canvas de valider_animal.php.
     *
     * @param string $signature
     * @return bool
     */
    public static function signature_valide(string $signature): bool {
        return (bool) preg_match('#^data:image/png;base64,[A-Za-z0-9+/=]{100,}$#', $signature);
    }

    /**
     * Annule une validation sur animal vivant (erreur de saisie, validateur non habilité).
     *
     * @param int $id
     * @return void
     */
    public static function annuler(int $id): void {
        global $DB;

        $DB->set_field(self::TABLE, 'statut', self::STATUT_ANNULE, ['id' => $id]);
    }

    /**
     * Crée une nouvelle demande de validation, avec son propre lien à jeton.
     *
     * @param int $userid
     * @param int $acteid
     * @return \stdClass
     */
    public static function creer_demande(int $userid, int $acteid): \stdClass {
        global $DB;

        $expiry = (int) (get_config('local_simhub', 'asvtokenexpiry') ?: 7 * DAYSECS);
        $record = (object) [
            'userid' => $userid,
            'acteid' => $acteid,
            'datevalidation' => null,
            'nomvalidateur' => null,
            'prenomvalidateur' => null,
            'certificationcochee' => 0,
            'signature' => null,
            'statut' => self::STATUT_EN_ATTENTE,
            'token' => \core\uuid::generate(),
            'tokenexpire' => time() + $expiry,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Retrouve une demande par son jeton, si elle n'a pas expiré.
     *
     * @param string $token
     * @return \stdClass|false
     */
    public static function get_par_token(string $token) {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['token' => $token]);
        if (!$record) {
            return false;
        }
        if ($record->tokenexpire && $record->tokenexpire < time()) {
            return false;
        }
        return $record;
    }

    /**
     * Enregistre la validation par le validateur (externe ou non), via le formulaire court du §9.3.
     *
     * @param string $token
     * @param string $nom
     * @param string $prenom
     * @param bool $certificationcochee Case attestant que le validateur est vétérinaire/encadrant autorisé.
     * @param string $signature Tracé de signature (SVG/PNG base64).
     * @return bool
     */
    public static function valider(
        string $token,
        string $nom,
        string $prenom,
        bool $certificationcochee,
        string $signature
    ): bool {
        global $DB;

        $record = self::get_par_token($token);
        if (
            !$record || $record->statut !== self::STATUT_EN_ATTENTE || !$certificationcochee
                || trim($nom) === '' || trim($prenom) === '' || !self::signature_valide($signature)
        ) {
            return false;
        }

        $record->nomvalidateur = $nom;
        $record->prenomvalidateur = $prenom;
        $record->certificationcochee = 1;
        $record->signature = $signature;
        $record->datevalidation = time();
        $record->statut = self::STATUT_VALIDE;
        $DB->update_record(self::TABLE, $record);
        return true;
    }

    /**
     * Validations animal vivant d'un étudiant.
     *
     * @param int $userid
     * @return \stdClass[]
     */
    public static function get_pour_etudiant(int $userid): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['userid' => $userid]);
    }
}
