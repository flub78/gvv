# Plan d'implémentation — Système d'Acceptations et Reconnaissances

Date : 8 février 2026

## Références

- PRD : [doc/prds/approbation_de_documents_prd.md](../prds/approbation_de_documents_prd.md)
- Infrastructure existante : Module archivage documentaire (migration 067, contrôleur `archived_documents`, modèles)

## Objectif

Livrer un système complet d'acceptation et reconnaissance de documents, formations et contrôles pour les membres du club identifiés dans GVV, avec :
- Acceptation simple (un clic) pour les membres
- Double validation (instructeur/élève) pour les formations et contrôles
- Traçabilité complète avec horodatage

## Note de réduction de périmètre (révision PRD)

Le PRD a été révisé pour exclure toute acceptation par une personne non-inscrite dans GVV : signature externe (directe, lien, QR code, papier), autorisation parentale et rattachement différé au dossier d'un pilote sont désormais couverts par le module Formulaires, pas par ce système.

Conséquences sur ce plan :
- Les anciens Lots 6 (signature externe), 7 (page publique de signature) et 8 (mode papier) sont retirés — non commencés, ils n'ont plus lieu d'être.
- Les Lots 1 à 3 étaient déjà livrés avant cette révision et incluent des éléments désormais hors périmètre : catégorie `autorisation`, colonne `target_type` (interne/externe), rattachement d'une acceptation externe à un pilote (3.7), table `acceptance_signatures`, table `acceptance_tokens`. Ce code et ce schéma restent en place tels quels (aucun retrait rétroactif) mais ne reçoivent plus de développement complémentaire ; les nouveaux éléments créés par l'administration n'utilisent plus la catégorie `autorisation` ni le mode externe.
- Les lots suivants ont été renumérotés en conséquence (ancien Lot 9 → Lot 6, etc.).

## Note de priorité (premier lot)

Le premier lot fonctionnel livré est le Lot 4 — l'acceptation de documents déjà archivés (catégorie `document`, appuyée sur `archived_documents`/`document_types`, existant réutilisé). Le Lot 5 (double validation formation/contrôle) est secondaire et n'est pas requis pour cette première livraison.

**Évolution probable** : plutôt que d'étendre ce module avec de nouvelles catégories/sources d'éléments à accepter, l'évolution privilégiée consiste à compléter le module Formulaires pour transformer une réponse en document archivé — ce document devient alors un élément à accepter par le mécanisme générique du Lot 4, sans multiplier les sources spécialisées.

## Analyse de l'existant

### Infrastructure réutilisable
- **Module archivage documentaire** : tables `document_types` et `archived_documents` (migration 067), contrôleur, modèles et vues déjà en place
- **Module Messages du jour (MOTD)** : tables `motd_messages`/`motd_user_message_state`, contrôleur, modèles déjà en place (migration 143, module livré) — canal de notification par défaut, voir `doc/prds/messages_du_jour_prd.md`
- **File_compressor** : compression images/PDF pour les uploads
- **Système email** : helpers et contrôleur existants
- **Gvvmetadata** : système de métadonnées pour formulaires et tables

### Gaps identifiés
- Pas de viewer PDF intégré (PDF.js) — le navigateur affiche nativement
- Le module Messages du jour ne supporte pas aujourd'hui un message non masquable (`motd_user_message_state.hidden` toujours modifiable par l'utilisateur) — nécessaire pour les niveaux `mandatory_soft`/`mandatory_hard`, voir Lot 3d
- Pas de mécanisme de blocage applicatif global empêchant toute action tant qu'une validation obligatoire bloquante n'est pas faite, voir Lot 3d

## Architecture

### Nouvelles tables

| Table | Rôle |
|-------|------|
| `acceptance_items` | Éléments à faire accepter (documents, formations, briefings) |
| `acceptance_records` | Enregistrements d'acceptation/refus par personne |
| `acceptance_signatures` | Signatures externes (tactiles, uploads papier) |
| `acceptance_tokens` | Liens temporaires pour signatures externes |

### Relations avec l'existant
- `acceptance_items.category` utilise les catégories du PRD (document, formation, controle, briefing) — la valeur `autorisation` reste définie dans l'enum existant pour compatibilité avec les éléments déjà créés avant la révision du PRD, mais n'est plus proposée pour un nouvel élément
- Les fichiers PDF des éléments sont stockés dans `uploads/acceptances/items/`

### Contrôleurs

| Contrôleur | Rôle |
|------------|------|
| `acceptance_admin` | CRUD éléments, suivi acceptations (admin) |
| `acceptance` | Acceptation, historique personnel (membre) |
| `acceptance_training` | Délivrance/réception formations (instructeur/élève) |

