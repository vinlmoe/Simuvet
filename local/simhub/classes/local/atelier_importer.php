<?php

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

use local_simhub\persistent\atelier;

/**
 * Import souple de fiches ateliers depuis un fichier CSV hétérogène (§12.1).
 *
 * Les tableaux de suivi des quatre ENV ne partagent pas le même format : plutôt que
 * d'imposer un schéma de colonnes strict, l'import reconnaît un ensemble d'alias de
 * noms de colonnes usuels (accents/espaces/casse ignorés) et n'exige que le strict
 * minimum (numéro + nom court). Tout le reste peut être corrigé/enrichi après import
 * (§12.1 "correction et enrichissement après import"), notamment via manage/ateliers.php.
 */
class atelier_importer {

    /** Alias de colonnes reconnus, normalisés (minuscule, sans accent ni espace) => propriété atelier. */
    const ALIASES = [
        'numero' => 'numero', 'num' => 'numero', 'id' => 'numero', 'reference' => 'numero',
        'nomcourt' => 'nomcourt', 'nom' => 'nomcourt', 'titre' => 'nomcourt', 'intitule' => 'nomcourt',
        'nomlong' => 'nomlong', 'nomcomplet' => 'nomlong', 'description' => 'nomlong',
        'descriptioncourte' => 'descriptioncourte', 'resume' => 'descriptioncourte',
        'discipline' => 'discipline', 'matiere' => 'discipline', 'domaine' => 'discipline',
        'espece' => 'espece', 'especes' => 'espece',
        'niveau' => 'niveaudifficulte', 'niveaudedifficulte' => 'niveaudifficulte', 'difficulte' => 'niveaudifficulte',
        'duree' => 'dureeindicative', 'dureeminutes' => 'dureeindicative', 'dureeindicative' => 'dureeindicative',
        'statut' => 'statut', 'etat' => 'statut',
        'envcode' => 'envcode', 'etablissement' => 'envcode', 'ecole' => 'envcode', 'env' => 'envcode',
        'salle' => 'salle', 'piece' => 'salle',
        'zone' => 'zone', 'secteur' => 'zone',
        'codeposte' => 'codeposte', 'poste' => 'codeposte', 'numeroposte' => 'codeposte',
        'indicationtextuelle' => 'indicationtextuelle', 'localisation' => 'indicationtextuelle', 'emplacement' => 'indicationtextuelle',
        'commentaire' => 'commentaireadmin', 'commentaireadmin' => 'commentaireadmin', 'note' => 'commentaireadmin',
    ];

    /** Statuts atelier reconnus en entrée, alias => valeur canonique. */
    const STATUT_ALIASES = [
        'actif' => atelier::STATUT_ACTIF, 'active' => atelier::STATUT_ACTIF, 'oui' => atelier::STATUT_ACTIF,
        'nonutilise' => atelier::STATUT_NON_UTILISE, 'inactif' => atelier::STATUT_NON_UTILISE,
        'indisponible' => atelier::STATUT_INDISPONIBLE, 'hs' => atelier::STATUT_INDISPONIBLE,
        'archive' => atelier::STATUT_ARCHIVE,
    ];

    /**
     * Normalise un intitulé de colonne (minuscule, sans accent, sans espace/ponctuation) pour
     * le comparer aux alias connus, quelle que soit la façon dont il a été saisi dans le tableur.
     *
     * @param string $header
     * @return string
     */
    public static function normalise_header(string $header): string {
        $header = \core_text::strtolower(trim($header));
        $header = str_replace(
            ['é', 'è', 'ê', 'ë', 'à', 'â', 'ô', 'û', 'ù', 'ç', 'î', 'ï'],
            ['e', 'e', 'e', 'e', 'a', 'a', 'o', 'u', 'u', 'c', 'i', 'i'],
            $header
        );
        return preg_replace('/[^a-z0-9]/', '', $header);
    }

    /**
     * Importe le contenu CSV fourni (contenu brut du fichier). Crée ou met à jour les
     * ateliers selon la clé (envcode, numero).
     *
     * @param string $content Contenu brut du fichier CSV.
     * @param string $delimiter Séparateur de colonnes (',' ou ';').
     * @param string $defaultenvcode Établissement par défaut si la colonne envcode est absente.
     * @return array{crees:int,majs:int,erreurs:string[]}
     */
    public static function importer(string $content, string $delimiter, string $defaultenvcode): array {
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $lines = array_filter($lines, fn($l) => trim($l) !== '');
        $lines = array_values($lines);

        $result = ['crees' => 0, 'majs' => 0, 'erreurs' => []];

        if (empty($lines)) {
            $result['erreurs'][] = 'Fichier vide.';
            return $result;
        }

        $headerscols = str_getcsv(array_shift($lines), $delimiter);
        $colmap = [];
        foreach ($headerscols as $index => $header) {
            $normalised = self::normalise_header($header);
            if (isset(self::ALIASES[$normalised])) {
                $colmap[$index] = self::ALIASES[$normalised];
            }
        }

        if (!in_array('numero', $colmap, true) || !in_array('nomcourt', $colmap, true)) {
            $result['erreurs'][] = 'Colonnes obligatoires introuvables : il faut au moins un numéro et un nom d\'atelier.';
            return $result;
        }

        foreach ($lines as $lineno => $line) {
            $row = str_getcsv($line, $delimiter);
            $data = [];
            foreach ($colmap as $index => $property) {
                $data[$property] = isset($row[$index]) ? trim($row[$index]) : '';
            }

            if (empty($data['numero']) || empty($data['nomcourt'])) {
                $result['erreurs'][] = 'Ligne ' . ($lineno + 2) . ' ignorée : numéro ou nom manquant.';
                continue;
            }

            $envcode = $data['envcode'] ?? '';
            if ($envcode === '') {
                $envcode = $defaultenvcode;
            }
            if ($envcode === '') {
                $result['erreurs'][] = 'Ligne ' . ($lineno + 2) . ' ignorée : établissement (envcode) inconnu.';
                continue;
            }

            if (!empty($data['statut'])) {
                $normalisedstatut = self::normalise_header($data['statut']);
                $data['statut'] = self::STATUT_ALIASES[$normalisedstatut] ?? atelier::STATUT_NON_UTILISE;
            }
            if (isset($data['dureeindicative']) && $data['dureeindicative'] !== '') {
                $data['dureeindicative'] = (int) preg_replace('/[^0-9]/', '', $data['dureeindicative']);
            }

            try {
                $existing = atelier::get_record(['envcode' => $envcode, 'numero' => $data['numero']]);
                if ($existing) {
                    foreach ($data as $property => $value) {
                        if ($value !== '' && $existing->has_property($property)) {
                            $existing->set($property, $value);
                        }
                    }
                    $existing->update();
                    $result['majs']++;
                } else {
                    $data['envcode'] = $envcode;
                    if (empty($data['statut'])) {
                        $data['statut'] = atelier::STATUT_NON_UTILISE;
                    }
                    $nouvel = new atelier(0, (object) $data);
                    $nouvel->create();
                    $result['crees']++;
                }
            } catch (\Exception $e) {
                $result['erreurs'][] = 'Ligne ' . ($lineno + 2) . ' : ' . $e->getMessage();
            }
        }

        return $result;
    }
}
