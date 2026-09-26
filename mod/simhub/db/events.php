<?php
// Une séance appartient à l'étudiant et à l'atelier : toutes les UC qui contiennent
// l'atelier recalculent leur note.

defined('MOODLE_INTERNAL') || die();

$observers = [
    ['eventname' => '\local_simhub\event\session_completed', 'callback' => '\mod_simhub\observer::seance_modifiee'],
    ['eventname' => '\local_simhub\event\session_validated', 'callback' => '\mod_simhub\observer::seance_modifiee'],
    ['eventname' => '\local_simhub\event\parcours_updated', 'callback' => '\mod_simhub\observer::parcours_modifie'],
];
