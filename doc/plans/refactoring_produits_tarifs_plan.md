# Plan de développement — Refactoring `tarifs` → `produits` + `tarifs`

> Aucun PRD dédié n'existe pour ce refactoring technique interne (pas de nouvelle exigence
> métier, uniquement une restructuration de données). Ce document intègre donc le contexte,
> les objectifs et les critères de succès directement, comme le prévoit la commande `/plan`
> en l'absence de PRD. Un design note (`doc/design_notes/`) avec le diagramme ERD (plantuml)
> doit être produit à l'étape 1 avant toute modification de code.

---

## 1. Contexte et problème à résoudre

La table `tarifs` mélange aujourd'hui deux notions :

- **le produit** : `reference`, `description`, `compte`, `club`, `is_cotisation`,
  `nb_personnes_max`, `public`, `type_ticket` — des attributs qui ne devraient pas varier
  d'une ligne de prix à l'autre pour une même référence ;
- **le tarif à une date donnée** : `date`, `date_fin`, `prix`, `nb_tickets` — la partie qui
  varie réellement dans le temps.

Une même `reference` (ex. `"Heure de vol Dynamic"`) possède plusieurs lignes dans `tarifs`,
une par période de prix. C'est déjà le modèle utilisé implicitement par tout le code de
facturation (`tarifs_model::get_tarif($reference, $date)` sélectionne la ligne dont la `date`
est la plus proche ≤ date facturée). Le refactoring rend cette intention explicite et évite la
duplication/incohérence des attributs produit sur chaque ligne de prix.

## 2. Objectifs

1. Séparer `tarifs` en `produits` (identité du produit) et `tarifs` (historique de prix, `produit_id` → `produits.id`).
2. Fournir un CRUD Produits avec un bouton « Tarifs » ouvrant le CRUD des tarifs du produit sélectionné.
3. Supprimer `saisie_par` (redondant avec `created_by`), ajouter les 4 champs d'audit sur les deux tables.
4. **Zéro impact fonctionnel** : refacturer un vol ou un achat existant avant/après le refactoring doit produire des écritures strictement identiques (même compte, même montant, même date, même description).
5. Migrer toutes les données de production sans perte, avec une fenêtre de bascule sûre et réversible.

## 3. Hors périmètre

- Pas de changement des règles de facturation elles-mêmes (`Facturation*.php` métier).
- Pas de changement du format des écritures comptables.
- Pas de refonte des `types_ticket`, `comptes`, `sections`.

---

## 4. Points de vigilance identifiés (analyse du code existant)

Ces constats conditionnent la stratégie de migration (§5) — à valider avant de démarrer.

| # | Constat | Fichier(s) | Implication |
|---|---|---|---|
| A | La clé fonctionnelle réelle est le couple **(`reference`, `club`)**, pas `reference` seule : deux sections peuvent avoir la même référence. `get_tarif()` et `select_page()` filtrent toujours par `club`. | `application/models/tarifs_model.php` (`get_tarif`, `selector`) | `produits` doit avoir une contrainte `UNIQUE(reference, club)`, pas `UNIQUE(reference)`. |
| B | Au moins une surcharge club (**ACES**) stocke directement le **`tarifs.id`** (clé primaire) dans `avions.maprix` / `pompes.ppu`, au lieu de la `reference` texte utilisée partout ailleurs. | `application/libraries/Facturation_aces.php:100,182` | Les `id` existants de `tarifs` **doivent être préservés à l'identique** — impose une migration *ALTER en place* plutôt qu'une recréation avec renumérotation. |
| C | 8 accès SQL directs à la table plate `tarifs` **hors du modèle**, qui mélangent colonnes produit et colonnes prix dans une seule requête. | `application/controllers/reservations.php:961` (`_get_tarif_price`)<br>`application/controllers/welcome.php:250`<br>`application/controllers/vols_decouverte.php:226,925,1339,1482` | Ces requêtes doivent être réécrites en jointure `produits`/`tarifs` — elles ne passent pas par `tarifs_model` donc un simple refactor du modèle ne suffit pas. |
| D | Plusieurs endroits appellent `tarifs_model->get_by_id('reference', ...)` ou `get_by_id('id', ...)`, `selector()`, `get_tarif()` en s'attendant à un résultat "plat" (produit + prix mélangés). | `achats_model.php`, `vols_decouverte_model.php`, `pompes_model.php`, `Facturation*.php`, `avion.php`, `planeur.php`, `compta.php`, `config.php` | `Tarifs_model` doit rester une **façade de compatibilité** : mêmes signatures, mêmes clés de retour, résultat obtenu via jointure interne. |
| E | Les attributs "produit" (`description`, `compte`, `is_cotisation`, `nb_personnes_max`, `public`, `type_ticket`) sont actuellement stockés sur *chaque* ligne de prix et pourraient diverger entre deux lignes d'une même référence dans les données réelles (saisie historique). | Données en base | Nécessite un **audit de données** avant la migration (§5.2) avec règle de résolution explicite (dernière ligne par date fait foi) et rapport des divergences pour revue manuelle. |
| F | `type_ticket` n'est mentionné dans aucune des deux tables par la demande initiale. | — | **Décision à valider** (§6) : proposition = le placer dans `produits` (c'est un attribut du produit — quel type de ticket il crédite — pas du prix). |

---

## 5. Stratégie de migration retenue

Migration **en place**, en 3 étapes séparées par des migrations CI numérotées, avec une
période de transition où l'ancien schéma plat et le nouveau schéma coexistent :

1. **Créer `produits`** et le peupler par agrégation des lignes `tarifs` groupées par
   `(reference, club)` (résolution des divergences = valeurs de la ligne la plus récente).
2. **Ajouter `tarifs.produit_id`**, le back-filler par jointure sur `(reference, club)`, poser
   la contrainte de clé étrangère. `tarifs.id` n'est jamais renuméroté. Les anciennes colonnes
   restent en place à ce stade (double lecture possible, aucun code cassé).
