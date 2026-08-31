# SimHub — plugin Moodle V1 en construction

Plugin Moodle local (`local_simhub`) issu du cahier des charges *SimHub
V0* (ENVA, ENVT, Oniris, VetAgro Sup — août 2026).

**Statut : V1 en construction, non testé sur instance Moodle réelle.**
Ce dépôt contient désormais une implémentation fonctionnelle du
périmètre "V1 indispensable" du cahier des charges (§13) : schéma de
données complet, capacités, fiche atelier avec CRUD gestionnaire,
accueil étudiant filtré, sessions et auto-évaluation guidée, module
ASV (validation simulation + animal vivant). Certains points restent
volontairement simplifiés ou en attente des retours des responsables
de salle (§15) — voir "Ce qui reste à faire" ci-dessous.

## Pourquoi un plugin `local` et pas un bloc ou une activité

Le cahier des charges (§10) laisse ce choix au prestataire. Un plugin
`local` a été retenu ici car SimHub :
- n'est pas rattaché à un cours ou une activité Moodle en particulier
  (les ateliers sont transverses à toutes les UC) ;
- a besoin de ses propres tables et pages, avec un contexte système ;
- doit rester un point d'entrée central, tout en consommant les rôles,
  cohortes, UC et carnet de notes Moodle par API plutôt qu'en devenant
  lui-même une activité de cours.

Ce choix est un point de départ raisonnable, pas un arbitrage définitif
— à confirmer une fois les contraintes de l'infrastructure EVE connues.

**Important : le plugin doit vivre dans `local/simhub/`** dans une
installation Moodle (le composant `local_simhub` est résolu par
Moodle à partir de ce chemin). Ce dépôt place directement ce dossier
à la racine.

## Arborescence

