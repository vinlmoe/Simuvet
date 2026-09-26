<?php
// Listes déroulantes avec recherche (cours, étudiants, ateliers) pour les formulaires écrits
// à la main, à la place de la saisie d'identifiants numériques Moodle.

namespace local_simhub\local;

defined('MOODLE_INTERNAL') || die();

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
        global $DB;

        $options = [0 => get_string('aucune_uc', 'local_simhub')];
        $cours = $DB->get_records_select_menu('course', 'id <> :siteid', ['siteid' => SITEID], 'fullname', 'id, fullname');
        foreach ($cours as $courseid => $fullname) {
            $options[$courseid] = format_string($fullname);
        }
        return self::select($options, $name, $selection, $id);
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
        return self::select($options, $name, $selection, $id, true);
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
        $options = [0 => get_string('atelier_aucun_choix', 'local_simhub')];
        foreach (\local_simhub\persistent\atelier::get_records([], 'numero') as $atelier) {
            $options[$atelier->get('id')] = $atelier->get('numero') . ' — ' . $atelier->get('nomcourt');
        }
        return self::select($options, $name, $selection, $id);
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
        $PAGE->requires->js_call_amd('core/form-autocomplete', 'enhance',
            ['#' . $id, false, false, get_string('search'), false, true, get_string('noselection', 'form')]);

        return \html_writer::select($options, $name, $selection, false, $attrs);
    }
}