---

## Lots d'implémentation

### Lot 1 — Modèle de données & migration

- [x] 1.1 Concevoir le schéma `acceptance_items` (titre, catégorie, fichier PDF, type interne/externe, obligatoire, date limite, double validation, rôles, cibles utilisateurs)
- [x] 1.2 Concevoir le schéma `acceptance_records` (item_id, user_login, statut, horodatage, formule, rôle dans double validation, rattachement pilote)
- [x] 1.3 Concevoir le schéma `acceptance_signatures` (record_id, nom, prénom, qualité signataire, bénéficiaire mineur, type signature, fichier, données signature tactile)
- [x] 1.4 Concevoir le schéma `acceptance_tokens` (token, item_id, créé par, expiration, usage unique, mode)
- [x] 1.5 Créer la migration `068_acceptance_system.php`
- [x] 1.6 Mettre à jour `application/config/migration.php` (version 68)
- [x] 1.7 Écrire les tests de migration `AcceptanceSystemMigrationTest.php` (29 tests, 104 assertions)
- [x] 1.8 Valider : migration up (67→68), rollback down (68→67), re-up (67→68) — tous OK

### Lot 2 — Modèles de données

- [x] 2.1 Créer `acceptance_items_model.php` (CRUD, select_page avec join créateur, get_active_items, get_items_for_user, get_overdue_items, image, selector) — 15 tests
- [x] 2.2 Créer `acceptance_records_model.php` (CRUD, select_page avec joins, get_by_user, get_by_item, get_pending_for_user, count_pending_for_user, accept, refuse, link_to_pilot, get_linked_records, image) — 15 tests
- [x] 2.3 Créer `acceptance_signatures_model.php` (CRUD, select_page avec joins, get_by_record, create_tactile, create_upload avec autorisation parentale, image) — 8 tests
- [x] 2.4 Créer `acceptance_tokens_model.php` (generate_token crypto-sécurisé 64 hex, validate_token, mark_used, cleanup_expired, get_by_item, image) — 16 tests
- [x] 2.5 Ajouter les métadonnées dans `Gvvmetadata.php` pour les 4 tables + 2 vues + default_fields
- [x] 2.6 Écrire les tests MySQL des modèles (54 tests, 186 assertions) + fichiers de langue FR/EN/NL

### Lot 3 — Administration des éléments

- [x] 3.1 Créer le contrôleur `acceptance_admin.php` (liste, création, édition, activation/désactivation)
- [x] 3.2 Créer la vue liste des éléments (`bs_itemsListView.php`)
- [x] 3.3 Créer le formulaire création/édition d'élément (`bs_itemFormView.php`)
- [x] 3.4 Implémenter l'upload PDF lors de la création d'un élément (réutiliser `File_compressor`)
- [x] 3.5 Créer la vue suivi des acceptations par élément (`bs_trackingView.php`)
- [x] 3.6 Implémenter les filtres : en attente, en retard, proches échéance, non rattachées
- [x] 3.7 Implémenter le rattachement d'une acceptation externe à un pilote :
  - [x] 3.7.1 Action "Rattacher à un pilote" dans le suivi des acceptations (sélecteur de membre)
  - [x] 3.7.2 Enregistrer le rattachement (linked_pilot_login, linked_by, linked_at) sans modifier l'acceptation d'origine
  - [x] 3.7.3 Indicateur visuel distinguant les acceptations rattachées et non rattachées
  - [x] 3.7.4 L'acceptation rattachée apparaît dans le dossier du pilote concerné
- [x] 3.8 Ajouter les entrées menu admin dans `bs_menu.php`
- [x] 3.9 Fichiers de langue FR/EN/NL pour l'administration
- [x] 3.10 Valider : test Playwright accès page admin, création d'un élément, suivi des acceptations (4 tests, tous passent)

### Lot 3b — Champ `code` sur `acceptance_items` (prérequis orchestrateur)

- [ ] 3b.1 Créer une migration `09X_acceptance_items_code.php` : ajout colonne `code VARCHAR(50) NULL UNIQUE COMMENT 'Code ASCII snake_case référencé par workflows.json'`
- [ ] 3b.2 Mettre à jour `application/config/migration.php`
- [ ] 3b.3 Ajouter `code` dans `Gvvmetadata.php` pour `acceptance_items` (type string, affichage admin)
- [ ] 3b.4 Ajouter `code` dans les fichiers de langue FR/EN/NL
- [ ] 3b.5 Écrire le test PHPUnit de migration (up, vérification contrainte UNIQUE, down)

