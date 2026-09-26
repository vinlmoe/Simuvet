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
 * SimHub.
 *
 * @package    mod_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['aucunatelier'] = 'No workshop is linked to this course unit yet.';
$string['aucunetudiant'] = 'No enrolled student.';
$string['aucuneseance'] = 'No session awaiting validation.';
$string['atelier'] = 'Workshop';
$string['ateliersuc'] = 'Course unit workshops';
$string['avancement'] = 'Your progress: {$a->pct} % ({$a->realises} of {$a->total} workshops).';
$string['avancementcol'] = 'Progress';
$string['avancementetudiants'] = 'Student progress';
$string['composer'] = 'Choose the course unit workshops';
$string['echeance'] = 'Due date';
$string['etat'] = 'Status';
$string['exportcsv'] = 'Export tracking (CSV)';
$string['grille'] = 'Self-assessment grid';
$string['modulename'] = 'SimHub';
$string['modulename_help'] = 'Links simulation room workshops to this course unit. Students complete workshops by scanning their QR code, without going through the course; their progress and grade are updated in every course unit that contains the workshop. Teachers track their students, validate their sessions and, as course unit leads, choose the workshops and edit their self-assessment grid.';
$string['modulenameplural'] = 'SimHub';
$string['nonverifie'] = 'presence not verified';
$string['notecalcul'] = 'The grade is the student\'s progress percentage on the course unit workshops (the mandatory ones if any, otherwise all), scaled to the maximum grade.';
$string['obligatoire'] = 'Mandatory';
$string['pluginadministration'] = 'SimHub administration';
$string['pluginname'] = 'SimHub';
$string['privacy:metadata'] = 'The SimHub activity stores no personal data: sessions are kept by the SimHub local plugin and grades by the gradebook.';
$string['scaninfo'] = 'To complete a workshop, scan its QR code in the room: your progress is updated here automatically.';
$string['seancesavalider'] = 'Sessions to validate';
$string['simhub:addinstance'] = 'Add a SimHub activity';
$string['simhub:manageparcours'] = 'Choose the course unit workshops and edit their self-assessment grid';
$string['simhub:validateasvsimulation'] = 'Validate an ASV act in simulation for course unit students';
$string['simhub:validatesession'] = 'Validate course unit student sessions';
$string['simhub:view'] = 'View the SimHub activity';
$string['simhub:viewprogression'] = 'Track course unit student progress';
$string['suividetaille'] = 'Detailed tracking';
$string['validerasv'] = 'Validate an ASV act';
