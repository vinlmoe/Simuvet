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
├── manage/
│   ├── ateliers.php             Liste de gestion des fiches ateliers (§6)
│   └── atelier_edit.php         Création/modification, gestion du statut indisponible (§6.1)
├── asv/
│   ├── index.php                 Pilotage du parcours ASV (§9.4)
│   ├── valider_simulation.php    Validation ASV en simulation par un encadrant (§9.2)
│   ├── demander_validation_animal.php  Génération du lien de validation animal vivant (§9.3)
│   └── valider_animal.php        Page publique à jeton, sans compte Moodle (§9.3)
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
direct plutôt que par `core\persistent`.

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
- **Accueil étudiant** (`index.php`) : formulaire de filtres (mot-clé,
  discipline, espèce, niveau, durée max) + cartes atelier avec statut
  personnel, boutons localisation/ressources/commencer-terminer (§5.2,
  §5.3).
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
- **QR code** (`qr.php`) : point d'entrée qui démarre une session à
  la volée, avec accroche pour le contrôle anti-faux-scan (§7.3) déjà
  paramétrable (`local_simhub/controlepresenceactif`).

## Ce qui reste à faire

**Import/export (§12)**
- [ ] Import initial CSV/XLSX hétérogène des listes d'ateliers,
      salles, rattachements (§12.1).
- [ ] Exports CSV/XLSX/PDF par étudiant, UC, parcours, cohorte (§12.3).
- [ ] Génération PDF (fiches, attestations, livret ASV).

**Ergonomie et robustesse**
- [ ] Upload effectif des ressources et du plan de salle (formulaires
      actuels acceptent des `fileitemid`/URLs, mais aucune UI d'upload
      avec `file_manager`/`filepicker` n'est encore branchée).
- [ ] Positionnement du repère sur le plan de salle au clic (actuellement
      deux champs numériques `planrepx`/`planrepy`).
- [ ] Accueil étudiant : les sept sections personnalisées du §5.1 ("À
      faire pour mes UC", "Mes parcours en cours", "Parcours ASV"...)
      restent à construire au-dessus du filtre générique actuel, une
      fois les rattachements réels connus après import.
- [ ] UI de gestion des parcours et rattachements (actuellement classes
      `record/parc_atelier.php` et `record/rattachement.php` sans page
      dédiée).
- [ ] Web services / API externe (`classes/external/`) pour un futur
      composant mobile et pour la synchronisation QR code hors-ligne.

**V1+ souhaitable (§13)**
- [ ] Contrôle anti-faux-scan renforcé (réseau local / code de séance
      combinés — le code de séance existe côté modèle
      `record/seancecode.php` mais n'est pas encore relié à `qr.php`).
- [ ] Badges Moodle.
- [ ] Tableaux de bord par cohorte/parcours plus riches que la liste
      actuelle (§12.2).

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
  `moodleform`, `core_privacy`...) n'a pu être faite. Première étape
  indispensable avant d'aller plus loin : installer sur une instance
  de test (`php admin/cli/upgrade.php`) et corriger les éventuelles
  erreurs d'API.
- Avant tout développement UI substantiel supplémentaire, il reste
  conseillé d'attendre les retours des responsables de salle (§15 du
  cahier des charges) sur les filtres prioritaires, les grilles
  d'auto-évaluation existantes et le mode de validation ASV.
