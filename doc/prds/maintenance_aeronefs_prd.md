# PRD — Gestion de la Maintenance des Aéronefs

Date : 3 août 2026 — mis à jour le 4 août 2026 (alignement architectural sur le module Formation), 5 août 2026 (nommage `maintenance_programme_sections`, cf. design Phase 0)

## Contexte

GVV gère aujourd'hui la flotte (planeurs, avions, ULM) pour la réservation, le suivi des vols et la facturation, mais ne suit **aucune information de maintenance** : ni échéance de visite, ni potentiel horaire restant, ni historique d'intervention. C'est le point 21 de `doc/todo.md` ("Support de la gestion de la maintenance... PRD à rédiger").

Plusieurs éléments existent déjà et sont directement réutilisables :

- **Fiche aéronef** (`machinesa` / modèle `Avions_model`) : identité, horamètre, prix, section de rattachement. Aucun champ de potentiel ou d'échéance n'y figure aujourd'hui.
- **Système documentaire** (`document_types` / `archived_documents`, voir `doc/prds/archivage_documentaire_prd.md`) : upload, versioning, validation, alerte avant expiration. La colonne `machine_immat` existe déjà dans `archived_documents` mais n'est pas exploitable car `document_types.scope` ne connaît que `pilot`, `section` et `club` — pas encore `machine`.
- **Rôle `mecano`** : déjà défini ("capacité à gérer la maintenance des aéronefs"), déjà utilisé pour restreindre la catégorie de vol "Vol d'essai", mais sans écran de gestion de maintenance à ce jour.
- **Dashboard** : une section "Maintenance et suivi de navigabilité" existe déjà en placeholder désactivé ("Programmes d'entretien", "Opérations de maintenance" — *bientôt disponible*).
- **Réservations** : un statut "Maintenance" existe déjà pour bloquer manuellement un aéronef (`doc/prds/aircraft_booking_prd.md`).
- **Alarmes génériques** (`doc/prds/gestion_alarmes_generiques_prd.md`) : la maintenance y est identifiée comme une des trois familles d'alarme à couvrir, mais ce PRD exclut explicitement la gestion du planning de maintenance lui-même — il ne fait qu'attendre des échéances à afficher.
- **Module Formulaires** (`doc/prds/remplissage_formulaires_prd.md`, EF12) : établit déjà le principe "saisie en ligne OU téléversement d'un scan du document rempli à la main", que ce PRD reprend pour les comptes rendus de maintenance.
- **Module Formation** (`doc/prds/archives/suivi_formation_prd.md`, tables `formation_programmes` / `formation_lecons` / `formation_sujets` / `formation_inscriptions` / `formation_seances`) : GVV sait déjà gérer un programme structuré à trois niveaux (programme → leçon → sujet), ouvert pour un pilote via une inscription, et fait progresser par des séances qui évaluent chaque sujet. Ce PRD reprend délibérément cette architecture pour la maintenance plutôt que d'en inventer une nouvelle.

Ce PRD couvre la Mission C de `doc/offre_de_stage.md` ("Gestion de la maintenance"), en la précisant et en l'ancrant sur l'existant.

### Analogie avec le module Formation

Le module Maintenance est conçu comme le miroir du module Formation. Un développeur qui connaît l'un doit reconnaître immédiatement la structure de l'autre.

