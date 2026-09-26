# SimHub — plugin Moodle V1 en construction

Plugin Moodle local (`local_simhub`) issu du cahier des charges *SimHub
V0* (ENVA, ENVT, Oniris, VetAgro Sup — août 2026).

**Statut : V1 en construction, testée de bout en bout sur Moodle 4.5 LTS (PHP 8.3) et
Moodle 5.0 (PHP 8.4), PostgreSQL — voir « Vérification sur instance réelle » ci-dessous.**
Ce dépôt contient désormais une implémentation fonctionnelle du
périmètre "V1 indispensable" du cahier des charges (§13) : schéma de
données complet, capacités, fiche atelier avec CRUD gestionnaire,
accueil étudiant filtré, sessions et auto-évaluation guidée, module
ASV (validation simulation + animal vivant). Certains points restent
volontairement simplifiés ou en attente des retours des responsables
de salle (§15) — voir "Ce qui reste à faire" ci-dessous.

## Deux plugins : `local_simhub` et l'activité `mod_simhub`

Le cahier des charges (§10) laisse le choix de la forme au prestataire. SimHub en combine
deux :
- **`local/simhub`** porte tout ce qui est transversal à l'école : fiches ateliers, salle,
  ressources, QR codes, séances, grilles d'auto-évaluation, module ASV, accueil étudiant.
