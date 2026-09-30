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
 *
 * Contre la fraude (un étudiant qui signerait lui-même son lien), une signature externe
 * n'est jamais acquise d'emblée : elle passe à « signe » et ne devient « valide » qu'après
 * contrôle par un encadrant (asv/controle_signatures.php), aidé d'indices relevés à la
 * signature (compte Moodle connecté, adresse IP, nom du signataire).
 */
class asv_valanimal {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_asv_valanimal';

    /** @var string Statut : en_attente. */
    const STATUT_EN_ATTENTE = 'en_attente';
    /** @var string Statut : signée par le validateur externe, en attente du contrôle interne. */
    const STATUT_SIGNE = 'signe';
    /** @var string Statut : valide (signature contrôlée et confirmée par un encadrant). */
    const STATUT_VALIDE = 'valide';
    /** @var string Statut : signature rejetée au contrôle interne. */
    const STATUT_REJETE = 'rejete';
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
        global $DB, $USER;

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
            'demandeuruserid' => isloggedin() ? (int) $USER->id : 0,
            'demandeip' => getremoteaddr(''),
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
        $record = self::get_par_token($token);
        return $record && self::signer($record, $nom, $prenom, $certificationcochee, $signature);
    }

    /**
     * Saisie du validateur complète : nom, prénom, certification cochée et tracé de signature.
     *
     * @param string $nom
     * @param string $prenom
     * @param bool $certificationcochee
     * @param string $signature
     * @return bool
     */
    public static function saisie_valide(string $nom, string $prenom, bool $certificationcochee, string $signature): bool {
        return $certificationcochee && trim($nom) !== '' && trim($prenom) !== '' && self::signature_valide($signature);
    }

    /**
     * Enregistre la signature du validateur sur une demande en attente : elle reste à
     * contrôler par un encadrant avant de compter (STATUT_SIGNE).
     *
     * @param \stdClass $record
     * @param string $nom
     * @param string $prenom
     * @param bool $certificationcochee
     * @param string $signature
     * @return bool
     */
    protected static function signer(
        \stdClass $record,
        string $nom,
        string $prenom,
        bool $certificationcochee,
        string $signature
    ): bool {
        global $DB, $USER;

        if ($record->statut !== self::STATUT_EN_ATTENTE || !self::saisie_valide($nom, $prenom, $certificationcochee, $signature)) {
            return false;
        }

        $record->nomvalidateur = $nom;
        $record->prenomvalidateur = $prenom;
        $record->certificationcochee = 1;
        $record->signature = $signature;
        $record->datevalidation = time();
        $record->statut = self::STATUT_SIGNE;
        // Indices pour le contrôle interne.
        $record->signatureip = getremoteaddr('');
        $record->signatureuserid = isloggedin() && !isguestuser() ? (int) $USER->id : 0;
        $DB->update_record(self::TABLE, $record);
        return true;
    }

    /**
     * Contrôle interne d'une signature externe : confirmée, elle devient une validation
     * acquise ; rejetée, l'étudiant doit refaire une demande.
     *
     * @param int $id
     * @param int $controleuruserid
     * @param bool $confirme
     * @param string $motif Motif du rejet (conservé dans l'historique).
     * @return \stdClass|null La demande mise à jour, null si elle n'était pas à contrôler.
     */
    public static function controler(int $id, int $controleuruserid, bool $confirme, string $motif = ''): ?\stdClass {
        global $DB;

        $record = $DB->get_record(self::TABLE, ['id' => $id]);
        if (!$record || $record->statut !== self::STATUT_SIGNE) {
            return null;
        }
        $record->statut = $confirme ? self::STATUT_VALIDE : self::STATUT_REJETE;
        $record->controleuruserid = $controleuruserid;
        $record->datecontrole = time();
        $record->motifcontrole = $motif;
        $DB->update_record(self::TABLE, $record);
        return $record;
    }

    /**
     * Signatures externes en attente du contrôle interne, les plus anciennes d'abord.
     *
     * @return \stdClass[]
     */
    public static function get_a_controler(): array {
        global $DB;

        return $DB->get_records(self::TABLE, ['statut' => self::STATUT_SIGNE], 'datevalidation ASC, id ASC');
    }

    /**
     * Indices de fraude à examiner lors du contrôle : ce ne sont pas des preuves (le
     * vétérinaire peut signer sur le téléphone de l'étudiant, sur le même réseau Wi-Fi),
     * mais des points à vérifier.
     *
     * @param \stdClass $record Demande signée.
     * @param \stdClass|null $etudiant Utilisateur étudiant (lastname, lastip).
     * @return string[] Libellés, les plus graves d'abord.
     */
    public static function indices(\stdClass $record, ?\stdClass $etudiant): array {
        $indices = [];
        $norm = fn($t) => \core_text::strtolower(trim((string) $t));
        if ($etudiant && $norm($record->nomvalidateur) !== '' && $norm($record->nomvalidateur) === $norm($etudiant->lastname)) {
            $indices[] = get_string('asv_indice_nom', 'local_simhub');
        }
        if (!empty($record->signatureuserid)) {
            $indices[] = (int) $record->signatureuserid === (int) $record->userid
                ? get_string('asv_indice_session_etudiant', 'local_simhub')
                : get_string('asv_indice_session_autre', 'local_simhub');
        }
        $ip = (string) ($record->signatureip ?? '');
        if ($ip !== '') {
            $ipetudiant = [];
            if ((int) ($record->demandeuruserid ?? 0) === (int) $record->userid && !empty($record->demandeip)) {
                $ipetudiant[] = $record->demandeip;
            }
            if ($etudiant && !empty($etudiant->lastip)) {
                $ipetudiant[] = $etudiant->lastip;
            }
            if (in_array($ip, $ipetudiant, true)) {
                $indices[] = get_string('asv_indice_ip', 'local_simhub', s($ip));
            }
        }
        if ((int) $record->datevalidation - (int) $record->timecreated < 2 * MINSECS
                && (int) ($record->demandeuruserid ?? 0) === (int) $record->userid) {
            $indices[] = get_string('asv_indice_rapide', 'local_simhub');
        }
        return $indices;
    }

    /**
     * Lien groupé : regroupe sous un même jeton les demandes de plusieurs étudiants pour un
     * acte, afin qu'un validateur externe les signe en une fois (en reprenant la demande
     * déjà en attente de chaque étudiant, s'il en a une).
     *
     * @param int $acteid
     * @param int[] $userids
     * @return \stdClass lottoken, expire (échéance la plus proche des demandes regroupées).
     */
    public static function creer_lot(int $acteid, array $userids): \stdClass {
        global $DB;

        $lottoken = \core\uuid::generate();
        $expire = 0;
        foreach ($userids as $userid) {
            $demande = self::get_ou_creer_demande((int) $userid, $acteid);
            $DB->set_field(self::TABLE, 'lottoken', $lottoken, ['id' => $demande->id]);
            $expire = $expire ? min($expire, (int) $demande->tokenexpire) : (int) $demande->tokenexpire;
        }
        return (object) ['lottoken' => $lottoken, 'expire' => $expire];
    }

    /**
     * Demandes d'un lien groupé encore à signer (en attente, non expirées).
     *
     * @param string $lottoken
     * @return \stdClass[] id => demande
     */
    public static function get_lot(string $lottoken): array {
        global $DB;

        if ($lottoken === '') {
            return [];
        }
        return $DB->get_records_select(
            self::TABLE,
            'lottoken = :lottoken AND statut = :statut AND tokenexpire > :now',
            ['lottoken' => $lottoken, 'statut' => self::STATUT_EN_ATTENTE, 'now' => time()],
            'id'
        );
    }

    /**
     * Signature groupée : les demandes cochées du lien groupé reçoivent la même validation.
     *
     * @param string $lottoken
     * @param int[] $ids Demandes retenues par le validateur.
     * @param string $nom
     * @param string $prenom
     * @param bool $certificationcochee
     * @param string $signature
     * @return \stdClass[] Demandes validées.
     */
    public static function valider_lot(
        string $lottoken,
        array $ids,
        string $nom,
        string $prenom,
        bool $certificationcochee,
        string $signature
    ): array {
        if (!self::saisie_valide($nom, $prenom, $certificationcochee, $signature)) {
            return [];
        }
        $ids = array_flip(array_map('intval', $ids));
        $validees = [];
        foreach (self::get_lot($lottoken) as $record) {
            if (isset($ids[(int) $record->id]) && self::signer($record, $nom, $prenom, true, $signature)) {
                $validees[] = $record;
            }
        }
        return $validees;
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
