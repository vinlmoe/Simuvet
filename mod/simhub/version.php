<?php
// Activité SimHub : ajoutée par le responsable d'UC dans le cours de son UC.

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'mod_simhub';
$plugin->version   = 2026092900;
$plugin->requires  = 2023100900;
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = '0.1.0-dev';
$plugin->dependencies = ['local_simhub' => 2026092900];
