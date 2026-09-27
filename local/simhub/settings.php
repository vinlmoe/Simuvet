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
 * Page de réglages "Administration fonctionnelle" (§11, profil
 * Administrateur fonctionnel). Regroupe les paramètres transverses,
 * propres à l'école : chaque école a son propre Moodle (§4).
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    $settings = new admin_settingpage('local_simhub', get_string('pluginname', 'local_simhub'));
    $ADMIN->add('localplugins', $settings);

    $settings->add(new admin_setting_configtext(
        'local_simhub/envcode',
        get_string('setting_envcode', 'local_simhub'),
        get_string('setting_envcode_desc', 'local_simhub'),
        '',
        PARAM_ALPHANUMEXT
    ));

    $categories = [0 => get_string('setting_categoryid_systeme', 'local_simhub')]
        + core_course_category::make_categories_list();
    $settings->add(new admin_setting_configselect(
        'local_simhub/categoryid',
        get_string('setting_categoryid', 'local_simhub'),
        get_string('setting_categoryid_desc', 'local_simhub'),
        0,
        $categories
    ));

    $settings->add(new admin_setting_configduration(
        'local_simhub/seancecodeduration',
        get_string('setting_seancecodeduration', 'local_simhub'),
        get_string('setting_seancecodeduration_desc', 'local_simhub'),
        3600
    ));

    $settings->add(new admin_setting_configduration(
        'local_simhub/asvtokenexpiry',
        get_string('setting_asvtokenexpiry', 'local_simhub'),
        get_string('setting_asvtokenexpiry_desc', 'local_simhub'),
        7 * DAYSECS
    ));

    $settings->add(new admin_setting_configcheckbox(
        'local_simhub/controlepresenceactif',
        get_string('setting_controlepresenceactif', 'local_simhub'),
        get_string('setting_controlepresenceactif_desc', 'local_simhub'),
        0
    ));

    $settings->add(new admin_setting_configtextarea(
        'local_simhub/reseauxsalle',
        get_string('setting_reseauxsalle', 'local_simhub'),
        get_string('setting_reseauxsalle_desc', 'local_simhub'),
        '',
        PARAM_RAW_TRIMMED
    ));

    $settings->add(new admin_setting_configtext(
        'local_simhub/dureeminpct',
        get_string('setting_dureeminpct', 'local_simhub'),
        get_string('setting_dureeminpct_desc', 'local_simhub'),
        0,
        PARAM_INT
    ));

    $settings->add(new admin_setting_configtext(
        'local_simhub/etablissementnom',
        get_string('setting_etablissementnom', 'local_simhub'),
        get_string('setting_etablissementnom_desc', 'local_simhub'),
        ''
    ));

    // Logo de l'établissement (§4 "Paramétrable ENVF") : utilisé en en-tête des documents
    // PDF (attestations, livret ASV, fiches ateliers) plutôt qu'un logo générique SimHub.
    $settings->add(new admin_setting_configstoredfile(
        'local_simhub/logo',
        get_string('setting_logo', 'local_simhub'),
        get_string('setting_logo_desc', 'local_simhub'),
        'logo',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.png', '.jpg', '.jpeg', '.svg']]
    ));

    // Badges Moodle délivrés à la certification globale d'un niveau ASV (§9.4, §13) : un
    // badge de site existant par niveau, choisi dans une liste réelle plutôt que saisi par
    // id. La délivrance à la fin d'un parcours se configure parcours par parcours, dans
    // classes/form/parcours_form.php.
    $badgeoptions = \local_simhub\local\badge_helper::get_options();
    foreach (['a1' => 'A1', 'a2' => 'A2', 'a3' => 'A3'] as $key => $label) {
        $settings->add(new admin_setting_configselect(
            'local_simhub/badgeasv' . $key,
            get_string('setting_badgeasv' . $key, 'local_simhub'),
            get_string('setting_badgeasv' . $key . '_desc', 'local_simhub'),
            '',
            $badgeoptions
        ));
    }

    // Une évolution est prévue : référentiels paramétrables par école (disciplines, espèces,
    // salles/zones) plutôt que saisis librement (§4 « Paramétrable ENVF »).
}
