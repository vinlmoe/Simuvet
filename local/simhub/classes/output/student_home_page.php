<?php

namespace local_simhub\output;

defined('MOODLE_INTERNAL') || die();

use local_simhub\local\atelier_filter;
use local_simhub\persistent\atelier;
use local_simhub\persistent\parcours;
use local_simhub\persistent\session;
use local_simhub\record\ae_reponse;
use local_simhub\record\indispo;
use local_simhub\record\asv_valsim;
use local_simhub\record\rattachement;
use local_simhub\persistent\asv_acte;
use local_simhub\local\annee_resolver;
use local_simhub\local\cohort_helper;
use renderable;
use templatable;

/**
 * Accueil étudiant personnalisé (§5.1) : sept sections construites au-dessus d'un même
 * calcul de statut personnel par atelier (§5.3) :
 * à faire pour mes UC, mes parcours en cours, parcours ASV, ateliers déjà commencés,
 * ateliers à reprendre, recommandés pour mon année, et la liste complète filtrable.
 */
class student_home_page implements renderable, templatable {

    /** @var int */
    protected $userid;
    /** @var atelier_filter */
    protected $filter;

    public function __construct(int $userid, atelier_filter $filter) {
        $this->userid = $userid;
        $this->filter = $filter;
    }

    public function export_for_template(\renderer_base $output): array {
        global $DB;

        $envcode = $this->filter->envcode;
        $anneeoptions = annee_resolver::get_options(false);

        // Base commune : tous les ateliers actifs de l'établissement, et le statut
        // personnel de l'étudiant sur chacun (§5.3), calculés une seule fois et réutilisés
        // pour construire les différentes sections du §5.1.
        $tousactifs = atelier::get_records(
            $envcode !== '' ? ['envcode' => $envcode, 'statut' => atelier::STATUT_ACTIF] : ['statut' => atelier::STATUT_ACTIF]
        );
        $sessions = session::get_pour_etudiant($this->userid);

        $dernieresessionparatelier = [];
        foreach ($sessions as $s) {
            $aid = $s->get('atelierid');
            if (!isset($dernieresessionparatelier[$aid])) {
                $dernieresessionparatelier[$aid] = $s;
            }
        }

        $cardsbyid = [];
        $statutsparid = [];
        foreach ($tousactifs as $atelier) {
            $record = $atelier->to_record();
            [$card, $statutperso] = $this->build_card($record, $dernieresessionparatelier[$record->id] ?? null);
            $cardsbyid[$record->id] = $card;
            $statutsparid[$record->id] = $statutperso;
        }

        // 1. À faire pour mes UC : ateliers rattachés à un cours dans lequel l'étudiant est
        // inscrit, pas encore réalisés (§5.1).
        $mesuc = [];
        $courseids = array_keys(enrol_get_users_courses($this->userid, true));
        if (!empty($courseids)) {
            [$insql, $params] = $DB->get_in_or_equal($courseids);
            $rattachements = $DB->get_records_select('local_simhub_rattachement', "courseid $insql", $params);
            foreach ($rattachements as $r) {
                if (isset($cardsbyid[$r->atelierid]) && $statutsparid[$r->atelierid] !== 'valide') {
                    $mesuc[$r->atelierid] = $cardsbyid[$r->atelierid];
                }
            }
        }

        // 4/5. Ateliers commencés / à reprendre.
        $commences = [];
        $areprendre = [];
        foreach ($statutsparid as $aid => $statutperso) {
            if ($statutperso === 'commence') {
                $commences[$aid] = $cardsbyid[$aid];
            } else if ($statutperso === 'areprendre') {
                $areprendre[$aid] = $cardsbyid[$aid];
            }
        }

        // 2. Mes parcours en cours : parcours dans lesquels l'étudiant a au moins une
        // session, avec un pourcentage d'avancement (§8.1), tant qu'il n'est pas à 100%.
        $parcoursencours = [];
        $parcoursids = array_unique(array_filter(array_map(fn($s) => $s->get('parcoursid'), $sessions)));
        foreach ($parcoursids as $pid) {
            $parcours = new parcours($pid);
            $composition = $parcours->get_ateliers();
            $atelierids = array_column($composition, 'atelierid');
            if (empty($atelierids)) {
                continue;
            }
            $realises = 0;
            foreach ($atelierids as $aid) {
                if (($statutsparid[$aid] ?? 'pascommence') !== 'pascommence' && ($statutsparid[$aid] ?? '') !== 'commence') {
                    $realises++;
                }
            }
            $pct = round(100 * $realises / count($atelierids));
            if ($pct < 100) {
                $parcoursencours[] = [
                    'id' => $pid,
                    'nom' => s($parcours->get('nom')),
                    'pct' => $pct,
                    'url' => (new \moodle_url('/local/simhub/index.php', ['parcoursid' => $pid]))->out(false),
                ];
            }
        }

        // 7. Recommandés pour mon groupe : ateliers rattachés à une cohorte choisie
        // directement par le gestionnaire (manage/rattachements.php), dont l'étudiant est
        // membre — appartenance réelle, sans déduction sur un nom de groupe (§5.1).
        $recommandesgroupe = [];
        $cohortids = cohort_helper::get_cohortes_utilisateur($this->userid);
        if (!empty($cohortids)) {
            foreach (rattachement::get_pour_cohortes($cohortids) as $r) {
                if (isset($cardsbyid[$r->atelierid]) && $statutsparid[$r->atelierid] === 'pascommence') {
                    $recommandesgroupe[$r->atelierid] = $cardsbyid[$r->atelierid];
                }
            }
        }

        // 3. Parcours ASV : résumé rapide (nombre d'actes validés / total du référentiel).
        $asvtotal = $envcode !== '' ? count(asv_acte::get_referentiel($envcode)) : 0;
        $asvvalides = count(asv_valsim::get_actes_valides($this->userid));

        // 6. Tous les ateliers disponibles, avec le filtre libre de l'étudiant (§5.2).
        $tousliste = [];
        foreach ($this->filter->get_ateliers() as $atelier) {
            if (isset($cardsbyid[$atelier->id])) {
                $tousliste[] = $cardsbyid[$atelier->id];
            }
        }

        return [
            'title' => get_string('simhub:studenthome', 'local_simhub'),

            'mesuc' => array_values($mesuc),
            'hasmesuc' => !empty($mesuc),

            'commences' => array_values($commences),
            'hascommences' => !empty($commences),

            'areprendre' => array_values($areprendre),
            'hasareprendre' => !empty($areprendre),

            'recommandesgroupe' => array_values($recommandesgroupe),
            'hasrecommandesgroupe' => !empty($recommandesgroupe),

            'parcoursencours' => $parcoursencours,
            'hasparcoursencours' => !empty($parcoursencours),

            'asvtotal' => $asvtotal,
            'asvvalides' => $asvvalides,
            'hasasv' => $asvtotal > 0,
            'urlasv' => (new \moodle_url('/local/simhub/asv/index.php'))->out(false),

            'ateliers' => array_values($tousliste),
            'hasateliers' => !empty($tousliste),
            'nomessage' => get_string('aucun_atelier', 'local_simhub'),
            'formurl' => (new \moodle_url('/local/simhub/index.php'))->out(false),
            'filtre' => [
                'motcle' => s($this->filter->motcle),
                'discipline' => s($this->filter->discipline),
                'espece' => s($this->filter->espece),
                'niveaudifficulte' => s($this->filter->niveaudifficulte),
                'dureemax' => $this->filter->dureemax ?: '',
                'anneeetude' => $this->filter->anneeetude ?: '',
            ],
            'anneeoptions' => array_map(
                fn($val, $label) => ['value' => $val, 'label' => $label, 'selected' => (string) $val === (string) $this->filter->anneeetude],
                array_keys($anneeoptions),
                $anneeoptions
            ),
            'strings' => [
                'filtremotcle' => get_string('filtre_motcle', 'local_simhub'),
                'filtrediscipline' => get_string('filtre_discipline', 'local_simhub'),
                'filtreespece' => get_string('filtre_espece', 'local_simhub'),
                'filtreniveau' => get_string('filtre_niveau', 'local_simhub'),
                'filtreduree' => get_string('filtre_duree', 'local_simhub'),
                'filtreannee' => get_string('filtre_annee', 'local_simhub'),
                'filtreappliquer' => get_string('filtre_appliquer', 'local_simhub'),
                'filtrereinitialiser' => get_string('filtre_reinitialiser', 'local_simhub'),
                'boutonlocalisation' => get_string('bouton_localisation', 'local_simhub'),
                'boutonressources' => get_string('bouton_ressources', 'local_simhub'),
                'boutoncommencer' => get_string('bouton_commencer', 'local_simhub'),
                'boutonterminer' => get_string('bouton_terminer', 'local_simhub'),
                'sectionmesuc' => get_string('section_mesuc', 'local_simhub'),
                'sectionparcours' => get_string('section_parcours', 'local_simhub'),
                'sectionasv' => get_string('section_asv', 'local_simhub'),
                'sectioncommences' => get_string('section_commences', 'local_simhub'),
                'sectionareprendre' => get_string('section_areprendre', 'local_simhub'),
                'sectionrecommandes' => get_string('section_recommandes', 'local_simhub'),
                'sectiontous' => get_string('section_tous', 'local_simhub'),
                'voirasv' => get_string('asv_parcours', 'local_simhub'),
            ],
        ];
    }

