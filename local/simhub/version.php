<?php
// This file is part of Moodle - http://moodle.org/
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
 * Métadonnées du plugin.
 *
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_simhub';
$plugin->version   = 2026093000;      // YYYYMMDDXX.
$plugin->requires  = 2023100900;      // Moodle 4.3+ (LTS visée, à ajuster selon la version EVE cible).
$plugin->maturity  = MATURITY_ALPHA;  // V1 en construction.
$plugin->release   = '0.2.0-dev';

// Dépendances éventuelles (aucune obligatoire en V1 ; le module cohort
// natif et le carnet de notes natif suffisent).
$plugin->dependencies = [];
