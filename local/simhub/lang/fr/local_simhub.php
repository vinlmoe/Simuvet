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
 * @package    local_simhub
 * @copyright  2026 Écoles nationales vétérinaires de France (ENVF)
 * @license    https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SimHub';
$string['nav_accueil'] = 'Accueil SimHub';
$string['nav_seance'] = 'Séance en cours';
$string['nav_qrcode'] = 'QR code de l\'atelier';
$string['nav_plan'] = 'Repère sur le plan de salle';
$string['nav_ressources'] = 'Ressources pédagogiques';
$string['nav_retour'] = 'Retour';
$string['simhub:studenthome'] = 'Mon espace ateliers';

// Réglages.
$string['setting_envcode'] = 'Code établissement';
$string['setting_envcode_desc'] = 'Code court de l\'école (ex. ENVA, ENVT, ONIRIS, VETAGROSUP). Chaque école ayant son propre Moodle, il sert uniquement à la traçabilité : il est enregistré sur les ateliers, parcours et actes ASV créés ou importés, et figure dans les exports.';
$string['setting_seancecodeduration'] = 'Durée de validité d\'un code de séance';
$string['setting_seancecodeduration_desc'] = 'Durée pendant laquelle un code de séance temporaire (§7.3, contrôle anti-faux-scan) reste valable.';
$string['setting_asvtokenexpiry'] = 'Durée de validité d\'un lien de validation ASV externe';
$string['setting_asvtokenexpiry_desc'] = 'Durée pendant laquelle le lien envoyé à un validateur externe (vétérinaire, maître de stage...) reste actif avant expiration du jeton.';
$string['setting_controlepresenceactif'] = 'Activer le contrôle anti-faux-scan';
$string['setting_controlepresenceactif_desc'] = 'Si désactivé, aucune vérification de présence n\'est demandée lors du scan d\'un QR code (fonctionnalité classée V1+ souhaitable, §13).';
$string['setting_etablissementnom'] = 'Nom de l\'établissement';
$string['setting_etablissementnom_desc'] = 'Nom complet affiché en en-tête des documents PDF (attestations, livret ASV, fiches ateliers), ex. "École Nationale Vétérinaire d\'Alfort".';
$string['setting_logo'] = 'Logo de l\'établissement';
$string['setting_logo_desc'] = 'Image affichée en en-tête des documents PDF générés par SimHub (attestations de fin de parcours, livret ASV, fiches ateliers). Formats acceptés : PNG, JPG, SVG.';

// Statuts atelier.
$string['statut_actif'] = 'Actif';
$string['statut_non_utilise'] = 'Non utilisé';
$string['statut_indisponible'] = 'Indisponible';
$string['statut_archive'] = 'Archivé';

// Niveaux auto-évaluation.
$string['niveau_reussi'] = 'Réussi';
$string['niveau_a_consolider'] = 'À consolider';
$string['niveau_a_reprendre'] = 'À reprendre';

// Capacités (libellés affichés dans l'écran de gestion des rôles).
$string['simhub:view'] = 'Consulter SimHub';
$string['simhub:startsession'] = 'Démarrer / terminer un atelier';
$string['simhub:submitautoeval'] = 'Répondre à une auto-évaluation guidée';
$string['simhub:viewprogression'] = 'Consulter la progression des étudiants';
$string['simhub:validatesession'] = 'Valider une réalisation d\'atelier';
$string['simhub:exportsuivi'] = 'Exporter les données de suivi';
$string['simhub:manageparcours'] = 'Gérer les parcours pédagogiques';
$string['simhub:managerattachement'] = 'Gérer les rattachements pédagogiques';
$string['simhub:manageateliers'] = 'Gérer les fiches ateliers';
$string['simhub:manageressources'] = 'Gérer les ressources pédagogiques';
$string['simhub:managestatuts'] = 'Gérer les statuts et indisponibilités';
$string['simhub:manageqrcodes'] = 'Gérer les QR codes';
$string['simhub:importexport'] = 'Importer / exporter des données';
$string['simhub:manageasv'] = 'Administrer le module ASV';
$string['simhub:validateasvsimulation'] = 'Valider un acte ASV en simulation';
$string['simhub:configure'] = 'Configurer SimHub (administration fonctionnelle)';

// Privacy API (RGPD).
$string['privacy:metadata:local_simhub_session'] = 'Historique des réalisations d\'ateliers de simulation par l\'utilisateur.';
$string['privacy:metadata:local_simhub_session:userid'] = 'L\'identifiant de l\'utilisateur ayant réalisé l\'atelier.';
$string['privacy:metadata:local_simhub_session:atelierid'] = 'L\'atelier concerné.';
$string['privacy:metadata:local_simhub_session:timestart'] = 'La date de démarrage de la session.';
$string['privacy:metadata:local_simhub_session:timeend'] = 'La date de fin de la session.';
$string['privacy:metadata:local_simhub_session:statut'] = 'Le statut de la session (commencée, réalisée, certifiée, non terminée).';

$string['privacy:metadata:local_simhub_ae_reponse'] = 'Réponses de l\'utilisateur aux critères d\'auto-évaluation guidée.';
$string['privacy:metadata:local_simhub_ae_reponse:sessionid'] = 'La session concernée.';
$string['privacy:metadata:local_simhub_ae_reponse:critereid'] = 'Le critère évalué.';
$string['privacy:metadata:local_simhub_ae_reponse:niveau'] = 'Le niveau atteint (réussi, à consolider, à reprendre).';

