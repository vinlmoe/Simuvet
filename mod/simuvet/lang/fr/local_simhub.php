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

// Privacy API (RGPD) - libellés minimaux, à compléter avec classes/privacy/provider.php.
$string['privacy:metadata:local_simhub_session'] = 'Historique des réalisations d\'ateliers de simulation par l\'utilisateur.';
$string['privacy:metadata:local_simhub_ae_reponse'] = 'Réponses de l\'utilisateur aux critères d\'auto-évaluation guidée.';
$string['privacy:metadata:local_simhub_ae_bilan'] = 'Auto-bilans rédigés par l\'utilisateur en fin d\'atelier.';
$string['privacy:metadata:local_simhub_asv_valanimal'] = 'Données de validation ASV sur animal vivant, incluant l\'identité du validateur externe et sa signature.';
