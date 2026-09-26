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
 * Import souple des rattachements pédagogiques et de la composition des parcours (§12.1),.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\record\rattachement;
use local_simhub\persistent\ressource;

/**
 * Import souple des rattachements pédagogiques et de la composition des parcours (§12.1),
 * en complément de atelier_importer (qui ne couvre que la fiche atelier elle-même).
 *
 * Les deux imports identifient un atelier par son (envcode, numero) déjà connu de la base
 * — importer d'abord les ateliers avec atelier_importer avant d'utiliser ceux-ci.
 */
class liaison_importer {
    /**
     * Importe des rattachements atelier <-> UC/année/cohorte (§6).
     *
     * Colonnes reconnues : numero (ou atelier), envcode (optionnel, sinon $defaultenvcode),
     * courseid, anneeetude, cohortid, caractere (recommande|obligatoire), niveauattendu.
     *
     * @param string $content
     * @param string $delimiter
     * @param string $defaultenvcode
     * @return array{crees:int,erreurs:string[]}
     */
    public static function importer_rattachements(string $content, string $delimiter, string $defaultenvcode): array {
        $aliases = [
            'numero' => 'numero', 'atelier' => 'numero', 'atelierdumero' => 'numero',
            'envcode' => 'envcode', 'etablissement' => 'envcode',
            'courseid' => 'courseid', 'iduc' => 'courseid', 'uc' => 'courseid',
            'anneeetude' => 'anneeetude', 'annee' => 'anneeetude',
            'cohortid' => 'cohortid', 'cohorte' => 'cohortid',
            'caractere' => 'caractere', 'obligatoire' => 'caractere',
            'niveauattendu' => 'niveauattendu', 'niveau' => 'niveauattendu',
        ];

        $result = ['crees' => 0, 'erreurs' => []];
        [$colmap, $lines, $error] = self::parse_header($content, $delimiter, $aliases, ['numero']);
        if ($error) {
            $result['erreurs'][] = $error;
            return $result;
        }

        foreach ($lines as $lineno => $line) {
            $data = self::map_row($line, $delimiter, $colmap, $aliases);

            if (empty($data['numero'])) {
                $result['erreurs'][] = get_string('import_ligne_numero', 'local_simhub', $lineno + 2);
                continue;
            }

            $envcode = $data['envcode'] ?: $defaultenvcode;
            $atelier = self::trouver_atelier($data['numero'], $envcode);
            if (!$atelier) {
                $result['erreurs'][] = get_string(
                    'import_ligne_atelier_introuvable',
                    'local_simhub',
                    (object) ['ligne' => $lineno + 2, 'numero' => $data['numero'], 'envcode' => $envcode]
                );
                continue;
            }

            rattachement::creer($atelier->get('id'), [
                'courseid' => self::trouver_cours($data['courseid'] ?? ''),
                'anneeetude' => self::parse_annee($data['anneeetude'] ?? ''),
                'cohortid' => !empty($data['cohortid']) ? (int) $data['cohortid'] : null,
                'caractere' => !empty($data['caractere']) && stripos($data['caractere'], 'obl') !== false
                    ? rattachement::CARACTERE_OBLIGATOIRE : rattachement::CARACTERE_RECOMMANDE,
                'niveauattendu' => $data['niveauattendu'] ?: null,
            ]);
            $result['crees']++;
        }

        return $result;
    }