$string['privacy:metadata:local_simhub_ae_bilan'] = 'Auto-bilans rédigés par l\'utilisateur en fin d\'atelier.';
$string['privacy:metadata:local_simhub_ae_bilan:sessionid'] = 'La session concernée.';
$string['privacy:metadata:local_simhub_ae_bilan:pointmaitrise'] = 'Le point le mieux maîtrisé, tel que rédigé par l\'utilisateur.';
$string['privacy:metadata:local_simhub_ae_bilan:pointaretravailler'] = 'Le point à retravailler, tel que rédigé par l\'utilisateur.';
$string['privacy:metadata:local_simhub_ae_bilan:pointattention'] = 'Le point d\'attention pour le prochain essai, tel que rédigé par l\'utilisateur.';

$string['privacy:metadata:local_simhub_val_encadrant'] = 'Validations d\'ateliers réalisées par un encadrant.';
$string['privacy:metadata:local_simhub_val_encadrant:sessionid'] = 'La session validée.';
$string['privacy:metadata:local_simhub_val_encadrant:validateuruserid'] = 'L\'encadrant ayant réalisé la validation.';
$string['privacy:metadata:local_simhub_val_encadrant:statut'] = 'Le résultat de la validation (validé, refusé).';
$string['privacy:metadata:local_simhub_val_encadrant:commentaire'] = 'Le commentaire libre de l\'encadrant.';

$string['privacy:metadata:local_simhub_asv_valsim'] = 'Validations ASV en simulation.';
$string['privacy:metadata:local_simhub_asv_valsim:userid'] = 'L\'étudiant concerné par la validation.';
$string['privacy:metadata:local_simhub_asv_valsim:acteid'] = 'L\'acte ASV concerné.';
$string['privacy:metadata:local_simhub_asv_valsim:validateuruserid'] = 'L\'encadrant ayant validé l\'acte.';
$string['privacy:metadata:local_simhub_asv_valsim:statut'] = 'Le résultat de la validation.';

$string['privacy:metadata:local_simhub_asv_valanimal'] = 'Données de validation ASV sur animal vivant, incluant l\'identité du validateur externe et sa signature.';
$string['privacy:metadata:local_simhub_asv_valanimal:userid'] = 'L\'étudiant concerné par la validation.';
$string['privacy:metadata:local_simhub_asv_valanimal:acteid'] = 'L\'acte ASV concerné.';
$string['privacy:metadata:local_simhub_asv_valanimal:nomvalidateur'] = 'Le nom du validateur (éventuellement externe à Moodle).';
$string['privacy:metadata:local_simhub_asv_valanimal:prenomvalidateur'] = 'Le prénom du validateur.';
$string['privacy:metadata:local_simhub_asv_valanimal:signature'] = 'Le tracé de signature du validateur.';

// Chaînes des événements.
$string['event_atelier_created'] = 'Atelier créé';
$string['event_session_started'] = 'Session d\'atelier démarrée';
$string['event_session_completed'] = 'Session d\'atelier terminée';
$string['event_asv_valide_simulation'] = 'Acte ASV validé en simulation';
$string['event_asv_valide_animal'] = 'Acte ASV validé sur animal vivant';

// Gestion des ateliers.
$string['manage_ateliers'] = 'Gestion des ateliers';
$string['rattachements'] = 'Rattachements';
// Grille d'auto-évaluation guidée (§5.6, §7.2).
$string['ae_modele'] = 'Grille d\'auto-évaluation';
$string['ae_champ_titre'] = 'Titre';
$string['ae_champ_actif'] = 'Grille active';
$string['ae_champ_risques'] = 'Rubrique dédiée aux erreurs/risques';
$string['ae_champ_critere'] = 'Nouveau critère';
$string['ae_gerer_rubriques'] = 'Gérer les rubriques et critères';
$string['ae_ajouter_rubrique'] = 'Ajouter une rubrique';
$string['ae_ajouter_critere'] = 'Ajouter';
$string['ae_badge_risques'] = 'Erreurs/risques';

$string['qr_lien_intro'] = 'Ce QR code ouvre la fiche de l\'atelier et démarre une session lors du scan (§7). Imprimez-le et collez-le sur l\'atelier.';
$string['qr_regenerer'] = 'Régénérer le lien (invalide l\'ancien QR code imprimé)';
$string['qr_imprimer'] = 'Imprimer';
$string['qr_telecharger'] = 'Télécharger (SVG)';
$string['atelier_nouveau'] = 'Nouvel atelier';
$string['atelier_modifier'] = 'Modifier l\'atelier';
$string['atelier_enregistre'] = 'Atelier enregistré.';
$string['atelier_supprime'] = 'Atelier supprimé.';
$string['parcours_nouveau'] = 'Nouveau parcours';
$string['ressource_nouvelle'] = 'Nouvelle ressource';
$string['autoeval_enregistree'] = 'Auto-évaluation enregistrée.';
$string['session_terminee'] = 'Atelier terminé.';
$string['session_demarree'] = 'Atelier démarré.';
$string['asv_validation_enregistree'] = 'Validation ASV enregistrée.';
$string['champ_numero'] = 'Numéro (invariant)';
$string['champ_nomcourt'] = 'Nom court';
$string['champ_nomlong'] = 'Nom long';
$string['champ_descriptioncourte'] = 'Description courte';
$string['champ_discipline'] = 'Discipline';
$string['champ_espece'] = 'Espèce(s)';
$string['champ_niveaudifficulte'] = 'Niveau de difficulté';
$string['champ_dureeindicative'] = 'Durée indicative (minutes)';
$string['champ_statut'] = 'Statut';
$string['champ_envcode'] = 'Établissement';
$string['champ_salle'] = 'Salle';
$string['champ_zone'] = 'Zone';
$string['champ_codeposte'] = 'Code / numéro de poste';
$string['champ_indicationtextuelle'] = 'Indication textuelle';
$string['champ_commentaireadmin'] = 'Commentaire (interne)';
$string['champ_commentaire_indispo'] = 'Commentaire sur l\'indisponibilité';
$string['champ_echeance_indispo'] = 'Échéance prévisionnelle';
$string['champ_pointmaitrise'] = 'Point le mieux maîtrisé';
$string['champ_pointaretravailler'] = 'Point à retravailler';
$string['champ_pointattention'] = 'Point d\'attention pour le prochain essai';

