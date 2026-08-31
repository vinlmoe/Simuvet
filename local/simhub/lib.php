<?php
// Fonctions de callback Moodle pour l'intégration de SimHub.
//
// Ce fichier reste volontairement léger en V0 : il pose les points
// d'entrée attendus par le noyau Moodle (navigation, service de
// fichiers), sans encore porter la logique métier détaillée, qui devra
// être développée dans classes/local/ (managers) au fil des sprints.

defined('MOODLE_INTERNAL') || die();

/**
 * Ajoute SimHub à la navigation primaire pour les utilisateurs
 * disposant de la capacité local/simhub:view.
 *
 * TODO : différencier l'entrée de menu selon le profil (étudiant vs
 * gestionnaire) une fois les pages respectives développées.
 */
function local_simhub_extend_navigation(global_navigation $nav) {
    global $PAGE;

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

    if (has_capability('local/simhub:manageateliers', $context)) {
        $node->add(
            get_string('manage_ateliers', 'local_simhub'),
            new moodle_url('/local/simhub/manage/ateliers.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_simhub_manage'
        );
    }

    if (has_capability('local/simhub:manageparcours', $context) || has_capability('local/simhub:viewprogression', $context)) {
        $node->add(
            get_string('filtre_parcours', 'local_simhub'),
            new moodle_url('/local/simhub/manage/parcours.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_simhub_parcours'
        );
    }

    if (has_capability('local/simhub:importexport', $context)) {
        $node->add(
            get_string('import_ateliers', 'local_simhub'),
            new moodle_url('/local/simhub/manage/import.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_simhub_import'
        );
    }

    if (has_capability('local/simhub:manageasv', $context) || has_capability('local/simhub:validateasvsimulation', $context)) {
        $node->add(
            get_string('asv_parcours', 'local_simhub'),
            new moodle_url('/local/simhub/asv/index.php'),
            navigation_node::TYPE_CUSTOM,
            null,
            'local_simhub_asv'
        );
    }

    $nav->add_node($node);
}

/**
 * Sert les fichiers stockés via l'API filestorage de Moodle pour les
 * ressources d'atelier (PDF, vidéos, plans de salle...).
 *
 * Zones de fichiers prévues :
 *   - 'ressource'  : fichiers attachés à local_simhub_ressource.fileitemid
 *   - 'plan'       : image de plan de salle, local_simhub_atelier.planimageitemid
 *
 * TODO : implémenter le contrôle de visibilité (une ressource de type
 * "source_editable" ne doit jamais être servie à un utilisateur qui n'a
 * pas local/simhub:manageressources, cf. §6.2 du cahier des charges).
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