| Formation | Maintenance | Rôle |
|---|---|---|
| Pilote | **Entité maintenable** : un aéronef, ou un équipement rattaché à un aéronef | Le sujet suivi par un programme |
| Programme de formation (`formation_programmes`) | **Programme d'entretien** (`maintenance_programmes`) | Racine du contenu structuré (markdown) + règles de butée |
| Leçon (`formation_lecons`) | **Section** (`maintenance_programme_sections`) | Regroupement de tâches au sein d'un programme |
| Sujet (`formation_sujets`) | **Tâche** (`maintenance_taches`) | Point de contrôle élémentaire, rattaché à une section |
| Inscription — "ouvrir une formation" (`formation_inscriptions`) | **Dossier d'entretien** — "ouvrir un dossier d'entretien" (`maintenance_dossiers`) | Association programme + entité maintenable, avec cycle de vie (ouvert/suspendu/clôturé/abandonné) |
| Séance de formation (`formation_seances`) | **Opération de maintenance** (`maintenance_operations`) | Un événement daté qui fait progresser un dossier |
| Évaluation d'un sujet lors d'une séance (`formation_evaluations`) | **Réalisation d'une tâche lors d'une opération** (`maintenance_realisations`) | Case à cocher (fait / non fait / non applicable) + commentaire, comme l'évaluation d'un sujet |
| Instructeur | Mécano | Rôle qui enregistre les séances/opérations |

Le programme d'entretien est donc structuré sur trois niveaux, comme le programme de formation : **programme → section → tâche**.

Cette analogie structure directement la Portée et les Exigences fonctionnelles ci-dessous.

## Objectifs

- Suivre, pour chaque entité maintenable (aéronef ou équipement), le potentiel soumis à une échéance de maintenance (calendaire et/ou horaire).
- Permettre l'enregistrement d'une opération de maintenance **soit par saisie directe dans GVV, soit par téléversement d'un compte rendu papier scanné ou photographié**. Dans tous les cas les champs qui affectent les potentiels devront être saisies dans GVV pour permettre le calcul automatique des échéances. Les opérations élémentaires pourront être décrites dans GVV ou les fiches papier.
- Réutiliser le système documentaire existant pour stocker programmes de maintenance, bulletins de service et comptes rendus, plutôt que d'inventer un nouveau mécanisme de fichiers.
- Construire le module Maintenance sur la même architecture conceptuelle que le module Formation (programme → dossier ouvert → opérations qui le font progresser), pour un code et un modèle mental communs entre les deux modules.
- Donner une vue simple et immédiate de l'état de navigabilité de chaque aéronef (à jour / échéance proche / dépassée).
- Exposer les échéances de maintenance au futur mécanisme d'alarmes génériques, sans réimplémenter de logique de notification.
- Activer le rôle `mecano` et les cartes de dashboard déjà réservées pour la maintenance.
- Rester simple : priorité à la facilité de saisie pour un mécanicien ou un trésorier bénévole, pas à l'exhaustivité d'un logiciel de CAMO professionnel.

## Non-objectifs

- Remplacer un logiciel de gestion de navigabilité certifié (CAMO). GVV reste un outil de suivi club, pas un outil d'agrément.
- Gestion de stock de pièces détachées ou d'ateliers.
- Planification d'atelier (affectation mécanicien/créneaux, ordonnancement des tâches).
- Blocage automatique des réservations en cas d'échéance dépassée : l'indisponibilité de l'aéronef reste une action manuelle via le statut "Maintenance" des réservations (`doc/prds/aircraft_booking_prd.md`). Une automatisation éventuelle sera une évolution ultérieure, hors périmètre.
- Le moteur d'alarme lui-même (armement, notifications, acquittement) : couvert par `doc/prds/gestion_alarmes_generiques_prd.md`. Ce PRD se contente de fournir les échéances de maintenance comme source pour ce moteur.
- Import automatique de bulletins de service depuis des bases constructeur.
- Signature électronique qualifiée des comptes rendus.
- Rapports réglementaires DGAC formatés — seul un export PDF de synthèse par aéronef est prévu en phase 1.
- Équipements composés eux-mêmes de sous-équipements (un seul niveau : aéronef → équipement).

## Portée

### Inclus

