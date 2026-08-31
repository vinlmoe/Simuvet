<?php
// Capacités SimHub - correspondance avec les profils décrits au §11 du
// cahier des charges :
//   Étudiant | Enseignant/formateur | Responsable d'UC
//   Responsable/gestionnaire de salle | Administrateur fonctionnel
//   Validateur externe ASV (accès par jeton, hors capacités Moodle)

defined('MOODLE_INTERNAL') || die();

$capabilities = [

    // --- Étudiant : consultation, réalisation, auto-évaluation. ---
    'local/simhub:view' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'student' => CAP_ALLOW,
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:startsession' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],
    'local/simhub:submitautoeval' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'student' => CAP_ALLOW,
        ],
    ],

    // --- Enseignant / formateur : pilotage pédagogique, validations. ---
    'local/simhub:viewprogression' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:validatesession' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:exportsuivi' => [
        'captype' => 'read',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'teacher' => CAP_ALLOW,
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // --- Responsable d'UC : rattachement d'ateliers, parcours. ---
    'local/simhub:manageparcours' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:managerattachement' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // --- Responsable / gestionnaire de salle : fiches, statuts, QR, imports. ---
    'local/simhub:manageateliers' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:manageressources' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:managestatuts' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:manageqrcodes' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:importexport' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // --- Module ASV. ---
    'local/simhub:manageasv' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],
    'local/simhub:validateasvsimulation' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW,
        ],
    ],

    // --- Administrateur fonctionnel : référentiels, droits, supervision. ---
    'local/simhub:configure' => [
        'captype' => 'write',
        'contextlevel' => CONTEXT_SYSTEM,
        'archetypes' => [
            'manager' => CAP_ALLOW,
        ],
    ],

    // NB : le "Validateur externe ASV" (§9.3, §11) n'a volontairement
    // aucune capacité Moodle : il agit sans compte, via un lien à jeton
    // (voir local_simhub_asv_valanimal.token et validate_animal.php,
    // à écrire). Le contrôle d'accès pour ce flux se fait par jeton
    // signé + expiration, pas par le système de rôles.
];