// Accueil étudiant / filtres (§5).
$string['filtre_uc'] = 'UC';
$string['filtre_parcours'] = 'Parcours';
$string['filtre_annee'] = 'Année d\'étude';
$string['filtre_discipline'] = 'Discipline';
$string['filtre_espece'] = 'Espèce';
$string['filtre_niveau'] = 'Niveau de difficulté';
$string['filtre_duree'] = 'Durée';
$string['filtre_statutperso'] = 'Statut personnel';
$string['filtre_motcle'] = 'Mot-clé';
$string['filtre_appliquer'] = 'Filtrer';
$string['filtre_reinitialiser'] = 'Réinitialiser';
$string['statutperso_pascommence'] = 'Pas commencé';
$string['statutperso_commence'] = 'Commencé';
$string['statutperso_realise'] = 'Réalisé';
$string['statutperso_valide'] = 'Validé';
$string['statutperso_areprendre'] = 'À reprendre';
$string['aucun_atelier'] = 'Aucun atelier ne correspond à ces critères.';
$string['bouton_localisation'] = 'Voir où il est';
$string['bouton_ressources'] = 'Voir les ressources';
$string['bouton_commencer'] = 'Commencer';
$string['bouton_terminer'] = 'Terminer l\'atelier et s\'auto-évaluer';
$string['section_mesuc'] = 'À faire pour mes UC';
$string['section_parcours'] = 'Mes parcours en cours';
$string['section_asv'] = 'Parcours ASV';
$string['section_commences'] = 'Ateliers déjà commencés';
$string['section_areprendre'] = 'Ateliers à reprendre';
$string['section_tous'] = 'Tous les ateliers disponibles';
$string['section_recommandes'] = 'Recommandés pour mon groupe';
$string['champ_cohorte'] = 'Groupe (cohorte Moodle)';

// Module ASV (§9).
$string['asv_gerer_actes'] = 'Gérer le référentiel des actes';
$string['asv_acte_nouveau'] = 'Nouvel acte';
$string['asv_acte_inactif'] = 'Inactif';
$string['asv_champ_code'] = 'Code';
$string['asv_champ_niveau'] = 'Niveau';
$string['asv_champ_ucid'] = 'UC (id de cours)';
$string['asv_parcours'] = 'Parcours ASV';
$string['asv_livret'] = 'Livret de compétences ASV';
$string['asv_ateliers_lies'] = 'Ateliers de simulation où cet acte se pratique';
$string['asv_lier_atelier'] = 'Lier cet atelier';
$string['asv_aucun_acte_lie'] = 'Aucun acte lié à cet atelier : le référentiel complet est proposé.';
$string['asv_valider_depuis_atelier'] = 'Valider un acte ASV pour cet atelier';
$string['asv_attestations_groupees'] = 'Attestations de certification (génération groupée)';
$string['asv_aucun_eligible'] = "Aucun étudiant n'a encore validé entièrement ce niveau.";
$string['asv_nb_eligibles'] = '{$a} étudiant(s) ont validé ce niveau et peuvent recevoir leur attestation.';
$string['asv_telecharger_tout'] = 'Télécharger toutes les attestations (ZIP)';
$string['asv_telecharger'] = 'Télécharger';
$string['filtrer'] = 'Filtrer';
$string['asv_valider_simulation'] = 'Valider en simulation';
$string['asv_demander_validation_animal'] = 'Demander une validation sur animal vivant';
$string['asv_formulaire_validateur_titre'] = 'Validation de l\'acte sur animal vivant';
$string['asv_champ_nom'] = 'Nom';
$string['asv_champ_prenom'] = 'Prénom';
$string['asv_champ_certification'] = 'Je certifie être vétérinaire ou encadrant autorisé à valider cet acte.';
$string['asv_champ_signature'] = 'Signature';
$string['asv_valide_avec_succes'] = 'Validation enregistrée. Merci.';
$string['asv_lien_invalide'] = 'Ce lien de validation est invalide ou a expiré.';
// Contrôle anti-faux-scan (§7.3).
$string['seancecode_intro'] = 'Un contrôle de présence est demandé pour cet atelier : saisissez le code de séance affiché en salle par l\'encadrant.';
$string['seancecode_champ'] = 'Code de séance';
$string['seancecode_valider'] = 'Valider le code';
$string['seancecode_pasdecode'] = 'Je n\'ai pas de code, commencer quand même';
$string['seancecode_invalide'] = 'Ce code est invalide ou a expiré. Vous pouvez commencer sans code : votre réalisation sera alors soumise à une validation par un encadrant.';
$string['seancecode_sansvalidation'] = 'Session démarrée sans code de séance. Votre réalisation devra être validée par un encadrant.';
$string['seancecode_generer'] = 'Générer un code de séance';
$string['seancecode_champ_salle'] = 'Salle';
$string['seancecode_genere'] = 'Code généré';
$string['seancecode_validite'] = 'Valable jusqu\'à {$a}';
$string['sessions_a_valider'] = 'Sessions à valider';

