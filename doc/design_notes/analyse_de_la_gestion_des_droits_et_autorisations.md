# Analyse : Gestion des Droits, Rôles et Qualifications dans GVV

**Date :** 2026-06-12  
**Statut :** Analyse — pas de code modifié

---

## 1. Contexte

GVV gère deux catégories de concepts souvent confondus :

- **Les droits d'accès** : ce qu'un utilisateur est autorisé à voir ou modifier dans l'application.
- **Les qualifications** : les habilitations aéronautiques ou associatives d'un membre (brevet de pilote, qualification instructeur, visite médicale…).

Ces deux catégories se croisent : une qualification conditionne souvent un droit (seul un instructeur peut saisir une séance de formation), mais elles ont des natures différentes — les droits d'accès sont des décisions administratives attribuées par l'association, les qualifications sont des faits techniques avec des dates de validité.

GVV a accumulé cinq mécanismes distincts, nés à des époques différentes, pour gérer ces deux préoccupations. Leur coexistence est source de confusion pour les administrateurs, de bugs potentiels, et de complexité pour l'évolution du code.

---

## 2. État des lieux : les cinq mécanismes

### 2.1 Le champ de bits `mniveaux` — Qualifications déclaratives

**Emplacement :** colonne `membres.mniveaux` (INTEGER)  
**Label UI :** "Qualifications" (FR), "Qualifications" (EN), "Kwalificaties" (NL)

`mniveaux` est un entier dont chaque bit représente une qualification ou un rôle déclaré. Les constantes sont définies dans `application/config/constants.php` :

| Constante | Valeur | Nature |
|-----------|--------|--------|
| INTERNET | 1 | Accès web (obsolète ?) |
| PRESIDENT | 2 | Gouvernance |
| VICE_PRESIDENT | 4 | Gouvernance |
| TRESORIER | 8 | Gouvernance |
| SECRETAIRE | 16 | Gouvernance |
| SECRETAIRE_ADJ | 32 | Gouvernance |
| CA | 64 | Gouvernance |
| CHEF_PILOTE | 128 | Opérationnel |
| VI_PLANEUR | 256 | Qualification technique |
| VI_AVION | 512 | Qualification technique |
| MECANO | 1024 | Opérationnel |
| PILOTE_PLANEUR | 2048 | Qualification technique |
| PILOTE_AVION | 4096 | Qualification technique |
| REMORQUEUR | 8192 | Qualification technique |
| PLIEUR | 16384 | Opérationnel |
| ITP | 32768 | Instructeur Théorique Planeur |
| IVV | 65536 | Instructeur Vol à Voile |
| FI_AVION | 131072 | Flight Instructor Avion |
| FE_AVION | 262144 | Flight Examiner Avion |
| TREUILLARD | 524288 | Opérationnel |
| CHEF_DE_PISTE | 1048576 | Opérationnel |

**Usages actifs dans le code :**
- `membres_model::qualif_selector()` filtre les membres par bit pour construire des listes déroulantes.
- `Formation_access::is_instructeur()` (chemin legacy) détecte un instructeur par les bits ITP|IVV|FI_AVION|FE_AVION.
- `Formation_access::can_manage_programmes()` utilise le bit CA.
- `cartes_membre_model` trouve le président via `mniveaux & 2`.
- `programme.php` filtre les destinataires d'e-mail.

**Caractéristiques :**
- Purement déclaratif, sans date de validité.
- Mis à jour manuellement par un administrateur.
- Ne distingue pas les sections (un instructeur est instructeur globalement).
- Mélange dans un seul champ des rôles de gouvernance (Président, Trésorier) et des qualifications techniques (IVV, MECANO).

---

### 2.2 Le champ de bits `macces` — Responsabilités

**Emplacement :** colonne `membres.macces` (INTEGER)  
**Label UI :** "Responsabilités" (FR), "Responsabilities" (EN), "Verantwoordelijkheden" (NL)

Encodé et décodé par les mêmes helpers `array2int()`/`int2array()` que `mniveaux`. Son contenu est affiché dans le formulaire membre mais ses constantes ne sont pas définies dans `constants.php` — elles sont implicites et indocumentées.

**État dans le code :** `macces` est affiché dans la fiche membre et inclus dans la liste de champs lors de la fusion de membres, mais **aucune logique d'autorisation dans le code ne l'interroge**. Il est traité comme un champ informatif sans effet sur les accès.

**Conclusion :** `macces` est un champ vestigial sans sémantique opérationnelle claire. Sa distinction avec `mniveaux` n'est pas documentée ni respectée.

