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
 * Navigation interne de SimHub : fil d'Ariane, bouton retour, menus par domaine et onglets
 * des fiches atelier / parcours.
 *
 * Toutes les pages du plugin passent par navigation::preparer() plutôt que d'appeler
 * directement $PAGE->set_url()/set_title()/set_heading() : cela garantit qu'aucune page
 * ne peut être atteinte sans que l'utilisateur sache où il se trouve (fil d'Ariane
 * « Accueil / SimHub / Ateliers / … ») ni comment revenir en arrière (bouton retour
 * calculé à partir du même fil, donc jamais désynchronisé du chemin réel).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Helper de navigation transverse au plugin.
 */
class navigation {
    /** @var array Dernier fil d'Ariane passé à preparer(), utilisé par barre(). */
    protected static $ariane = [];

    /** @var \moodle_url|null URL de la page courante, utilisée pour surligner la section active. */
    protected static $urlcourante = null;

    /** @var array|null Onglets à afficher sous la barre : [type, id, clé active]. */
    protected static $onglets = null;

    /**
     * URL de l'accueil SimHub.
     *
     * @return \moodle_url
     */
    public static function url_accueil(): \moodle_url {
        return new \moodle_url('/local/simhub/index.php');
    }

    /**
     * Sections du plugin accessibles à l'utilisateur courant, regroupées par domaine.
     *
     * Source unique de vérité : utilisée à la fois par la barre de navigation interne
     * (barre()) et par l'entrée SimHub du menu Moodle (local_simhub_extend_navigation()),
     * pour que les deux ne divergent jamais. Chaque page reste protégée indépendamment
     * par son propre require_capability() : cette liste n'est qu'un jeu de raccourcis.
     *
     * 'motifs' liste les chemins de pages rattachées à la section (sous-pages d'un atelier,
     * d'un parcours...), pour que le menu reste surligné sur toute la rubrique.
     *
     * @param \context $context
     * @return array clé => ['libelle', 'url', 'groupe', 'motifs', 'compteur']
     */
    public static function sections(\context $context): array {
        $sections = [];

        $ajouter = function (
            string $cle,
            string $groupe,
            string $libelle,
            string $chemin,
            array $motifs = [],
            int $compteur = 0
        ) use (&$sections) {
            $sections[$cle] = [
                'libelle' => $libelle,
                'url' => new \moodle_url($chemin),
                'groupe' => $groupe,
                'motifs' => $motifs ?: [$chemin],
                'compteur' => $compteur,
            ];
        };
        $str = function (string $cle): string {
            return get_string($cle, 'local_simhub');
        };

        $ajouter(
            'accueil',
            'accueil',
            $str('nav_accueil'),
            '/local/simhub/index.php',
            ['/local/simhub/index.php', '/local/simhub/atelier.php', '/local/simhub/session', '/local/simhub/qr.php']
        );

        if (has_capability('local/simhub:manageateliers', $context)) {
            $ajouter('ateliers', 'ateliers', $str('manage_ateliers'), '/local/simhub/manage/ateliers.php', [
                '/local/simhub/manage/atelier', '/local/simhub/manage/ressource', '/local/simhub/manage/ae_',
                '/local/simhub/manage/rattachements.php',
            ]);
            $ajouter('dashboardsalle', 'ateliers', $str('dashboard_salle'), '/local/simhub/manage/dashboard_salle.php');
        }
        if (has_capability('local/simhub:importexport', $context)) {
            $ajouter('import', 'ateliers', $str('import_ateliers'), '/local/simhub/manage/import.php');
        }

        if (
            has_capability('local/simhub:manageparcours', $context)
                || has_capability('local/simhub:viewprogression', $context)
        ) {
            $ajouter(
                'parcours',
                'parcours',
                $str('filtre_parcours'),
                '/local/simhub/manage/parcours.php',
                ['/local/simhub/manage/parcours']
            );
        }
        if (has_capability('local/simhub:viewprogression', $context)) {
            $ajouter('dashboard', 'parcours', $str('dashboard_parcours'), '/local/simhub/manage/dashboard.php');
        }

        if (has_capability('local/simhub:validatesession', $context)) {
            $ajouter('seancecode', 'seances', $str('seancecode_generer'), '/local/simhub/manage/seancecode_generer.php');
            $ajouter(
                'sessionsavalider',
                'seances',
                $str('sessions_a_valider'),
                '/local/simhub/manage/sessions_a_valider.php',
                [],
                self::nb_sessions_a_valider()
            );
        }

        if (has_capability('local/simhub:view', $context)) {
            $ajouter(
                'asv',
                'asv',
                $str('asv_parcours'),
                '/local/simhub/asv/index.php',
                [
                    '/local/simhub/asv/index.php',
                    '/local/simhub/asv/demander',
                    '/local/simhub/asv/livret',
                    '/local/simhub/asv/attestation',
                ]
            );
        }
        if (droits::peut_valider_asv()) {
            $ajouter('asvvalider', 'asv', $str('asv_valider_simulation'), '/local/simhub/asv/valider_simulation.php');
        }
        if (has_capability('local/simhub:manageasv', $context)) {
            $ajouter(
                'asvactes',
                'asv',
                $str('asv_gerer_actes'),
                '/local/simhub/manage/asv_actes.php',
                ['/local/simhub/manage/asv_acte']
            );
            $ajouter('asvattestations', 'asv', $str('asv_attestations_groupees'), '/local/simhub/manage/asv_attestations.php');
        }

        if (has_capability('moodle/role:assign', $context)) {
            $ajouter(
                'roles',
                'roles',
                $str('nav_roles'),
                '/admin/roles/assign.php?contextid=' . $context->id,
                ['/admin/roles/assign.php']
            );
        }

        return $sections;
    }