// Tableaux de bord (§12.2).
$string['dashboard_parcours'] = 'Tableau de bord parcours';
$string['dashboard_salle'] = 'Tableau de bord salle';
$string['dashboard_sansressource'] = 'Sans ressource';
$string['dashboard_sansuc'] = 'Sans UC';
$string['dashboard_peuutilise'] = 'Peu utilisés';

// Badges Moodle (§13).
$string['badge_aucun'] = 'Aucun';
$string['champ_badge'] = 'Badge délivré à la réalisation complète (optionnel)';
$string['setting_badgeasva1'] = 'Badge ASV — niveau A1';
$string['setting_badgeasva1_desc'] = 'Badge de site délivré automatiquement à l\'étudiant lorsqu\'il génère l\'attestation de certification A1 (§9.4).';
$string['setting_badgeasva2'] = 'Badge ASV — niveau A2';
$string['setting_badgeasva2_desc'] = 'Badge de site délivré automatiquement à l\'étudiant lorsqu\'il génère l\'attestation de certification A2 (§9.4).';
$string['setting_badgeasva3'] = 'Badge ASV — niveau A3';
$string['setting_badgeasva3_desc'] = 'Badge de site délivré automatiquement à l\'étudiant lorsqu\'il génère l\'attestation de certification A3 (§9.4, certification globale de fin de A3).';
$string['sessions_aucune_a_valider'] = 'Aucune session en attente de validation.';
$string['session_valider'] = 'Valider';
$string['session_refuser'] = 'Refuser';

$string['asv_niveau_a1'] = 'A1';
$string['asv_niveau_a2'] = 'A2';
$string['asv_niveau_a3'] = 'A3';

// Import (§12.1).
$string['import_ateliers'] = 'Importer des ateliers';
$string['import_description'] = 'Importe des données depuis un fichier CSV, Excel (XLSX) ou LibreOffice (ODS) ; pour un classeur, seule la première feuille est lue. Les colonnes peuvent être dans n\'importe quel ordre et sous des intitulés variés (accents et casse ignorés) : les tableaux des écoles n\'ont pas besoin d\'être harmonisés au préalable. Le détail des colonnes de chaque type est dans l\'aide du champ « Type d\'import ».';
$string['import_fichier'] = 'Fichier CSV';

// Export (§12.3).
$string['export_csv'] = 'Exporter (CSV)';

// Navigation par domaines, onglets et sélecteurs.
$string['onglet_fiche'] = 'Fiche';
$string['onglet_vue_etudiant'] = 'Vue étudiant';
$string['onglet_pdf'] = 'Fiche PDF';
$string['onglet_composition'] = 'Composition';
$string['onglet_suivi'] = 'Suivi';
$string['onglet_export_csv'] = 'Export CSV';
$string['nav_gerer'] = 'Gérer';
$string['nav_groupe_accueil'] = 'Mon espace';
$string['nav_groupe_ateliers'] = 'Ateliers';
$string['nav_groupe_parcours'] = 'Parcours';
$string['nav_groupe_seances'] = 'Séances';
$string['nav_groupe_asv'] = 'ASV';
$string['type'] = 'Type';
$string['nb_ateliers'] = 'Ateliers';
$string['parcours_type_recommande'] = 'Recommandé';
$string['parcours_type_obligatoire'] = 'Obligatoire';
$string['parcours_type_lie_uc'] = 'Lié à une UC';
$string['parcours_type_lie_annee'] = 'Lié à une année';
$string['parcours_type_certifiant'] = 'Certifiant';
$string['parcours_type_asv'] = 'ASV';
$string['aucune_uc'] = '— Aucune UC —';
$string['choisir_etudiant'] = '— Choisir un étudiant —';
$string['atelier_aucun_choix'] = '— Aucun atelier —';
$string['champ_uc_optionnel'] = 'UC Moodle (optionnel)';
$string['rattachement_ajouter'] = 'Ajouter un rattachement';
$string['optionnel'] = '(optionnel)';
$string['champ_cohorte_recommandation'] = 'Cohorte (optionnel — pour recommander directement à ses membres)';
$string['etudiant'] = 'Étudiant';
$string['asv_acte'] = 'Acte';
$string['asv_atelier_associe'] = 'Atelier de simulation associé (optionnel)';
$string['asv_certification_a3'] = 'Certification globale de fin de A3';
$string['asv_signature_effacer'] = 'Effacer la signature';
$string['asv_valider_acte'] = 'Valider cet acte';
$string['telecharger_fiche_pdf'] = 'Télécharger la fiche (PDF)';
$string['gerer_atelier'] = 'Gérer cet atelier';
$string['asv_code_existe'] = 'Le code d\'acte « {$a} » existe déjà pour cet établissement.';
$string['numero_existe'] = 'Ce numéro d\'atelier est déjà utilisé dans cet établissement.';