    /**
     * Construit les données de carte étudiante (§5.3) pour un atelier et sa session la plus
     * récente, ainsi que le statut personnel dérivé (pascommence|commence|realise|valide|areprendre).
     *
     * @param \stdClass $atelier Enregistrement brut d'atelier.
     * @param session|null $sessionencours Dernière session de l'étudiant sur cet atelier, si existante.
     * @return array{0: array, 1: string}
     */
    private function build_card(\stdClass $atelier, ?session $sessionencours): array {
        $statutperso = 'pascommence';

        if ($sessionencours) {
            if ($sessionencours->get('statut') === session::STATUT_COMMENCE) {
                $statutperso = 'commence';
            } else if ($sessionencours->get('statut') === session::STATUT_CERTIFIE) {
                $statutperso = 'valide';
            } else {
                $statutperso = 'realise';
                if (ae_reponse::get_a_retravailler($sessionencours->get('id'))) {
                    $statutperso = 'areprendre';
                }
            }
        }

        $indisponibilite = null;
        if ($atelier->statut === 'indisponible') {
            $indisponibilite = indispo::get_en_cours($atelier->id);
        }

        $card = [
            'id' => $atelier->id,
            'numero' => s($atelier->numero),
            'nomcourt' => s($atelier->nomcourt),
            'discipline' => s($atelier->discipline),
            'espece' => s($atelier->espece),
            'dureeindicative' => (int) $atelier->dureeindicative,
            'salle' => s($atelier->salle),
            'zone' => s($atelier->zone),
            'statutatelier' => $atelier->statut,
            'estindisponible' => $atelier->statut === 'indisponible',
            'commentaireindispo' => $indisponibilite ? s($indisponibilite->commentaire) : '',
            'statutperso' => $statutperso,
            'statutpersolabel' => get_string('statutperso_' . $statutperso, 'local_simhub'),
            'estcommence' => $statutperso === 'commence',
            'sessionid' => $sessionencours ? $sessionencours->get('id') : 0,
            'urlfiche' => (new \moodle_url('/local/simhub/atelier.php', ['id' => $atelier->id]))->out(false),
            'urllocalisation' => (new \moodle_url('/local/simhub/atelier.php',
                ['id' => $atelier->id, 'onglet' => 'localisation']))->out(false),
            'urlressources' => (new \moodle_url('/local/simhub/atelier.php',
                ['id' => $atelier->id, 'onglet' => 'ressources']))->out(false),
            'urlcommencer' => (new \moodle_url('/local/simhub/session.php',
                ['atelierid' => $atelier->id, 'action' => 'demarrer', 'sesskey' => sesskey()]))->out(false),
            'urlterminer' => (new \moodle_url('/local/simhub/session.php',
                ['atelierid' => $atelier->id, 'action' => 'terminer',
                 'sessionid' => $sessionencours ? $sessionencours->get('id') : 0]))->out(false),
        ];

        return [$card, $statutperso];
    }
}
