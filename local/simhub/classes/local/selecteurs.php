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
 * Listes déroulantes avec recherche (cours, étudiants, ateliers) pour les formulaires écrits
 * à la main, à la place de la saisie d'identifiants numériques Moodle.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_simhub\local;

/**
 * Sélecteurs réutilisables.
 */
class selecteurs {
    /**
     * Liste déroulante des cours (UC) du site.
     *
     * @param string $name
     * @param int $selection
     * @param string $id Attribut id HTML.
     * @return string HTML
     */
    public static function cours(string $name, int $selection, string $id): string {
        return self::select(self::options_cours(), $name, $selection, $id);
    }

    /**
     * Cours du site (UC), pour une liste de choix.
     *
     * @return array id => nom, précédé de « aucune UC » (0).
     */
    public static function options_cours(): array {
        global $DB;

        $options = [0 => get_string('aucune_uc', 'local_simhub')];
        $cours = $DB->get_records_select_menu('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname', 'id, fullname');
        foreach ($cours as $courseid => $fullname) {
            $options[$courseid] = format_string($fullname);
        }
        return $options;
    }

    /**
     * Liste déroulante des utilisateurs actifs, triés par nom.
     *
     * @param string $name
     * @param int $selection
     * @param string $id Attribut id HTML.
     * @param callable|null $filtre fn(int $userid): bool, pour ne proposer qu'une partie des utilisateurs.
     * @return string HTML
     */
    public static function etudiants(string $name, int $selection, string $id, ?callable $filtre = null): string {
        return self::select(self::options_etudiants($filtre), $name, $selection, $id, true);
    }

    /**
     * Utilisateurs actifs, pour une liste de choix.
     *
     * @param callable|null $filtre fn(int $userid): bool
     * @return array id => « Nom (identifiant) », précédé d'un choix vide.
     */
    public static function options_etudiants(?callable $filtre = null): array {
        global $DB, $CFG;

        $options = ['' => get_string('choisir_etudiant', 'local_simhub')];
        $users = $DB->get_records_select(
            'user',
            'deleted = 0 AND suspended = 0 AND confirmed = 1 AND id <> :guest',
            ['guest' => $CFG->siteguest],
            'lastname, firstname',
            'id, username, email, ' . implode(', ', \core_user\fields::get_name_fields())
        );
        foreach ($users as $user) {
            if ($filtre && !$filtre((int) $user->id)) {
                continue;
            }
            $options[$user->id] = fullname($user) . ' (' . $user->username . ')';
        }
        return $options;
    }

    /**
     * Étudiants inscrits (actifs) à un cours, sans ses enseignants : même règle que le
     * suivi d'une UC, qui écarte les titulaires de mod/simhub:viewprogression.
     *
     * @param int $courseid
     * @return array userid => true
     */
    public static function etudiants_du_cours(int $courseid): array {
        $context = \context_course::instance($courseid);
        $inscrits = array_map('intval', array_keys(get_enrolled_users($context, '', 0, 'u.id', null, 0, 0, true)));
        return array_fill_keys(
            array_filter($inscrits, fn($uid) => !has_capability('mod/simhub:viewprogression', $context, $uid)),
            true
        );
    }

    /**
     * Liste déroulante des ateliers de l'établissement.
     *
     * @param string $name
     * @param int $selection
     * @param string $id Attribut id HTML.
     * @return string HTML
     */
    public static function ateliers(string $name, int $selection, string $id): string {
        return self::select(self::options_ateliers(), $name, $selection, $id);
    }

    /**
     * Ateliers, pour une liste de choix.
     *
     * @return array id => « numéro — nom », précédé de « aucun » (0).
     */
    public static function options_ateliers(): array {
        $options = [0 => get_string('atelier_aucun_choix', 'local_simhub')];
        foreach (\local_simhub\persistent\atelier::get_records([], 'numero') as $atelier) {
            $options[$atelier->get('id')] = $atelier->get('numero') . ' — ' . $atelier->get('nomcourt');
        }
        return $options;
    }

    /**
     * Rend la liste et l'améliore en champ de recherche (core/form-autocomplete).
     *
     * @param array $options
     * @param string $name
     * @param int $selection
     * @param string $id
     * @param bool $requis
     * @return string HTML
     */
    protected static function select(array $options, string $name, int $selection, string $id, bool $requis = false): string {
        global $PAGE;

        $attrs = ['id' => $id, 'class' => 'form-control'];
        if ($requis) {
            $attrs['required'] = 'required';
        }
        $PAGE->requires->js_call_amd(
            'core/form-autocomplete',
            'enhance',
            ['#' . $id, false, false, get_string('search'), false, true, get_string('noselection', 'form')]
        );

        return \html_writer::select($options, $name, $selection, false, $attrs);
    }
}