// Rôles système (§11).
$string['nav_roles'] = 'Rôles SimHub';
$string['nav_groupe_roles'] = 'Rôles';
$string['role_simhubencadrant'] = 'Formateur SimHub';
$string['role_simhubencadrant_desc'] = 'Formateur transversal désigné dans la catégorie SimHub : suivi de tous les parcours, validation des séances et des actes ASV en simulation pour tous les étudiants. Un enseignant d\'UC n\'en a pas besoin (§11).';
$string['role_simhubresponsableuc'] = 'Responsable d\'UC SimHub';
$string['role_simhubresponsableuc_desc'] = 'Vue transversale des parcours et rattachements de toutes les UC. Un responsable d\'UC gère la sienne depuis l\'activité SimHub de son cours, sans ce rôle (§11).';
$string['role_simhubgestionnairesalle'] = 'Gestionnaire de salle SimHub';
$string['role_simhubgestionnairesalle_desc'] = 'Responsable de salle : fiches ateliers, ressources, statuts, QR codes, import/export (§11).';
$string['role_simhubadminfonctionnel'] = 'Administrateur fonctionnel SimHub';
$string['role_simhubadminfonctionnel_desc'] = 'Tous les droits SimHub, y compris le référentiel ASV et le paramétrage, et l\'attribution des autres rôles SimHub dans la catégorie SimHub (§11).';

// Textes auparavant codés en dur dans les pages.
$string['ae_autobilan'] = 'Auto-bilan';
$string['asv_acte_libelle'] = 'Acte : {$a}';
$string['asv_transmettre_lien'] = 'Indiquez l\'adresse e-mail du vétérinaire, maître de stage ou encadrant autorisé qui a supervisé le geste sur animal vivant : le lien de validation lui est envoyé directement.';
$string['asv_exporter_livret'] = 'Exporter le livret (PDF)';
$string['asv_col_simulation'] = 'Simulation';
$string['asv_col_animal'] = 'Animal vivant';
$string['asv_col_valides_simulation'] = 'Validés en simulation';
$string['asv_col_valides_animal'] = 'Validés sur animal vivant';
$string['asv_col_validation_simulation'] = 'Validation simulation';
$string['asv_col_validation_animal'] = 'Validation animal vivant';
$string['asv_aucun_acte_niveau'] = 'Aucun acte {$a} dans le référentiel de cet établissement.';
$string['asv_certification_niveau'] = 'Certification {$a}';
$string['asv_certification_incomplete'] = 'La certification {$a->niveau} de {$a->nom} ne peut pas encore être délivrée : {$a->nb} acte(s) restent à valider.';
$string['attestation_incomplete'] = 'L\'attestation ne peut pas encore être délivrée : {$a->nom} est à {$a->pct} % du parcours « {$a->parcours} » ({$a->realises}/{$a->total} ateliers réalisés).';
$string['attestation_titre'] = 'Attestation de réalisation';
$string['pdf_certifie_que'] = '{$a} certifie que';
$string['attestation_parcours_realise'] = 'a réalisé l\'ensemble des ateliers du parcours <b>{$a}</b>.';
$string['atelier'] = 'Atelier';
$string['pdf_delivree_le'] = 'Délivrée le {$a}';
$string['asv_attestation_titre'] = 'Attestation de certification';
$string['asv_attestation_soustitre'] = 'Actes vétérinaires délégables ASV — niveau {$a}';
$string['asv_attestation_texte'] = 'a validé, en simulation puis sur animal vivant, l\'ensemble des actes vétérinaires délégables du niveau {$a} listés ci-dessous :';
$string['asv_manque_simulation'] = 'simulation manquante';
$string['asv_manque_animal'] = 'animal vivant manquant';
$string['avancement'] = 'Avancement';
$string['suivi_valide'] = 'Validé';
$string['suivi_realise'] = 'Réalisé';
$string['suivi_commence'] = 'Commencé';
$string['attestation_pdf'] = 'Attestation (PDF)';
$string['rattachements_existants'] = 'Rattachements existants';
$string['retirer'] = 'Retirer';
$string['niveau_attendu_optionnel'] = 'Niveau attendu (optionnel)';
$string['plan_absent'] = 'Aucune image de plan n\'a encore été téléversée pour cet atelier.';
$string['plan_consigne'] = 'Cliquez sur le plan à l\'endroit où se trouve l\'atelier.';
$string['plan_positionner'] = 'Positionner le repère sur le plan';
$string['dash_etudiants'] = 'Étudiants';
$string['dash_avancement_moyen'] = 'Avancement moyen';
$string['dash_pas_commence'] = 'Pas commencé';
$string['dash_commence'] = 'Commencé';
$string['dash_termine'] = 'Terminé';
$string['dash_a_reprendre'] = 'À reprendre';
$string['dash_echeance'] = 'Échéance proche/dépassée';
$string['detail'] = 'Détail';
$string['parcours_ateliers_titre'] = 'Ateliers du parcours';
$string['parcours_ajouter_atelier'] = 'Ajouter un atelier';
$string['ordre'] = 'Ordre';
$string['echeance_optionnel'] = 'Échéance (optionnel)';
$string['import_aucun_fichier'] = 'Aucun fichier reçu.';
$string['import_ordre'] = 'Importez d\'abord les fiches ateliers : les autres imports retrouvent chaque atelier par son numéro.';
$string['import_type'] = 'Type d\'import';
$string['import_type_rattachements'] = 'Rattachements (UC / année / cohorte)';
$string['import_type_parcours'] = 'Composition de parcours';
$string['import_separateur'] = 'Séparateur';
$string['import_sep_pointvirgule'] = 'Point-virgule (;) — Excel français';
$string['import_sep_virgule'] = 'Virgule (,)';
$string['import_fichier_vide'] = 'Fichier vide.';
$string['import_colonnes_manquantes'] = 'Colonnes obligatoires introuvables : il faut au moins un numéro et un nom d\'atelier.';
$string['import_ligne_numero_nom'] = 'Ligne {$a} ignorée : numéro ou nom manquant.';
$string['import_ligne_numero'] = 'Ligne {$a} ignorée : numéro d\'atelier manquant.';
$string['import_ligne_parcours'] = 'Ligne {$a} ignorée : parcours ou numéro d\'atelier manquant.';
$string['import_colonne_manquante'] = 'Colonne obligatoire introuvable : {$a}.';
$string['niveau_facile'] = 'Facile';
$string['niveau_intermediaire'] = 'Intermédiaire';
$string['niveau_avance'] = 'Avancé';
$string['champ_planimage'] = 'Image du plan de salle';
$string['ressource_type_fiche_methode'] = 'Fiche méthode';
$string['ressource_type_pdf_etudiant'] = 'PDF étudiant';
$string['ressource_type_video'] = 'Vidéo';
$string['ressource_type_consignes'] = 'Consignes';
$string['ressource_type_criteres_reussite'] = 'Critères de réussite';
$string['ressource_type_erreurs_frequentes'] = 'Erreurs fréquentes';
$string['ressource_type_liens_utiles'] = 'Liens utiles';
$string['ressource_type_complementaire'] = 'Complémentaire';
$string['ressource_type_source_editable'] = 'Source éditable (jamais visible étudiant)';
$string['champ_visibilite'] = 'Visibilité';
$string['visibilite_interne'] = 'Interne (gestionnaires uniquement)';
$string['champ_url_ressource'] = 'Lien externe (optionnel si fichier fourni)';
$string['champ_fichier_ressource'] = 'Fichier (optionnel si lien fourni)';
$string['champ_ordre_affichage'] = 'Ordre d\'affichage';
$string['ressource_lien_ou_fichier'] = 'Indiquez un lien ou un fichier.';
$string['import_ligne_erreur'] = 'Ligne {$a->ligne} : {$a->erreur}';
$string['import_ligne_atelier_introuvable'] = 'Ligne {$a->ligne} ignorée : atelier {$a->numero} ({$a->envcode}) introuvable — importez-le d\'abord.';