---

### 2.3 `user_roles_per_section` — Nouveau système d'autorisation par section

**Emplacement :** tables `user_roles_per_section` et `types_roles`  
**Bibliothèque :** `application/libraries/Gvv_Authorization.php`

Ce système associe explicitement un utilisateur à un rôle dans une section donnée. Les rôles définis dans `types_roles` :

| ID | Nom | Portée |
|----|-----|--------|
| 1 | user | Connexion, consultation personnelle |
| 2 | auto_planchiste | CRUD sur ses propres données |
| 5 | planchiste | CRUD données de vol |
| 6 | ca | Consultation complète (finances globales) |
| 7 | bureau | Consultation complète (finances personnelles) |
| 8 | tresorier | Modification données financières (une section) |
| 9 | super-tresorier | Modification données financières (toutes sections) |
| 10 | club-admin | Accès complet |
| 11 | instructeur | Gestion formations |
| 12 | mecano | Mécanicien |
| 17 | pilote_rem | Pilote remorqueur |

**Usages actifs :**
- `inst_selector()` et `pilrem_selector()` dans `membres_model` interrogent directement `user_roles_per_section` pour construire les listes d'instructeurs et de remorqueurs.
- `Formation_access::is_instructeur()` (chemin nouveau système).
- `Gvv_Controller::require_roles()` / `allow_roles()` pour la protection des contrôleurs migrés.
- `welcome.php` affiche des éléments de dashboard conditionnellement selon `has_role('instructeur')`.

**Migration progressive en cours :** le champ `use_new_auth` (par utilisateur) détermine quel système est utilisé. Phase M2 du plan de refactoring (env. 20 contrôleurs migrés sur ~53).

**Caractéristiques :**
- Non hiérarchique : chaque rôle est indépendant.
- Sensible à la section : un utilisateur peut être instructeur dans la section planeur et simple user dans la section ULM.
- Tracé (champs `granted_at`, `revoked_at`, `granted_by`).
- Ne contient pas de qualifications techniques avec dates de validité.

---

### 2.4 La table `events` — Qualifications datées et licences

**Emplacement :** tables `events` et `events_types`

La table `events` a été conçue initialement pour enregistrer des faits marquants du carnet de vol (gain de 1000 m, circuit de 300 km). Elle a été progressivement étendue pour stocker des qualifications et licences avec des dates de validité :

| Champ | Rôle |
|-------|------|
| `emlogin` | Pilote concerné |
| `etype` | Type d'événement (FK → `events_types`) |
| `edate` | Date de l'événement / obtention |
| `date_expiration` | Date d'expiration (si `events_types.expirable = 1`) |
| `ecomment` | Numéro de licence ou commentaire libre |

Types d'événements utilisés pour les qualifications :
- Visite médicale (identifié par `config('medical_id')`)
- BPP (Brevet Pilote Planeur)
- SPL, PPL
- Qualification instructeur
- Contrôle de compétence, Emport passager

**Usages actifs :**
- `event_model::medical_validity_date()` — retourne la date d'expiration de la dernière visite médicale valide.
- `event_model::inst_validity()` — retourne la date d'expiration de la qualification instructeur.
- `alarmes.php` utilise ces deux méthodes pour les alertes médicales et instructeur.
- `forms_public.php` extrait numéros de licence et dates pour remplir des formulaires PDF.

**Caractéristiques :**
- Chaque pilote n'a qu'un enregistrement par type d'événement (logique `replace` : supprime et recrée).
- Conçu pour les vols de référence, étendu par la contrainte pour stocker des licences.
- Pas de stockage de fichiers (pas de PDF attaché).
- Overlap potentiel avec `archived_documents`.

---

### 2.5 La table `archived_documents` — Gestion documentaire avec validité

**Emplacement :** tables `archived_documents` et `document_types`  
**Modèle :** `application/models/archived_documents_model.php`  
**Migrations :** 067+

Système plus récent (2026) conçu spécifiquement pour stocker des fichiers PDF associés à des entités (pilote, section, club), avec gestion des dates de validité, versionnage et alertes :

| Statut | Condition |
|--------|-----------|
| `active` | valid_until > aujourd'hui + alert_days |
| `expiring_soon` | valid_until dans la fenêtre d'alerte |
| `expired` | valid_until < aujourd'hui |
| `missing` | document requis absent |
| `pending` | en attente de validation |
| `rejected` | refusé |

`document_types` configure les alertes (`alert_days_before`), le caractère obligatoire (`required`), la portée (`scope` : pilot / section / club).