```
local/simhub/
├── version.php                 Métadonnées du plugin
├── lib.php                     Callbacks Moodle (navigation, fichiers, visibilité ressources)
├── settings.php                 Page de réglages admin
├── index.php                    Accueil étudiant (§5) : filtres + cartes atelier
├── atelier.php                  Fiche atelier étudiant (§5.4 localisation, §5.5 ressources)
├── session.php                  Démarrage/fin de session + auto-évaluation guidée (§7, §5.6)
├── qr.php                       Point d'entrée QR code (§7)
├── session_code.php             Saisie du code de séance anti-faux-scan (§7.3)
├── manage/
│   ├── ateliers.php             Liste de gestion des fiches ateliers (§6)
│   ├── atelier_edit.php         Création/modification, gestion du statut indisponible (§6.1)
│   ├── atelier_plan.php          Positionnement du repère plan par simple clic (§5.4)
│   ├── atelier_fiche_pdf.php     Export PDF imprimable d'une fiche atelier (§12.3)
│   ├── parcours_attestation_pdf.php  Attestation PDF de fin de parcours, si 100% d'avancement (§8.1)
│   ├── ressources.php            Liste des ressources d'un atelier (§6.2)
│   ├── ressource_edit.php        Ajout/modification d'une ressource, avec upload de fichier
│   ├── rattachements.php         Rattachement UC/année/cohorte d'un atelier hors parcours (§6)
│   ├── import.php                Import CSV souple : ateliers, rattachements, parcours (§12.1)
│   ├── export.php                Export CSV : liste des ateliers, suivi de parcours (§12.3)
│   ├── parcours.php              Liste des parcours pédagogiques (§8)
│   ├── parcours_edit.php         Création/modification d'un parcours
│   ├── parcours_ateliers.php     Composition d'un parcours (ajout/ordre/retrait d'ateliers)
│   ├── parcours_suivi.php        Suivi de progression par étudiant (§8.1)
│   ├── dashboard.php              Tableau de bord par parcours/cohorte (§12.2)
│   ├── dashboard_salle.php        Tableau de bord responsable de salle (§12.2)
│   ├── seancecode_generer.php    Génération d'un code de séance par salle (§7.3)
│   └── sessions_a_valider.php    File d'attente de validation manuelle par un encadrant (§7.3)
├── asv/
│   ├── index.php                 Pilotage du parcours ASV (§9.4)
│   ├── valider_simulation.php    Validation ASV en simulation par un encadrant (§9.2)
│   ├── demander_validation_animal.php  Génération du lien de validation animal vivant (§9.3)
│   ├── valider_animal.php        Page publique à jeton, sans compte Moodle (§9.3)
│   ├── livret_pdf.php            Export PDF du livret de compétences ASV (§9.4)
│   └── attestation_pdf.php       Attestation PDF de certification globale par niveau (§9.4)
├── db/
│   ├── install.xml               Schéma complet des 18 tables (V1)
│   ├── access.php                Capacités, mappées aux profils du §11
│   └── upgrade.php                Squelette de migration (vide : pas encore mis en production)
├── lang/
│   ├── fr/local_simhub.php        Chaînes françaises (langue principale)
│   └── en/local_simhub.php        Chaînes anglaises (obligatoires Moodle)
├── classes/
│   ├── persistent/                Entités avec cycle de vie propre (atelier, parcours,
│   │                               ressource, session, ae_modele, asv_acte) : validation
│   │                               de champs via core\persistent.
│   ├── record/                    Entités plus légères (liaisons, historiques, réponses)
│   │                               sans timemodified/usermodified : accès $DB direct.
│   ├── local/atelier_filter.php   Filtres de recherche étudiant (§5.2)
│   ├── local/atelier_importer.php Import CSV souple des ateliers (§12.1)
│   ├── local/liaison_importer.php Import CSV des rattachements et de la composition de parcours
│   ├── local/pdf_helper.php       En-tête PDF commun (logo + nom d'établissement, §4)
│   ├── local/annee_resolver.php   Libellés/options d'année d'étude A1-A5 (saisie directe)
│   ├── local/cohort_helper.php    Sélection directe d'une cohorte Moodle à recommander (§5.1)
│   ├── local/badge_helper.php     Délivrance d'un badge Moodle existant (§13)
│   ├── output/                    Renderer + classe templatable de l'accueil étudiant
│   ├── form/atelier_form.php      Formulaire moodleform de la fiche atelier
│   ├── event/                     Événements métier (atelier créé, session, validations ASV)
│   └── privacy/provider.php       Fournisseur RGPD (export/suppression des données perso)
├── templates/
│   └── student_home.mustache      Template de l'accueil étudiant
└── README.md                      Ce document
```

## Schéma de données (`db/install.xml`)

18 tables couvrant l'intégralité du périmètre V1 décrit dans le cahier
des charges :

| Table | Section du CdC | Contenu |
|---|---|---|
| `local_simhub_atelier` | §6 | Fiche atelier : identification, statut, localisation |
| `local_simhub_indispo` | §6.1 | Historique des indisponibilités |
| `local_simhub_ressource` | §6.2 | Ressources étudiantes + sources éditables |
| `local_simhub_parcours` / `local_simhub_parc_atelier` | §8 | Parcours et composition |
| `local_simhub_rattachement` | §6 | Rattachement UC / année / cohorte |
| `local_simhub_session` | §7.1 | Réalisation d'un atelier par un étudiant |
| `local_simhub_ae_modele` / `_rubrique` / `_critere` / `_reponse` / `_bilan` | §5.6, §7.2 | Auto-évaluation guidée (modèle + réponses) |
| `local_simhub_val_encadrant` | §7.3 | Validation par un encadrant |
| `local_simhub_asv_acte` / `_valsim` / `_valanimal` | §9 | Module ASV et livret numérique |
| `local_simhub_qrtoken` | §7 | Jeton QR par atelier |
| `local_simhub_seancecode` | §7.3 | Code de séance (anti-faux-scan) |

