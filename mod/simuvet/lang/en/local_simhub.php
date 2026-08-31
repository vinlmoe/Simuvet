<?php

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'SimHub';
$string['simhub:studenthome'] = 'My workshops';

$string['setting_envcode'] = 'Institution code';
$string['setting_envcode_desc'] = 'Short code identifying the veterinary school (e.g. ENVA, ENVT, ONIRIS, VETAGROSUP).';
$string['setting_seancecodeduration'] = 'Session code validity';
$string['setting_seancecodeduration_desc'] = 'How long a temporary session code (anti-fake-scan control) stays valid.';
$string['setting_asvtokenexpiry'] = 'ASV external validation link validity';
$string['setting_asvtokenexpiry_desc'] = 'How long the link sent to an external validator (vet, placement supervisor...) remains active.';
$string['setting_controlepresenceactif'] = 'Enable anti-fake-scan control';
$string['setting_controlepresenceactif_desc'] = 'If disabled, no presence check is required when scanning a workshop QR code.';

$string['statut_actif'] = 'Active';
$string['statut_non_utilise'] = 'Not in use';
$string['statut_indisponible'] = 'Unavailable';
$string['statut_archive'] = 'Archived';

$string['niveau_reussi'] = 'Achieved';
$string['niveau_a_consolider'] = 'To consolidate';
$string['niveau_a_reprendre'] = 'To redo';

$string['simhub:view'] = 'View SimHub';
$string['simhub:startsession'] = 'Start / end a workshop';
$string['simhub:submitautoeval'] = 'Submit a guided self-assessment';
$string['simhub:viewprogression'] = 'View student progression';
$string['simhub:validatesession'] = 'Validate a workshop completion';
$string['simhub:exportsuivi'] = 'Export tracking data';
$string['simhub:manageparcours'] = 'Manage learning pathways';
$string['simhub:managerattachement'] = 'Manage pedagogical links';
$string['simhub:manageateliers'] = 'Manage workshop records';
$string['simhub:manageressources'] = 'Manage learning resources';
$string['simhub:managestatuts'] = 'Manage statuses and unavailability';
$string['simhub:manageqrcodes'] = 'Manage QR codes';
$string['simhub:importexport'] = 'Import / export data';
$string['simhub:manageasv'] = 'Administer the ASV module';
$string['simhub:validateasvsimulation'] = 'Validate an ASV act in simulation';
$string['simhub:configure'] = 'Configure SimHub';

$string['privacy:metadata:local_simhub_session'] = 'History of simulation workshops completed by the user.';
$string['privacy:metadata:local_simhub_ae_reponse'] = 'User answers to self-assessment criteria.';
$string['privacy:metadata:local_simhub_ae_bilan'] = 'End-of-workshop self-assessment summaries written by the user.';
$string['privacy:metadata:local_simhub_asv_valanimal'] = 'ASV live-animal validation data, including external validator identity and signature.';