**Caractéristiques :**
- Stocke les fichiers physiques (PDF).
- Versionnage.
- Validation administrative (approbation/rejet).
- Peut désactiver une alarme unitairement (`alarm_disabled`).
- Conçu comme couche documentaire générique — intégré à la plateforme documentaire (`gestion_documentaire.md`).

---

### 2.6 Le système d'alarmes — Deux familles non unifiées

Deux systèmes d'alarme coexistent (cf. `gestion_alarmes_design.md`) :

1. **Alarmes à date fixe** (`archived_documents_model`) : statuts calculés depuis `valid_until`.
2. **Alarmes calculées** (`alarmes.php`) : expérience récente (atterrissages, heures), visite médicale, qualification instructeur, emport passager — calculées à la volée depuis le carnet de vol et `events`.

Un design d'`AlarmAggregator` est documenté mais pas encore implémenté.

---

## 3. Problèmes et incohérences

### 3.1 Confusion qualification / droit d'accès

`mniveaux` mélange deux natures distinctes :
- **Rôles de gouvernance** (Président, Trésorier, CA) → affectent les droits d'accès.
- **Qualifications techniques** (IVV, MECANO, REMORQUEUR) → décrivent des compétences, pas des accès.

Ces deux catégories n'ont pas la même durée de vie, ni le même mode de mise à jour, ni le même usage dans le code.

### 3.2 Redondance instructeur / remorqueur

Un instructeur est défini à deux endroits :
- Bit `IVV` ou `ITP` dans `mniveaux` (legacy).
- Entrée `types_roles_id = 11` dans `user_roles_per_section` (nouveau système).

Pendant la période de migration, les deux doivent rester synchronisés manuellement. `inst_selector()` utilise déjà exclusivement le nouveau système, tandis que `Formation_access::is_instructeur()` bascule selon `use_new_auth`. Une désynchronisation crée un comportement imprévisible.

### 3.3 `macces` sans sémantique opérationnelle

`macces` est affiché dans l'interface mais n'est consulté par aucune logique d'autorisation. Son contenu est indéfini (pas de constantes documentées). C'est un champ vestigial qui génère de la confusion pour les administrateurs.

### 3.4 Confusion events / archived_documents pour les licences

Le problème n'est pas seulement technique : c'est un problème d'expérience utilisateur.

Du point de vue d'un administrateur, **une licence est une entité unique** : un pilote a un PPL avec un numéro, une date d'obtention, une date de validité, et éventuellement une copie PDF. Que le PDF soit disponible ou non ne change pas la nature de la licence. Pourtant le système actuel oblige à deux interactions séparées sur deux pages différentes :
- Modifier la date de validité → passer par `events`.
- Déposer ou mettre à jour le PDF → passer par `archived_documents`.

Cette séparation est un artefact d'implémentation, pas une nécessité métier. Elle est source de confusion et risque de désynchronisation (date dans `events` ≠ date sur le PDF archivé).

### 3.5 Sélecteurs qualif incohérents

- `qualif_selector()` (dans `membres_model`) utilise `mniveaux` — retourne les membres selon les bits du champ legacy.
- `inst_selector()` et `pilrem_selector()` (dans `membres_model`) interrogent `user_roles_per_section` — utilisent le nouveau système.

Ces deux approches coexistent dans le même fichier, sans règle claire sur laquelle utiliser pour un nouveau besoin.

### 3.6 Pas de support multi-sections pour les qualifications

Les qualifications dans `mniveaux` sont globales (pas de dimension section). Un IVV de la section planeur est aussi visible comme IVV de la section ULM. Le nouveau système `user_roles_per_section` résout cela pour les rôles d'accès, mais les qualifications techniques (visite médicale, BPP) n'ont pas de dimension section dans `events`.

---

## 4. Situation idéale

### 4.1 Séparation nette en deux axes

```
Axe 1 : Droits d'accès à l'application
  → Géré par user_roles_per_section + Gvv_Authorization
  → Non hiérarchique, par section, tracé

Axe 2 : Qualifications et licences (entités métier avec dates de validité)
  → La qualification est l'entité principale ; le PDF est un attribut optionnel
  → Géré par events (étendu pour supporter un fichier joint)
  → Alarmes unifiées via AlarmAggregator

Axe 3 : Documents administratifs (entités dont le document est la chose principale)
  → Attestations d'assurance, manuels d'exploitation, briefings, autorisations parentales
  → Géré par archived_documents
  → Pas de qualification aéronautique dans cette table
```