- **`mod/simhub`** est une activité que le **responsable d'UC ajoute lui-même dans le cours
  de son UC**. Elle porte le parcours d'ateliers de l'UC, le suivi de ses étudiants, la
  validation de leurs séances et la note (pourcentage d'avancement) dans le carnet du cours.

Ainsi, **un enseignant n'a de droits que sur les UC où il est inscrit comme enseignant**, et
en obtient sur une autre UC dès qu'il y est ajouté : aucun rôle système à attribuer.

**Important : les plugins doivent vivre dans `local/simhub/` et `mod/simhub/`** d'une
installation Moodle (les composants sont résolus à partir de ces chemins). Ce dépôt reproduit
directement cette arborescence. Copier les dossiers plutôt que de créer des liens
symboliques : `require(__DIR__ . '/../../config.php')` suit le chemin réel du fichier.

## Arborescence

```
local/simhub/
├── version.php                 Métadonnées du plugin
├── lib.php                     Callbacks Moodle (navigation, fichiers, visibilité ressources)
├── settings.php                 Page de réglages admin
├── index.php                    Accueil étudiant (§5) : filtres + cartes atelier
├── parcours.php                 Parcours côté étudiant : ordre, échéances, avancement (§8)
├── atelier.php                  Fiche atelier étudiant (§5.4 localisation, §5.5 ressources)
├── session.php                  Démarrage/fin de session + auto-évaluation guidée (§7, §5.6)
├── qr.php                       Point d'entrée QR code (§7)
├── session_code.php             Saisie du code de séance anti-faux-scan (§7.3)
├── manage/
│   ├── ateliers.php             Liste de gestion des fiches ateliers (§6)
│   ├── atelier_edit.php         Création/modification, gestion du statut indisponible (§6.1)
│   ├── atelier_plan.php          Positionnement du repère plan par simple clic (§5.4)
│   ├── atelier_qr.php             Lien de scan QR d'un atelier, régénération (§7)
│   ├── ae_modele_edit.php         Création/activation de la grille d'auto-évaluation (§5.6)
│   ├── ae_rubriques.php           Rubriques et critères de la grille (§5.6, §7.2)
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
├── js/vendor/qrcode.js             Bibliothèque QR vendorisée (MIT, §7)
├── thirdpartylibs.xml               Déclaration de la bibliothèque tierce
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
- **Menu de navigation** (`lib.php::local_simhub_extend_navigation`) :
  une entrée SimHub avec des sous-entrées qui n'apparaissent que si
  l'utilisateur a la capacité de gestion correspondante (§11) —
  `showinflatnavigation` est activé sur chaque nœud pour qu'il
  apparaisse dans la navigation "primaire" de Moodle 4 (barre du haut /
  menu déroulant) plutôt que seulement dans le tiroir latéral, où un
  nœud ajouté via `extend_navigation()` peut facilement passer
  inaperçu selon le thème. Ce menu reste un simple raccourci : chaque
  page vérifie sa propre capacité indépendamment (`require_capability`),
  y compris quand elle n'est pas atteinte depuis le menu.
- **Fiche atelier** (`atelier.php`) : bloc localisation (salle, zone,
  poste, plan + repère, §5.4) et bloc ressources visibles étudiant
  (§5.5), en respectant la visibilité `interne` des sources éditables
  (`lib.php::local_simhub_pluginfile`).
- **Sessions et auto-évaluation guidée** (`session.php`) : démarrage,
  fin, formulaire dynamique rubriques/critères à trois niveaux
  (Réussi/À consolider/À reprendre) et auto-bilan libre (§5.6, §7.2).
- **Gestion de la grille d'auto-évaluation** (`manage/ae_modele_edit.php`,
  `manage/ae_rubriques.php`) : création du modèle d'un atelier, ajout de
  rubriques (avec la rubrique dédiée aux erreurs/risques du §5.6) et de
  leurs critères observables — la partie manquait entièrement jusqu'ici :
  la grille était lisible et remplissable côté étudiant, mais rien ne
  permettait de la créer.
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

## Corrections d'une revue globale

Une relecture complète du dépôt (imports de classes, chaînes de langue,
capacités, échappement HTML, code mort) a permis de trouver et corriger
plusieurs bugs réels, au-delà des simples ajustements de confort :

- **Import de classe incorrect (fatal)** : trois fichiers
  (`manage/ressources.php`, `manage/ressource_edit.php`,
  `classes/form/ressource_form.php`) référençaient
  `local_simhub\record\ressource`, une classe qui n'existe pas (la
  bonne est `local_simhub\persistent\ressource`) — ces pages auraient
  provoqué une erreur fatale "Class not found" à l'exécution.
- **QR code jamais généré** : aucun code n'appelait jamais
  `qrtoken::get_ou_creer()`, donc `qr.php` n'avait jamais de jeton à
  reconnaître — un atelier ne pouvait en réalité jamais être scanné.
  Corrigé via un hook `atelier::after_create()` (le `before_create()`
  d'origine ne pouvait de toute façon pas fonctionner : l'id de
  l'atelier n'existe pas encore à ce stade). Une page
  `manage/atelier_qr.php` a été ajoutée pour afficher, imprimer et
  télécharger le QR code, généré entièrement côté navigateur via la
  bibliothèque vendorisée `js/vendor/qrcode.js` (qrcode-generator de
  Kazuhiko Arase, MIT — voir `thirdpartylibs.xml`), sans dépendance
  réseau ni service externe.
- **Champ "échéance" invisible** : `manage/parcours_ateliers.php` lisait
  un paramètre `echeance` côté serveur, mais le formulaire ne
  comportait aucun champ pour le saisir — impossible à renseigner
  depuis l'interface. Un champ date a été ajouté.
- **Événement et clôture de session incohérents sans grille
  d'auto-évaluation** : `session.php` déclenchait
  `session_completed` uniquement quand une grille existait, jamais
  sinon, et réécrivait `timeend` à chaque simple rechargement de la
  page. Corrigé pour ne clôturer et déclencher l'événement qu'une seule
  fois, avec ou sans grille.
- **Nom de cohorte non échappé** dans `manage/rattachements.php`
  (le tableau HTML de Moodle ne échappe pas ses cellules par défaut).
- **Code mort** : `atelier::get_actifs()`, devenu obsolète depuis
  l'introduction de `classes/local/atelier_filter.php`, n'était plus
  appelé nulle part — supprimé plutôt que laissé comme piège pour un
  futur mainteneur.
- Nettoyage de commentaires/TODO obsolètes (`lib.php`, persistent
  `atelier`) qui décrivaient un état antérieur du code déjà dépassé.

Cette revue a aussi vérifié systématiquement : que chaque `use
local_simhub\...` référence une classe qui existe réellement, que
chaque `get_string()` (y compris à clé dynamique) a une entrée dans les
deux fichiers de langue, que chaque capacité utilisée dans le code est
bien déclarée dans `db/access.php` et réciproquement, et que les noms
de table SQL utilisés correspondent au schéma.

**Deux séries de corrections signalées en usage réel** s'y ajoutent :

- **Paramètre requis manquant sur les formulaires écrits à la main** :
  contrairement aux formulaires basés sur `moodleform` (qui gèrent
  cela automatiquement), sept formulaires HTML bruts omettaient un
  champ caché pour un paramètre que la page exige dès sa première
  ligne (`required_param()`) — les soumettre faisait planter la page
  immédiatement. Touchés : ajout de rubrique/critère à une grille
  d'auto-évaluation (`manage/ae_modele_edit.php`,
  `manage/ae_rubriques.php` — signalé en usage réel), positionnement du
  repère sur le plan et régénération du QR (`manage/atelier_plan.php`,
  `manage/atelier_qr.php`), soumission de l'auto-évaluation et démarrage
  de session (`session.php`, `session_code.php`), et la page publique
  de validation ASV sur animal vivant (`asv/valider_animal.php`, la
  plus sensible : aucune connexion Moodle en secours si le jeton se
  perd).
- **Libellés génériques mal réutilisés** : de nombreux boutons/titres
  affichaient "Nouvel atelier" / "Modifier l'atelier" / "Atelier
  enregistré" sur des pages qui n'ont rien à voir avec un atelier
  (parcours, ressources, grille d'auto-évaluation, rattachements,
  sessions, validations ASV...). Remplacés par des libellés propres à
  chaque contexte, en réutilisant les chaînes génériques de Moodle
  (`edit`, `add`, `savechanges`, `changessaved`) là où c'est pertinent
  plutôt que de dupliquer du texte.

## Vérification sur instance réelle (septembre 2026)

Le plugin a été installé sur un Moodle 4.5 LTS vierge (PHP 8.3) et sur un Moodle 5.0
(PHP 8.4, installation puis mise à jour depuis la version précédente du plugin), puis
éprouvé par un navigateur automatisé (Playwright) avec quatre profils : administrateur,
étudiant (compte ordinaire, inscrit à une UC et membre d'une cohorte), encadrant (rôle
système « Encadrant SimHub », dont on vérifie aussi qu'il est refusé sur les pages de
gestion des ateliers, des actes ASV et de l'import) et validateur externe sans compte. Les 50 pages du plugin s'affichent sans erreur ni avertissement
PHP, et les 29 parcours fonctionnels suivants aboutissent : création/modification
d'atelier (dont passage en indisponible), ressource, grille d'auto-évaluation
(rubrique + critère), rattachement, parcours et composition avec échéance, repère plan,
régénération QR, code de séance, import CSV, acte ASV, validation ASV en simulation,
accueil et filtres étudiant, démarrage/fin de séance avec auto-évaluation, scan QR,
code de séance absent, demande et signature de validation animal vivant, file de
validation encadrant, attestations, suivi et tableaux de bord, exports CSV/PDF.

Bugs réels corrigés à cette occasion :

- **Aucun étudiant ne pouvait accéder à SimHub.** Les capacités étaient accordées au
  rôle `student`, qui n'existe que dans les cours ; or SimHub vérifie tout au niveau
  système. `view`, `startsession` et `submitautoeval` sont désormais accordées à
  l'utilisateur authentifié (archétype `user`), y compris sur une instance déjà
  installée (pas de mise à jour 2026092500).
- **Validation ASV animal vivant impossible** : la balise `<canvas>` de signature
  n'était pas fermée, si bien que le bouton d'envoi se retrouvait à l'intérieur du
  canvas et n'était jamais affiché. Le bouton affichait en outre « Valider en
  simulation » ; il indique maintenant « Valider cet acte ».
- **Doublons refusés brutalement par la base** : un numéro d'atelier ou un code d'acte
  ASV déjà utilisé dans l'établissement provoquait « Erreur d'écriture vers la base
  de données ». Les deux sont désormais signalés dans le formulaire.

## Droits par UC et catégorie SimHub (§10, §11)

Décisions validées le 26 septembre 2026 (rapport `docs/rapport_architecture_droits.pdf`).

**Chaque école a son propre Moodle.** Le code établissement (`envcode`) ne sert plus qu'à la
traçabilité : il est enregistré sur les ateliers, parcours et actes ASV, figure dans les
exports, mais ne filtre plus aucune liste et n'est plus saisi dans les formulaires.

**Deux contextes de droits** (`classes/local/contexte.php`, `classes/local/droits.php`) :

| Profil | Où sont ses droits | Qui les donne |
|---|---|---|
| Étudiant | Utilisateur authentifié + inscription au cours | Automatique |
| Enseignant | Rôle `teacher` dans le cours de l'UC (activité `mod_simhub`) | Inscription au cours |
| Responsable d'UC | Rôle `editingteacher` dans le cours de l'UC | Inscription au cours ; il ajoute l'activité |
| Gestionnaire de salle, formateur ASV | Rôle SimHub dans la **catégorie SimHub** | L'administrateur fonctionnel |
| Administrateur fonctionnel | Rôle SimHub dans la catégorie SimHub | Un administrateur Moodle, une fois |

- **Catégorie SimHub** : réglage `local_simhub/categoryid`. Les capacités transversales y
  sont vérifiées (`contexte::racine()`) ; sans réglage, c'est le niveau système, comme
  avant. Un rôle déjà attribué au système reste valable (le système est parent de la
  catégorie). Les fichiers restent stockés au contexte système (`contexte::fichiers()`).
- **Délégation** : l'administrateur fonctionnel reçoit `moodle/role:assign` et ne peut
  attribuer que les rôles SimHub, dans la catégorie (raccourci « Rôles SimHub »).
- **Enseignant d'UC** : il suit uniquement les inscrits de son cours, valide les séances d'un
  étudiant de son UC sur un atelier de son UC (où que la séance ait été lancée), et valide
  les actes ASV en simulation de ses étudiants.
- **Responsable d'UC** : choisit les ateliers de son UC (un rattachement au cours est créé ou
  retiré automatiquement) et modifie leur grille d'auto-évaluation. La grille étant partagée
  par toutes les UC, la page affiche l'auteur et la date de la dernière modification.
- **Validation ASV en simulation** : enseignants de l'UC *et* formateurs désignés dans la
  catégorie (rôle « Formateur SimHub »).

**Une séance, plusieurs UC.** L'étudiant scanne le QR code de l'atelier sans choisir d'UC.
L'état d'un atelier ne dépend que de ses séances sur cet atelier ; à chaque séance terminée
ou validée (événements `session_completed`, `session_validated`), l'observateur de
`mod_simhub` recalcule la note de l'étudiant dans **toutes** les UC qui contiennent l'atelier.
Modifier la composition d'un parcours (`parcours_updated`) recalcule toute l'UC.

**Note** : pourcentage d'avancement (ateliers obligatoires s'il y en a, sinon tous),
rapporté à la note maximale de l'activité. Les groupes du cours filtrent le suivi.

Vérifié sur Moodle 5.0 (PHP 8.4, PostgreSQL), en installation neuve et en mise à jour depuis
la version précédente : 31 contrôles de droits et de notes, 28 pages parcourues par
navigateur (responsable d'UC, enseignant, étudiant, gestionnaire, administrateur
fonctionnel, administrateur), validation d'une séance depuis le cours.

**Sauvegarde et restauration** : l'activité suit le cours (sauvegarde, restauration,
duplication, import). La composition du parcours est sauvegardée par numéro d'atelier et
retrouvée dans le référentiel du site ; un atelier absent est signalé dans le journal de
restauration. Les séances des étudiants restent dans SimHub : les notes sont recalculées
pour les inscrits du nouveau cours à la fin de la restauration.

## Conformité aux règles Moodle (septembre 2026)

| Contrôle | Résultat |
|---|---|
| Standard de code `moodle` (moodle-cs / phpcs), les deux plugins | 0 erreur, 0 avertissement |
| En-tête GPL et bloc `@package` / `@copyright` / `@license` | Tous les fichiers PHP et templates |
| Schéma (`admin/cli/check_database_schema.php`), installation neuve et mise à jour | « Database structure is ok » |
| API Privacy : test de conformité du cœur (`privacy/tests/privacy/provider_test.php`) | Passe ; `core_userlist_provider` ajouté, champs du personnel déclarés |
| PHPUnit `local_simhub` (confidentialité) et `mod_simhub` (droits, notes, sauvegarde) | 9 tests, 38 assertions |
| Templates Mustache : exemple de contexte rendu par Moodle | Les deux templates |
| Événements standard d'activité (`course_module_viewed`, `..._instance_list_viewed`) | Déclenchés |
| Requêtes compatibles toutes bases (pas de `DISTINCT` sur une colonne texte) | Corrigé dans l'observateur |
| Débogage développeur (`DEBUG_DEVELOPER`), 28 pages parcourues | Aucun avertissement |

Le détenteur du copyright indiqué (« Écoles nationales vétérinaires de France ») est à
confirmer. Les tests se lancent avec `vendor/bin/phpunit --testsuite local_simhub_testsuite`
et `--testsuite mod_simhub_testsuite` après `admin/tool/phpunit/cli/init.php`.

Points de bonnes pratiques restant ouverts :
- le JavaScript est intégré aux pages (QR code, signature, clic sur le plan) au lieu de
  modules AMD (`amd/src`) ;
- quelques formulaires sont écrits en HTML plutôt qu'avec `moodleform` (grille,
  composition de parcours, validation ASV) ;
- les classes Bootstrap 4 (`badge-warning`, `mr-2`...) restent acceptées par Moodle 5.0 ;
  les équivalents Bootstrap 5 sont à ajouter là où ils manquent.

## Internationalisation

Plus aucun texte affiché n'est écrit en dur dans le code : pages, formulaires, messages
d'import, tableaux de bord, template de l'accueil et documents PDF (livret, attestations)
passent tous par `lang/fr` et `lang/en` (345 chaînes, identiques dans les deux langues).
Les descriptions d'événements (journaux Moodle) sont en anglais, selon la convention
Moodle pour ces textes non traduits. Au passage, le filtre « niveau » de l'accueil
étudiant conserve désormais la valeur choisie après une recherche.

## Conformité des autres sections (§5 à §8, §12, RGPD)

Même démarche que pour l'ASV : relecture de chaque section contre les exigences citées
dans le code, correction des écarts, puis scénario automatisé dédié (18 contrôles, tous
passants sur Moodle 4.5 et 5.0).

| Écart corrigé | Section |
|---|---|
| « Mes parcours en cours » était toujours vide : aucune séance ne recevait de parcours. L'accueil propose désormais les parcours de la cohorte de l'étudiant, de ses UC et ceux qu'il a commencés, dès 0 %, avec la prochaine échéance (en rouge si dépassée). | §5.1, §8.1 |
| Nouvelle page parcours côté étudiant (`parcours.php`) : ateliers dans l'ordre, requis ou non, échéances, statut personnel, barre d'avancement et attestation une fois terminé. | §8 |
| La case « obligatoire » est prise en compte : s'il y a au moins un atelier obligatoire, seuls ceux-ci conditionnent l'achèvement et l'attestation ; sinon tous. Une seule règle (`classes/local/parcours_helper.php`) pour l'accueil, le suivi, le tableau de bord, l'export et l'attestation. | §8.1 |
| Le suivi d'un parcours lié à une UC inclut les inscrits de l'UC (en plus de la cohorte et des étudiants ayant commencé). | §8.1, §12.2 |
| Les ateliers indisponibles restent visibles de l'étudiant, avec le motif et la date de retour prévue, sans bouton « Commencer ». | §5.3, §6.1 |
| Passer un atelier en indisponible exige un motif (affiché aux étudiants) et permet une date de remise en service ; l'historique complet (début, motif, échéance, référent, état) s'affiche sur la fiche. | §6.1 |
| Aucune séance ne peut démarrer sur un atelier non actif (bouton, QR ou code de séance) ; une séance déjà en cours reste terminable. | §6.1, §7 |
| Un nouveau scan QR (ou clic sur « Commencer ») reprend la séance en cours au lieu d'en créer une seconde. | §7.1 |
| Une « source éditable » est toujours interne, même si « visible étudiant » est choisi, et n'est jamais listée ni servie à un étudiant. | §6.2 |
| Filtres discipline et espèce de l'accueil : listes des valeurs existantes, comparaison insensible à la casse et aux accents. | §5.2 |
| L'observation de l'encadrant ASV est déclarée au fournisseur RGPD. | RGPD |

## Module ASV : conformité au §9 (septembre 2026)

Relecture du module ASV contre les exigences du §9 citées dans le code, puis correction
des écarts, vérifiée par un scénario automatisé dédié (17 contrôles, tous passants sur
Moodle 4.5 et 5.0) :

1. **Ordre simulation → animal vivant imposé côté serveur** (§9.1) : une demande de
   validation sur animal vivant est refusée tant que l'acte n'est pas validé en
   simulation, même en appelant la page directement ; un refus en simulation ne la
   débloque pas.
2. **Une seule demande active par acte** : revenir sur la page réaffiche le même lien
   (avec sa date d'expiration) au lieu d'en générer un nouveau à chaque visite.
3. **Signature obligatoire** (§9.3) : le navigateur bloque l'envoi sans tracé, et le
   serveur refuse aussi toute signature vide ou invalide, avec un message explicite.
4. **Signature dans le livret PDF** (§9.1 « date et signature ») : le tracé du
   validateur apparaît sous la date et son nom ; le nom de l'encadrant figure aussi pour
   la validation en simulation.
5. **Refus et annulation** : l'encadrant enregistre « validé » ou « non validé (à
   reprendre) » avec une observation. Une validation saisie par erreur peut être
   annulée depuis la fiche de l'étudiant, avec un motif obligatoire. Elle reste tracée
   en base (statut `annule`, motif, auteur, date) mais ne compte plus pour le livret ni
   pour la certification.
6. **Pilotage par étudiant** (§9.4) : la page ASV encadrant liste les étudiants
   (filtrables par cohorte/promotion) avec leurs validations en simulation et sur animal
   vivant, leurs demandes en attente et les certifications acquises. Chaque nom ouvre une
   fiche ASV (`asv/etudiant.php`) : état acte par acte, livret PDF, attestations et
   annulations. La synthèse par acte compte désormais des étudiants distincts.
7. **Demandes en attente visibles par l'étudiant**, avec leur date d'expiration et un
   lien pour réafficher le lien de validation.

**Certification globale de fin de A3** (§9.4) : l'attestation A3 exige désormais la
validation, en simulation puis sur animal vivant, de **tous les actes A1, A2 et A3**, et
le document l'indique explicitement. Les attestations A1 et A2 restent disponibles comme
étapes intermédiaires. La génération groupée et le badge du niveau A3 suivent la même
règle.

Au passage : les identifiants renvoyés par PostgreSQL (chaînes) sont normalisés avant
comparaison dans le calcul de certification, et les appels CSV précisent leur caractère
d'échappement (avertissement de dépréciation sous PHP 8.4).

## Navigation par domaines et onglets

- **Menus par domaine** : la barre interne ne présente plus une douzaine de boutons à
  plat mais un menu déroulant par domaine (Ateliers, Parcours, Séances, ASV), chacun
  surligné sur toutes ses sous-pages. Un domaine à une seule entrée reste un bouton.
  Le menu Séances porte une **pastille** avec le nombre de séances non vérifiées en
  attente de validation.
- **Onglets de fiche** : toutes les sous-pages d'un atelier (fiche, ressources, grille
  d'auto-évaluation, rattachements, plan, QR, validation ASV, vue étudiant, PDF) et d'un
  parcours (fiche, composition, suivi, export) partagent les mêmes onglets — on passe
  de l'une à l'autre sans revenir à la liste.
- **Listes** : dans la liste des ateliers et des parcours, le nom mène à la fiche et
  les liens « | » sont remplacés par un menu « Gérer », construit à partir des mêmes
  onglets.
- **Fiche atelier étudiant** : boutons « Commencer » / « Terminer et s'auto-évaluer »
  directement sur la fiche (page d'arrivée après un scan QR), et lien « Gérer cet
  atelier » pour les gestionnaires.
- **Plus d'identifiants à taper** : UC, étudiant et atelier se choisissent dans des
  listes avec recherche (`classes/local/selecteurs.php`) au lieu d'identifiants
  numériques Moodle (rattachements, parcours, actes ASV, validation en simulation,
  attestation A3).
- Fil d'Ariane dédoublonné (`navbar->ignore_active()`), et chaînes françaises codées en
  dur sur ces pages déplacées dans les fichiers de langue.

## Navigation interne (signalé en usage réel)

Aucune page du plugin n'appelait `$PAGE->navbar->add()` : le fil d'Ariane
Moodle s'arrêtait donc à « Accueil », sans indiquer dans quelle rubrique ni
sur quel atelier on se trouvait, et aucune page n'offrait de retour vers la
précédente. Ajout de `classes/local/navigation.php`, par lequel passent
désormais toutes les pages :

- `navigation::preparer($PAGE, $url, $titre, $etapes)` remplace la suite
  `set_context()/set_url()/set_pagelayout()/set_title()/set_heading()` et
  construit le fil d'Ariane complet — par exemple
  « Accueil / SimHub / Ateliers / Suture cutanée / QR code de l'atelier ».
  Les sous-pages d'un atelier affichent le nom de l'atelier, pas un libellé
  générique.
- `navigation::barre()`, affichée juste après `$OUTPUT->header()`, rend un
  bouton **Retour** (calculé à partir de la dernière étape cliquable du fil
  d'Ariane, donc jamais désynchronisé du chemin réel) et les raccourcis vers
  les sections auxquelles l'utilisateur a droit, la section courante étant
  surlignée.
- `navigation::sections()` est la source unique de vérité de cette liste :
  `local_simhub_extend_navigation()` (menu Moodle) la réutilise, les deux ne
  peuvent donc plus diverger. Chaque page reste protégée indépendamment par
  son propre `require_capability()`.

Titres de page revus au passage pour nommer l'activité en cours (« QR code de
l'atelier », « Ressources pédagogiques », « Repère sur le plan de salle »,
« Gérer les rubriques et critères »...) au lieu de répéter le nom de
l'atelier, désormais porté par le fil d'Ariane.

Deux pages restent volontairement hors de ce dispositif :
`asv/valider_animal.php` (page publique à jeton, mise en page `login`, sans
navigation Moodle) et les branches d'erreur des exports PDF.

### Entrée dans le menu du haut

Moodle 4 n'expose pas de hook pour la navigation primaire : selon le thème,
un nœud ajouté via `extend_navigation()` peut n'apparaître que dans le tiroir
latéral des cours. Pour un accès permanent depuis n'importe quelle page,
ajouter dans *Administration du site → Présentation → Réglages du thème →
Éléments de menu personnalisés* :

```
SimHub|/local/simhub/index.php
```

## Tables manquantes en base sur une instance déjà installée (signalé en usage réel)

Signalé ainsi : une rubrique ajoutée sur `manage/ae_rubriques.php` n'apparaissait jamais
dans la liste après validation, sans aucune erreur affichée.

Cause : plusieurs tables (`local_simhub_ae_modele`, `_ae_rubrique`, `_ae_critere`,
`_ae_reponse`, `_ae_bilan`, `_val_encadrant`, les tables ASV...) ont été ajoutées à
`db/install.xml` au fil du développement de ce squelette, après que l'instance de test
avait déjà installé une version antérieure du plugin. Moodle ne relit `install.xml` qu'à
l'installation initiale d'un plugin ; toute table ajoutée ensuite doit être créée via un
pas de `db/upgrade.php`, ce qui n'avait pas été fait ici : `version.php` n'avait jamais été
incrémenté malgré ces ajouts de tables. Sur une instance installée avant leur ajout, ces
tables n'existaient donc simplement pas — `$DB->insert_record()` sur une table absente
lève en principe une exception, mais selon la configuration d'affichage des erreurs du
site, celle-ci peut rester invisible côté utilisateur, qui ne voit alors que « rien ne
s'est passé ».

Premier correctif (version 2026090101) insuffisant : `install_from_xmldb_file()`
rejoue TOUTES les tables du fichier XML sans vérifier au préalable lesquelles existent
déjà, et échoue dès la première déjà en place (« Table ... already exists »), comme
constaté en usage réel lors de l'exécution de la mise à jour.

Correctif définitif (version 2026090102) : `db/upgrade.php` charge la structure du
fichier `db/install.xml` via `xmldb_file`/`getStructure()`, puis ne crée, une par une,
que les tables pour lesquelles `$dbman->table_exists()` renvoie faux — aucun risque pour
les tables déjà en place, aucun échec sur celles déjà créées. Après mise à jour du code,
il faut visiter *Administration du site → Notifications* pour que Moodle détecte le
changement de version et exécute cette mise à jour.

## Mise en page des pages de gestion (signalé en usage réel)

Les 17 pages sous `manage/` utilisaient `$PAGE->set_pagelayout('admin')`.
C'est la mise en page réservée aux écrans d'administration du site :
selon le thème, elle masque le tiroir de navigation latéral standard
et affiche le fil d'Ariane "Administration du site" à la place — ce
qui explique à la fois pourquoi SimHub restait invisible dans le menu
latéral une fois sur ces pages, et pourquoi le bandeau affiché n'avait
aucun rapport avec SimHub. Remplacé par `'standard'` partout, la mise
en page normale utilisée par le reste du plugin (`index.php`,
`atelier.php`, `asv/*.php`...).

## Génération du QR code (§7)

`manage/atelier_qr.php` affiche, imprime et permet de télécharger le
QR code d'un atelier, généré **entièrement côté navigateur** — aucune
donnée n'est envoyée à un service tiers. La bibliothèque
`js/vendor/qrcode.js` (paquet npm `qrcode-generator` v2.0.4, MIT,
Kazuhiko Arase) a été récupérée depuis le registre npm officiel et
vendorisée telle quelle (déclarée dans `thirdpartylibs.xml`, convention
standard des plugins Moodle) plutôt que réécrite de mémoire, pour
garantir un code réellement scannable. Elle est intégrée directement
dans la page (plutôt que via `$PAGE->requires->js()`) pour garantir
qu'elle est chargée avant le script qui l'utilise, sans dépendre de
l'ordre d'injection des scripts du thème.

**Bug corrigé (signalé en usage réel) : "Erreur d'écriture vers la
base de données" lors de la régénération.** `local_simhub_qrtoken`
porte une contrainte d'unicité sur `atelierid` (une seule ligne par
atelier), mais `qrtoken::regenerer()` désactivait l'ancienne ligne puis
tentait d'en insérer une nouvelle pour le même atelier — ce qui viole
cette contrainte. Corrigé pour mettre à jour la ligne existante en
place. `get_ou_creer()` répare aussi automatiquement toute ligne restée
à `actif=0` par une régénération antérieure ayant échoué à cause de ce
bug (elle rendait le scan silencieusement impossible, sans que rien
dans l'interface ne l'indique).

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
- Ce code a été vérifié sur Moodle 4.5 LTS et 5.0 (voir plus haut) ; reste à
  confirmer la version exacte de l'infrastructure EVE. Historiquement : tous
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