> Ce champ est requis par le Module 4 (Orchestrateur) qui référence les éléments d'acceptation via `acceptance_code` dans `workflows.json`. NULL autorisé pour les éléments non gérés par l'orchestrateur.

### Lot 3c — Ciblage d'un utilisateur individuel sur `acceptance_items`

- [x] 3c.1 Créer une migration `168_acceptance_items_target_user.php` : ajout colonne `target_user_login VARCHAR(25) NULL COMMENT 'Membre individuel cible, alternative a target_roles'` + FK vers `membres(mlogin)` ON DELETE CASCADE
- [x] 3c.2 Mettre à jour `application/config/migration.php` (version 168)
- [x] 3c.3 Ajouter `target_user_login` dans `Gvvmetadata.php` pour `acceptance_items` (sélecteur membre, optionnel)
- [x] 3c.4 Adapter le formulaire de création/édition d'élément (`bs_itemFormView.php`) : choix exclusif entre un utilisateur individuel (`target_user_login`) et une ou plusieurs catégories (`target_roles`), piloté par un radio `target_mode`
- [x] 3c.5 Adapter la résolution des destinataires dans `acceptance_items_model.php` (`get_items_for_user`) pour exclure les éléments ciblant individuellement un autre utilisateur — `get_active_items` reste inchangé (non spécifique à un utilisateur)
- [x] 3c.6 Fichiers de langue FR/EN/NL
- [x] 3c.7 Écrire le test PHPUnit de migration (up, FK, down) + tests modèle pour la résolution utilisateur individuel — `AcceptanceItemsTargetUserMigrationTest.php` (3 tests) + tests ajoutés à `AcceptanceItemsModelTest.php` (4 tests), 90 tests acceptance au total, tous verts

> Complète le schéma du Lot 1 (`target_roles` seul, catégories uniquement) pour permettre de cibler un pilote précis plutôt qu'une catégorie — cf. PRD, Cas d'utilisation Administrateur.

### Lot 3d — Niveaux d'obligation et intégration message du jour (prérequis Lot 4)

> **Tranché** : 3d.4 et 3d.5 référencent tous les deux la page de validation membre (Lot 4, contrôleur `acceptance.php`, non encore implémenté) — 3d.4 pour son lien depuis le message du jour, 3d.5 comme destination du blocage. Plutôt que de créer une page de validation minimale hors plan, le schéma/modèle de ce lot (3d.1-3d.3, 3d.6-3d.8) est livré seul ; 3d.4 et 3d.5 restent à faire une fois le Lot 4 disponible. Le Lot 4 étant désormais livré, 3d.4 est fait ; 3d.5 (blocage applicatif) reste à faire.

- [x] 3d.1 Migration : remplacer `acceptance_items.mandatory` (TINYINT booléen, Lot 1) par `mandatory_level ENUM('optional','mandatory_soft','mandatory_hard') NOT NULL DEFAULT 'optional'` — migration `171_acceptance_items_mandatory_level.php`, backfill `mandatory=1 → mandatory_hard`
- [x] 3d.2 Migration sur `motd_messages` : ajout colonne `dismissible TINYINT(1) NOT NULL DEFAULT 1` (message non masquable si `0`) — migration `172_motd_messages_dismissible.php`
- [x] 3d.3 Adapter `Motd_user_state_model` pour refuser le masquage (`hidden`) d'un message `dismissible = 0` : `hide_message()` renvoie `FALSE` (contrôleur `motd.php` renvoie une erreur HTTP 403 explicite, bouton masqué côté vue si non masquable) ; `hide_all_messages()` masque les messages masquables et laisse les autres visibles (pas d'échec silencieux : ils restent affichés)
- [x] 3d.4 À la création/modification/(dés)activation d'un `acceptance_items`, générer un message du jour par destinataire ciblé (résolution `target_user_login` individuel, ou rôle x section via `acceptance_item_roles`/`Email_lists_model::get_users_by_role_and_section()`, ou — sans ciblage — tous les membres actifs), en excluant ceux ayant déjà accepté/refusé ; niveau/masquabilité dérivés de `mandatory_level` (`optional` → masquable, `mandatory_soft`/`mandatory_hard` → non masquable), lien vers `acceptance/read/<item_id>` — `Acceptance_items_model::sync_target_motd()`/`clear_target_motd()`/`clear_target_motd_for_user()`, appelés depuis `acceptance_admin.php` (création/édition/`toggle_active`/`delete`) et `acceptance.php` (`accept`/`refuse`, retrait du message de l'utilisateur qui vient de traiter l'élément). **Tranché** : toujours `target_type='user'` (un message par destinataire), jamais de diffusion `target_type='all'`/liste partagée, pour que chaque personne puisse faire disparaître son propre message indépendamment des autres destinataires sans casser la règle 3d.3 (non-masquable tant que non validé) — tests `AcceptanceItemsModelTest.php` (section `sync_target_motd`)
- [ ] 3d.5 Implémenter le blocage applicatif global pour `mandatory_hard` : filtre/hook redirigeant toute requête utilisateur vers la page de validation tant que l'élément n'est pas validé (exceptions : page de validation elle-même, déconnexion) — **bloqué sur le Lot 4** ; précédent identifié dans le code existant : `MY_Controller::_check_login_permission()` (`application/core/MY_Controller.php`), seul mécanisme actuel de redirection globale conditionnelle exécuté pour tous les contrôleurs
- [x] 3d.6 Adapter `Gvvmetadata.php` et le formulaire admin (`bs_itemFormView.php`) pour `mandatory_level` (menu déroulant à 3 valeurs, badges liste/suivi)
- [x] 3d.7 Fichiers de langue FR/EN/NL
- [x] 3d.8 Tests PHPUnit : migrations 171/172 (up/idempotence/down), refus de masquage MOTD pour `dismissible = 0` (unitaire + `hide_all_messages`), mise à jour des fixtures existantes (`mandatory` → `mandatory_level`)