- **Entités maintenables** : chaque aéronef et chaque équipement qui lui est rattaché (moteur, hélice, parachute, radio, etc.) est une entité maintenable, au même titre qu'un pilote en formation. Un équipement peut être transféré d'un aéronef à un autre ; il conserve alors son historique et son potentiel.
- **Programmes d'entretien** : documents structurés en markdown à trois niveaux (programme → section → tâche), comme les programmes de formation (programme → leçon → sujet), portant des règles de butée (échéance calendaire, seuil d'heures de vol, ou les deux). Versionnés via le système documentaire existant.
- **Dossiers d'entretien** : association d'un programme d'entretien à une entité maintenable (aéronef ou équipement), avec un statut de cycle de vie (ouvert / suspendu / clôturé / abandonné) — l'équivalent exact d'une formation ouverte. Une entité maintenable peut avoir plusieurs dossiers ouverts simultanément (ex. programme cellule sur l'aéronef + programme repliage sur son parachute).
- **Opérations de maintenance** : équivalent des séances de formation. Rattachées à un dossier d'entretien, enregistrées par un mécano, en saisie directe ou par dépôt d'un compte rendu scanné/photographié — un seul écran pour les deux modes.
- **Réalisation des tâches du programme** : lors d'une opération, chaque tâche du programme peut être cochée (fait / non fait / non applicable), regroupée par section pour l'affichage, avec un commentaire par tâche, plus un commentaire global sur l'opération — comme l'évaluation des sujets lors d'une séance de formation.
- **Bulletins de service** : documents typés, avec statut à traiter / traité / non applicable, validation par un mécano ou un admin.
- Mise à jour du potentiel de l'entité concernée à partir d'une opération enregistrée, quel que soit le mode de saisie utilisé.
- Historique des opérations par entité maintenable.
- Vue de synthèse de l'état de navigabilité, par entité et pour l'ensemble de la flotte d'une section, avec export PDF de synthèse par aéronef.
- Dashboard maintenance dédié regroupant toutes les cartes du module (au-delà des deux cartes déjà réservées sur le dashboard principal).
- Activation du rôle `mecano` sur les nouveaux écrans.

### Exclu (phase 1)

- Planification d'atelier et gestion de stock (cf. non-objectifs).
- Blocage automatique des réservations sur échéance dépassée.
- Le moteur de notification des échéances (dépend de `gestion_alarmes_generiques_prd.md`).
- Import automatique de bulletins de service constructeur.
- Rapports réglementaires formatés au-delà de l'export PDF de synthèse par aéronef.
- Catalogue imposé d'équipements ou de programmes par type d'aéronef : configuration entièrement libre en phase 1.

## Personae & rôles

- **Mécano** (rôle existant `mecano`) : joue le rôle équivalent de l'instructeur en formation. Configure les entités maintenables et leurs équipements, crée et ouvre des dossiers d'entretien, enregistre les opérations de maintenance, dépose les comptes rendus, consulte l'historique de la flotte de sa section.
- **Administrateur club** : mêmes droits que le mécano, plus configuration des programmes d'entretien et des types de documents de maintenance, supervision multi-section.
- **Responsable de section / trésorier** : consulte l'état de navigabilité de la flotte de sa section, sans droit de modification.
- **Pilote** : consulte en lecture seule l'état de navigabilité d'un aéronef (utile avant réservation), sans accès au détail des interventions.

## Parcours clés

### Parcours 1 — Création d'un programme d'entretien (Admin/Mécano)

1. L'admin ou le mécano crée un programme d'entretien (ex. "Visite 100 h cellule", "Repliage parachute 6 ans"), rédigé en markdown structuré comme un programme de formation.
2. Il y définit des sections (ex. "Moteur", "Cellule", "Équipements de sécurité"), puis pour chaque section la liste des tâches/points de contrôle (ex. "vidange", "remplacement des filtres", "contrôle de compression").
3. Il définit la règle de butée du programme : échéance calendaire, seuil d'heures de vol, ou les deux.

### Parcours 2 — Ouverture d'un dossier d'entretien (Mécano/Admin)

1. Le mécano choisit une entité maintenable (un aéronef, ou un de ses équipements) et un programme d'entretien.
2. Il ouvre un dossier d'entretien associant les deux — exactement comme on ouvre une formation pour un pilote.
3. Le dossier apparaît dans l'historique de l'entité avec le statut "ouvert".

