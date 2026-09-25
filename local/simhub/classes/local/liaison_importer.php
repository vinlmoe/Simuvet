<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\record\rattachement;
use local_simhub\record\parc_atelier;

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
            $data = self::map_row($line, $delimiter, $colmap);

            if (empty($data['numero'])) {
                $result['erreurs'][] = get_string('import_ligne_numero', 'local_simhub', $lineno + 2);
                continue;
            }

            $envcode = $data['envcode'] ?: $defaultenvcode;
            $atelier = atelier::get_record(['envcode' => $envcode, 'numero' => $data['numero']]);
            if (!$atelier) {
                $result['erreurs'][] = get_string('import_ligne_atelier_introuvable', 'local_simhub',
                    (object) ['ligne' => $lineno + 2, 'numero' => $data['numero'], 'envcode' => $envcode]);
                continue;
            }

            rattachement::creer($atelier->get('id'), [
                'courseid' => !empty($data['courseid']) ? (int) $data['courseid'] : null,
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
            $data = self::map_row($line, $delimiter, $colmap);

            if (empty($data['parcours']) || empty($data['numero'])) {
                $result['erreurs'][] = get_string('import_ligne_parcours', 'local_simhub', $lineno + 2);
                continue;
            }

            $envcode = $data['envcode'] ?: $defaultenvcode;
            $atelier = atelier::get_record(['envcode' => $envcode, 'numero' => $data['numero']]);
            if (!$atelier) {
                $result['erreurs'][] = get_string('import_ligne_atelier_introuvable', 'local_simhub',
                    (object) ['ligne' => $lineno + 2, 'numero' => $data['numero'], 'envcode' => $envcode]);
                continue;
            }

            $cachekey = $envcode . '|' . $data['parcours'];
            if (!isset($parcoursids[$cachekey])) {
                $existant = parcours::get_record(['envcode' => $envcode, 'nom' => $data['parcours']]);
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

            parc_atelier::ajouter(
                $parcoursids[$cachekey],
                $atelier->get('id'),
                !empty($data['ordre']) ? (int) $data['ordre'] : 0,
                !empty($data['obligatoire']) && stripos((string) $data['obligatoire'], 'oui') !== false
            );
            $result['crees']++;
        }

        return $result;
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
     * @param string $line
     * @param string $delimiter
     * @param array $colmap
     * @return array
     */
    private static function map_row(string $line, string $delimiter, array $colmap): array {
        $row = str_getcsv($line, $delimiter, '"', '');
        $data = [];
        foreach ($colmap as $index => $property) {
            $data[$property] = isset($row[$index]) ? trim($row[$index]) : '';
        }
        return $data;
    }
}
