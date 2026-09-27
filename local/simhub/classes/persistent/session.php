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
 * Session de réalisation d'un atelier par un étudiant (§7.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\persistent;

/**
 * Session de réalisation d'un atelier par un étudiant (§7.1).
 */
class session extends \core\persistent {
    /** @var string Table de la base de données. */
    const TABLE = 'local_simhub_session';

    /** @var string Statut : commence. */
    const STATUT_COMMENCE = 'commence';
    /** @var string Statut : realise. */
    const STATUT_REALISE = 'realise';
    /** @var string Statut : certifie. */
    const STATUT_CERTIFIE = 'certifie';
    /** @var string Statut : non_termine. */
    const STATUT_NON_TERMINE = 'non_termine';

    /**
     * Propriétés persistées.
     *
     * @return array
     */
    protected static function define_properties() {
        return [
            'userid' => ['type' => PARAM_INT],
            'atelierid' => ['type' => PARAM_INT],
            'parcoursid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'courseid' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            'timestart' => ['type' => PARAM_INT],
            'timeend' => ['type' => PARAM_INT, 'default' => 0, 'null' => NULL_ALLOWED],
            // PARAM_ALPHANUMEXT : PARAM_ALPHA refuserait le « _ » de non_termine.
            'statut' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => self::STATUT_COMMENCE,
                'choices' => [
                    self::STATUT_COMMENCE,
                    self::STATUT_REALISE,
                    self::STATUT_CERTIFIE,
                    self::STATUT_NON_TERMINE,
                ],
            ],
            'methodescan' => [
                'type' => PARAM_ALPHA,
                'default' => '',
                'null' => NULL_ALLOWED,
                'choices' => ['', 'qr', 'manuel'],
            ],
            'controlepresence' => [
                'type' => PARAM_ALPHANUMEXT,
                'default' => '',
                'null' => NULL_ALLOWED,
                'choices' => ['', 'reseau_local', 'code_seance', 'validation_encadrant', 'non_verifie'],
            ],
            'dureesuspecte' => [
                'type' => PARAM_INT,
                'default' => 0,
            ],
        ];
    }

    /**
     * Démarre une session pour un étudiant sur un atelier (scan QR ou démarrage manuel, §7).
     *
     * @param int $userid
     * @param int $atelierid
     * @param array $extra Propriétés additionnelles (parcoursid, courseid, methodescan, controlepresence).
     * @return session
     */
    public static function demarrer(int $userid, int $atelierid, array $extra = []): session {
        $data = array_merge([
            'userid' => $userid,
            'atelierid' => $atelierid,
            'timestart' => time(),
            'statut' => self::STATUT_COMMENCE,
        ], $extra);

        $session = new self(0, (object) $data);
        $session->create();
        return $session;
    }

    /**
     * Point d'entrée unique pour démarrer une séance, quel que soit le chemin (bouton, QR,
     * code de séance) : refuse un atelier qui n'est pas actif (indisponible, archivé...,
     * §6.1) et reprend la séance déjà en cours plutôt que d'en créer une seconde (§7.1).
     *
     * @param int $userid
     * @param int $atelierid
     * @param array $extra Voir demarrer().
     * @return session
     */
    public static function demarrer_ou_reprendre(int $userid, int $atelierid, array $extra = []): session {
        $atelier = new atelier($atelierid);
        if ($atelier->get('statut') !== atelier::STATUT_ACTIF) {
            throw new \moodle_exception(
                'atelier_non_demarrable',
                'local_simhub',
                new \moodle_url('/local/simhub/atelier.php', ['id' => $atelierid])
            );
        }

        foreach (self::get_pour_etudiant($userid, $atelierid) as $existante) {
            if ($existante->get('statut') === self::STATUT_COMMENCE) {
                return $existante;
            }
        }

        $session = self::demarrer($userid, $atelierid, $extra);
        \local_simhub\event\session_started::create([
            'objectid' => $session->get('id'),
            'context' => \context_system::instance(),
        ])->trigger();
        return $session;
    }

    /**
     * Termine la session courante : marque comme réalisée et horodate la fin.
     *
     * @return void
     */
    public function terminer(): void {
        $this->set('timeend', time());
        $this->set('dureesuspecte', self::est_trop_courte($this->get('atelierid'), time() - $this->get('timestart')) ? 1 : 0);
        $this->set('statut', self::STATUT_REALISE);
        $this->update();
    }

    /**
     * Historique des sessions d'un étudiant, la plus récente d'abord (§7.1, §5.1 "ateliers déjà commencés").
     *
     * @param int $userid
     * @param int|null $atelierid Restreindre à un atelier donné.
     * @return session[]
     */
    public static function get_pour_etudiant(int $userid, ?int $atelierid = null): array {
        $params = ['userid' => $userid];
        if ($atelierid !== null) {
            $params['atelierid'] = $atelierid;
        }
        return self::get_records($params, 'timestart DESC, id', 'DESC');
    }

    /**
     * Vrai si une séance de cette durée est anormalement courte (§7.1) : moins que le
     * pourcentage paramétré de la durée indicative de l'atelier. Sans réglage ou sans durée
     * indicative, aucune séance n'est signalée.
     *
     * @param int $atelierid
     * @param int $duree Durée de la séance, en secondes.
     * @return bool
     */
    public static function est_trop_courte(int $atelierid, int $duree): bool {
        $pct = (int) get_config('local_simhub', 'dureeminpct');
        if ($pct <= 0) {
            return false;
        }
        $indicative = (int) (new atelier($atelierid))->get('dureeindicative');
        return $indicative > 0 && $duree < $indicative * MINSECS * $pct / 100;
    }
}