### Parcours 3 — Opération de maintenance en saisie directe (Mécano)

1. Le mécano ouvre "Nouvelle opération" sur un dossier d'entretien.
2. Il renseigne la date et une remarque libre.
3. Il coche les tâches du programme réalisées pendant l'opération (ex. "vidange", "remplacement des filtres"), regroupées par section, avec un commentaire possible par tâche, comme l'évaluation des sujets d'une séance de formation.
4. Il valide : l'opération est historisée et le potentiel de l'entité est mis à jour automatiquement.

### Parcours 4 — Opération de maintenance par dépôt d'un compte rendu papier (Mécano)

1. Le mécano ouvre "Nouvelle opération" sur un dossier d'entretien.
2. Sur le même écran que la saisie directe, il utilise le bouton de téléchargement pour déposer le scan ou la photo du compte rendu signé par l'atelier.
3. Il renseigne uniquement les champs indispensables au calcul du potentiel (date, tâches concernées) — le détail de l'intervention reste dans le document joint, non ressaisi.
4. Il valide : le document est archivé via le système documentaire existant et rattaché à l'opération, consultable facilement depuis celle-ci ; le potentiel est mis à jour exactement comme en saisie directe.

### Parcours 5 — Transfert d'un équipement vers un autre aéronef (Mécano/Admin)

1. Le mécano ouvre la fiche d'un équipement (ex. un parachute de secours).
2. Il change son aéronef de rattachement.
3. L'équipement conserve son potentiel, ses dossiers d'entretien et son historique d'opérations, désormais visibles depuis le nouvel aéronef.

### Parcours 6 — Consultation de l'état de navigabilité (Pilote/Responsable)

1. L'utilisateur consulte la fiche d'un aéronef ou le tableau de flotte.
2. Il voit un état visuel par entité maintenable : à jour, échéance proche, dépassée.
3. Selon ses droits, il peut ouvrir l'historique des dossiers, des opérations et des documents associés.

## Exigences fonctionnelles

### EF1 — Entités maintenables : aéronefs et équipements

1. Chaque aéronef est une entité maintenable de premier niveau.
2. Un équipement (ex. moteur, hélice, parachute, radio) est une entité maintenable rattachée à un seul aéronef à la fois ; il n'y a pas de sous-équipement (un seul niveau de rattachement).
3. Un équipement peut être créé, modifié, désactivé (suppression logique) et transféré vers un autre aéronef, sans perte de son potentiel ni de son historique.
4. La liste des équipements n'est pas figée par le code : elle est entièrement libre et configurable par aéronef, sans catalogue imposé en phase 1.

### EF2 — Programmes d'entretien

1. Un programme d'entretien est un document structuré en markdown à trois niveaux (programme → section → tâche), exactement sur le modèle des programmes de formation (programme → leçon → sujet).
2. Une section regroupe une ou plusieurs tâches/points de contrôle à réaliser ; un programme comporte une ou plusieurs sections.
3. Un programme d'entretien porte une règle de butée : échéance calendaire, seuil d'heures de vol, ou les deux.
4. Les programmes d'entretien sont gérés par le système documentaire existant (`document_types` / `archived_documents`), étendu avec une portée `machine`.
5. Le versioning déjà présent dans le système documentaire s'applique : une nouvelle version d'un programme conserve l'historique des versions précédentes.

### EF3 — Dossiers d'entretien

