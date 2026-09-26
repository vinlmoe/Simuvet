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
 * Reconnaissance du réseau local de la salle de simulation (§7.3).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Contrôle de présence automatique par plage d'adresses IP.
 */
class reseau {
    /**
     * Plages d'adresses de la salle, au format de Moodle (une par ligne ou séparées par des
     * virgules : 192.168.1.0/24, 10.2.3.4-50, 172.16.).
     *
     * @return string Plages normalisées pour address_in_subnet(), vide si non paramétré.
     */
    public static function plages(): string {
        $brut = (string) get_config('local_simhub', 'reseauxsalle');
        $plages = array_filter(array_map('trim', preg_split('/[\s,]+/', $brut)));
        return implode(',', $plages);
    }

    /**
     * Vrai si l'utilisateur se connecte depuis le réseau de la salle.
     *
     * @param string|null $ip Adresse à tester, celle de la requête par défaut.
     * @return bool
     */
    public static function dans_la_salle(?string $ip = null): bool {
        $plages = self::plages();
        if ($plages === '') {
            return false;
        }
        return address_in_subnet($ip ?? getremoteaddr(), $plages);
    }
}
