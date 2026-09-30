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
 * Le lien n'est jamais montré à l'étudiant : il est envoyé par e-mail à l'adresse du
 * validateur qu'il indique, adresse conservée et affichée dans le livret. L'étudiant ne peut
 * ainsi pas ouvrir lui-même la page de validation pour se valider.
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
     * Refuse une adresse de validateur qui est celle de l'étudiant lui-même.
     *
     * @param int $userid Étudiant.
     * @param string $email Adresse du validateur.
     * @return bool
     */
    public static function email_acceptable(int $userid, string $email): bool {
        $etudiant = \core_user::get_user($userid, 'id, email', MUST_EXIST);
        return validate_email($email)
            && \core_text::strtolower(trim($email)) !== \core_text::strtolower(trim($etudiant->email));
    }

    /**
     * Crée une nouvelle demande de validation, avec son propre lien à jeton, et l'envoie au
     * validateur. Une éventuelle demande encore en attente pour le même acte est expirée :
     * une seule demande active par acte.
     *
     * @param int $userid
     * @param int $acteid
     * @param string $email Adresse du validateur, destinataire du lien.
     * @return \stdClass Demande créée ; « envoye » indique si l'e-mail est parti.
     */
    public static function creer_demande(int $userid, int $acteid, string $email): \stdClass {
        global $DB;

        if (!self::email_acceptable($userid, $email)) {
            throw new \invalid_parameter_exception('emailvalidateur');
        }
        if ($ancienne = self::get_demande_en_attente($userid, $acteid)) {
            $DB->set_field(self::TABLE, 'tokenexpire', time() - 1, ['id' => $ancienne->id]);
        }

        $expiry = (int) (get_config('local_simhub', 'asvtokenexpiry') ?: 7 * DAYSECS);
        $record = (object) [
            'userid' => $userid,
            'acteid' => $acteid,
            'datevalidation' => null,
            'nomvalidateur' => null,
            'prenomvalidateur' => null,
            'certificationcochee' => 0,
            'signature' => null,
            'emailvalidateur' => trim($email),
            'statut' => self::STATUT_EN_ATTENTE,
            'token' => \core\uuid::generate(),
            'tokenexpire' => time() + $expiry,
            'timecreated' => time(),
        ];
        $record->id = $DB->insert_record(self::TABLE, $record);
        $record->envoye = self::envoyer_lien($record);
        return $record;
    }

    /**
     * Envoie (ou renvoie) au validateur l'e-mail contenant le lien de validation.
     *
     * @param \stdClass $demande
     * @return bool Vrai si l'e-mail est parti.
     */
    public static function envoyer_lien(\stdClass $demande): bool {
        global $SITE;

        $etudiant = \core_user::get_user($demande->userid, '*', MUST_EXIST);
        $acte = new \local_simhub\persistent\asv_acte($demande->acteid);
        $a = (object) [
            'etudiant' => fullname($etudiant),
            'acte' => $acte->get('nom'),
            'lien' => (new \moodle_url('/local/simhub/asv/valider_animal.php', ['token' => $demande->token]))->out(false),
            'expire' => userdate($demande->tokenexpire, get_string('strftimedatetimeshort', 'langconfig')),
            'site' => format_string($SITE->fullname),
        ];

        // Destinataire sans compte Moodle : copie de l'utilisateur « noreply » (mis en cache par
        // Moodle, d'où le clone) dont seule l'adresse compte pour l'envoi.
        $destinataire = clone \core_user::get_noreply_user();
        $destinataire->email = $demande->emailvalidateur;
        $destinataire->emailstop = 0;
        $destinataire->firstname = '';
        $destinataire->lastname = $demande->emailvalidateur;

        return email_to_user(
            $destinataire,
            \core_user::get_noreply_user(),
            get_string('asv_mail_sujet', 'local_simhub', $a),
            get_string('asv_mail_corps', 'local_simhub', $a)
        );
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
     * @param int $validateurid Utilisateur Moodle connecté qui valide (0 si aucun) : jamais l'étudiant lui-même.
     * @return bool
     */
    public static function valider(
        string $token,
        string $nom,
        string $prenom,
        bool $certificationcochee,
        string $signature,
        int $validateurid = 0
    ): bool {
        global $DB;

        $record = self::get_par_token($token);
        if (
            !$record || $record->statut !== self::STATUT_EN_ATTENTE || !$certificationcochee
                || ($validateurid && $validateurid == $record->userid)
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
     * Vrai si l'acte est déjà validé sur animal vivant pour cet étudiant.
     *
     * @param int $userid
     * @param int $acteid
     * @return bool
     */
    public static function est_valide(int $userid, int $acteid): bool {
        global $DB;

        return $DB->record_exists(self::TABLE, ['userid' => $userid, 'acteid' => $acteid, 'statut' => self::STATUT_VALIDE]);
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
