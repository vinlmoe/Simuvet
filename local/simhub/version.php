<?php
// This file is part of Moodle - http://moodle.org/
//
// SimHub - Gestion des ateliers de simulation, ressources pédagogiques
// et parcours pratiques pour les ENV (ENVA, ENVT, Oniris, VetAgro Sup).
//
// Squelette technique V0 - issu du cahier des charges SimHub.
// À valider et compléter avec les responsables des salles de simulation
// avant tout développement de production.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_simhub';
$plugin->version   = 2026083102;      // YYYYMMDDXX.
$plugin->requires  = 2023100900;      // Moodle 4.3+ (LTS visée, à ajuster selon la version EVE cible).
$plugin->maturity  = MATURITY_ALPHA;  // V1 en construction, non testé sur instance réelle.
$plugin->release   = '0.2.0-dev';

// Dépendances éventuelles (aucune obligatoire en V1 ; le module cohort
// natif et le carnet de notes natif suffisent).
$plugin->dependencies = [];
