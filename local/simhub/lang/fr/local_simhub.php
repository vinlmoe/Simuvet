<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SimHub';
$string['simhub:studenthome'] = 'Mon espace ateliers';

// Réglages.
$string['setting_envcode'] = 'Code établissement';
$string['setting_envcode_desc'] = 'Code court identifiant l\'école (ex. ENVA, ENVT, ONIRIS, VETAGROSUP). Utilisé pour distinguer les référentiels par établissement.';
$string['setting_seancecodeduration'] = 'Durée de validité d\'un code de séance';
$string['setting_seancecodeduration_desc'] = 'Durée pendant laquelle un code de séance temporaire (§7.3, contrôle anti-faux-scan) reste valable.';
$string['setting_asvtokenexpiry'] = 'Durée de validité d\'un lien de validation ASV externe';
$string['setting_asvtokenexpiry_desc'] = 'Durée pendant laquelle le lien envoyé à un validateur externe (vétérinaire, maître de stage...) reste actif avant expiration du jeton.';
$string['setting_controlepresenceactif'] = 'Activer le contrôle anti-faux-scan';
$string['setting_controlepresenceactif_desc'] = 'Si désactivé, aucune vérification de présence n\'est demandée lors du scan d\'un QR code (fonctionnalité classée V1+ souhaitable, §13).';

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

// Événements.
$string['event_atelier_created'] = 'Atelier créé';
$string['event_session_started'] = 'Session d\'atelier démarrée';
$string['event_session_completed'] = 'Session d\'atelier terminée';
$string['event_asv_valide_simulation'] = 'Acte ASV validé en simulation';
$string['event_asv_valide_animal'] = 'Acte ASV validé sur animal vivant';

// Gestion des ateliers.
$string['manage_ateliers'] = 'Gestion des ateliers';
$string['atelier_nouveau'] = 'Nouvel atelier';
$string['atelier_modifier'] = 'Modifier l\'atelier';
$string['atelier_enregistre'] = 'Atelier enregistré.';
$string['atelier_supprime'] = 'Atelier supprimé.';
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

// Module ASV (§9).
$string['asv_parcours'] = 'Parcours ASV';
$string['asv_livret'] = 'Livret de compétences ASV';
$string['asv_valider_simulation'] = 'Valider en simulation';
$string['asv_demander_validation_animal'] = 'Demander une validation sur animal vivant';
$string['asv_lien_valanimal'] = 'Lien de validation animal vivant';
$string['asv_formulaire_validateur_titre'] = 'Validation de l\'acte sur animal vivant';
$string['asv_champ_nom'] = 'Nom';
$string['asv_champ_prenom'] = 'Prénom';
$string['asv_champ_certification'] = 'Je certifie être vétérinaire ou encadrant autorisé à valider cet acte.';
$string['asv_champ_signature'] = 'Signature';
$string['asv_valide_avec_succes'] = 'Validation enregistrée. Merci.';
$string['asv_lien_invalide'] = 'Ce lien de validation est invalide ou a expiré.';
$string['asv_niveau_a1'] = 'A1';
$string['asv_niveau_a2'] = 'A2';
$string['asv_niveau_a3'] = 'A3';