    /**
     * Nombre de séances non vérifiées en attente d'une décision d'encadrant (§7.3).
     *
     * @return int
     */
    protected static function nb_sessions_a_valider(): int {
        global $DB;

        return $DB->count_records_sql(
            "SELECT COUNT(1)
               FROM {local_simhub_session} s
              WHERE (s.controlepresence = 'non_verifie' OR s.dureesuspecte = 1)
                AND NOT EXISTS (SELECT 1 FROM {local_simhub_val_encadrant} v WHERE v.sessionid = s.id)"
        );
    }

    /**
     * Onglets de gestion d'un atelier, filtrés selon les capacités de l'utilisateur.
     *
     * Réutilisés par les onglets affichés sur chaque sous-page et par le menu « Gérer »
     * de la liste des ateliers.
     *
     * @param int $atelierid
     * @return array clé => ['libelle' => string, 'url' => \moodle_url]
     */
    public static function liens_atelier(int $atelierid): array {
        $context = contexte::racine();
        $liens = [];
        $ajouter = function (string $cle, string $libelle, string $chemin, array $params, ?string $cap = null)
 use (&$liens, $context) {
            if ($cap === null || has_capability($cap, $context)) {
                $liens[$cle] = ['libelle' => $libelle, 'url' => new \moodle_url($chemin, $params)];
            }
        };
        $str = function (string $cle): string {
            return get_string($cle, 'local_simhub');
        };

        $ajouter('fiche', $str('onglet_fiche'), '/local/simhub/manage/atelier_edit.php', ['id' => $atelierid]);
        $ajouter(
            'ressources',
            $str('nav_ressources'),
            '/local/simhub/manage/ressources.php',
            ['atelierid' => $atelierid],
            'local/simhub:manageressources'
        );
        $ajouter('ae', $str('ae_modele'), '/local/simhub/manage/ae_modele_edit.php', ['atelierid' => $atelierid]);
        $ajouter(
            'stats',
            $str('stats_lien'),
            '/local/simhub/manage/ae_stats.php',
            ['atelierid' => $atelierid],
            'local/simhub:viewprogression'
        );
        $ajouter(
            'rattachements',
            $str('rattachements'),
            '/local/simhub/manage/rattachements.php',
            ['atelierid' => $atelierid],
            'local/simhub:managerattachement'
        );
        $ajouter('plan', $str('nav_plan'), '/local/simhub/manage/atelier_plan.php', ['id' => $atelierid]);
        $ajouter(
            'qr',
            $str('nav_qrcode'),
            '/local/simhub/manage/atelier_qr.php',
            ['id' => $atelierid],
            'local/simhub:manageqrcodes'
        );
        $ajouter(
            'asv',
            $str('asv_valider_simulation'),
            '/local/simhub/asv/valider_simulation.php',
            ['atelierid' => $atelierid],
            'local/simhub:validateasvsimulation'
        );
        $ajouter('vueetudiant', $str('onglet_vue_etudiant'), '/local/simhub/atelier.php', ['id' => $atelierid]);
        $ajouter('pdf', $str('onglet_pdf'), '/local/simhub/manage/atelier_fiche_pdf.php', ['id' => $atelierid]);

        return $liens;
    }

