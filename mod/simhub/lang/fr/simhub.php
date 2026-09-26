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

$string['atelier'] = 'Atelier';
$string['ateliersuc'] = 'Ateliers de l\'UC';
$string['aucunatelier'] = 'Aucun atelier n\'est encore associé à cette UC.';
$string['aucuneseance'] = 'Aucune séance en attente de validation.';
$string['aucunetudiant'] = 'Aucun étudiant inscrit.';
$string['avancement'] = 'Votre avancement : {$a->pct} % ({$a->realises} ateliers sur {$a->total}).';
$string['avancementcol'] = 'Avancement';
$string['avancementetudiants'] = 'Avancement des étudiants';
$string['composer'] = 'Choisir les ateliers de l\'UC';
$string['echeance'] = 'Échéance';
$string['etat'] = 'État';
$string['exportuc'] = 'Suivi de l\'UC :';
$string['grille'] = 'Grille d\'auto-évaluation';
$string['historique'] = 'Historique';
$string['modulename'] = 'SimHub';
$string['modulename_help'] = 'Associe à cette UC des ateliers de la salle de simulation. Les étudiants réalisent les ateliers en scannant leur QR code, sans passer par le cours ; leur avancement et leur note se mettent à jour dans toutes les UC qui contiennent l\'atelier. L\'enseignant suit ses étudiants, valide leurs séances et, s\'il est responsable de l\'UC, choisit les ateliers et modifie leur grille d\'auto-évaluation.';
$string['modulenameplural'] = 'SimHub';
$string['notecalcul'] = 'La note est le pourcentage d\'avancement de l\'étudiant sur les ateliers de l\'UC (les ateliers obligatoires s\'il y en a, sinon tous), rapporté à la note maximale.';
$string['obligatoire'] = 'Obligatoire';
$string['pluginadministration'] = 'Administration de SimHub';
$string['pluginname'] = 'SimHub';
$string['privacy:metadata'] = 'L\'activité SimHub ne stocke aucune donnée personnelle : les séances sont conservées par le plugin local SimHub et les notes par le carnet de notes.';
$string['scaninfo'] = 'Pour réaliser un atelier, scannez son QR code en salle : votre avancement se met à jour ici automatiquement.';
$string['seancesavalider'] = 'Séances à valider';
$string['simhub:addinstance'] = 'Ajouter une activité SimHub';
$string['simhub:manageparcours'] = 'Choisir les ateliers de l\'UC et modifier leur grille d\'auto-évaluation';
$string['simhub:validateasvsimulation'] = 'Valider un acte ASV en simulation pour les étudiants de l\'UC';
$string['simhub:validatesession'] = 'Valider les séances des étudiants de l\'UC';
$string['simhub:view'] = 'Voir l\'activité SimHub';
$string['simhub:viewprogression'] = 'Suivre l\'avancement des étudiants de l\'UC';
$string['suividetaille'] = 'Suivi détaillé';
$string['validerasv'] = 'Valider un acte ASV';