> **Tranché** : le blocage `mandatory_hard` (3d.5) exempte la déconnexion et la page de validation elle-même. Les club-admins (rôle `club-admin`) ne sont jamais bloqués par une acceptation, quel que soit le niveau d'obligation — ils gardent un accès complet à GVV pour ne pas risquer de se bloquer eux-mêmes hors d'état d'administrer le club. Cf. PRD, Canal de notification et niveaux d'obligation.

### Lot 4 — Acceptation interne (utilisateurs membres) — priorité (voir Note de priorité)

Premier lot fonctionnel livré, centré sur la catégorie `document` et son lien avec les documents déjà archivés (`archived_documents`). Dépend du Lot 3d pour les niveaux d'obligation et la notification par message du jour.

> **Tranché** : un élément `acceptance_items` de catégorie `document` référence un `archived_documents.id` existant (nouvelle colonne `archived_document_id` nullable, FK) plutôt que de téléverser un nouveau `pdf_path` propre à l'acceptation — cohérent avec l'intitulé du lot et l'évolution probable notée dans le PRD (formulaires → document archivé → acceptance). `pdf_path` (Lot 1) reste utilisable pour les autres catégories qui n'ont pas de document archivé source.

> **Tranché** : le ciblage par rôle(s) utilise une table de jointure `acceptance_item_roles` (item_id, types_roles_id, section_id nullable = toutes sections), sur le modèle de `email_list_roles`/`_criteria_tab.php` (grille rôle x section), plutôt que le champ texte libre `acceptance_items.target_roles` (noms de rôles en clair, sans notion de section). `target_roles` est conservé en base pour compatibilité mais n'est plus alimenté par le formulaire admin.