3. **Basculer le code** (modèle façade, contrôleurs, vues, requêtes SQL directes) sur le
   nouveau schéma, tout en vérifiant la non-régression à chaque étape.
4. **Nettoyage final** : suppression des colonnes redondantes de `tarifs`
   (`reference`, `description`, `compte`, `club`, `is_cotisation`, `nb_personnes_max`,
   `public`, `type_ticket`, `saisie_par`) une fois tout le code basculé et validé.

Cette approche évite de renuméroter `tarifs.id` (point B) et permet de tester chaque étape
indépendamment avant le point de non-retour (suppression des colonnes).

---

## 6. Décisions à valider avant de démarrer (étape 0)

- [x] **`type_ticket`** : confirmer son placement dans `produits` (recommandé) plutôt que `tarifs`. Confirmé
- [x] **Nom de la vue de listing** : conserver `vue_tarifs` (minimise les changements dans `Gvvmetadata.php`) ou introduire `vue_produits` + `vue_tarifs_produit` distincts. Introduire vue produit.
- [x] **Règle de résolution des divergences** (point E) : valider "la ligne avec la `date` la plus récente fait foi pour les attributs produit", et décider si un rapport de divergences bloque la migration ou est seulement informatif. Les divergences sont bloquantes, la migration ne peut pas continuer tant qu'elles ne sont pas résolues.
- [x] **Fenêtre de maintenance** : confirmer si la migration de données (étape 2) peut tourner en ligne ou nécessite un arrêt applicatif court sur la base de production. AU plus simple, suspendre l'exploitation si nécéssaire.

---

## 7. Phasage détaillé

### Étape 1 — Design note (aucun code applicatif)

- [x] Créer `doc/design_notes/refactoring_produits_tarifs.md` : schéma cible, diagramme ERD plantuml (`doc/design_notes/diagrams/produits_tarifs.puml` + image liée), règles de compatibilité (façade `Tarifs_model`), liste des points de vigilance (§4).
- [x] Revue et validation du design note par l'utilisateur avant de continuer.

**Validation** : le document est lisible, le diagramme s'affiche sur GitHub, aucune ambiguïté sur le schéma cible.

---

### Étape 2 — Audit des données existantes

- [x] Écrire une requête (ou petit script PHP jetable, non conservé) qui, pour chaque
      `(reference, club)`, compare les valeurs de `description`, `compte`, `is_cotisation`,
      `nb_personnes_max`, `public`, `type_ticket` entre toutes les lignes du groupe.
- [x] Produire un rapport texte des groupes divergents (le cas échéant) pour chaque club/section utilisant GVV. Voir [`doc/design_notes/refactoring_produits_tarifs_audit.md`](../design_notes/refactoring_produits_tarifs_audit.md).
- [x] Faire valider la règle de résolution (dernière date fait foi) au vu du rapport, ou corriger manuellement les données sources si des divergences sont anormales. Rapport : aucune divergence anormale, aucune correction nécessaire.

**Validation** : rapport revu, aucune divergence bloquante non expliquée.

- [x] ☑ Étape 2 terminée et validée

---

### Étape 3 — Affichage détaillé des erreurs de migration (correctif générique, toutes migrations)

Constat (revue effectuée avant cette étape) : en l'état actuel, un échec de migration n'affiche
**aucun diagnostic exploitable** dans deux des trois cas possibles :

1. Erreur structurelle (fichier/classe/méthode manquants) → déjà affichée via `show_error()`,
   mais message générique.
2. **Erreur SQL au sein d'une étape** (`$this->db->query()` qui échoue) : `db_debug = FALSE`
   (`application/config/database.php:51`) fait que la requête retourne simplement `FALSE` sans
   lever d'erreur, et `CI_Migration::version()` (`system/libraries/Migration.php:206`)
   **n'examine jamais** le résultat de l'étape exécutée — la migration est marquée réussie alors
   qu'elle a échoué en silence.
3. **Exception PHP levée explicitement dans une migration** (pattern recommandé du projet, ex.
   `024_sections.php`) : aucun `set_exception_handler` n'existe dans le code, et
   `application/controllers/migration.php::to_level()` n'entoure pas l'appel à
   `$this->migration->version()` d'un `try/catch`. L'exception remonte non interceptée ; en
   `ENVIRONMENT = production` (défaut, `index.php:22`), `error_reporting(0)` supprime tout
   affichage → page blanche, aucun diagnostic visible, même quand la migration avait construit
   un message détaillé.

Cette étape corrige les 3 cas pour **toutes les migrations présentes et futures**, pas
seulement celles de ce refactoring. Elle est placée avant les migrations 146/147/148 pour que
leur mise au point en environnement de test bénéficie immédiatement d'un diagnostic fiable.

- [x] Créer `application/libraries/MY_Migration.php` (extension `MY_` du core CI — **ne pas
      modifier** `system/libraries/Migration.php`) : surcharge `version()` pour, après
      l'exécution de chaque étape (`call_user_func(array($migration_instance, $method))`),
      vérifier `$this->db->_error_message()` / `_error_number()` et lever une exception
      explicite si une erreur SQL est présente, au lieu de continuer silencieusement. Le
      constructeur de `CI_Migration` ne pouvant pas être réutilisé via `parent::__construct()`
      (son garde anti-double-init se déclenche pour toute sous-classe, y compris en appelant
      explicitement le parent — vérifié empiriquement), il est dupliqué à l'identique dans
      `MY_Migration::__construct()`.
- [x] Le message d'exception inclut : nom du fichier de migration, méthode (`up`/`down`),
      niveau visé, numéro et message d'erreur SQL.