`local_simhub_ressource`, `local_simhub_session` et
`local_simhub_asv_acte` portent désormais un champ `usermodified` en
plus de `timecreated`/`timemodified`, ajouté pour qu'elles puissent
s'appuyer sur `core\persistent` comme `atelier`, `parcours` et
`ae_modele`. Les tables plus légères (liaisons N-N, historiques,
réponses d'auto-évaluation, validations) n'ont pas ce triptyque complet
et sont gérées par des classes `classes/record/*` en accès `$DB`
direct plutôt que par `core\persistent`. `local_simhub_parcours` porte
également un champ `badgeid`, nullable, référençant un badge de site
Moodle existant à délivrer à la réalisation complète du parcours (§13).

Points d'attention repris du cahier des charges et déjà traduits dans
le schéma :
- **numéro d'atelier invariant** (`numero`), distinct du statut —
  §5.3 insiste sur ce point ;
- **`envcode`** sur les tables de référentiel (`atelier`, `parcours`,
  `asv_acte`) pour respecter le principe *"Paramétrable ENVF"* (§4) :
  chaque établissement gère ses propres données dans une base
  partagée, sans les mélanger ;
- **validation externe ASV sans compte Moodle** : `asv_valanimal` porte
  un `token` (accès par lien) plutôt qu'un `userid` pour le
  validateur, conformément au §9.3 (*"pas nécessairement inscrit dans
  Moodle"*) ;
- **pas de ticketing** : `local_simhub_indispo` reste volontairement
  plate (commentaire, référent, échéance), sans statuts de workflow,
  conformément au principe de maîtrise du périmètre (§14).

## Ce qui est fonctionnel

- Schéma de base de données complet et installable (`db/install.xml`,
  format XMLDB validé).
- Capacités (`db/access.php`) couvrant les six profils du §11.
- Toutes les classes persistent/record du modèle de données (§6-§9),
  avec les opérations de base attendues par le cahier des charges
  (démarrer/terminer une session, ouvrir/clôturer une indisponibilité,
  générer un jeton QR, créer une demande de validation animal vivant...).
- `classes/privacy/provider.php` : export et suppression RGPD pour les
  sessions, auto-évaluations, validations et données de validateur ASV.
- Événements métier (`classes/event/`) pour atelier créé, session
  démarrée/terminée, validations ASV.
- **Accueil étudiant** (`index.php`) : les sept sections du §5.1 — à
  faire pour mes UC (via les rattachements et les UC où l'étudiant est
  inscrit), mes parcours en cours (avec % d'avancement), parcours ASV
  (résumé actes validés/total), ateliers déjà commencés, ateliers à
  reprendre, recommandés pour mon groupe (voir ci-dessous), et enfin la
  liste complète avec formulaire de filtres (mot-clé, discipline,
  espèce, niveau, durée max, année) + cartes atelier avec statut
  personnel et boutons localisation/ressources/commencer-terminer
  (§5.2, §5.3).
- **Recommandation directe par cohorte Moodle** (`classes/local/cohort_helper.php`) :
  plutôt que de déduire l'année d'étude d'un étudiant par inférence sur
  le nom de ses groupes, le gestionnaire choisit directement, dans une
  liste réelle de cohortes existantes, celle(s) à qui un atelier ou un
  parcours est recommandé (`manage/rattachements.php`,
  `classes/form/parcours_form.php`) ; la correspondance avec l'étudiant
  se fait ensuite par appartenance effective à la cohorte
  (`cohort_members`), sans réflexion. L'année d'étude (A1 à A5, via
  `classes/local/annee_resolver.php`) reste un champ de classement
  saisi directement par le gestionnaire, indépendant de ce mécanisme.
- **Fiche atelier** (`atelier.php`) : bloc localisation (salle, zone,
  poste, plan + repère, §5.4) et bloc ressources visibles étudiant
  (§5.5), en respectant la visibilité `interne` des sources éditables
  (`lib.php::local_simhub_pluginfile`).
- **Sessions et auto-évaluation guidée** (`session.php`) : démarrage,
  fin, formulaire dynamique rubriques/critères à trois niveaux
  (Réussi/À consolider/À reprendre) et auto-bilan libre (§5.6, §7.2).
- **Gestion des ateliers** (`manage/`) : liste + formulaire de
  création/modification, avec ouverture/clôture automatique de
  l'indisponibilité selon le changement de statut (§6.1).
- **Module ASV** (`asv/`) : vue de progression étudiante ou de
  pilotage (§9.4), validation en simulation par un encadrant (§9.2),
  génération d'un lien de validation animal vivant et page publique à
  jeton avec signature au doigt (`<canvas>` vanilla JS, §9.3).
- **QR code et contrôle anti-faux-scan** (`qr.php`, `session_code.php`,
  `manage/seancecode_generer.php`, `manage/sessions_a_valider.php`, §7.3) :
  si `local_simhub/controlepresenceactif` est désactivé, le scan
  démarre directement la session ; sinon l'étudiant est renvoyé vers
  une page de saisie du code de séance généré par l'encadrant. Jamais
  bloquant de façon absolue : sans code, l'étudiant peut tout de même
  démarrer sa session, marquée non vérifiée, et elle apparaît alors
  dans une file d'attente que tout profil `validatesession` peut
  valider ou refuser manuellement.
- **Import CSV** (`manage/import.php`, `classes/local/atelier_importer.php`) :
  import souple des ateliers avec reconnaissance d'alias de colonnes
  (accents/casse/espaces ignorés) pour s'adapter aux tableaux
  hétérogènes des quatre écoles (§12.1) ; upsert par (établissement,
  numéro) pour permettre des imports répétés sans doublons.
- **Export CSV** (`manage/export.php`) : liste des ateliers, et suivi
  de progression d'un parcours (§12.3).
- **Tableaux de bord** (§12.2) : `manage/dashboard.php` donne, pour
  chaque parcours, une vue d'ensemble par cohorte (étudiants n'ayant
  pas commencé / en cours / terminé, ateliers à reprendre, échéances
  proches ou dépassées, avancement moyen) plutôt que la seule liste de
  parcours ou le tableau brut par étudiant ; `manage/dashboard_salle.php`
  donne au responsable de salle des tuiles et des listes actionnables
  (ateliers en maintenance, sans ressource, sans rattachement UC, peu
  utilisés).
- **Badges Moodle** (`classes/local/badge_helper.php`, §13) : SimHub
  délivre un badge de site Moodle existant — jamais créé par le plugin
  lui-même — quand un étudiant génère l'attestation de fin de parcours
  (badge choisi parcours par parcours, `classes/form/parcours_form.php`)
  ou l'attestation de certification ASV par niveau (un réglage par
  niveau A1/A2/A3, `settings.php`). N'échoue jamais silencieusement la
  génération d'une attestation si les badges sont désactivés ou le
  badge introuvable.
- **Parcours pédagogiques** (`manage/parcours*.php`, §8) : création,
  composition (ajout/ordre/obligatoire), et suivi de progression par
  étudiant avec pourcentage d'avancement (§8.1), à partir des membres
  d'une cohorte Moodle si le parcours y est rattaché.
- **Ressources et plan de salle** (`manage/ressources.php`,
  `manage/ressource_edit.php`) : upload réel de fichiers (PDF, vidéo,
  image de plan) via l'API filestorage de Moodle (`filemanager`),
  avec visibilité étudiant/interne effective (§6.2), et upload de
  l'image de plan directement depuis la fiche atelier (§5.4).
- **Repère sur plan positionnable au clic** (`manage/atelier_plan.php`) :
  un clic sur l'image du plan déjà téléversée calcule et enregistre
  directement les coordonnées `planrepx`/`planrepy`, sans saisie
  manuelle de pourcentages (§5.4).
- **Rattachements pédagogiques** (`manage/rattachements.php`) : UI de
  gestion des liens atelier ↔ UC/année/cohorte hors parcours (§6),
  jusque-là seulement exploités en lecture par les filtres étudiants.
- **Import étendu** (`classes/local/liaison_importer.php`) : en plus
  des ateliers, import CSV des rattachements et de la composition de
  parcours (crée le parcours s'il n'existe pas), les deux retrouvant
  l'atelier par (établissement, numéro) déjà importé (§12.1).
- **Export PDF** (`asv/livret_pdf.php`, `manage/atelier_fiche_pdf.php`) :
  livret de compétences ASV (référentiel + validations simulation/animal
  vivant avec date et validateur, §9.4) et fiche atelier imprimable
  (§12.3), via le TCPDF fourni par Moodle (`lib/pdflib.php`).
- **Logo et nom d'établissement configurables** (`settings.php`,
  `classes/local/pdf_helper.php`) : un logo (PNG/JPG) et le nom complet
  de l'établissement, paramétrables par l'administrateur fonctionnel
  (§4 "Paramétrable ENVF"), affichés en en-tête de tous les documents
  PDF plutôt qu'un habillage générique SimHub.
