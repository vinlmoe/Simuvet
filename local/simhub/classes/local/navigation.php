<?php
// Navigation interne de SimHub : fil d'Ariane, bouton retour et barre de sections.
//
// Toutes les pages du plugin passent par navigation::preparer() plutôt que d'appeler
// directement $PAGE->set_url()/set_title()/set_heading() : cela garantit qu'aucune page
// ne peut être atteinte sans que l'utilisateur sache où il se trouve (fil d'Ariane
// « Accueil / SimHub / Ateliers / … ») ni comment revenir en arrière (bouton retour
// calculé à partir du même fil, donc jamais désynchronisé du chemin réel).

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

/**
 * Helper de navigation transverse au plugin.
 */
class navigation {

    /** @var array Dernier fil d'Ariane passé à preparer(), utilisé par barre(). */
    protected static $ariane = [];

    /** @var \moodle_url|null URL de la page courante, utilisée pour surligner la section active. */
    protected static $urlcourante = null;

    /**
     * URL de l'accueil SimHub.
     *
     * @return \moodle_url
     */
    public static function url_accueil(): \moodle_url {
        return new \moodle_url('/local/simhub/index.php');
    }

    /**
     * Sections principales du plugin accessibles à l'utilisateur courant.
     *
     * Source unique de vérité : utilisée à la fois par la barre de navigation interne
     * (barre()) et par l'entrée SimHub du menu Moodle (local_simhub_extend_navigation()),
     * pour que les deux ne divergent jamais. Chaque page reste protégée indépendamment
     * par son propre require_capability() : cette liste n'est qu'un jeu de raccourcis.
     *
     * @param \context $context
     * @return array clé => ['libelle' => string, 'url' => \moodle_url]
     */
    public static function sections(\context $context): array {
        $sections = [];

        $ajouter = function (string $cle, string $libelle, string $chemin) use (&$sections) {
            $sections[$cle] = ['libelle' => $libelle, 'url' => new \moodle_url($chemin)];
        };

        $ajouter('accueil', get_string('nav_accueil', 'local_simhub'), '/local/simhub/index.php');

        if (has_capability('local/simhub:manageateliers', $context)) {
            $ajouter('ateliers', get_string('manage_ateliers', 'local_simhub'), '/local/simhub/manage/ateliers.php');
        }

        if (has_capability('local/simhub:manageparcours', $context)
                || has_capability('local/simhub:viewprogression', $context)) {
            $ajouter('parcours', get_string('filtre_parcours', 'local_simhub'), '/local/simhub/manage/parcours.php');
        }

        if (has_capability('local/simhub:viewprogression', $context)) {
            $ajouter('dashboard', get_string('dashboard_parcours', 'local_simhub'), '/local/simhub/manage/dashboard.php');
        }

        if (has_capability('local/simhub:manageateliers', $context)) {
            $ajouter('dashboardsalle', get_string('dashboard_salle', 'local_simhub'), '/local/simhub/manage/dashboard_salle.php');
        }

        if (has_capability('local/simhub:validatesession', $context)) {
            $ajouter('seancecode', get_string('seancecode_generer', 'local_simhub'), '/local/simhub/manage/seancecode_generer.php');
            $ajouter('sessionsavalider', get_string('sessions_a_valider', 'local_simhub'), '/local/simhub/manage/sessions_a_valider.php');
        }

        if (has_capability('local/simhub:view', $context)) {
            $ajouter('asv', get_string('asv_parcours', 'local_simhub'), '/local/simhub/asv/index.php');
        }

        if (has_capability('local/simhub:manageasv', $context)) {
            $ajouter('asvactes', get_string('asv_gerer_actes', 'local_simhub'), '/local/simhub/manage/asv_actes.php');
        }

        if (has_capability('local/simhub:importexport', $context)) {
            $ajouter('import', get_string('import_ateliers', 'local_simhub'), '/local/simhub/manage/import.php');
        }

        return $sections;
    }

    /**
     * Prépare une page du plugin : contexte, URL, mise en page, titre et fil d'Ariane.
     *
     * Le fil d'Ariane est toujours ancré sur l'accueil SimHub, puis suit les étapes
     * intermédiaires fournies, et se termine par la page courante (sans lien). Le bouton
     * retour rendu par barre() pointe automatiquement sur la dernière étape cliquable.
     *
     * @param \moodle_page $page Généralement $PAGE.
     * @param \moodle_url $url URL canonique de la page courante.
     * @param string $titre Titre affiché (déjà échappé si issu de données utilisateur).
     * @param array $etapes Étapes intermédiaires : [[libellé, \moodle_url], ...].
     * @param string $pagelayout Mise en page Moodle ; 'standard' conserve le tiroir de navigation.
     * @return void
     */
    public static function preparer(\moodle_page $page, \moodle_url $url, string $titre,
            array $etapes = [], string $pagelayout = 'standard'): void {
        $page->set_context(\context_system::instance());
        $page->set_url($url);
        $page->set_pagelayout($pagelayout);
        $page->set_title($titre . ' | ' . get_string('pluginname', 'local_simhub'));
        $page->set_heading($titre);

        self::$urlcourante = $url;
        self::$ariane = $etapes;

        $accueil = self::url_accueil();
        // L'accueil ne se met pas en lien vers lui-même.
        if ($url->out_omit_querystring() !== $accueil->out_omit_querystring()) {
            $page->navbar->add(get_string('pluginname', 'local_simhub'), $accueil);
        } else {
            $page->navbar->add(get_string('pluginname', 'local_simhub'));
        }

        foreach ($etapes as [$libelle, $etapeurl]) {
            $page->navbar->add($libelle, $etapeurl);
        }

        if (!empty($etapes) || $url->out_omit_querystring() !== $accueil->out_omit_querystring()) {
            $page->navbar->add($titre);
        }
    }

    /**
     * Barre de navigation interne à afficher juste après $OUTPUT->header().
     *
     * Contient le bouton retour (vers l'étape précédente du fil d'Ariane, ou l'accueil
     * SimHub à défaut) et les raccourcis vers les sections accessibles, la section
     * courante étant mise en évidence.
     *
     * @return string HTML
     */
    public static function barre(): string {
        $context = \context_system::instance();

        $retour = self::url_accueil();
        if (!empty(self::$ariane)) {
            [, $retour] = self::$ariane[count(self::$ariane) - 1];
        }

        $out = \html_writer::start_div('local-simhub-nav mb-3', [
            'style' => 'display:flex;flex-wrap:wrap;gap:8px;align-items:center;'
                . 'padding:8px 0;border-bottom:1px solid rgba(0,0,0,.1);',
        ]);

        $courante = self::$urlcourante ? self::$urlcourante->out_omit_querystring() : '';

        // Le bouton retour n'a de sens que si l'on n'est pas déjà sur sa cible.
        if ($retour->out_omit_querystring() !== $courante) {
            $out .= \html_writer::link($retour, '◂ ' . get_string('nav_retour', 'local_simhub'), [
                'class' => 'btn btn-secondary btn-sm',
            ]);
        }

        foreach (self::sections($context) as $section) {
            $actif = $section['url']->out_omit_querystring() === $courante;
            $out .= \html_writer::link($section['url'], $section['libelle'], [
                'class' => 'btn btn-sm ' . ($actif ? 'btn-primary' : 'btn-outline-secondary'),
                'aria-current' => $actif ? 'page' : null,
            ]);
        }

        $out .= \html_writer::end_div();

        return $out;
    }
}