### 4.2 Droits d'accès : un seul système, `mniveaux` supprimé

Le système `user_roles_per_section` est la cible. Il couvre :
- La dimension section.
- La traçabilité (qui a accordé, quand, révocation).
- Des rôles non hiérarchiques et extensibles.

**`mniveaux` doit être entièrement supprimé**, pas seulement réduit. Chacun de ses bits a une destination naturelle :

| Bits | Migration cible |
|------|-----------------|
| PRESIDENT, VICE_PRESIDENT, CA, TRESORIER, SECRETAIRE, SECRETAIRE_ADJ | `user_roles_per_section` — rôles de gouvernance |
| IVV, ITP, FI_AVION, FE_AVION | `user_roles_per_section` rôle `instructeur` (id 11) — déjà là |
| MECANO | `user_roles_per_section` rôle `mecano` (id 12) — déjà là |
| REMORQUEUR | `user_roles_per_section` rôle `pilote_rem` (id 17) — déjà là |
| PILOTE_PLANEUR, PILOTE_AVION, VI_PLANEUR, VI_AVION | `events` ou `archived_documents` — qualifications aéronautiques avec dates |
| PLIEUR, TREUILLARD, CHEF_DE_PISTE, CHEF_PILOTE | `user_roles_per_section` — rôles opérationnels à créer si besoin |
| INTERNET | Obsolète — à supprimer sans remplacement |

`mniveaux` reste en base uniquement pendant la période de migration comme source de lecture pour les contrôleurs non encore migrés. Il n'a pas de place dans l'architecture cible.

`macces` est supprimé sans remplacement : aucune logique d'autorisation ne le consulte.

### 4.3 Qualifications : `events` comme entité centrale, PDF optionnel

**Principe** : la qualification est l'entité métier. Le PDF en est la preuve optionnelle. Il ne faut pas créer deux sources de vérité pour la validité : la date d'obtention, la date d'expiration et le numéro restent dans `events`, et le document n'est qu'une pièce justificative rattachée à cette qualification.

La conséquence est qu'un écran unique de gestion des qualifications pilote l'opération, mais n'écrit la validité qu'à un seul endroit. `events` (ou une table `qualifications` qui lui succède) porte toutes les données métier de la qualification, et le PDF est un attribut ou une annexe de cet enregistrement :

| Attribut | Emplacement |
|----------|-------------|
| Type de qualification (PPL, médical, BPP…) | `events.etype` → `events_types` |
| Date d'obtention | `events.edate` |
| Date de validité | `events.date_expiration` |
| Numéro de licence | `events.ecomment` |
| Fichier PDF (optionnel) | `events.file_path` (champ à ajouter) ou FK vers un stockage fichier |

`archived_documents` reste l'outil des **documents administratifs** dont le document lui-même est la chose principale : attestations d'assurance, manuels d'exploitation, briefings passagers, autorisations parentales. Ces documents ont un cycle de vie propre (validation, versionnage, workflow de signature) qui n'a pas de sens pour une qualification aéronautique.

**Règle d'implémentation** : lorsqu'un type est une qualification, l'utilisateur passe par le flux qualification et non par le flux archivage documentaire. L'UI peut accepter le dépôt du PDF, mais la création de la qualification est refusée depuis `archived_documents` afin d'éviter toute divergence sur la validité.

