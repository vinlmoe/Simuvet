# SimHub — squelette technique V0

Squelette de plugin Moodle local (`local_simhub`) issu du cahier des
charges *SimHub V0* (ENVA, ENVT, Oniris, VetAgro Sup — août 2026).

**Statut : cadrage technique, non fonctionnel.** Ce squelette pose la
structure du plugin, le schéma de base de données complet et les
capacités (droits), afin de servir de point de départ concret à
l'équipe de développement. Il ne contient **pas** d'interface aboutie
ni de logique métier complète : ceux-ci restent à construire une fois
le cahier des charges validé par les responsables des salles de
simulation (§15 du document).

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

## Arborescence

```
local_simhub/
├── version.php              Métadonnées du plugin
├── lib.php                  Callbacks Moodle (navigation, fichiers)
├── settings.php              Page de réglages admin
├── index.php                 Stub de page d'accueil (à développer)
├── db/
│   ├── install.xml           Schéma complet des 18 tables (V1)
│   ├── access.php            Capacités, mappées aux profils du §11
│   └── upgrade.php           Squelette de migration (vide en V0)
├── lang/
│   ├── fr/local_simhub.php   Chaînes françaises (langue principale)
│   └── en/local_simhub.php   Chaînes anglaises (obligatoires Moodle)
├── classes/
│   └── persistent/
│       └── atelier.php       Classe persistent de référence
└── README.md                 Ce document
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

## Ce qui est fonctionnel dans ce squelette

- Le schéma de base de données est complet et installable tel quel sur
  une instance Moodle (`db/install.xml` respecte le format XMLDB).
- Les capacités (`db/access.php`) couvrent les six profils du §11.
- `classes/persistent/atelier.php` est une classe persistent complète
  et utilisable (validation des champs, get_actifs()), à prendre comme
  modèle pour les autres entités.
- `index.php` affiche une liste brute des ateliers actifs — juste assez
  pour vérifier que le plugin s'installe et interroge la base
  correctement.

## Ce qui reste à faire (feuille de route suggérée)

Reprise de la priorisation du cahier des charges (§13) :

**Sprint 0 — fondations techniques**
- [ ] Classes persistent restantes (`parcours`, `session`, `ressource`,
      `asv_acte`, etc.), sur le modèle de `atelier.php`.
- [ ] `classes/privacy/provider.php` (obligatoire pour tout plugin
      Moodle traitant des données personnelles — sessions, auto-évaluations,
      signatures ASV).
- [ ] `classes/event/` pour les événements métier (atelier créé,
      session terminée, validation ASV...) — utile pour la messagerie
      et les logs Moodle.
- [ ] Web services / API externe (`classes/external/`) pour le futur
      composant mobile éventuel et pour la synchronisation QR code.

**V1 indispensable**
- [ ] Fiche atelier (CRUD gestionnaire) + statuts + indisponibilités.
- [ ] Ressources (upload, visibilité étudiant/interne).
- [ ] Accueil étudiant personnalisé (§5.1) avec les sept sections
      listées dans le cahier des charges.
- [ ] Filtres et recherche (§5.2) — probablement une interface JS
      (module AMD) au-dessus d'un web service de recherche.
- [ ] Localisation simple (plan + repère éditable, §5.4).
- [ ] Génération et lecture de QR codes (librairie externe à choisir :
      la génération peut se faire côté PHP, la lecture côté client en
      JS/AMD via l'appareil photo).
- [ ] Auto-évaluation guidée (formulaire dynamique à partir du modèle
      rubriques/critères, affichage vertical mobile-first — §7.2).
- [ ] Rattachement pédagogique et parcours (UI de gestion, ordre des
      ateliers).
- [ ] Validation encadrant.
- [ ] Import (CSV/XLSX hétérogènes, §12.1) et exports de base.
- [ ] Module ASV V1 : validation en simulation + validation animal
      vivant (page à jeton, hors authentification Moodle).

**V1+ souhaitable**
- [ ] Contrôle anti-faux-scan (réseau local / code de séance / mixte,
      §7.3).
- [ ] Génération PDF (fiches, attestations, livret ASV).
- [ ] Badges Moodle.
- [ ] Tableaux de bord par cohorte/parcours (§12.2).

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
- Ce squelette n'a pas été testé sur une instance Moodle réelle
  (environnement de génération sans runtime PHP/Moodle disponible). Une
  première étape de développement doit être de l'installer sur une
  instance de test et de corriger les éventuelles erreurs de syntaxe ou
  d'API avant d'aller plus loin.
- Avant tout développement UI substantiel, il est fortement conseillé
  d'attendre les retours des responsables de salle (§15 du cahier des
  charges) sur les filtres prioritaires, les grilles d'auto-évaluation
  existantes et le mode de validation ASV — plusieurs choix
  structurants de schéma (ex. multiplicité des espèces par atelier)
  pourraient évoluer selon ces retours.