    /**
     * Importe la composition de parcours (§8) : crée le parcours s'il n'existe pas encore
     * (par nom + envcode), puis y ajoute les ateliers listés dans l'ordre du fichier.
     *
     * Colonnes reconnues : parcours (nom), type, envcode, numero (atelier), ordre, obligatoire.
     *
     * @param string $content
     * @param string $delimiter
     * @param string $defaultenvcode
     * @return array{crees:int,parcourscrees:int,erreurs:string[]}
     */
    public static function importer_parcours(string $content, string $delimiter, string $defaultenvcode): array {
        $aliases = [
            'parcours' => 'parcours', 'nomparcours' => 'parcours',
            'type' => 'type',
            'envcode' => 'envcode', 'etablissement' => 'envcode',
            'numero' => 'numero', 'atelier' => 'numero',
            'ordre' => 'ordre', 'obligatoire' => 'obligatoire',
        ];

        $result = ['crees' => 0, 'parcourscrees' => 0, 'erreurs' => []];
        [$colmap, $lines, $error] = self::parse_header($content, $delimiter, $aliases, ['parcours', 'numero']);
        if ($error) {
            $result['erreurs'][] = $error;
            return $result;
        }

        $parcoursids = [];

        foreach ($lines as $lineno => $line) {
            $data = self::map_row($line, $delimiter, $colmap, $aliases);

            if (empty($data['parcours']) || empty($data['numero'])) {
                $result['erreurs'][] = get_string('import_ligne_parcours', 'local_simhub', $lineno + 2);
                continue;
            }

            $envcode = $data['envcode'] ?: $defaultenvcode;
            $atelier = self::trouver_atelier($data['numero'], $envcode);
            if (!$atelier) {
                $result['erreurs'][] = get_string(
                    'import_ligne_atelier_introuvable',
                    'local_simhub',
                    (object) ['ligne' => $lineno + 2, 'numero' => $data['numero'], 'envcode' => $envcode]
                );
                continue;
            }

            $cachekey = $data['parcours'];
            if (!isset($parcoursids[$cachekey])) {
                $existant = parcours::get_record(['nom' => $data['parcours']]);
                if ($existant) {
                    $parcoursids[$cachekey] = $existant->get('id');
                } else {
                    $nouveau = new parcours(0, (object) [
                        'nom' => $data['parcours'],
                        'type' => !empty($data['type']) ? $data['type'] : 'recommande',
                        'envcode' => $envcode,
                    ]);
                    $nouveau->create();
                    $parcoursids[$cachekey] = $nouveau->get('id');
                    $result['parcourscrees']++;
                }
            }

            parcours_helper::ajouter_atelier(
                new parcours($parcoursids[$cachekey]),
                (int) $atelier->get('id'),
                !empty($data['ordre']) ? (int) $data['ordre'] : 0,
                self::est_vrai($data['obligatoire'] ?? ''),
                null
            );
            $result['crees']++;
        }

        return $result;
    }

    /**
     * Importe la localisation d'ateliers existants (§5.4, §12.1 « salles et zones ») : seuls
     * les champs présents dans le fichier sont mis à jour.
     *
     * Colonnes reconnues : numero, salle, zone, codeposte (ou poste), indicationtextuelle
     * (ou localisation, emplacement).
     *
     * @param string $content
     * @param string $delimiter
     * @param string $defaultenvcode
     * @return array{majs:int,erreurs:string[]}
     */
    public static function importer_localisation(string $content, string $delimiter, string $defaultenvcode): array {
        $aliases = [
            'numero' => 'numero', 'atelier' => 'numero', 'envcode' => 'envcode', 'etablissement' => 'envcode',
            'salle' => 'salle', 'local' => 'salle', 'zone' => 'zone', 'secteur' => 'zone',
            'codeposte' => 'codeposte', 'poste' => 'codeposte', 'numeroposte' => 'codeposte',
            'indicationtextuelle' => 'indicationtextuelle', 'localisation' => 'indicationtextuelle',
            'emplacement' => 'indicationtextuelle',
        ];
        $result = ['majs' => 0, 'erreurs' => []];
        [$colmap, $lines, $error] = self::parse_header($content, $delimiter, $aliases, ['numero']);
        if ($error) {
            $result['erreurs'][] = $error;
            return $result;
        }
        foreach ($lines as $lineno => $line) {
            $data = self::map_row($line, $delimiter, $colmap, $aliases);
            $atelier = self::atelier_de_ligne($data, $defaultenvcode, $lineno, $result);
            if (!$atelier) {
                continue;
            }
            foreach (['salle', 'zone', 'codeposte', 'indicationtextuelle'] as $champ) {
                if (in_array($champ, $colmap, true)) {
                    $atelier->set($champ, $data[$champ] !== '' ? $data[$champ] : null);
                }
            }
            $atelier->update();
            $result['majs']++;
        }
        return $result;
    }

