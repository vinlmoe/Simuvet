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
 * Lecture des fichiers importés : CSV, XLSX ou ODS (§12.1).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Ramène tout fichier importé à un contenu CSV UTF-8, que les importeurs savent lire.
 */
class tableur {
    /** Extensions acceptées. */
    const EXTENSIONS = ['csv', 'txt', 'xlsx', 'ods'];

    /**
     * Contenu CSV d'un fichier : tel quel pour un CSV (converti en UTF-8 si besoin), la
     * première feuille sinon, avec le séparateur demandé.
     *
     * @param string $chemin Fichier sur le disque.
     * @param string $nom Nom d'origine, pour l'extension.
     * @param string $separateur
     * @return string
     */
    public static function vers_csv(string $chemin, string $nom, string $separateur): string {
        $ext = strtolower(pathinfo($nom, PATHINFO_EXTENSION));
        if ($ext === 'xlsx' || $ext === 'ods') {
            return self::feuille_vers_csv($chemin, $ext, $separateur);
        }
        $contenu = file_get_contents($chemin);
        // Les exports Excel français sont souvent en Windows-1252.
        if (!mb_check_encoding($contenu, 'UTF-8')) {
            $contenu = mb_convert_encoding($contenu, 'UTF-8', 'Windows-1252');
        }
        return preg_replace('/^\xEF\xBB\xBF/', '', $contenu);
    }

    /**
     * Première feuille d'un classeur XLSX ou ODS, lue par OpenSpout (fourni avec Moodle).
     *
     * @param string $chemin
     * @param string $ext xlsx ou ods
     * @param string $separateur
     * @return string
     */
    protected static function feuille_vers_csv(string $chemin, string $ext, string $separateur): string {
        $lecteur = $ext === 'xlsx' ? new \OpenSpout\Reader\XLSX\Reader() : new \OpenSpout\Reader\ODS\Reader();
        $lecteur->open($chemin);
        $sortie = fopen('php://temp', 'w+');
        foreach ($lecteur->getSheetIterator() as $feuille) {
            foreach ($feuille->getRowIterator() as $ligne) {
                $cellules = array_map([self::class, 'texte'], $ligne->toArray());
                if (implode('', $cellules) !== '') {
                    fputcsv($sortie, $cellules, $separateur, '"', '');
                }
            }
            break;
        }
        $lecteur->close();
        rewind($sortie);
        $csv = stream_get_contents($sortie);
        fclose($sortie);
        return $csv;
    }

    /**
     * Valeur de cellule en texte : dates au format AAAA-MM-JJ, nombres entiers sans décimale.
     *
     * @param mixed $valeur
     * @return string
     */
    protected static function texte($valeur): string {
        if ($valeur instanceof \DateTimeInterface) {
            return $valeur->format('Y-m-d');
        }
        if (is_float($valeur) && floor($valeur) == $valeur) {
            return (string) (int) $valeur;
        }
        if (is_bool($valeur)) {
            return $valeur ? '1' : '0';
        }
        // Les importeurs lisent une ligne à la fois : pas de retour à la ligne dans une cellule.
        return trim(preg_replace('/\s*[\r\n]+\s*/', ' ', (string) $valeur));
    }
}
