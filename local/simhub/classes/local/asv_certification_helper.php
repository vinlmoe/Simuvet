<?php

namespace local_simhub\local;

use local_simhub\persistent\asv_acte;
use local_simhub\record\asv_valsim;
use local_simhub\record\asv_valanimal;

defined('MOODLE_INTERNAL') || die();

/**
 * Détermine qui a effectivement validé un niveau ASV complet (simulation + animal vivant
 * sur tous les actes du niveau, §9.4), pour la certification individuelle
 * (asv/attestation_pdf.php) comme pour la génération groupée par niveau
 * (manage/asv_attestations.php) plutôt que de dupliquer cette logique aux deux endroits.
 */
class asv_certification_helper {

    /**
     * Niveaux dont tous les actes doivent être validés pour certifier un niveau. La
     * certification de fin de A3 est globale (§9.4) : elle couvre tout le référentiel
     * cumulé A1 + A2 + A3 ; les attestations A1 et A2 restent des étapes intermédiaires.
     *
     * @param string $niveau
     * @return string[]
     */
    public static function niveaux_requis(string $niveau): array {
        return $niveau === 'A3' ? ['A1', 'A2', 'A3'] : [$niveau];
    }

    /**
     * Actes à valider pour certifier un niveau.
     *
     * @param string $envcode
     * @param string $niveau
     * @return asv_acte[]
     */
    public static function get_actes_requis(string $envcode, string $niveau): array {
        $actes = [];
        foreach (self::niveaux_requis($niveau) as $n) {
            $actes = array_merge($actes, asv_acte::get_referentiel($envcode, $n));
        }
        return $actes;
    }

    /**
     * État de chaque acte du référentiel pour un étudiant : dernière décision en simulation,
     * validation sur animal vivant, demande en attente.
     *
     * @param int $userid
     * @param string $envcode
     * @return array acteid => ['acte', 'sim', 'annulation', 'animal', 'attente']
     */
    public static function etat_etudiant(int $userid, string $envcode): array {
        $etat = [];
        foreach (asv_acte::get_referentiel($envcode) as $acte) {
            $etat[(int) $acte->get('id')] = ['acte' => $acte, 'sim' => null, 'annulation' => null, 'animal' => null,
                'attente' => null];
        }

        // Historique trié du plus récent au plus ancien : une validation l'emporte sur un
        // refus antérieur, un refus ou une annulation ne s'affiche que s'il n'y a pas mieux.
        foreach (asv_valsim::get_historique($userid) as $v) {
            if (!isset($etat[$v->acteid])) {
                continue;
            }
            if ($v->statut === asv_valsim::STATUT_ANNULE) {
                // Seule la plus récente décision annulée est montrée, et seulement si rien
                // n'a été décidé depuis.
                if (!$etat[$v->acteid]['sim'] && !$etat[$v->acteid]['annulation']) {
                    $etat[$v->acteid]['annulation'] = $v;
                }
                continue;
            }
            $actuel = $etat[$v->acteid]['sim'];
            if (!$actuel || ($actuel->statut !== asv_valsim::STATUT_VALIDE && $v->statut === asv_valsim::STATUT_VALIDE)) {
                $etat[$v->acteid]['sim'] = $v;
            }
        }

        foreach (asv_valanimal::get_pour_etudiant($userid) as $v) {
            if (!isset($etat[$v->acteid])) {
                continue;
            }
            if ($v->statut === asv_valanimal::STATUT_VALIDE) {
                $etat[$v->acteid]['animal'] = $v;
            } else if ($v->statut === asv_valanimal::STATUT_EN_ATTENTE && $v->tokenexpire > time()) {
                $etat[$v->acteid]['attente'] = $v;
            }
        }

        return $etat;
    }

    /**
     * Actes du niveau non encore couverts (simulation et/ou animal vivant manquants) pour
     * un étudiant. Une liste vide signifie que le niveau est complet.
     *
     * @param int $userid
     * @param string $niveau
     * @param string $envcode
     * @return string[] Libellés des manques, un par acte incomplet.
     */
    public static function get_actes_manquants(int $userid, string $niveau, string $envcode): array {
        $actes = self::get_actes_requis($envcode, $niveau);

        $actesvalidessim = asv_valsim::get_actes_valides($userid);
        $actesvalidesanimal = [];
        foreach (asv_valanimal::get_pour_etudiant($userid) as $v) {
            if ($v->statut === asv_valanimal::STATUT_VALIDE) {
                $actesvalidesanimal[$v->acteid] = true;
            }
        }

        $manquants = [];
        foreach ($actes as $acte) {
            $simok = in_array((int) $acte->get('id'), $actesvalidessim, true);
            $animalok = !empty($actesvalidesanimal[$acte->get('id')]);
            if (!$simok || !$animalok) {
                $manquants[] = $acte->get('nom')
                    . ($simok ? '' : ' (' . get_string('asv_manque_simulation', 'local_simhub') . ')')
                    . ($animalok ? '' : ' (' . get_string('asv_manque_animal', 'local_simhub') . ')');
            }
        }

        return $manquants;
    }

    /**
     * Identifiants des étudiants ayant entièrement validé un niveau (tous les actes, en
     * simulation et sur animal vivant), parmi ceux ayant au moins une validation en
     * simulation enregistrée pour ce niveau - un candidat sans aucune validation ne peut de
     * toute façon pas être complet, inutile de le tester.
     *
     * @param string $niveau
     * @param string $envcode
     * @return int[]
     */
    public static function get_etudiants_eligibles(string $niveau, string $envcode): array {
        global $DB;

        $actes = self::get_actes_requis($envcode, $niveau);
        if (empty($actes)) {
            return [];
        }
        $acteids = array_map(fn($a) => $a->get('id'), $actes);

        [$insql, $inparams] = $DB->get_in_or_equal($acteids);
        $candidats = $DB->get_fieldset_sql(
            "SELECT DISTINCT userid FROM {local_simhub_asv_valsim} WHERE acteid $insql AND statut = ?",
            array_merge($inparams, [\local_simhub\record\asv_valsim::STATUT_VALIDE])
        );

        $eligibles = [];
        foreach ($candidats as $userid) {
            if (empty(self::get_actes_manquants((int) $userid, $niveau, $envcode))) {
                $eligibles[] = (int) $userid;
            }
        }

        return $eligibles;
    }
}