- **Attestations de fin de parcours et de certification ASV**
  (`manage/parcours_attestation_pdf.php`, `asv/attestation_pdf.php`) :
  document PDF délivré uniquement si l'étudiant a effectivement terminé
  le parcours (100% d'avancement, §8.1) ou validé la totalité d'un
  niveau ASV en simulation **et** sur animal vivant (§9.1, §9.4
  "certification globale de fin de A3") — sinon la page affiche l'état
  d'avancement à la place du document.

## Ce qui reste à faire

**Import/export (§12)**
- [ ] Import XLSX natif (l'import actuel n'accepte que le CSV ; un
      export Excel non converti doit d'abord être enregistré en CSV).
- [ ] Export XLSX (seul CSV et PDF sont couverts pour l'instant).

**Ergonomie et robustesse**
- [ ] Web services / API externe (`classes/external/`) pour un futur
      composant mobile et pour la synchronisation QR code hors-ligne.
- [ ] Création/affectation des cohortes elles-mêmes : SimHub ne fait
      que lire les cohortes Moodle existantes et leurs membres pour la
      recommandation (§5.1) — leur création et l'inscription des
      étudiants restent de la gestion Moodle standard, hors périmètre
      de ce plugin.

**V1+ souhaitable (§13)**
- [ ] Reconnaissance réseau local pour le contrôle anti-faux-scan
      (§7.3) : seuls le code de séance et la validation encadrant sont
      implémentés pour l'instant ; le contrôle par plage IP de salle
      demanderait de connaître l'infrastructure réseau réelle des ENV.

**Hors périmètre V1** (rappel §14, pour éviter la dérive de périmètre)
Ticketing complet, mode OSCE, signature électronique qualifiée,
workflows d'approbation complexes, géolocalisation intérieure
sophistiquée, gestion documentaire avec versioning complet.

## Remarques pour l'équipe de développement

- Le format des identifiants de tables (`local_simhub_...`) respecte
  la limite de 28 caractères imposée par Moodle — vérifié pour chaque
  nom de table de ce schéma.
- Aucune dépendance à un plugin tiers n'est requise en V1 : cohortes,
  rôles, cours et carnet de notes Moodle natifs suffisent (§10).
- Ce code n'a pas été testé sur une instance Moodle réelle
  (environnement de génération sans runtime Moodle disponible) : tous
  les fichiers PHP passent `php -l` et `db/install.xml` est un XML
  bien formé, mais aucune vérification contre l'API Moodle réelle
  (signatures exactes, comportements de `core\persistent`,
  `moodleform`, `core_privacy`...) n'a pu être faite — en particulier
  `classes/local/badge_helper.php`, écrit d'après la classe `\badge`
  documentée de `lib/badgeslib.php` (`issue()`, `is_issued()`,
  `BADGE_TYPE_SITE`, `BADGE_STATUS_INACTIVE`) mais jamais exécutée
  contre un site avec les badges activés. Première étape
  indispensable avant d'aller plus loin : installer sur une instance
  de test (`php admin/cli/upgrade.php`) et corriger les éventuelles
  erreurs d'API.
- Avant tout développement UI substantiel supplémentaire, il reste
  conseillé d'attendre les retours des responsables de salle (§15 du
  cahier des charges) sur les filtres prioritaires, les grilles
  d'auto-évaluation existantes et le mode de validation ASV.
