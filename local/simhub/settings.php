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

    $settings->add(new admin_setting_configtext(
        'local_simhub/etablissementnom',
        get_string('setting_etablissementnom', 'local_simhub'),
        get_string('setting_etablissementnom_desc', 'local_simhub'),
        ''
    ));

    // Année d'étude déduite des groupes Moodle (§5.1 "recommandés pour mon année") : chaque
    // école nomme ses groupes différemment, d'où une expression régulière paramétrable
    // plutôt qu'une convention imposée. Voir classes/local/annee_resolver.php.
    $settings->add(new admin_setting_configtext(
        'local_simhub/groupeanneeregex',
        get_string('setting_groupeanneeregex', 'local_simhub'),
        get_string('setting_groupeanneeregex_desc', 'local_simhub'),
        \local_simhub\local\annee_resolver::DEFAULT_PATTERN
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

    // TODO : ajouter ici les référentiels paramétrables par école
    // (disciplines, espèces, salles/zones) plutôt qu'en dur dans le
    // code, conformément au principe "Paramétrable ENVF" (§4). Une UI
    // de gestion de référentiels (classes/local/referentiel_manager.php)
    // est prévue mais non développée dans ce squelette.
}
