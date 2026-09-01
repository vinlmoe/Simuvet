<?php
// Fonctions de callback Moodle pour l'intégration de SimHub : navigation et service de
// fichiers. La logique métier vit dans classes/local/, classes/persistent/ et
// classes/record/.

defined('MOODLE_INTERNAL') || die();

/**
 * Ajoute SimHub à la navigation pour les utilisateurs disposant de la capacité
 * local/simhub:view, avec des sous-entrées conditionnées à la capacité de gestion
 * correspondante (§11 : chaque profil ne voit que les pages qui le concernent). Chaque
 * page reste de toute façon protégée indépendamment par son propre require_capability() —
 * ce menu n'est qu'un raccourci, jamais le seul contrôle d'accès.
 *
 * showinflatnavigation est activé sur chaque nœud pour que le lien apparaisse dans la
 * navigation "primaire" de Moodle 4 (barre du haut / menu déroulant), et pas seulement
 * dans le tiroir de navigation latéral où un nœud ajouté via extend_navigation() peut
 * facilement passer inaperçu selon le thème.
 */
function local_simhub_extend_navigation(global_navigation $nav) {
    $context = context_system::instance();
    if (!has_capability('local/simhub:view', $context)) {
        return;
    }

    $node = navigation_node::create(
        get_string('pluginname', 'local_simhub'),
        new moodle_url('/local/simhub/index.php'),
        navigation_node::TYPE_CUSTOM,
        null,
        'local_simhub',
        new pix_icon('i/report', '')
    );
    $node->showinflatnavigation = true;

    $entrees = [];

    if (has_capability('local/simhub:manageateliers', $context)) {
        $entrees['local_simhub_manage'] = [
            get_string('manage_ateliers', 'local_simhub'),
            '/local/simhub/manage/ateliers.php',
        ];
    }

    if (has_capability('local/simhub:manageparcours', $context) || has_capability('local/simhub:viewprogression', $context)) {
        $entrees['local_simhub_parcours'] = [
            get_string('filtre_parcours', 'local_simhub'),
            '/local/simhub/manage/parcours.php',
        ];
    }

    if (has_capability('local/simhub:viewprogression', $context)) {
        $entrees['local_simhub_dashboard'] = [
            get_string('dashboard_parcours', 'local_simhub'),
            '/local/simhub/manage/dashboard.php',
        ];
    }

    if (has_capability('local/simhub:manageateliers', $context)) {
        $entrees['local_simhub_dashboardsalle'] = [
            get_string('dashboard_salle', 'local_simhub'),
            '/local/simhub/manage/dashboard_salle.php',
        ];
    }

    if (has_capability('local/simhub:importexport', $context)) {
        $entrees['local_simhub_import'] = [
            get_string('import_ateliers', 'local_simhub'),
            '/local/simhub/manage/import.php',
        ];
    }

    if (has_capability('local/simhub:validatesession', $context)) {
        $entrees['local_simhub_seancecode'] = [
            get_string('seancecode_generer', 'local_simhub'),
            '/local/simhub/manage/seancecode_generer.php',
        ];
        $entrees['local_simhub_sessionsavalider'] = [
            get_string('sessions_a_valider', 'local_simhub'),
            '/local/simhub/manage/sessions_a_valider.php',
        ];
    }

    if (has_capability('local/simhub:manageasv', $context) || has_capability('local/simhub:validateasvsimulation', $context)) {
        $entrees['local_simhub_asv'] = [
            get_string('asv_parcours', 'local_simhub'),
            '/local/simhub/asv/index.php',
        ];
    }

    foreach ($entrees as $key => [$label, $url]) {
        $enfant = $node->add($label, new moodle_url($url), navigation_node::TYPE_CUSTOM, null, $key);
        $enfant->showinflatnavigation = true;
    }

    $nav->add_node($node);
}

/**
 * Sert les fichiers stockés via l'API filestorage de Moodle pour les
 * ressources d'atelier (PDF, vidéos, plans de salle...).
 *
 * Zones de fichiers prévues :
 *   - 'ressource'  : fichiers attachés à local_simhub_ressource.fileitemid, avec contrôle
 *                    de visibilité (une source éditable n'est jamais servie à un
 *                    utilisateur sans local/simhub:manageressources, §6.2)
 *   - 'plan'       : image de plan de salle, local_simhub_atelier.planimageitemid
 */
function local_simhub_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, array $options = []) {
    global $DB;

    if ($context->contextlevel != CONTEXT_SYSTEM) {
        return false;
    }

    require_login();

    $allowedareas = ['ressource', 'plan'];
    if (!in_array($filearea, $allowedareas, true)) {
        return false;
    }

    $itemid = (int) reset($args);

    if ($filearea === 'ressource') {
        $ressource = $DB->get_record('local_simhub_ressource', ['fileitemid' => $itemid]);
        // Une source éditable (§6.2) n'est jamais servie à un utilisateur qui n'a pas la
        // capacité de gérer les ressources, quel que soit son droit de consultation générale.
        if ($ressource
                && $ressource->visibilite === \local_simhub\persistent\ressource::VISIBILITE_INTERNE
                && !has_capability('local/simhub:manageressources', $context)) {
            return false;
        }
    }

    $itemid = array_shift($args);
    $filename = array_pop($args);
    $filepath = $args ? '/' . implode('/', $args) . '/' : '/';

    $fs = get_file_storage();
    $file = $fs->get_file($context->id, 'local_simhub', $filearea, $itemid, $filepath, $filename);
    if (!$file || $file->is_directory()) {
        return false;
    }

    send_stored_file($file, null, 0, $forcedownload, $options);
}