// Module ASV : refus, annulation, demandes en attente, certification globale.
$string['asv_non_valide_le'] = 'Non validé le {$a}';
$string['asv_en_attente_jusquau'] = 'Demande en attente (lien valable jusqu\'au {$a})';
$string['asv_annuler_simulation'] = 'Annuler la validation en simulation';
$string['asv_annuler_animal'] = 'Annuler la validation sur animal vivant';
$string['asv_voir_lien'] = 'Suivre la demande de validation';
$string['asv_telecharger_attestation'] = 'Télécharger l\'attestation';
$string['asv_actes_restants'] = '{$a} acte(s) restant(s)';
$string['asv_motif_obligatoire'] = 'Le motif de l\'annulation est obligatoire.';
$string['asv_annule_par'] = 'Annulée par {$a->nom} le {$a->date} : {$a->motif}';
$string['asv_validation_annulee'] = 'Validation annulée. Elle reste tracée mais ne compte plus pour le livret ni la certification.';
$string['asv_confirmer_annulation'] = 'Annuler la validation de l\'acte « {$a->acte} » pour {$a->etudiant} ?';
$string['asv_motif'] = 'Motif (conservé dans l\'historique)';
$string['asv_confirmer'] = 'Confirmer l\'annulation';
$string['asv_resultat'] = 'Résultat';
$string['asv_resultat_valide'] = 'Validé';
$string['asv_resultat_non_valide'] = 'Non validé (à reprendre)';
$string['asv_commentaire'] = 'Observation (optionnel)';
$string['asv_enregistrer_decision'] = 'Enregistrer la décision';
$string['asv_decision_enregistree'] = 'Décision enregistrée.';
$string['asv_simulation_requise'] = 'Cet acte doit d\'abord être validé en simulation avant de pouvoir demander une validation sur animal vivant.';
$string['asv_validation_incomplete'] = 'Validation non enregistrée : indiquez votre nom et votre prénom, cochez la certification et signez dans le cadre.';
$string['asv_signature_requise'] = 'Veuillez signer dans le cadre avant de valider.';
$string['asv_tous_etudiants'] = 'Tous les étudiants ayant une validation ASV';
$string['asv_col_en_attente'] = 'Demandes en attente';
$string['asv_col_certifications'] = 'Certifications acquises';
$string['asv_certif_globale_courte'] = 'Globale fin de A3';
$string['asv_aucun_etudiant'] = 'Aucun étudiant à afficher pour ce filtre.';
$string['asv_synthese_par_acte'] = 'Synthèse par acte (nombre d\'étudiants)';
$string['asv_attestation_soustitre_global'] = 'Certification globale de fin de A3 — actes vétérinaires délégables ASV des niveaux A1, A2 et A3';
$string['asv_attestation_texte_global'] = 'a validé, en simulation puis sur animal vivant, l\'ensemble des actes vétérinaires délégables des niveaux A1, A2 et A3 listés ci-dessous :';