    /**
     * Importe des liens vers des ressources d'ateliers existants (§12.1) : fiche méthode,
     * vidéo, lien Moodle ou externe. Une ressource de même titre sur l'atelier est mise à jour.
     *
     * Colonnes reconnues : numero, titre, url (ou lien), type, visibilite (etudiant|interne),
     * ordre.
     *
     * @param string $content
     * @param string $delimiter
     * @param string $defaultenvcode
     * @return array{crees:int,majs:int,erreurs:string[]}
     */
    public static function importer_ressources(string $content, string $delimiter, string $defaultenvcode): array {
        $aliases = [
            'numero' => 'numero', 'atelier' => 'numero', 'envcode' => 'envcode', 'etablissement' => 'envcode',
            'titre' => 'titre', 'nom' => 'titre', 'url' => 'url', 'lien' => 'url', 'adresse' => 'url',
            'type' => 'type', 'visibilite' => 'visibilite', 'ordre' => 'ordre',
        ];
        $result = ['crees' => 0, 'majs' => 0, 'erreurs' => []];
        [$colmap, $lines, $error] = self::parse_header($content, $delimiter, $aliases, ['numero', 'titre', 'url']);
        if ($error) {
            $result['erreurs'][] = $error;
            return $result;
        }
        $types = ['fiche_methode', 'pdf_etudiant', 'video', 'consignes', 'criteres_reussite', 'erreurs_frequentes',
            'liens_utiles', 'complementaire', ressource::TYPE_SOURCE_EDITABLE];
        foreach ($lines as $lineno => $line) {
            $data = self::map_row($line, $delimiter, $colmap, $aliases);
            $atelier = self::atelier_de_ligne($data, $defaultenvcode, $lineno, $result);
            if (!$atelier) {
                continue;
            }
            $url = clean_param($data['url'], PARAM_URL);
            if ($data['titre'] === '' || $url === '') {
                $result['erreurs'][] = get_string('import_ligne_ressource', 'local_simhub', $lineno + 2);
                continue;
            }
            $parforme = array_combine(array_map([atelier_importer::class, 'normalise_header'], $types), $types);
            $type = $parforme[atelier_importer::normalise_header($data['type'] ?? '')] ?? 'liens_utiles';
            $visibilite = stripos($data['visibilite'] ?? '', 'int') === 0 || $type === ressource::TYPE_SOURCE_EDITABLE
                ? ressource::VISIBILITE_INTERNE : ressource::VISIBILITE_ETUDIANT;

            $r = ressource::get_record(['atelierid' => $atelier->get('id'), 'titre' => $data['titre']]);
            $nouvelle = !$r;
            $r = $r ?: new ressource(0, (object) ['atelierid' => $atelier->get('id'), 'titre' => $data['titre']]);
            $r->set('url', $url);
            $r->set('type', $type);
            $r->set('visibilite', $visibilite);
            $r->set('ordre', (int) ($data['ordre'] ?? 0));
            if ($nouvelle) {
                $r->create();
                $result['crees']++;
            } else {
                $r->update();
                $result['majs']++;
            }
        }
        return $result;
    }

    /**
     * Interprète une case « obligatoire » : oui, o, x, 1, yes, obligatoire.
     *
     * @param string $valeur
     * @return bool
     */
    private static function est_vrai(string $valeur): bool {
        $oui = ['oui', 'o', 'x', '1', 'yes', 'y', 'obligatoire', 'vrai'];
        return in_array(atelier_importer::normalise_header($valeur), $oui, true);
    }