    /**
     * Onglets de gestion d'un parcours.
     *
     * @param int $parcoursid
     * @return array clé => ['libelle' => string, 'url' => \moodle_url]
     */
    public static function liens_parcours(int $parcoursid): array {
        $context = contexte::racine();
        $parcours = new \local_simhub\persistent\parcours($parcoursid);
        $liens = [];
        if (has_capability('local/simhub:manageparcours', $context)) {
            $liens['fiche'] = ['libelle' => get_string('onglet_fiche', 'local_simhub'),
                'url' => new \moodle_url('/local/simhub/manage/parcours_edit.php', ['id' => $parcoursid])];
        }
        if (droits::peut_gerer_parcours($parcours)) {
            $liens['ateliers'] = ['libelle' => get_string('onglet_composition', 'local_simhub'),
                'url' => new \moodle_url('/local/simhub/manage/parcours_ateliers.php', ['parcoursid' => $parcoursid])];
        }
        $liens['suivi'] = ['libelle' => get_string('onglet_suivi', 'local_simhub'),
            'url' => new \moodle_url('/local/simhub/manage/parcours_suivi.php', ['parcoursid' => $parcoursid])];
        if (has_capability('local/simhub:exportsuivi', $context) || droits::peut_suivre_parcours($parcours)) {
            $liens['export'] = ['libelle' => get_string('onglet_export_csv', 'local_simhub'),
                'url' => new \moodle_url('/local/simhub/manage/export.php', ['type' => 'parcours', 'parcoursid' => $parcoursid])];
        }
        return $liens;
    }

    /**
     * Menu déroulant « Gérer » pour une ligne de tableau (liste des ateliers, des parcours).
     *
     * @param array $liens clé => ['libelle' => string, 'url' => \moodle_url]
     * @return string HTML
     */
    public static function menu_actions(array $liens): string {
        $items = '';
        foreach ($liens as $lien) {
            $items .= \html_writer::link($lien['url'], s($lien['libelle']), ['class' => 'dropdown-item']);
        }
        return \html_writer::div(
            \html_writer::tag('button', get_string('nav_gerer', 'local_simhub'), [
                'type' => 'button',
                'class' => 'btn btn-sm btn-outline-secondary dropdown-toggle',
                'data-toggle' => 'dropdown',
                'data-bs-toggle' => 'dropdown',
                'aria-haspopup' => 'true',
                'aria-expanded' => 'false',
            ]) . \html_writer::div($items, 'dropdown-menu'),
            'dropdown'
        );
    }