- [x] Modifier `application/controllers/migration.php::to_level()` : entourer l'appel à
      `$this->migration->version($target_level)` d'un `try/catch (Throwable $e)`. En cas
      d'exception : logguer via `gvv_error()` puis afficher le détail via `show_error()`
      (message de l'exception + niveau de départ/cible) — l'affichage passe par notre propre
      code, donc il fonctionne quel que soit `ENVIRONMENT`/`error_reporting`.
- [x] Le cas déjà géré (erreurs structurelles via `error_string()`) reste au moins aussi
      informatif qu'aujourd'hui (logique inchangée, seule la vérification post-étape est ajoutée).

**Validation** :
- [x] Test manuel : migration temporaire `146_test_failing_migration.php` (SQL invalide),
      `migration_version` temporairement porté à 146, migration lancée depuis l'IHM
      (`gvv.net`, `ENVIRONMENT=production`) via requêtes HTTP authentifiées (`testadmin`) —
      message détaillé affiché (fichier, méthode, niveau visé, code et message MySQL) au lieu
      d'une page blanche. Table `migrations` vérifiée intacte (`version = 145`) après l'échec.
      Migration de test et configuration supprimées ensuite.
- [x] Nouveau test PHPUnit conservé : `application/tests/mysql/MyMigrationErrorHandlingTest.php`
      (3 tests : erreur SQL silencieuse, exception explicite, chemin nominal non impacté).
- [x] `playwright/tests/migration-test.spec.js` : skip par défaut (`RUN_MIGRATION_TEST` non
      positionné, test destructif protégé) — comportement de base inchangé, pas de régression.
- [x] `./run-all-tests.sh` : 1611 tests passés, 0 échec, 48 skips pré-existants sans rapport
      avec ce changement.

- [x] ☑ Étape 3 terminée et validée — **prérequis avant de lancer les migrations 146/147/148**
      pour garantir un diagnostic exploitable en cas d'échec.

---

### Étape 4 — Migration 146 : création de `produits`

Fichier : `application/migrations/146_create_produits_table.php`

- [x] Créer la table `produits` :
  ```sql
  CREATE TABLE `produits` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `reference` varchar(32) NOT NULL,
    `description` varchar(80) DEFAULT NULL,
    `compte` int(11) NOT NULL DEFAULT 0,
    `club` tinyint(1) DEFAULT 0,
    `is_cotisation` tinyint(1) NOT NULL DEFAULT 0,
    `nb_personnes_max` tinyint(3) UNSIGNED NOT NULL DEFAULT 1,
    `public` tinyint(4) DEFAULT 1,
    `type_ticket` int(11) DEFAULT NULL,
    `created_by` varchar(25) DEFAULT NULL,
    `created_at` datetime DEFAULT NULL,
    `updated_by` varchar(25) DEFAULT NULL,
    `updated_at` datetime DEFAULT NULL,
    PRIMARY KEY (`id`),
    UNIQUE KEY `reference_club` (`reference`, `club`)
  ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb3 COLLATE=utf8mb3_general_ci;
  ```
- [x] Peupler `produits` par agrégation de `tarifs` groupé par `(reference, club)`, valeurs prises sur la ligne de `date` maximale du groupe (départage par `id` le plus grand en cas d'égalité de date, cf. règle validée étape 2). `created_at`/`created_by` = ceux de la ligne la plus ancienne du groupe (départage par `id` le plus petit) ; `updated_at`/`updated_by` = ceux de la ligne la plus récente. Garde-fou intégré à la migration : lève une exception si `COUNT(*)` de `produits` diffère du nombre de groupes `(reference, club)` de `tarifs`.
- [x] Mettre à jour `application/config/migration.php` → `146`.

**Validation** :
- [x] `php -l` sur le fichier de migration.
- [x] `COUNT(DISTINCT reference, club)` dans `tarifs` (102) == `COUNT(*)` dans `produits` (102), et par club : 78/10/8/6 — identique à l'audit étape 2.
- [x] Contrôle manuel exécuté via l'IHM (`gvv.net`, migration 145→146) et vérification SQL directe sur les 5 groupes divergents identifiés à l'audit (Déjeuner, Diner, Heure de vol Dynamic, Nuitée Troyes, Remorqué 500m) : valeurs `produits` conformes à la règle "ligne la plus récente fait foi" dans tous les cas.

- [x] ☑ Étape 4 terminée et validée

---

### Étape 5 — Migration 147 : `tarifs.produit_id`

Fichier : `application/migrations/147_tarifs_add_produit_id.php`

- [x] `ALTER TABLE tarifs ADD COLUMN produit_id INT(11) NULL AFTER id;`
- [x] `UPDATE tarifs t JOIN produits p ON t.reference = p.reference AND t.club = p.club SET t.produit_id = p.id;`
- [x] Vérifier `SELECT COUNT(*) FROM tarifs WHERE produit_id IS NULL` == 0, sinon échec de migration explicite (exception).
- [x] **Écart au plan initial, constaté par `./run-all-tests.sh`** : `ALTER TABLE tarifs MODIFY produit_id INT(11) NOT NULL` n'est **pas** posé à cette étape. La façade `Tarifs_model` n'étant réécrite qu'à l'étape 7, le code applicatif actuel — et plusieurs tests (`ReservationsBalanceCheckTest`, `PaiementsEnLigneCotisationPiloteTest::testCotisationTarifFlag`) — insèrent encore des lignes `tarifs` sans fournir `produit_id`. Un `NOT NULL` immédiat cassait ces insertions (`Field 'produit_id' doesn't have a default value`), confirmé par 24 échecs à l'exécution complète de la suite. `produit_id` reste donc `NULL` (100 % backfillée sur les lignes existantes) ; la contrainte `NOT NULL` est déplacée à l'étape 7, une fois la façade garantissant sa présence à chaque `create()`. Voir docblock de `147_tarifs_add_produit_id.php` et `doc/design_notes/refactoring_produits_tarifs.md`.
- [x] `ALTER TABLE tarifs ADD CONSTRAINT fk_tarifs_produit FOREIGN KEY (produit_id) REFERENCES produits(id);` (une FK autorise les valeurs NULL, non contrôlées tant que la colonne l'est).
- [x] Colonnes d'audit déjà présentes sur `tarifs` (vérifié via `SHOW CREATE TABLE`, aucune action nécessaire).
- [x] Anciennes colonnes (`reference`, `description`, etc.) non touchées — lecture en compatibilité transitoire.
- [x] Mettre à jour `application/config/migration.php` → `147`.

**Validation** :
- [x] `php -l` sur le fichier de migration.
- [x] Toutes les lignes de `tarifs` ont un `produit_id` valide (`SELECT COUNT(*) FROM tarifs WHERE produit_id IS NULL` = 0).
- [x] `./run-all-tests.sh` — 1608/1611 tests verts après correction (3 échecs restants : `FormsSubformMigrationTest`, `FormsSubjectReferenceMigrationTest`, `FormsUploadResponseMigrationTest`, tous "Row size too large" sur la table `forms`, sans rapport avec `tarifs`/`produits`/migrations 139-140-144, non liés à ce travail — pré-existants/environnementaux).

- [x] ☑ Étape 5 terminée et validée — **point de contrôle** : à partir d'ici, les deux schémas (plat + relationnel) coexistent, rollback possible en désactivant simplement l'usage de `produit_id`.

---

### Étape 6 — Modèle `Produits_model`

Fichier (nouveau) : `application/models/produits_model.php`

- [x] CRUD standard sur `produits` (hérité de `Common_Model` — création/lecture/mise à jour/suppression, injection automatique des champs d'audit ; aucune surcharge nécessaire).
- [x] `image($id)` → `description`, ou `reference` si `description` est vide.
- [x] `selector($where = array(), $order = "asc", $filter_section = false)` → remplace l'actuel `Tarifs_model::selector()` pour tous les sélecteurs de produit (dropdown avion/planeur/achats/config/compta/vols_decouverte). Clé de retour = `reference` (pas `id`), comme l'ancien sélecteur : de nombreux appelants stockent la reference texte (ex. `avions.maprix`, `pompes.ppu`), pas l'id.
- [x] Comportement de filtrage par section conservé à l'identique de `Tarifs_model::selector()` : filtre sur `club` dès que `$this->section` est non vide, indépendamment du paramètre `$filter_section` (reproduit fidèlement le comportement actuel, y compris cette particularité).

**Validation** : `application/tests/mysql/ProduitsModelMySqlTest.php` (5 tests, nouveau, conservé) — instanciation, cycle create/get/update/delete (vérifie l'injection audit et l'immutabilité de `created_at` en update), `image()` avec/sans description, `selector()` filtré par section. `./run-all-tests.sh` : 1613/1616 verts (752 tests mysql, +5 par rapport à l'étape précédente), mêmes 3 échecs pré-existants sans rapport (`forms`, cf. étape 5).

- [x] ☑ Étape 6 terminée et validée

---

### Étape 7 — `Tarifs_model` en façade de compatibilité

Fichier : `application/models/tarifs_model.php` (modifié, pas remplacé)

Objectif : **toutes les méthodes publiques existantes gardent leur signature et la forme de
leur résultat** (mêmes clés de tableau), pour que les ~15 appelants n'aient rien à changer.

- [x] `get_tarif($reference, $date)` : réécrit en jointure `tarifs JOIN produits ON produit_id = produits.id WHERE produits.reference = ... AND produits.club = ... AND tarifs.date <= ...`, retourne les mêmes clés qu'aujourd'hui (`prix`, `description`, `compte`, ...).
- [x] `get_by_id('reference', $x)` et `get_by_id('id', $x)` : surchargé — `reference` joint `produits` ; `id` inchangé (délègue à `Common_Model::get_by_id`, compat point B).
- [x] `selector(...)` : délègue à `Produits_model::selector()`, clé de retour `reference` conservée.
- [x] `get_cotisation_products_for_section()` / `get_cotisation_product_by_id()` : réécrites en **LEFT JOIN + COALESCE(produits.x, tarifs.x)**, pas un simple INNER JOIN — un INNER JOIN cassait `PaiementsEnLigneCotisationPiloteTest::testCotisationTarifFlag`, qui insère encore une ligne `tarifs` à plat sans `produit_id`. Mêmes alias de sortie qu'avant.
- [x] `select_page()` (vue `vue_tarifs`) : réécrite en jointure `produits`/`tarifs`. Signature changée en `select_page($produit_id, $nb, $debut)` (scoping par produit, cf. étape 9 — le plan ne garantissait la signature inchangée que pour les méthodes listées ci-dessus, pas pour celle-ci).
- [x] `create()` / `update()` : n'écrivent plus que `produit_id`, `date`, `date_fin`, `prix`, `nb_tickets` + audit (whitelist), `saisie_par` abandonné.
- [x] `clone_elt()` (contrôleur `tarifs.php`) : adapté pour retirer `created_at`/`created_by`/`updated_at`/`updated_by` de la ligne source avant `create()` — sans ça le clone héritait de l'audit trail de la ligne d'origine au lieu d'un audit frais (vérifié en le testant : comportement corrigé, pas de régression car c'était déjà cassé avant, juste jamais testé).
- [x] **Migration 148** (`application/migrations/148_tarifs_produit_id_not_null.php`) : `produit_id` passé `NOT NULL`. Étendue au-delà du plan initial : `reference`/`saisie_par` (pas de défaut → INSERT échoue en SQL strict) **et** `compte`/`nb_personnes_max`/`is_cotisation` (ont un défaut, mais `MetaData::is_required()` ne regarde que `Null='NO'`, pas l'existence d'un défaut → la validation CodeIgniter les exigeait alors qu'ils ne sont plus dans le formulaire). Découvert et corrigé via `./run-all-tests.sh` + test manuel réel, cf. docblock de la migration.

**Validation** :
- [x] Tests unitaires/intégration existants sur `tarifs_model` verts (`VolsPlaneurePayeurFacturationTest`, `VolsCreationTarifManquantTest`, `PaiementsEnLigneCotisationPiloteTest`).
- [x] `ReservationsBalanceCheckTest` et `PaiementsEnLigneCotisationPiloteTest::testCotisationTarifFlag` : leurs fixtures de test (insertion directe de lignes `tarifs`) adaptées pour créer un `produits` correspondant — cassées par le passage de `produit_id` en NOT NULL, corrigées (`ReservationsBalanceCheckTest.php`, `PaiementsEnLigneCotisationPiloteTest.php`). Le toggle `is_cotisation` de `testCotisationTarifFlag` bascule désormais sur `produits.is_cotisation` (plus `tarifs.is_cotisation`), cohérent avec le nouveau schéma.
- [x] Test manuel réel (IHM, `gvv.net`, `ENVIRONMENT=development` temporairement pour diagnostiquer) : create/edit/clone/delete d'un tarif via `tarifs/page/<produit_id>` — `produit_id`, `date`, `prix` correctement enregistrés, aucune colonne legacy écrite, audit correctement injecté.
- [x] `./run-all-tests.sh` : 1613/1616 verts (3 échecs pré-existants sans rapport, table `forms`).

- [x] ☑ Étape 7 terminée et validée

---

### Étape 8 — Métadonnées (`Gvvmetadata.php`)

- [x] Déplacé vers `$this->field['produits'][...]` : `compte` (selector), `type_ticket` (selector),
      `public` (boolean), `is_cotisation` (boolean), `nb_personnes_max` (int, min 1, défaut 1),
      `reference` (Name = 'Produit').
- [x] Gardé sur `$this->field['tarifs'][...]` uniquement : `prix` (currency), `date` (défaut = today).
      (`date_fin`/`nb_tickets` n'avaient pas de déclaration explicite avant non plus — colonnes
      physiques introspectées directement, rien à déplacer.)
- [x] `vue_produits` créée (décision §6 confirmée : vue produit séparée) avec `nom_compte`,
      `reference`, `description`, `public`, `is_cotisation`, `nb_personnes_max`. `vue_tarifs`
      réduite à `prix`, `date`, `date_fin` (les colonnes produit y ont disparu, la liste des
      tarifs étant maintenant scopée à un seul produit — étape 9).
- [x] `Produits_model::select_page()` ajoutée (non prévue explicitement à l'étape 6, mais
      nécessaire : `Gvv_Controller::page()` générique l'appelle inconditionnellement).
- [x] Bouton d'action générique `tarifs` ajouté à `MetaData::action()` (rendu bouton, comme
      `edit`/`delete`/`clone_elt`) pour le bouton « Tarifs » de la liste produits (étape 9).
- [x] Fichiers de langue `produits_lang.php` (FR/EN/NL, nouveaux) et `tarifs_lang.php` (FR/EN/NL,
      réduits aux champs restants) — toutes les chaînes utilisateur restent dans les fichiers de
      langue (règle du projet), aucune régression sur le multi-langue.

**Validation** : formulaires produit et tarif testés réellement via l'IHM (étape 9) — libellés
corrects, aucun warning `input_field(...)` de métadonnée manquante dans les logs.

---

### Étape 9 — Contrôleurs et vues

#### 8.1 Nouveau contrôleur `Produits`

Fichier (nouveau) : `application/controllers/produits.php` (calqué sur `application/controllers/tarifs.php` actuel pour la structure CRUD/`Gvv_Controller`).

- [x] Actions standard : `page`, `create`, `edit`, `delete`, `formValidation` — héritées de `Gvv_Controller`, aucune surcharge nécessaire hormis `page()` (voir note ci-dessous).
- [x] Bouton/action **« Tarifs »** sur chaque ligne produit → `Produits::tarifs($id)` redirige vers `tarifs/page/<produit_id>`.
- [x] Vues (nouvelles) : `application/views/produits/bs_tableView.php`, `application/views/produits/bs_formView.php`.
- [x] **Découvertes en testant réellement l'IHM** (`ENVIRONMENT=development` temporaire pour voir les erreurs) :
  - `page()` a dû être surchargée pour exposer `$this->data['section']` à la vue (le mode rw/ro du tableau en dépend) — la version générique de `Gvv_Controller::page()` ne la définit pas, seul l'ancien contrôleur `tarifs.php` le faisait.
  - `Produits_model::create()` a dû être surchargée pour retirer la clé `id` (vide) du tableau avant insertion — `Common_Model::create()` ne le fait pas, contrairement à l'ancien `Tarifs_model::create()` dont `Produits_model` s'inspirait ; sans ce retrait, l'INSERT échoue en SQL strict (`Incorrect integer value: ''`).

#### 8.2 Contrôleur `Tarifs` remanié en sous-CRUD

Fichier : `application/controllers/tarifs.php` (modifié)

- [x] `page($produit_id = null, ...)` : liste uniquement les tarifs du produit donné ; fil d'ariane retour vers `produits/page`. `$produit_id` rendu optionnel (avec redirection vers `produits/page` si absent) : PHP 8 refuse une surcharge qui rendrait obligatoire un paramètre optionnel chez le parent (`Gvv_Controller::page()`) — fatal error `Declaration ... must be compatible`, découvert en testant réellement la page (invisible en `php -l` et non couvert par la suite PHPUnit, qui n'exerce pas le contrôleur).
- [x] Formulaire tarif : `produit_id` fixe (caché), champs `date`, `date_fin`, `prix`, `nb_tickets` uniquement. `saisie_par` supprimé.
- [x] `clone_elt($id)` : adapté pour repartir d'un audit trail frais (voir étape 7) ; testé réellement — prix copié, date du jour, `produit_id` conservé, `created_at`/`created_by` frais.
- [x] Filtrage "Afficher tout / par date / public" : conservé tel quel, adapté au sous-ensemble (ajout d'un filtre `produit_id` en plus des filtres existants) — pas de changement de comportement visible pour l'utilisateur au sein d'un produit.

#### 8.3 Menu et sélecteurs

- [x] `application/views/bs_menu.php:203` : le lien "Tarifs" pointe vers `produits/page`.
- [x] Tous les appels `$this->tarifs_model->selector(...)` basculés vers `$this->produits_model->selector(...)` :
  `avion.php`, `planeur.php` (variante `"nom"` en 3ᵉ argument conservée), `achats.php`,
  `config.php`, `compta.php`, `vols_decouverte.php` (x2, filtre `type_ticket=1` conservé —
  fonctionne car `type_ticket` vit maintenant sur `produits`).

**Validation** :
- [x] Test manuel réel (IHM `gvv.net`, `testadmin`, section Planeur) : liste produits (102 lignes,
      actions Tarifs/Édition/Suppression) → bouton Tarifs → liste des tarifs du produit → création
      d'un tarif (`produit_id` pré-rempli via `?produit_id=` sur le lien Créer, mécanisme générique
      de `Gvv_Controller::create()`) → édition → clonage → suppression → retour liste produits.
      Cycle complet CRUD produit (création/édition/suppression) également testé.
- [x] Test manuel : `avion/create`, `planeur/create`, `achats/create`, `config`,
      `vols_decouverte/create` chargent sans erreur (sélecteur produit peuplé). `compta.php`
      non testé bout en bout (nécessite un contexte compte pilote spécifique) mais utilise le
      même appel `produits_model->selector()` que les 5 autres, déjà validés.
- [x] `./run-all-tests.sh` : 1613/1616 verts. Playwright `ulm-billing-scenarios.spec.js` (14
      passés) et `vols-decouverte-public.spec.js` (8 passés, corrigé — sa fixture insérait aussi
      une ligne `tarifs` à plat sans `produit_id`, cf. `playwright/tests/vols-decouverte-public.spec.js`) verts.

- [x] ☑ Étape 9 terminée et validée

---

### Étape 10 — Requêtes SQL directes hors modèle

Réécrit chacun des accès directs à `tarifs` identifiés (point C) en jointure
`tarifs JOIN produits ON tarifs.produit_id = produits.id`, en conservant exactement les
mêmes filtres et le même résultat :

- [x] `application/controllers/reservations.php:956-970` (`_get_tarif_price`)
- [x] `application/controllers/welcome.php:245-255` (compteur cotisation section)
- [x] `application/controllers/vols_decouverte.php:224-231` (lookup produit VD par référence)
- [x] `application/controllers/vols_decouverte.php:920-931` (description tarif pour bon cadeau PDF)
- [x] `application/controllers/vols_decouverte.php:1335-1345` (requête produit VD publique, achat)
- [x] `application/controllers/vols_decouverte.php:1478-1490` (liste produits VD publique)
- [x] **Découvert en audit complémentaire, absent de la liste initiale du point C** :
      `application/controllers/paiements_en_ligne.php:2250` — même motif exact que
      `vols_decouverte.php:920-931` (description du bon cadeau PDF), corrigé à l'identique.
- [x] **Découvert hors périmètre SQL, mais bloquant pour la cohérence du refactoring** :
      `application/libraries/Database.php` (`$gvv_tables`, ordre de sauvegarde/restauration
      de la base) ne listait pas `produits` du tout. Comme `tarifs.produit_id` porte
      maintenant une contrainte `FOREIGN KEY` réelle vers `produits.id`, une restauration
      pourrait échouer si `tarifs` est recréée/repeuplée avant `produits`. `produits` ajoutée
      juste avant `tarifs` dans la liste. **Non couvert par la suite de tests automatisée**
      (aucun test n'exerce le backup/restore complet) — à vérifier manuellement par un cycle
      backup/restore réel avant de s'y fier en production.

**Validation** :
- [x] `./run-all-tests.sh` : 1613/1616 verts (mêmes 3 échecs pré-existants sans rapport).
- [x] Test Playwright `vols-decouverte-public.spec.js` vert (8 passés, 2 skips pré-existants).
- [x] Test PHPUnit `ReservationsBalanceCheckTest` vert (23 tests).
- [x] `php -l` sur tous les fichiers modifiés.

- [x] ☑ Étape 10 terminée et validée

---

### Étape 11 — Non-régression facturation (critère de succès principal)

- [x] Script de contrôle jetable (`RefacturationCheckTest.php`, supprimé après exécution) qui, sur `gvv.net` :
  1. prend 5 vols avion (club Avion) + 5 vols planeur (club Planeur) déjà facturés,
  2. dump les écritures associées (compte1, compte2, montant, date_op, description) avant refacturation,
  3. **refacture** ces mêmes vols via les fonctions existantes `Vols_avion_model::delete_facture()`+`facture()` /
     `Vols_planeur_model::delete_facture()`+`facture()` (le mécanisme déjà utilisé en production
     à chaque édition d'un vol),
  4. compare : **strictement identique** sur l'échantillon (compte, montant, date, description).
  - ⚠️ **Incident en cours de mise au point du script** (pas une régression du refactoring) :
    le tout premier essai, sans le contexte de section/club correct et sans charger
    `application/config/club.php`, a fait planter la facturation à mi-parcours pour 6 vols
    (1 avion : 16690 ; 5 planeur : 8578/8579/8577/8569/8571), les laissant temporairement sans
    écritures. Cause : `Common_Model` capture la section active *à la construction* du modèle
    (donc l'ordre "positionner la session avant de charger le modèle" est impératif), et
    `config('club')` non chargé fait basculer silencieusement sur la classe de facturation
    générique au lieu de `Facturation_accabs` (le module réellement configuré) — aucune
    exception, juste un résultat différent. Diagnostiqué, corrigé dans le script, et les 6 vols
    réparés en relançant la facturation dessus (résultat vérifié identique aux montants/comptes
    d'origine). La comparaison avant/après finale porte sur un échantillon différent, non
    affecté par cet incident.
- [x] Exécuté après l'étape 10 (SQL directs). Sera rejoué après l'étape 12 (nettoyage final).
- [x] `./run-all-tests.sh --coverage` : 1614/1617 verts (3 échecs pré-existants sans rapport,
      table `forms`). Tests nommément listés, tous verts (0 échec) :
      `VolsCreationTarifManquantTest`, `VolsAvionVolsPlaneurAuditMySqlTest` (2 skips
      pré-existants, données de test), `VolsPlaneurePayeurFacturationTest`,
      `TarifsIsCotisationMigrationTest`, `PaiementsEnLigneCotisationPiloteTest` (1 skip
      pré-existant, solde insuffisant), `AuditFinancesMigrationTest`.
- [x] Playwright complet : `ulm-billing-scenarios.spec.js` + `vols-decouverte-public.spec.js`
      (22 passés, 4 skips pré-existants) ; suites d'autorisation par club — `abraracourcix`,
      `asterix`, `goudurix`, `obelix` (135 passés, 0 échec).
- [x] Nouveau test PHPUnit conservé : `application/tests/mysql/ProduitsTarifsCrudIntegrationTest.php`
      — cycle CRUD complet Produits + Tarifs via les modèles (create/read joint/selector/image/
      update/delete). A nécessité deux ajouts mineurs à l'infrastructure de test partagée
      (`application/tests/integration_bootstrap.php`) : `MockSession::all_userdata()` (absente,
      utilisée par `select_page()`), et le chargement de `sections_model` en présence de code
      inchangé qui en dépend.
- [x] Nouveau test Playwright conservé : `playwright/tests/produits-tarifs-crud.spec.js` — parcours
      navigateur réel : liste produits → création d'un produit → bouton Tarifs → création d'un
      tarif → édition → suppression → suppression du produit. Vert, nettoyage vérifié en base.
- [x] Contrôle de non-régression facturation rejoué une seconde fois après l'étape 12 (migration
      149), sur un échantillon différent (jamais touché par le premier run) : à nouveau
      strictement identique, sans incident cette fois (voir étape 12 pour le détail des
      correctifs qui l'ont rendu possible).

- [x] ☑ Étape 11 terminée et validée

---

### Étape 12 — Nettoyage final (point de non-retour)

Fichier : `application/migrations/149_tarifs_drop_legacy_columns.php`

> Renumérotée 148 → 149 : la migration 148 a été consommée par l'étape 7
> (`148_tarifs_produit_id_not_null.php` — NOT NULL sur `produit_id` + assouplissement
> `reference`/`saisie_par`/`compte`/`nb_personnes_max`/`is_cotisation`, cf. étape 7).

À exécuter **uniquement après validation complète des étapes 7 à 11** sur l'environnement de test, et après confirmation explicite de l'utilisateur (demandée et obtenue).

- [x] **Sauvegarde préalable** : dump MySQL complet de `gvv2` pris avant toute action
      (`backups/gvv2_pre_migration149_*.sql`, 17 Mo, 91 tables, schéma `tarifs` pré-149 vérifié
      dans le dump). Dossier `backups/` déjà exclu de git (`.gitignore`).
- [x] Grep de contrôle exhaustif (voir "Découvertes" ci-dessous) **avant** d'exécuter la migration —
      a révélé plusieurs accès directs aux colonnes legacy non couverts par l'étape 10, corrigés
      avant d'exécuter le DROP.
- [x] `ALTER TABLE tarifs DROP COLUMN reference, DROP COLUMN description, DROP COLUMN compte, DROP COLUMN club, DROP COLUMN is_cotisation, DROP COLUMN nb_personnes_max, DROP COLUMN public, DROP COLUMN type_ticket, DROP COLUMN saisie_par;` — exécuté réellement via l'IHM (`gvv.net`, migration 148→149). `tarifs` ne porte plus que `id`, `produit_id`, `date`, `date_fin`, `prix`, `nb_tickets` + audit (vérifié par `SHOW CREATE TABLE`).
- [x] `application/config/migration.php` → `149`.
- [x] Relancer l'intégralité des suites de tests.

**Découvertes et correctifs (avant et après le DROP)** — le grep initial de l'étape 10 avait
couvert `controllers/`, `models/`, `libraries/`, `views/`, mais pas `helpers/`, ni les scripts de
test (PHPUnit et Playwright) qui insèrent ou interrogent `tarifs` directement en dehors du modèle :

- `application/models/achats_model.php` : **5 méthodes** (`select_page`, `select`, `achats_de`,
  `factures_en_cours`, `achats_de_facture`) faisaient `FROM achats, tarifs WHERE achats.produit =
  tarifs.reference` — non identifiées par l'audit initial du point C (limité aux 8 accès déjà
  listés). Réécrites en jointure `achats JOIN produits ON achats.produit = produits.reference
  JOIN tarifs ON tarifs.produit_id = produits.id` ; une colonne `compte` non qualifiée (résolue
  implicitement sur `tarifs.compte` par MySQL) qualifiée en `produits.compte`.
- `application/helpers/vd_quota_helper.php::get_sections_vd_disponibles()` : requête brute
  `SELECT 1 FROM tarifs WHERE club = ? AND type_ticket = 1 AND public = 1 ...` — cassait la page
  publique des vols de découverte (`vols_decouverte/public_vd`) avec une erreur fatale
  (`Unknown column`). Le dossier `helpers/` n'était couvert par aucun audit précédent. Réécrite
  en jointure `produits`.
- `application/models/tarifs_model.php` : `get_cotisation_products_for_section()` /
  `get_cotisation_product_by_id()` utilisaient un `LEFT JOIN` + `COALESCE(produits.x, tarifs.x)`
  (repli transitoire pour les lignes sans `produit_id`, cf. étape 7) — simplifiées en `INNER JOIN`
  pur maintenant que `produit_id` est garanti et que les colonnes de repli n'existent plus.
  `image()` : suppression du repli mort sur `tarifs.reference`.
- Tests PHPUnit insérant encore des colonnes produit legacy directement dans `tarifs` (cassés par
  la suppression des colonnes) : `ReservationsBalanceCheckTest.php` (fixture + 3 requêtes de
  vérification dupliquant `_get_tarif_price()` avec les anciennes colonnes plates),
  `PaiementsEnLigneCotisationPiloteTest.php` (fixture `testCotisationTarifFlag`),
  `VolsAvionVolsPlaneurAuditMySqlTest.php` (`activeAirplane()` joignait `tarifs.reference`
  directement). Tous réécrits en jointure `produits` / colonnes prix uniquement.
- Test Playwright `ulm-billing-scenarios.spec.js::getTariffPrice()` : même requête brute
  `SELECT prix FROM tarifs WHERE reference = ...` côté JS (calcul du prix attendu pour la
  comparaison) — réécrite en jointure SQL.
- Tests PHPUnit rejouant des **migrations historiques en isolation** contre le schéma actuel
  (`AuditFinancesMigrationTest` migration 092, `BackfillAuditHistoriqueMigrationTest` migration
  142, `TarifsIsCotisationMigrationTest` migration 099, `PaiementsEnLignePublicVdTest` migration
  101) : leur `up()`/`down()` manipule directement `tarifs.saisie_par` / `tarifs.type_ticket` /
  `tarifs.nb_personnes_max` / `tarifs.is_cotisation`, colonnes qui n'existent plus. Les migrations
  elles-mêmes restent inchangées (historique, déjà appliquées avec succès en leur temps) ; les
  tests ne sont plus rejouables tels quels sur le schéma post-149 et sautent désormais
  proprement (`markTestSkipped` avec message explicite) plutôt que d'échouer en erreur SQL —
  aucune perte de couverture réelle, ces migrations ayant déjà été validées historiquement.
- **Incident lors de la mise au point** : la première exécution de `./run-all-tests.sh` après ces
  corrections a elle-même causé un effet de bord — les tests `up()`/`down()` isolés de migration
  099 et 101 (avant l'ajout de leurs gardes de skip) ont **repositionné en `NOT NULL`**
  `tarifs.is_cotisation` et `tarifs.nb_personnes_max` (déjà rendues `NULL`-ables par la migration
  148) en rejouant l'ancienne définition de colonne. Sans conséquence puisque la migration 149
  a immédiatement suivi (DROP COLUMN indifférent à la nullabilité), mais confirme la fragilité
  identifiée : des tests de migration rejouée en isolation peuvent altérer un schéma évolué. Les
  gardes de skip ajoutés couvrent ce cas pour l'avenir.
- **`achats/create` sans paramètres `amount`/`pilot`** : erreur fatale `DivisionByZeroError`
  découverte en testant manuellement. **Confirmé pré-existant et sans rapport** (diff git : ce
  code n'a pas été touché) — la référence `ffvv_product` configurée
  (`application/config/club.php`) ne correspond à aucun produit réel dans cette base de test, ni
  dans l'ancien schéma plat ni dans le nouveau ; le calcul `$amount / $price` sans garde contre
  prix nul plantait déjà avant ce refactoring, simplement masqué en `ENVIRONMENT=production`
  (erreurs PHP supprimées, le serveur web renvoie 200 par défaut). Non corrigé (hors périmètre de
  ce refactoring) — signalé pour information.

**Validation** :
- [x] `./run-all-tests.sh` : 1598/1601 verts (3 échecs pré-existants sans rapport, table `forms`).
      64 skips (48 pré-existants + 16 nouveaux skips explicites de migrations historiques
      rejouées, voir ci-dessus — pas une perte de couverture).
- [x] Playwright complet : `ulm-billing-scenarios.spec.js` + `vols-decouverte-public.spec.js`
      (22 passés, 4 skips pré-existants), suites d'autorisation par club (135 passés),
      `produits-tarifs-crud.spec.js` (1 passé). 0 échec après correctifs.
- [x] Contrôle de non-régression facturation rejoué sur un échantillon différent (5 vols avion +
      5 vols planeur jamais touchés par le run précédent) → écritures strictement identiques,
      sans incident.

- [x] ☑ Étape 12 terminée et validée — **refactoring complet**

---

## 8. Plan de rollback

- Entre l'étape 5 et l'étape 12 (inclus jusqu'à la migration 148 posée), rollback possible en
  revenant au code précédent : les anciennes colonnes existent toujours sur `tarifs`, aucune
  donnée n'est perdue.
- Après la migration 149 (suppression des colonnes), rollback = restauration depuis sauvegarde
  MySQL antérieure à la migration 149 (prendre un dump complet juste avant cette étape).
- [x] Dump MySQL complet de sauvegarde pris immédiatement avant l'exécution de la migration 149
      (`backups/gvv2_pre_migration149_20260731_215031.sql`).

## 9. Checklist de complétion globale

- [x] Étape 1 — Design note
- [x] Étape 2 — Audit des données
- [x] Étape 3 — Affichage détaillé des erreurs de migration (correctif générique)
- [x] Étape 4 — Migration 146 (`produits`)
- [x] Étape 5 — Migration 147 (`tarifs.produit_id`)
- [x] Étape 6 — `Produits_model`
- [x] Étape 7 — `Tarifs_model` façade (+ migration 148, NOT NULL `produit_id`)
- [x] Étape 8 — Métadonnées
- [x] Étape 9 — Contrôleurs et vues (Produits + Tarifs + menu + sélecteurs)
- [x] Étape 10 — Requêtes SQL directes
- [x] Étape 11 — Non-régression facturation
- [x] Étape 12 — Migration 149 (nettoyage final)