    /**
     * Atelier désigné par une ligne, ou null avec l'erreur ajoutée au résultat.
     *
     * @param array $data
     * @param string $defaultenvcode
     * @param int $lineno
     * @param array $result
     * @return atelier|null
     */
    private static function atelier_de_ligne(array $data, string $defaultenvcode, int $lineno, array &$result): ?atelier {
        if (empty($data['numero'])) {
            $result['erreurs'][] = get_string('import_ligne_numero', 'local_simhub', $lineno + 2);
            return null;
        }
        $envcode = ($data['envcode'] ?? '') ?: $defaultenvcode;
        $atelier = self::trouver_atelier($data['numero'], $envcode);
        if (!$atelier) {
            $result['erreurs'][] = get_string(
                'import_ligne_atelier_introuvable',
                'local_simhub',
                (object) ['ligne' => $lineno + 2, 'numero' => $data['numero'], 'envcode' => $envcode]
            );
        }
        return $atelier;
    }

    /**
     * Atelier par son numéro. Le code établissement ne sert qu'à la traçabilité : s'il ne
     * correspond pas, un numéro unique suffit.
     *
     * @param string $numero
     * @param string $envcode
     * @return atelier|null
     */
    public static function trouver_atelier(string $numero, string $envcode): ?atelier {
        $atelier = atelier::get_record(['envcode' => $envcode, 'numero' => $numero]);
        if ($atelier) {
            return $atelier;
        }
        $candidats = atelier::get_records(['numero' => $numero]);
        return count($candidats) === 1 ? reset($candidats) : null;
    }

    /**
     * UC par identifiant ou nom abrégé du cours.
     *
     * @param string $valeur
     * @return int|null
     */
    public static function trouver_cours(string $valeur): ?int {
        global $DB;

        $valeur = trim($valeur);
        if ($valeur === '') {
            return null;
        }
        if (ctype_digit($valeur) && $DB->record_exists('course', ['id' => (int) $valeur])) {
            return (int) $valeur;
        }
        $id = $DB->get_field('course', 'id', ['shortname' => $valeur]);
        return $id ? (int) $id : null;
    }

    /**
     * Lit l'en-tête d'un CSV et construit la correspondance colonne => propriété reconnue.
     *
     * @param string $content
     * @param string $delimiter
     * @param array $aliases
     * @param string[] $required Propriétés qui doivent être présentes dans le fichier.
     * @return array{0: array, 1: string[], 2: string|null}
     */
    private static function parse_header(string $content, string $delimiter, array $aliases, array $required): array {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_values(array_filter($lines, fn($l) => trim($l) !== ''));

        if (empty($lines)) {
            return [[], [], get_string('import_fichier_vide', 'local_simhub')];
        }

        $headerscols = str_getcsv(array_shift($lines), $delimiter, '"', '');
        $colmap = [];
        foreach ($headerscols as $index => $header) {
            $normalised = atelier_importer::normalise_header($header);
            if (isset($aliases[$normalised])) {
                $colmap[$index] = $aliases[$normalised];
            }
        }

        foreach ($required as $property) {
            if (!in_array($property, $colmap, true)) {
                return [[], [], get_string('import_colonne_manquante', 'local_simhub', $property)];
            }
        }

        return [$colmap, $lines, null];
    }

    /**
     * Extrait une année d'étude (1 à 5) d'une valeur de cellule, qu'elle soit un chiffre
     * brut ("3") ou une notation courante ("A3", "Année 3").
     *
     * @param string $value
     * @return int|null
     */
    private static function parse_annee(string $value): ?int {
        if (trim($value) === '') {
            return null;
        }
        if (preg_match('/([1-5])/', $value, $matches)) {
            return (int) $matches[1];
        }
        return null;
    }

    /**
     * Associe les valeurs d'une ligne CSV aux colonnes reconnues.
     *
     * @param string $line
     * @param string $delimiter
     * @param array $colmap
     * @param array $aliases Colonnes connues, pour que chacune existe même absente du fichier.
     * @return array
     */
    private static function map_row(string $line, string $delimiter, array $colmap, array $aliases = []): array {
        $row = str_getcsv($line, $delimiter, '"', '');
        // Toute colonne connue existe, vide si absente du fichier.
        $data = array_fill_keys(array_values($aliases), '');
        foreach ($colmap as $index => $property) {
            $data[$property] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        return $data;
    }
}
