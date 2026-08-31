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

    // TODO : vérifier la visibilité de la ressource avant de servir le
    // fichier (cf. table local_simhub_ressource.visibilite), en plus du
    // contrôle de capacité déjà exercé par require_login().

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