    /**
     * Déclare les onglets à afficher sous la barre de navigation de la page courante.
     *
     * @param string $type 'atelier' ou 'parcours'.
     * @param int $id Identifiant de l'atelier ou du parcours.
     * @param string $actif Clé de l'onglet courant (voir liens_atelier()/liens_parcours()).
     * @return void
     */
    public static function onglets(string $type, int $id, string $actif): void {
        self::$onglets = [$type, $id, $actif];
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
    public static function preparer(
        \moodle_page $page,
        \moodle_url $url,
        string $titre,
        array $etapes = [],
        string $pagelayout = 'standard'
    ): void {
        $page->set_context(\context_system::instance());
        $page->set_url($url);
        $page->set_pagelayout($pagelayout);
        $page->set_title($titre . ' | ' . get_string('pluginname', 'local_simhub'));
        $page->set_heading($titre);

        self::$urlcourante = $url;
        self::$ariane = $etapes;
        self::$onglets = null;

        // Le nœud SimHub ajouté au menu Moodle (lib.php) rendrait sinon le chemin deux fois :
        // une fois déduit de la navigation, une fois via les étapes ci-dessous.
        $page->navbar->ignore_active();

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
     * Indique si la page courante relève d'une section (URL exacte ou motif de rubrique).
     *
     * @param array $section
     * @param string $chemin Chemin de la page courante, sans hôte ni paramètres.
     * @return bool
     */
    protected static function est_active(array $section, string $chemin): bool {
        foreach ($section['motifs'] as $motif) {
            if (strpos($chemin, $motif) === 0) {
                return true;
            }
        }
        return false;
    }

    /**
     * Barre de navigation interne à afficher juste après $OUTPUT->header().
     *
     * Contient le bouton retour (vers l'étape précédente du fil d'Ariane, ou l'accueil
     * SimHub à défaut), un menu par domaine (Ateliers, Parcours, Séances, ASV) regroupant
     * les sections accessibles, la rubrique courante étant mise en évidence, puis les
     * onglets de l'atelier ou du parcours en cours si la page en a déclaré.
     *
     * @return string HTML
     */
    public static function barre(): string {
        $context = contexte::racine();

        $retour = self::url_accueil();
        if (!empty(self::$ariane)) {
            [, $retour] = self::$ariane[count(self::$ariane) - 1];
        }

        $courante = self::$urlcourante ? self::$urlcourante->out_omit_querystring() : '';
        $chemin = self::$urlcourante ? self::$urlcourante->get_path(false) : '';

        $out = \html_writer::start_tag('nav', [
            'class' => 'local-simhub-nav mb-3',
            'aria-label' => get_string('pluginname', 'local_simhub'),
        ]);

        // Le bouton retour n'a de sens que si l'on n'est pas déjà sur sa cible.
        if ($retour->out_omit_querystring() !== $courante) {
            $out .= \html_writer::link($retour, '◂ ' . get_string('nav_retour', 'local_simhub'), [
                'class' => 'btn btn-secondary btn-sm',
            ]);
        }

        $groupes = [];
        foreach (self::sections($context) as $cle => $section) {
            $groupes[$section['groupe']][$cle] = $section;
        }

        foreach ($groupes as $groupe => $sections) {
            $actif = false;
            foreach ($sections as $section) {
                $actif = $actif || self::est_active($section, $chemin);
            }
            $classe = 'btn btn-sm ' . ($actif ? 'btn-primary' : 'btn-outline-secondary');

            // Un domaine à une seule entrée reste un simple bouton : un menu n'apporterait rien.
            if (count($sections) === 1) {
                $section = reset($sections);
                $out .= \html_writer::link($section['url'], self::libelle($section), [
                    'class' => $classe,
                    'aria-current' => $actif ? 'page' : null,
                ]);
                continue;
            }

            $total = array_sum(array_column($sections, 'compteur'));
            $titre = get_string('nav_groupe_' . $groupe, 'local_simhub')
                . ($total ? ' ' . self::pastille($total) : '');

            $items = '';
            foreach ($sections as $section) {
                $itemactif = self::est_active($section, $chemin);
                $items .= \html_writer::link($section['url'], self::libelle($section), [
                    'class' => 'dropdown-item' . ($itemactif ? ' active' : ''),
                    'aria-current' => $itemactif ? 'page' : null,
                ]);
            }

            $out .= \html_writer::div(
                \html_writer::tag('button', $titre, [
                    'type' => 'button',
                    'class' => $classe . ' dropdown-toggle',
                    // Bootstrap 4 (Moodle 4.x) et Bootstrap 5 (Moodle 5.x).
                    'data-toggle' => 'dropdown',
                    'data-bs-toggle' => 'dropdown',
                    'aria-haspopup' => 'true',
                    'aria-expanded' => 'false',
                ]) . \html_writer::div($items, 'dropdown-menu'),
                'dropdown'
            );
        }

        $out .= \html_writer::end_tag('nav');

        $out .= self::rendre_onglets();

        return $out;
    }

    /**
     * Libellé d'une section, avec sa pastille de compteur éventuelle.
     *
     * @param array $section
     * @return string HTML
     */
    protected static function libelle(array $section): string {
        return s($section['libelle']) . ($section['compteur'] ? ' ' . self::pastille($section['compteur']) : '');
    }

    /**
     * Pastille numérique (éléments en attente).
     *
     * @param int $nombre
     * @return string HTML
     */
    protected static function pastille(int $nombre): string {
        return \html_writer::span($nombre, 'badge badge-danger bg-danger text-white rounded-pill');
    }

    /**
     * Onglets de l'atelier ou du parcours déclarés par la page via onglets().
     *
     * @return string HTML
     */
    protected static function rendre_onglets(): string {
        global $OUTPUT;

        if (self::$onglets === null) {
            return '';
        }
        [$type, $id, $actif] = self::$onglets;
        $liens = $type === 'atelier' ? self::liens_atelier($id) : self::liens_parcours($id);

        $tabs = [];
        foreach ($liens as $cle => $lien) {
            $tabs[] = new \tabobject($cle, $lien['url'], $lien['libelle']);
        }
        return $OUTPUT->tabtree($tabs, $actif);
    }
}