**Ligne de partage** :
- *"Ce pilote a un PPL valide jusqu'au…"* → `events` (qualification, avec PDF en attribut optionnel).
- *"Cette section a une attestation d'assurance valide jusqu'au…"* → `archived_documents` (le document est l'entité).

### 4.4 Alarmes : un seul point d'entrée

L'`AlarmAggregator` (déjà conçu dans `gestion_alarmes_design.md`) est la façade unique :
- `DocumentAlarmProvider` adapte `archived_documents_model`.
- `ComputedAlarmProvider` adapte les calculs de `alarmes.php` et les validités de `events`.

Les notifications et la vue pilote passent toutes par cet agrégateur.

### 4.5 Sélecteurs : un seul mécanisme

Tous les sélecteurs de pilotes qualifiés (instructeurs, remorqueurs, mécaniciens) sont construits depuis `user_roles_per_section`. `qualif_selector()` basé sur `mniveaux` est supprimé ou réduit à des usages qui n'ont pas encore migré.

---

## 5. Chemin d'évolution progressif et réaliste

Le projet est en mode maintenance active ; les refactorings majeurs doivent être transparents pour les utilisateurs. Le chemin d'évolution est organisé en deux objectifs séquentiels.

### Objectif 1 — Supprimer `mniveaux` et `macces` (priorité 1)

Cet objectif est le premier livrable attendu. Tant qu'il n'est pas atteint, l'unification du concept de qualification reste secondaire.

**Étapes nécessaires :**

1. **Migrer tous les contrôleurs vers `Gvv_Authorization`** (fin de M2→M5) pour supprimer toute dépendance implicite aux bits.
2. **Compléter le dictionnaire des rôles dans `types_roles`** pour couvrir les responsabilités actuellement encodées dans `mniveaux` (chef de piste, plieur, treuillard, etc.).
3. **Rendre ces rôles administrables dans l'UI** (`gestion_roles`) afin qu'aucune capacité ne devienne non gérable après retrait des bits.
4. **Migrer les usages applicatifs restants de `mniveaux`** :
  - sélecteurs (`qualif_selector()`),
  - filtres e-mail,
  - accès formation legacy.
5. **Traiter `macces` en premier** : inventorier les usages club, puis déprécier et retirer son affichage (aucune logique métier active ne le lit).
6. **Exécuter un contrôle de cohérence en base** entre l'ancien état (`mniveaux`) et le nouvel état (`user_roles_per_section`) pour détecter les écarts avant bascule finale.
7. **Basculer puis supprimer** :
  - retirer l'affichage et l'édition de `mniveaux`/`macces` dans la fiche membre,
  - supprimer les colonnes et le code mort associé.

**Critères de sortie de l'objectif 1 :**

- Aucun contrôle d'accès ou sélecteur ne lit `mniveaux` ou `macces`.
- Tous les rôles opérationnels sont gérés via `user_roles_per_section`.
- Les écrans d'administration permettent de gérer ce qui était auparavant porté par les bits.

### Objectif 2 — Unifier le concept de qualification (priorité 2)

Cet objectif commence **après** la suppression effective de `mniveaux`/`macces`.

**But :** proposer une seule interface utilisateur de gestion des qualifications, avec ou sans justificatif PDF.

**Étapes proposées :**

1. **Définir le périmètre métier de "qualification"** (ce qui relève de la qualification vs ce qui reste un document administratif).
2. **Introduire une façade fonctionnelle unique "qualification"** (contrôleur + service) pour éviter les créations concurrentes par plusieurs flux.
3. **Arbitrer le stockage canonique** à ce moment : conserver `events`, basculer vers `archived_documents`, ou modèle hybride.
4. **Appliquer la règle de source de vérité unique** pour la date de validité et les numéros, quel que soit l'arbitrage retenu.
5. **Aligner les alarmes** via `AlarmAggregator` pour présenter une vue homogène côté utilisateur.

**Décision explicitement reportée à l'objectif 2 :** le choix final entre `events` et `archived_documents` comme support principal des qualifications n'est pas figé à ce stade.

---

## 6. Résumé des décisions à prendre

| Décision | Options | Recommandation |
|----------|---------|----------------|
| Sort de `macces` | Garder avec documentation, déprécier, supprimer | Déprécier si aucun club ne l'utilise activement |
| Stockage principal des qualifications et de leur justificatif | `events`, `archived_documents`, modèle hybride | Décision reportée à l'objectif 2, après suppression de `mniveaux`/`macces` |
| Bits `mniveaux` pour la gouvernance | Garder en parallèle, supprimer après migration | Supprimer après migration complète vers `user_roles_per_section` |
| Périmètre du rôle `instructeur` par section | Global (null), Par section, Les deux | Par section pour IVV/ITP, global pour FI/FE avion |
| Alarmes : quand implémenter AlarmAggregator ? | Maintenant, Après migration auth, Séparément | Après objectif 1 — dépend de données stables |

---

## 7. Références

- `doc/design_notes/2011_authorization_system.md` — Architecture DX_Auth legacy
- `doc/authorization/routes_and_permissions.md` — Référence routes et permissions
- `doc/plans/2025_authorization_refactoring_plan.md` — Plan de migration en cours (phase M2)
- `doc/design_notes/gestion_alarmes_design.md` — Architecture AlarmAggregator
- `doc/design_notes/gestion_documentaire.md` — Plateforme documentaire (archived_documents)
- `application/config/constants.php` — Constantes des bits mniveaux
- `application/libraries/Gvv_Authorization.php` — Nouveau système d'autorisation
- `application/libraries/Formation_access.php` — Gestion accès formation (dual-path)
- `application/models/membres_model.php` — Sélecteurs de pilotes qualifiés
- `application/models/event_model.php` — Validités médicale et instructeur
