<?php

namespace local_simhub\output;

defined('MOODLE_INTERNAL') || die();

use local_simhub\local\atelier_filter;
use local_simhub\persistent\session;
use local_simhub\record\ae_reponse;
use local_simhub\record\indispo;
use renderable;
use templatable;

/**
 * Accueil étudiant personnalisé (§5) : filtres + carte atelier.
 *
 * Reste en V1 une page "tout en un" plutôt que les sept sections distinctes du §5.1
 * (à faire pour mes UC, mes parcours en cours, parcours ASV...) : celles-ci demandent de
 * connaître précisément les rattachements réels une fois importés (§12.1), et sont donc
 * volontairement reportées à une itération suivante pour ne pas figer un découpage qui
 * pourrait ne pas correspondre aux données des quatre ENV.
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
        global $CFG;

        $ateliers = $this->filter->get_ateliers();
        $sessions = session::get_pour_etudiant($this->userid);

        // Dernière session par atelier, pour dériver le statut personnel affiché sur la carte (§5.3).
        $dernieresessionparatelier = [];
        foreach ($sessions as $s) {
            $aid = $s->get('atelierid');
            if (!isset($dernieresessionparatelier[$aid])) {
                $dernieresessionparatelier[$aid] = $s;
            }
        }

        $cards = [];
        foreach ($ateliers as $atelier) {
            $statutperso = 'pascommence';
            $sessionencours = null;
            $areprendre = false;

            if (isset($dernieresessionparatelier[$atelier->id])) {
                $s = $dernieresessionparatelier[$atelier->id];
                $sessionencours = $s;
                if ($s->get('statut') === session::STATUT_COMMENCE) {
                    $statutperso = 'commence';
                } else if ($s->get('statut') === session::STATUT_CERTIFIE) {
                    $statutperso = 'valide';
                } else {
                    $statutperso = 'realise';
                    if (ae_reponse::get_a_retravailler($s->get('id'))) {
                        $areprendre = true;
                        $statutperso = 'areprendre';
                    }
                }
            }

            $indisponibilite = null;
            if ($atelier->statut === 'indisponible') {
                $indisponibilite = indispo::get_en_cours($atelier->id);
            }

            $cards[] = [
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
                    ['atelierid' => $atelier->id, 'action' => 'demarrer']))->out(false),
                'urlterminer' => (new \moodle_url('/local/simhub/session.php',
                    ['atelierid' => $atelier->id, 'action' => 'terminer',
                     'sessionid' => $sessionencours ? $sessionencours->get('id') : 0]))->out(false),
            ];
        }

        return [
            'title' => get_string('simhub:studenthome', 'local_simhub'),
            'ateliers' => array_values($cards),
            'hasateliers' => !empty($cards),
            'nomessage' => get_string('aucun_atelier', 'local_simhub'),
            'formurl' => (new \moodle_url('/local/simhub/index.php'))->out(false),
            'filtre' => [
                'motcle' => s($this->filter->motcle),
                'discipline' => s($this->filter->discipline),
                'espece' => s($this->filter->espece),
                'niveaudifficulte' => s($this->filter->niveaudifficulte),
                'dureemax' => $this->filter->dureemax ?: '',
            ],
            'strings' => [
                'filtremotcle' => get_string('filtre_motcle', 'local_simhub'),
                'filtrediscipline' => get_string('filtre_discipline', 'local_simhub'),
                'filtreespece' => get_string('filtre_espece', 'local_simhub'),
                'filtreniveau' => get_string('filtre_niveau', 'local_simhub'),
                'filtreduree' => get_string('filtre_duree', 'local_simhub'),
                'filtreappliquer' => get_string('filtre_appliquer', 'local_simhub'),
                'filtrereinitialiser' => get_string('filtre_reinitialiser', 'local_simhub'),
                'boutonlocalisation' => get_string('bouton_localisation', 'local_simhub'),
                'boutonressources' => get_string('bouton_ressources', 'local_simhub'),
                'boutoncommencer' => get_string('bouton_commencer', 'local_simhub'),
                'boutonterminer' => get_string('bouton_terminer', 'local_simhub'),
            ],
        ];
    }
}
