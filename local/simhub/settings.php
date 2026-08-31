<?php
// Page de réglages "Administration fonctionnelle" (§11, profil
// Administrateur fonctionnel). Regroupe les paramètres transverses,
// paramétrables par établissement (principe "Paramétrable ENVF", §4).

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

    // TODO : ajouter ici les référentiels paramétrables par école
    // (disciplines, espèces, salles/zones) plutôt qu'en dur dans le
    // code, conformément au principe "Paramétrable ENVF" (§4). Une UI
    // de gestion de référentiels (classes/local/referentiel_manager.php)
    // est prévue mais non développée dans ce squelette.
}