// Parcours étudiant, indisponibilités, démarrage de séance.
$string['parcours_prochaine_echeance'] = 'prochaine échéance :';
$string['parcours_avancement'] = '{$a->realises} atelier(s) requis réalisé(s) sur {$a->total} ({$a->pct} %)';
$string['parcours_col_requis'] = 'Requis pour l\'achèvement';
$string['parcours_col_echeance'] = 'Échéance';
$string['parcours_en_retard'] = 'en retard';
$string['atelier_non_demarrable'] = 'Cet atelier n\'est pas disponible actuellement : aucune séance ne peut y être démarrée.';
$string['indispo_motif'] = 'Motif de l\'indisponibilité (visible par les étudiants)';
$string['indispo_motif_requis'] = 'Indiquez le motif de l\'indisponibilité : il est affiché aux étudiants.';
$string['indispo_echeance'] = 'Remise en service prévue';
$string['indispo_historique'] = 'Historique des indisponibilités';
$string['indispo_debut'] = 'Début';
$string['indispo_referent'] = 'Référent';
$string['indispo_cloturee'] = 'Clôturée';
$string['indispo_en_cours'] = 'En cours';
$string['indispo_retour_prevu'] = 'Retour prévu le {$a}.';
$string['privacy:metadata:local_simhub_asv_valsim:commentaire'] = 'Observation de l\'encadrant ou motif d\'annulation de la validation';
$string['setting_categoryid'] = 'Catégorie SimHub';
$string['setting_categoryid_desc'] = 'Catégorie de cours où sont attribués les rôles SimHub transversaux (gestionnaire de salle, administrateur fonctionnel, formateur ASV). L\'administrateur fonctionnel y attribue lui-même ces rôles, sans compte administrateur. Les enseignants n\'en ont pas besoin : leurs droits viennent de l\'activité SimHub de leur UC. Les rôles déjà attribués au niveau système restent valables.';
$string['setting_categoryid_systeme'] = 'Aucune (niveau système)';
$string['ae_derniere_modification'] = 'Grille partagée par toutes les UC qui utilisent cet atelier. Dernière modification : {$a->auteur}, le {$a->date}.';
$string['event_session_validated'] = 'Séance d\'atelier validée ou refusée';
$string['event_parcours_updated'] = 'Ateliers d\'un parcours modifiés';
$string['privacy:metadata:personnel'] = 'Références au personnel dans le référentiel partagé de l\'école (ateliers, indisponibilités, ressources, parcours, grilles, actes ASV, codes de séance). À la suppression d\'un utilisateur, les fiches sont conservées et la référence est effacée.';
$string['privacy:metadata:personnel:referentuserid'] = 'Personne référente de l\'atelier ou de l\'indisponibilité.';
$string['privacy:metadata:personnel:usermodified'] = 'Auteur de la dernière modification.';
$string['privacy:metadata:personnel:createuruserid'] = 'Encadrant ayant généré le code de séance.';
$string['champ_categorie'] = 'Catégorie';
$string['filtre_statut'] = 'Disponibilité';
$string['filtre_statut_visibles'] = 'Actifs et indisponibles';
$string['filtre_statut_actif'] = 'Actifs seulement';
$string['filtre_statut_indisponible'] = 'Indisponibles seulement';
$string['carte_uc'] = 'UC :';
$string['session_statut_commence'] = 'Commencée';
$string['session_statut_realise'] = 'Réalisée';
$string['session_statut_certifie'] = 'Validée';
$string['session_statut_non_termine'] = 'À refaire';
$string['session_val_valide'] = 'Validée par un encadrant';
$string['session_val_refuse'] = 'Refusée par un encadrant';
$string['session_demarree_le'] = 'Démarrée le';
$string['session_motif'] = 'À vérifier';
$string['session_motif_nonverifie'] = 'présence non vérifiée';
$string['session_motif_duree'] = 'durée anormalement courte ({$a->duree} min pour {$a->indicative} min indicatives)';
$string['setting_reseauxsalle'] = 'Réseau de la salle de simulation';
$string['setting_reseauxsalle_desc'] = 'Plages d\'adresses IP de la salle, une par ligne (ex. 192.168.10.0/24, 10.2.3.4-50, 172.16.). Quand le contrôle anti-faux-scan est actif, un étudiant connecté depuis ce réseau démarre sa séance sans code (§7.3). Vide : pas de reconnaissance réseau.';
$string['setting_dureeminpct'] = 'Durée minimale d\'une séance (% de la durée indicative)';
$string['setting_dureeminpct_desc'] = 'Une séance terminée en moins de ce pourcentage de la durée indicative de l\'atelier est signalée à l\'encadrant (§7.1), sans être bloquée. Par exemple 30 : un atelier de 20 minutes terminé en moins de 6 minutes est signalé. 0 : aucun signalement.';
$string['export_avancement'] = 'Avancement (%)';
$string['export_fin'] = 'Terminée le';
$string['export_validation'] = 'Validation';
$string['export_format_csv'] = 'CSV';
$string['export_format_xlsx'] = 'Excel (XLSX)';
$string['export_format_ods'] = 'LibreOffice (ODS)';
$string['export_telecharger'] = 'Télécharger :';
$string['export_cohorte'] = 'Suivi d\'une cohorte';
$string['col_uc'] = 'UC';
$string['champ_niveauattendu'] = 'Niveau attendu';
$string['champ_obligatoire'] = 'Obligatoire';
$string['champ_echeance'] = 'Échéance';
$string['champ_type'] = 'Type';
$string['visibilite_etudiant'] = 'Visible par les étudiants';
$string['champ_cohorte_optionnel'] = 'Cohorte (optionnel, pour recommander directement à ses membres)';
$string['stats_titre'] = 'Critères à retravailler';
$string['stats_intro'] = 'Réponses des étudiants à la grille d\'auto-évaluation, critère par critère, les plus souvent déclarés « à reprendre » ou « à consolider » en premier (§7.1). Les lignes surlignées dépassent 50 %.';
$string['stats_aucune'] = 'Aucune auto-évaluation enregistrée pour cet atelier.';
$string['stats_reponses'] = 'Réponses';
$string['stats_rubrique'] = 'Rubrique';
$string['stats_critere'] = 'Critère';
$string['stats_lien'] = 'Critères à retravailler';
$string['filtre_annee_optionnel'] = 'Année d\'étude (optionnel)';
$string['parcours_uc_verrouille'] = 'Ce parcours appartient à une activité SimHub d\'UC : son nom, son type et son cours se modifient depuis l\'activité.';
$string['import_type_ateliers'] = 'Fiches ateliers';
$string['import_type_localisation'] = 'Localisation (salle, zone, poste)';
$string['import_type_ressources'] = 'Liens vers les ressources';
$string['import_type_help'] = 'Ateliers : numéro et nom obligatoires ; un numéro déjà connu est mis à jour. Localisation : numéro, puis salle, zone, poste ou indication ; seules les colonnes présentes sont modifiées. Ressources : numéro, titre et adresse (URL), avec type et visibilité facultatifs ; une ressource de même titre est mise à jour. Rattachements : numéro, puis UC (identifiant ou nom abrégé du cours), année, cohorte, caractère obligatoire, niveau attendu. Parcours : nom du parcours, numéro, ordre, obligatoire (oui/non).';
$string['import_lancer'] = 'Importer';
$string['import_format_refuse'] = 'Format non reconnu : utilisez un fichier CSV, XLSX ou ODS.';
$string['import_bilan_crees'] = '{$a} créé(s)';
$string['import_bilan_majs'] = '{$a} mis à jour';
$string['import_bilan_parcours'] = '{$a} parcours créé(s)';
$string['import_ligne_ressource'] = 'Ligne {$a} : titre ou adresse (URL) manquant ou invalide.';
$string['privacy:metadata:local_simhub_asv_valanimal:emailvalidateur'] = 'L\'adresse e-mail du validateur, à laquelle le lien de validation a été envoyé.';
$string['asv_email_validateur'] = 'Adresse e-mail du validateur';
$string['asv_email_validateur_help'] = 'Adresse professionnelle du vétérinaire, maître de stage ou encadrant autorisé qui a supervisé le geste. Le lien de validation lui est envoyé par e-mail ; vous ne le recevez pas. Cette adresse figure dans votre livret.';
$string['asv_email_etudiant_refuse'] = 'Indiquez l\'adresse du validateur, pas la vôtre.';
$string['asv_envoyer_lien'] = 'Envoyer le lien au validateur';
$string['asv_envoyer_autre_adresse'] = 'Envoyer à une autre adresse (le lien précédent ne fonctionnera plus)';
$string['asv_renvoyer_lien'] = 'Renvoyer l\'e-mail';
$string['asv_lien_envoye'] = 'Le lien de validation a été envoyé à {$a}.';
$string['asv_lien_non_envoye'] = 'L\'e-mail n\'a pas pu être envoyé à {$a}. Vérifiez l\'adresse ou contactez l\'administrateur.';
$string['asv_demande_envoyee'] = 'Demande envoyée à {$a->email}. Le lien est valable jusqu\'au {$a->date}.';
$string['asv_deja_valide_animal'] = 'Cet acte est déjà validé sur animal vivant.';
$string['asv_autovalidation_interdite'] = 'Vous ne pouvez pas valider vous-même vos propres actes. Ce lien est destiné au vétérinaire, maître de stage ou encadrant qui a supervisé le geste.';
$string['asv_mail_sujet'] = 'Validation d\'un acte ASV sur animal vivant : {$a->etudiant}';
$string['asv_mail_corps'] = 'Bonjour,

{$a->etudiant} vous demande de valider l\'acte suivant, réalisé sous votre supervision sur animal vivant :

{$a->acte}

Pour le valider, ouvrez ce lien (aucun compte n\'est nécessaire) :
{$a->lien}

Ce lien est valable jusqu\'au {$a->expire}. Ne le transmettez pas à l\'étudiant.
Si vous n\'avez pas supervisé ce geste, ignorez ce message.

{$a->site}';