1. Un dossier d'entretien associe un programme d'entretien à une entité maintenable (aéronef ou équipement) — l'équivalent d'une inscription/formation ouverte.
2. Un dossier possède un statut : ouvert, suspendu, clôturé, abandonné, avec les dates correspondantes.
3. Une entité maintenable peut avoir plusieurs dossiers ouverts simultanément, sur des programmes différents (ex. un programme cellule sur l'aéronef et un programme repliage sur son parachute).
4. L'historique des dossiers d'une entité (y compris clôturés/abandonnés) reste consultable.

### EF4 — Opérations de maintenance

1. Une opération de maintenance est toujours rattachée à un dossier d'entretien — l'équivalent d'une séance de formation rattachée à une inscription.
2. Deux modes d'enregistrement sont proposés sur un même écran, au choix de l'utilisateur, jamais combinés obligatoirement :
   - **Saisie directe** : date, tâches du programme réalisées (cochées individuellement, regroupées par section, avec commentaire possible par tâche), remarque globale.
   - **Dépôt d'un compte rendu** : mêmes champs minimaux que la saisie directe pour permettre le calcul du potentiel, plus le fichier scanné/photographié du compte rendu papier (bouton de téléchargement sur le même écran), réutilisant le pipeline d'upload du système documentaire (types de fichiers, taille maximale, compression déjà en place).
3. Le mode de saisie choisi n'affecte pas les champs disponibles ensuite pour la consultation ou le calcul du potentiel : les deux modes produisent une opération exploitable de la même façon.
4. Une opération une fois enregistrée reste consultable dans l'historique du dossier ; sa correction reste possible selon les droits (pas de suppression silencieuse — traçabilité). Si un compte rendu papier est joint, il doit rester consultable facilement depuis l'opération.

### EF5 — Mise à jour du potentiel

1. L'enregistrement d'une opération met à jour automatiquement l'échéance et/ou le potentiel horaire restant de l'entité maintenable concernée.
2. Le potentiel horaire restant se calcule à partir de l'horamètre courant de l'aéronef et du seuil défini par le programme d'entretien (cf. EF2).
3. Toute mise à jour manuelle du potentiel (hors opération enregistrée) reste possible pour corriger une donnée erronée, avec traçabilité dans les journaux applicatifs (marqueur `MAINTENANCE` pour permettre le filtrage des logs), utilisateur et date.

### EF6 — Bulletins de service

1. Les bulletins de service sont gérés comme des documents typés et versionnés, rattachés à une entité maintenable, au même titre que les programmes d'entretien.
2. Un bulletin de service porte un statut : à traiter, traité, non applicable.
3. Seuls un mécano ou un administrateur peuvent faire évoluer ce statut.

### EF7 — Vue de synthèse et état de navigabilité

1. Une vue de synthèse par aéronef affiche l'état de chaque entité maintenable (aéronef et ses équipements) : à jour, échéance proche, dépassée.
2. Le seuil "échéance proche" est global à l'application, configurable par un administrateur, avec une valeur par défaut de 30 jours.
3. Une vue de synthèse de flotte, filtrable par section, affiche l'état global de chaque aéronef (le pire état parmi ses équipements).
4. Depuis la vue de synthèse, l'historique des dossiers, des opérations et les documents rattachés (programmes, bulletins, comptes rendus) sont accessibles selon les droits de l'utilisateur.
5. Un export PDF de synthèse est disponible par aéronef.

### EF8 — Rôles et accès

1. Le rôle `mecano` donne accès en écriture aux entités maintenables, aux programmes, dossiers, opérations et documents de maintenance de son périmètre.
2. L'administrateur club a les mêmes droits que le mécano, sur toutes les sections.
3. Le responsable de section et le trésorier ont un accès en lecture seule à l'état de navigabilité et à l'historique de leur section.
4. Le pilote a un accès en lecture seule limité à l'état de navigabilité (sans détail d'intervention), utile avant réservation.

### EF9 — Dashboard maintenance

1. Un dashboard maintenance dédié regroupe l'ensemble des cartes du module (entités maintenables, programmes, dossiers, opérations, bulletins de service).
2. Les cartes existantes "Programmes d'entretien" et "Opérations de maintenance" (actuellement désactivées, "bientôt disponible") sur le dashboard principal sont activées et pointent vers ce dashboard maintenance.
3. Leur visibilité suit la règle déjà en place (`is_mecano || is_admin`).

