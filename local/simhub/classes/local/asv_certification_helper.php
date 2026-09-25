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
     * Actes du niveau non encore couverts (simulation et/ou animal vivant manquants) pour
     * un étudiant. Une liste vide signifie que le niveau est complet.
     *
     * @param int $userid
     * @param string $niveau
     * @param string $envcode
     * @return string[] Libellés des manques, un par acte incomplet.
     */
    public static function get_actes_manquants(int $userid, string $niveau, string $envcode): array {
        $actes = asv_acte::get_referentiel($envcode, $niveau);

        $actesvalidessim = asv_valsim::get_actes_valides($userid);
        $actesvalidesanimal = [];
        foreach (asv_valanimal::get_pour_etudiant($userid) as $v) {
            if ($v->statut === asv_valanimal::STATUT_VALIDE) {
                $actesvalidesanimal[$v->acteid] = true;
            }
        }

        $manquants = [];
        foreach ($actes as $acte) {
            $simok = in_array($acte->get('id'), $actesvalidessim, true);
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

        $actes = asv_acte::get_referentiel($envcode, $niveau);
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