- [x] 4.0 Colonne `acceptance_items.archived_document_id` (migration `169_acceptance_items_archived_document.php`, FK `ON DELETE SET NULL`). Bouton d'action sur chaque document PDF de la liste des documents archivés (`archived_documents/page`) menant à la liste des acceptations déjà liées à ce document (`acceptance_admin/page?filter_archived_document_id=<id>`, bannière + filtre persistant), avec un bouton "Nouvelle demande d'acceptation pour ce document" menant au formulaire pré-rempli (`acceptance_admin/create/<archived_document_id>`, catégorie verrouillée sur `document`, document affiché en lecture seule à la place de l'upload PDF)
- [x] 4.0bis Simplification du formulaire de création/édition (`bs_itemFormView.php`) : `target_type` et double validation (`dual_validation`/`role_1`/`role_2`) masqués (inutilisés pour l'instant) ; ciblage par rôle remplacé par une grille rôle x section (table `acceptance_item_roles`, migration `170_acceptance_item_roles.php`), réutilisant le sélecteur des listes d'email (`Email_lists_model::get_available_roles/get_available_sections`) ; date de version verrouillée sur la date de dépôt du document archivé lorsque l'élément en référence un
> **Tranché** : le viewer PDF (4.4.1) est un simple `<iframe>` — un navigateur affiche un PDF via son lecteur natif (PDFium, etc.), dont le défilement interne n'est pas observable en JS depuis la page GVV. La détection de « défilement complet » (4.4.2) porte donc sur la page GVV elle-même : un repère (`#acceptanceReadSentinel`) est placé juste après l'iframe, et un `IntersectionObserver` révèle les boutons Accepter/Refuser quand ce repère devient visible. PDF.js (rendu page par page, défilement interne réellement observable) a été écarté pour cette première livraison — plus robuste mais nouvelle dépendance JS tierce à vendorer manuellement.

- [x] 4.1 Contrôleur membre `acceptance.php` (distinct de `acceptance_admin.php`) : `dashboard()`, `read($item_id)`, `accept($item_id)`, `refuse($item_id)`, `pdf($item_id)` (flux inline pour l'iframe), `history()`. Autorisation : élément actuellement ciblé pour l'utilisateur (`Acceptance_items_model::get_items_for_user()`, réécrite pour résoudre le ciblage rôle x section de `acceptance_item_roles` — l'ancienne version ne traitait que `target_user_login`), ou élément déjà pourvu d'un enregistrement personnel (relecture même si le ciblage a changé depuis), ou administrateur
- [x] 4.2 Vue tableau de bord (`acceptance/bs_dashboardView.php`) : cartes des éléments en attente (`Acceptance_items_model::get_pending_items_for_user()`), badges niveau d'obligation et échéance (en retard / proche)
- [x] 4.3 Badge du nombre d'éléments en attente dans `bs_menu.php` (menu Membres → « Mes acceptations »)
- [x] 4.4 Vue lecture et acceptation (`acceptance/bs_readView.php`) :
  - [x] 4.4.1 Viewer PDF intégré (iframe, cf. Tranché ci-dessus)
  - [x] 4.4.2 Détection défilement complet (IntersectionObserver sur repère de page, cf. Tranché ci-dessus)
  - [x] 4.4.3 Boutons Accepter/Refuser masqués (`.d-none`) jusqu'au défilement complet
  - [x] 4.4.4 Bouton "Refuser" (confirmation JS)
  - [x] 4.4.6 Message informatif en haut de page
- [x] 4.4.5bis Bouton "Plus tard" — implémenté sur le tableau de bord (`bs_dashboardView.php`), pas sur la vue de lecture, conformément à la section "Interfaces Utilisateur > Membre > Tableau de bord" du PRD ; affiché uniquement pour un élément facultatif dont la date limite n'est pas atteinte. Aucun état "reporté" n'existe en base (le modèle de données ne prévoit que pending/accepted/refused) : le bouton ramène simplement au tableau de bord, l'élément y reste tant qu'il n'est pas traité
- [x] 4.5 Formule d'acceptation enregistrée automatiquement (`acceptance_records.formula_text`) avec horodatage (`acted_at`), au clic sur Accepter — `Acceptance_records_model::get_or_create_pending()` + `accept()`/`refuse()` existants (Lot 2)
- [x] 4.6 Vue historique personnel (`acceptance/bs_historyView.php`) : éléments déjà acceptés/refusés, indicateur de retard
- [x] 4.7 Relire et modifier une réponse précédente : le lien "Relire" de l'historique rouvre `acceptance/read/<id>`, qui réaffiche le statut actuel et permet de changer de décision (accepter après refus, etc.)
- [x] 4.8 Fichiers de langue FR/EN/NL pour les membres (`acceptance_lang.php`, section "Member interface")
- [x] 4.9 Validé : tests PHPUnit modèle (ciblage rôle x section, `get_pending_items_for_user`, `get_or_create_pending`) + test Playwright bout en bout (admin cible un membre → membre lit, défile, accepte → disparaît du tableau de bord → apparaît dans l'historique)

> **Amendement (15 août 2026)** — périmètre de création restreint aux documents déjà archivés : un nouvel élément ne peut désormais être créé qu'en référençant un `archived_documents.id` existant, via un sélecteur, plutôt que par upload PDF libre ou choix de catégorie. `pdf_path`/`pdf_file` (Lot 1) et les catégories `formation`/`controle`/`briefing`/`autorisation` restent utilisables uniquement pour l'édition/téléchargement des éléments existants créés avant cet amendement — plus aucun nouvel élément de ce type. Justification : la contrainte d'archiver un document au préalable est mineure pour la quasi-totalité des cas d'usage, y compris l'approbation de séances de formation (export de la fiche de progression en document archivé) — cf. « Évolution probable », Note de priorité. Le sélecteur liste toutes sections confondues (pas de filtre par section active de l'admin) ; la fiche du document choisi est réaffichée par rendu serveur (pas de synchronisation JS).

- [x] 4.10 `Archived_documents_model` : `image($key)` enrichi (jointure `document_types`/`membres`, absente de `get_by_id()` — format `"{type} — {description}"`, `"... (Pilote: {nom} {prénom})"` si `pilot_login`, `"... (Immat: {machine_immat})"` si `machine_immat`) ; `selector($where = array())` ajouté, filtré sur `is_current_version = 1`, toutes sections — tests `ArchivedDocumentsModelTest.php`
- [x] 4.11 `acceptance_admin.php` : `create()` sans `archived_document_id` affiche désormais une étape préalable de sélection (nouvelle vue `acceptance_admin/selectDocumentView.php`, sélecteur `archived_document_id` en select2/`big_select`) ; choisir un document y navigue vers `create/<id>`, qui réutilise tel quel le formulaire pré-rempli existant (catégorie forcée à `document`, document affiché en lecture seule) — pas de duplication de cet affichage, pas de synchronisation JS. `bs_itemFormView.php` : le dropdown `category` et l'upload `pdf_file` ne s'affichent plus qu'en édition d'un élément legacy (`$action == MODIFICATION` sans `archived_document_id`) ; `_handle_pdf_upload`/`_delete_item_pdf` conservés tels quels pour ce seul cas
- [x] 4.12 Fichiers de langue FR/EN/NL pour le nouveau sélecteur (`acceptance_choose_document`, `acceptance_select_document_help`)
- [x] 4.13 Tests Playwright : `acceptance-admin-smoke.spec.js` adapté (fixture `archived_documents` en `beforeAll`, les 4 tests de création passent par l'étape de sélection) ; le test "should access create form" couvre les deux étapes (sélecteur seul, puis formulaire pré-rempli après choix) — PHPUnit `ArchivedDocumentsModelTest.php` : 8 nouveaux tests (`image()`/`selector()`), 38/38 verts ; suite Playwright acceptance/archived_documents/motd rejouée sans régression

### Lot 5 — Double validation (formations et contrôles) — secondaire (voir Note de priorité)

- [ ] 5.1 Créer le contrôleur `acceptance_training.php` (délivrance, confirmation, suivi)
- [ ] 5.2 Créer la vue délivrance de formation (`bs_training_deliver.php`) :
  - [ ] 5.2.1 Sélecteur d'élève
  - [ ] 5.2.2 Sélecteur de type de formation
  - [ ] 5.2.3 Date de la formation
  - [ ] 5.2.4 Bouton "Valider la délivrance"
- [ ] 5.3 Créer le mécanisme de notification à l'élève (flag en base + affichage au tableau de bord)
- [ ] 5.4 Créer la vue confirmation élève (`bs_training_confirm.php`)
- [ ] 5.5 Créer la vue suivi formations instructeur (`bs_training_instructor_list.php`)
- [ ] 5.6 Créer la vue historique formations élève (`bs_training_student_list.php`)
- [ ] 5.7 Gérer les formules automatiques (délivrance instructeur + réception élève)
- [ ] 5.8 Fichiers de langue FR/EN/NL
- [ ] 5.9 Valider : test PHPUnit double validation, test Playwright workflow complet instructeur→élève

### Lot 6 — Notifications

- [ ] 6.1 Implémenter la détection des éléments en attente à la connexion (hook dans le contrôleur de login ou layout)
- [ ] 6.2 Afficher un badge dans le menu avec le nombre d'éléments en attente
- [ ] 6.3 Créer le script/cron de détection des acceptations proches de la date limite
- [ ] 6.4 Implémenter l'envoi d'emails de rappel (réutiliser l'infrastructure email existante)
- [ ] 6.5 Notifications pour les doubles validations en attente (instructeur notifié quand élève n'a pas confirmé)
- [ ] 6.6 Valider : test PHPUnit détection en attente, vérifier affichage badge

### Lot 7 — Indicateurs visuels & date limite

- [ ] 7.1 Implémenter les indicateurs visuels Bootstrap 5 :
  - [ ] 7.1.1 Badge "en retard" (rouge) après date limite
  - [ ] 7.1.2 Badge "proche échéance" (orange) dans les X jours avant
  - [ ] 7.1.3 Badge "en attente" (bleu)
  - [ ] 7.1.4 Badge "accepté" (vert) / "refusé" (gris)
- [ ] 7.2 Afficher clairement "À accepter avant le [date]" sur chaque élément
- [ ] 7.3 Implémenter le filtre admin : en retard, proches échéance, en attente
- [ ] 7.4 Valider : vérification visuelle des badges, test Playwright

### Lot 8 — Export et rapports

- [ ] 8.1 Implémenter l'export CSV des acceptations par élément (admin)
- [ ] 8.2 Implémenter l'export de la liste des personnes n'ayant pas encore accepté
- [ ] 8.3 Valider : test PHPUnit format CSV, test Playwright téléchargement

### Lot 9 — Internationalisation complète

- [ ] 9.1 Vérifier que tous les libellés UI utilisent `$this->lang->line()`
- [ ] 9.2 Compléter les traductions EN et NL
- [ ] 9.3 Vérifier les formules d'acceptation dans les 3 langues
- [ ] 9.4 Valider : revue des fichiers de langue, aucune chaîne en dur dans les vues

### Lot 10 — Tests finaux & intégration

- [ ] 10.1 Exécuter `./run-all-tests.sh` — tous les tests PHPUnit passent
- [ ] 10.2 Tests Playwright smoke : accès aux pages admin, membre
- [ ] 10.3 Test Playwright E2E : parcours complet acceptation
- [ ] 10.4 Test Playwright E2E : parcours complet double validation formation
- [ ] 10.5 Vérifier les permissions (rôles admin, membre, instructeur)
- [ ] 10.6 Revue de sécurité : CSRF, XSS

---

## Schéma de base de données proposé

Schéma tel que livré par le Lot 1 (migration `068_acceptance_system.php`), avant la révision du PRD. Les colonnes/tables `target_type` (externe), `signature_mode`, `linked_pilot_login`/`linked_by`/`linked_at`, `acceptance_signatures` et `acceptance_tokens` couvrent la signature externe et le rattachement différé, désormais hors périmètre — elles restent en base sans retrait rétroactif mais ne sont plus alimentées par un nouveau développement.

`target_roles` ne couvre que le ciblage par catégorie. Le Lot 3c ajoute `target_user_login` pour cibler un utilisateur individuel, cf. PRD (Cas d'utilisation Administrateur).

`mandatory` (TINYINT booléen) ne couvre qu'un obligatoire/facultatif binaire. Le Lot 3d le remplace par `mandatory_level` (trois niveaux) et ajoute `motd_messages.dismissible`, cf. PRD (Canal de notification et niveaux d'obligation).

### Table `acceptance_items`

```sql
CREATE TABLE `acceptance_items` (
  `id` INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL COMMENT 'Titre de l element',
  `category` ENUM('document','formation','controle','briefing','autorisation') NOT NULL COMMENT 'Categorie acceptation',
  `pdf_path` VARCHAR(255) NULL COMMENT 'Chemin fichier PDF',
  `target_type` ENUM('internal','external') NOT NULL DEFAULT 'internal' COMMENT 'Interne ou externe',
  `version_date` DATE NULL COMMENT 'Date de creation/version',
  `mandatory` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Obligatoire',
  `deadline` DATE NULL COMMENT 'Date limite acceptation',
  `dual_validation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Necessite double validation',
  `role_1` VARCHAR(64) NULL COMMENT 'Premier role (ex: instructeur)',
  `role_2` VARCHAR(64) NULL COMMENT 'Second role (ex: eleve)',
  `target_roles` VARCHAR(255) NULL COMMENT 'Roles cibles separes par virgule (pilotes, instructeurs, bureau)',
  `active` TINYINT(1) NOT NULL DEFAULT 1 COMMENT 'Element actif',
  `created_by` VARCHAR(25) NOT NULL COMMENT 'Administrateur createur',
  `created_at` DATETIME NOT NULL,
  `updated_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  KEY `idx_category` (`category`),
  KEY `idx_active` (`active`),
  KEY `idx_deadline` (`deadline`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_records`

```sql
CREATE TABLE `acceptance_records` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `item_id` INT(11) UNSIGNED NOT NULL COMMENT 'Element concerne',
  `user_login` VARCHAR(25) NULL COMMENT 'Membre (NULL si externe)',
  `external_name` VARCHAR(128) NULL COMMENT 'Nom complet personne externe',
  `status` ENUM('pending','accepted','refused') NOT NULL DEFAULT 'pending',
  `validation_role` VARCHAR(64) NULL COMMENT 'Role dans double validation',
  `partner_record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement partenaire (double validation)',
  `formula_text` TEXT NULL COMMENT 'Formule enregistree',
  `acted_at` DATETIME NULL COMMENT 'Date action',
  `created_at` DATETIME NOT NULL,
  `initiated_by` VARCHAR(25) NULL COMMENT 'Responsable ayant initie (si externe)',
  `signature_mode` ENUM('direct','link','qrcode','paper') NULL COMMENT 'Mode signature externe',
  `linked_pilot_login` VARCHAR(25) NULL COMMENT 'Pilote rattache ulterieurement (acceptation externe)',
  `linked_by` VARCHAR(25) NULL COMMENT 'Utilisateur ayant effectue le rattachement',
  `linked_at` DATETIME NULL COMMENT 'Date du rattachement',
  PRIMARY KEY (`id`),
  KEY `idx_item` (`item_id`),
  KEY `idx_user` (`user_login`),
  KEY `idx_status` (`status`),
  KEY `idx_partner` (`partner_record_id`),
  CONSTRAINT `fk_acceptance_records_item` FOREIGN KEY (`item_id`)
    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_acceptance_records_user` FOREIGN KEY (`user_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_partner` FOREIGN KEY (`partner_record_id`)
    REFERENCES `acceptance_records` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_linked_pilot` FOREIGN KEY (`linked_pilot_login`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL,
  CONSTRAINT `fk_acceptance_records_linked_by` FOREIGN KEY (`linked_by`)
    REFERENCES `membres` (`mlogin`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_signatures`

```sql
CREATE TABLE `acceptance_signatures` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `record_id` BIGINT(20) UNSIGNED NOT NULL,
  `signer_first_name` VARCHAR(64) NOT NULL,
  `signer_last_name` VARCHAR(64) NOT NULL,
  `signer_quality` VARCHAR(64) NULL COMMENT 'pere, mere, tuteur legal (pour autorisation)',
  `beneficiary_first_name` VARCHAR(64) NULL COMMENT 'Prenom mineur (pour autorisation)',
  `beneficiary_last_name` VARCHAR(64) NULL COMMENT 'Nom mineur (pour autorisation)',
  `signature_type` ENUM('tactile','upload') NOT NULL,
  `signature_data` MEDIUMTEXT NULL COMMENT 'Donnees base64 signature tactile',
  `file_path` VARCHAR(255) NULL COMMENT 'Chemin fichier uploade',
  `original_filename` VARCHAR(255) NULL,
  `file_size` INT(11) UNSIGNED NULL,
  `mime_type` VARCHAR(64) NULL,
  `signed_at` DATETIME NOT NULL,
  `pilot_attestation` TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Attestation presence pilote (mode papier)',
  PRIMARY KEY (`id`),
  KEY `idx_record` (`record_id`),
  CONSTRAINT `fk_signatures_record` FOREIGN KEY (`record_id`)
    REFERENCES `acceptance_records` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

### Table `acceptance_tokens`

```sql
CREATE TABLE `acceptance_tokens` (
  `id` BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
  `token` VARCHAR(128) NOT NULL COMMENT 'Token aleatoire',
  `item_id` INT(11) UNSIGNED NOT NULL,
  `record_id` BIGINT(20) UNSIGNED NULL COMMENT 'Enregistrement associe une fois cree',
  `mode` ENUM('direct','link','qrcode') NOT NULL,
  `created_by` VARCHAR(25) NOT NULL,
  `created_at` DATETIME NOT NULL,
  `expires_at` DATETIME NOT NULL,
  `used` TINYINT(1) NOT NULL DEFAULT 0,
  `used_at` DATETIME NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_token` (`token`),
  KEY `idx_item` (`item_id`),
  KEY `idx_expires` (`expires_at`),
  CONSTRAINT `fk_tokens_item` FOREIGN KEY (`item_id`)
    REFERENCES `acceptance_items` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8;
```

---

## Stockage fichiers

```
uploads/
└── acceptances/
    └── items/              # PDF des éléments à accepter
        └── <item_id>/
            └── document.pdf
```

Le sous-répertoire `signatures/` (signatures et documents signés externes) a été livré avec le Lot 1 mais ne reçoit plus de nouveau contenu depuis la révision du PRD.

---

## Dépendances externes à intégrer

Aucune — les dépendances propres à la signature externe (Signature Pad JS, `phpqrcode`) ne sont plus nécessaires depuis la révision du PRD.

---

## Critères de fin

- [ ] Workflow acceptation simple : création élément → notification → lecture → acceptation en un clic → traçabilité
- [ ] Workflow double validation : instructeur valide → élève notifié → élève confirme → traçabilité complète
- [ ] Date limite : affichage, indicateurs visuels, filtres en retard
- [ ] Processus lecture obligatoire : défilement complet avant bouton accepter
- [ ] Export des acceptations
- [ ] Tous les tests PHPUnit et Playwright passent
- [ ] Internationalisation FR/EN/NL complète