### EF10 — Point d'ancrage pour les alarmes et les réservations (sans les implémenter)

1. Les échéances de maintenance (date ou seuil d'heures) doivent être consultables par le futur mécanisme d'alarmes génériques, sans duplication de la logique de calcul du potentiel.
2. Le statut "Maintenance" déjà existant sur les réservations n'est pas modifié ; ce PRD ne construit aucune automatisation entre échéance dépassée et blocage de réservation.

## Exigences non fonctionnelles

- **Simplicité** : la saisie d'une opération de maintenance doit être possible en moins d'une minute pour un utilisateur non technique.
- **UX** : le résultat de chaque action (enregistrement, mise à jour de potentiel, échec d'upload) doit être explicite, jamais silencieux.
- **Réutilisation** : aucun nouveau mécanisme de stockage de fichiers n'est créé — le système documentaire existant est étendu, pas dupliqué.
- **Cohérence de conception** : le module Maintenance suit la même architecture (programme / dossier / opération) et, autant que possible, les mêmes patterns d'implémentation que le module Formation, pour limiter la charge cognitive et de maintenance du code.
- **Traçabilité** : toute opération et toute modification manuelle de potentiel conservent utilisateur et date (champs d'audit standard GVV), avec un marqueur `MAINTENANCE` dans les journaux applicatifs.
- **Sécurité** : accès aux données de maintenance strictement limité selon rôle et section.
- **Compatibilité** : rendu responsive desktop/mobile (saisie possible depuis l'atelier).

## Contraintes & dépendances

- Dépend de l'extension du système documentaire (`document_types.scope`) pour couvrir la portée `machine`, déjà anticipée par la colonne `machine_immat` existante dans `archived_documents`.
- S'appuie sur le rôle `mecano` et les emplacements dashboard déjà réservés, sans les redéfinir.
- S'appuie sur l'architecture du module Formation (`formation_programmes` / `formation_lecons` / `formation_sujets` / `formation_inscriptions` / `formation_seances`) comme référence de conception, sans dépendance fonctionnelle directe entre les deux modules.
- La notification des échéances dépend de la livraison de `doc/prds/gestion_alarmes_generiques_prd.md` ; en attendant, la vue de synthèse (EF7) suffit à une consultation manuelle.
- Cohérence à assurer avec le statut "Maintenance" des réservations (`doc/prds/aircraft_booking_prd.md`) : ce PRD ne le modifie pas mais s'appuie sur la même terminologie.

## Mesures de succès

- Chaque aéronef actif de la flotte dispose d'au moins un dossier d'entretien ouvert.
- 100 % des opérations de maintenance réalisées sont enregistrées dans GVV (saisie ou compte rendu déposé), sans ressaisie parallèle dans un autre outil.
- Réduction des oublis d'échéance remontés par les mécanos/responsables de flotte.
- Le compte rendu papier reste utilisable tel quel : aucun mécanicien n'est contraint de ressaisir le détail d'une intervention déjà documentée sur papier.
- Un développeur familier du module Formation reconnaît directement la structure du module Maintenance.

## Questions ouvertes

- Faut-il un type d'opération (ex. visite programmée / dépannage / inspection), comme les types de séance en formation, ou une opération reste-t-elle non typée en phase 1 ?

### Résolues

- Catalogue d'équipements par défaut selon le type d'aéronef : non — configuration entièrement libre par aéronef en phase 1.
- Seuil par défaut de l'état "échéance proche" : global à l'application, 30 jours par défaut.
- Équipements amovibles (ex. parachute changeant d'aéronef) : suivis comme entité maintenable propre, rattachée à un aéronef à la fois et transférable (EF1, Parcours 5) — pas de suivi indépendant hors aéronef.
- Niveau d'export attendu en phase 1 : PDF de synthèse par aéronef (EF7).
- Validation du statut d'un bulletin de service : mécano ou administrateur (EF6).
