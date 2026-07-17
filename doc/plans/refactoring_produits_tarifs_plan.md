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

- [ ] Créer `doc/design_notes/refactoring_produits_tarifs.md` : schéma cible, diagramme ERD plantuml (`doc/design_notes/diagrams/produits_tarifs.puml` + image liée), règles de compatibilité (façade `Tarifs_model`), liste des points de vigilance (§4).
- [ ] Revue et validation du design note par l'utilisateur avant de continuer.

**Validation** : le document est lisible, le diagramme s'affiche sur GitHub, aucune ambiguïté sur le schéma cible.

---

### Étape 2 — Audit des données existantes

- [ ] Écrire une requête (ou petit script PHP jetable, non conservé) qui, pour chaque
      `(reference, club)`, compare les valeurs de `description`, `compte`, `is_cotisation`,
      `nb_personnes_max`, `public`, `type_ticket` entre toutes les lignes du groupe.
- [ ] Produire un rapport texte des groupes divergents (le cas échéant) pour chaque club/section utilisant GVV.
- [ ] Faire valider la règle de résolution (dernière date fait foi) au vu du rapport, ou corriger manuellement les données sources si des divergences sont anormales.

**Validation** : rapport revu, aucune divergence bloquante non expliquée.

- [ ] ☑ Étape 2 terminée et validée

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
seulement celles de ce refactoring. Elle est placée avant les migrations 143/144/145 pour que
leur mise au point en environnement de test bénéficie immédiatement d'un diagnostic fiable.

- [ ] Créer `application/libraries/MY_Migration.php` (extension `MY_` du core CI, cf.
      `application/libraries/MY_Email.php` pour le pattern existant — **ne pas modifier**
      `system/libraries/Migration.php`) : surcharge `version()` pour, après l'exécution de
      chaque étape (`call_user_func(array($migration_instance, $method))`), vérifier
      `$this->db->_error_message()` / `_error_number()` et lever une exception explicite si une
      erreur SQL est présente, au lieu de continuer silencieusement.
- [ ] Le message d'exception inclut : nom du fichier de migration, méthode (`up`/`down`),
      niveau visé, numéro et message d'erreur SQL.
- [ ] Modifier `application/controllers/migration.php::to_level()` : entourer l'appel à
      `$this->migration->version($target_level)` d'un `try/catch (Throwable $e)`. En cas
      d'exception : logguer via `gvv_error()` puis afficher le détail via `show_error()`
      (message de l'exception + niveau de départ/cible) — l'affichage passe par notre propre
      code, donc il fonctionne quel que soit `ENVIRONMENT`/`error_reporting`.
- [ ] Le cas déjà géré (erreurs structurelles via `error_string()`) reste au moins aussi
      informatif qu'aujourd'hui.

**Validation** :
- [ ] Test manuel : ajouter temporairement une migration de test volontairement défaillante
      (SQL invalide) sur l'environnement de test, lancer la migration depuis l'IHM (`gvv.net`),
      vérifier qu'un message détaillé (et non une page blanche) s'affiche, incluant l'erreur
      SQL. Supprimer la migration de test ensuite.
- [ ] Nouveau test PHPUnit (conservé dans la base de tests) couvrant `MY_Migration::version()`
      sur un cas d'échec SQL simulé, vérifiant qu'une exception détaillée est levée.
- [ ] `playwright/tests/migration-test.spec.js` toujours vert (non-régression du chemin nominal).
- [ ] `./run-all-tests.sh` vert.

- [ ] ☑ Étape 3 terminée et validée — **prérequis avant de lancer les migrations 143/144/145**
      pour garantir un diagnostic exploitable en cas d'échec.

---

### Étape 4 — Migration 143 : création de `produits`

Fichier : `application/migrations/143_create_produits_table.php`

- [ ] Créer la table `produits` :
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
- [ ] Peupler `produits` par agrégation de `tarifs` groupé par `(reference, club)`, valeurs prises sur la ligne de `date` maximale du groupe (cf. règle validée étape 2). `created_at`/`created_by` = ceux de la ligne la plus ancienne du groupe ; `updated_at`/`updated_by` = ceux de la ligne la plus récente.
- [ ] Mettre à jour `application/config/migration.php` → `143`.

**Validation** :
- [ ] `php -l` sur le fichier de migration.
- [ ] `COUNT(DISTINCT reference, club)` dans `tarifs` == `COUNT(*)` dans `produits`.
- [ ] Contrôle manuel sur 5 produits connus (avant/après) via phpMyAdmin.

---

### Étape 5 — Migration 144 : `tarifs.produit_id`

Fichier : `application/migrations/144_tarifs_add_produit_id.php`

- [ ] `ALTER TABLE tarifs ADD COLUMN produit_id INT(11) NULL AFTER id;`
- [ ] `UPDATE tarifs t JOIN produits p ON t.reference = p.reference AND t.club = p.club SET t.produit_id = p.id;`
- [ ] Vérifier `SELECT COUNT(*) FROM tarifs WHERE produit_id IS NULL` == 0, sinon échec de migration explicite (exception).
- [ ] `ALTER TABLE tarifs MODIFY produit_id INT(11) NOT NULL;`
- [ ] `ALTER TABLE tarifs ADD CONSTRAINT fk_tarifs_produit FOREIGN KEY (produit_id) REFERENCES produits(id);`
- [ ] Ajouter les colonnes d'audit manquantes si besoin (elles existent déjà sur `tarifs` d'après le schéma fourni).
- [ ] Ne pas toucher aux anciennes colonnes (`reference`, `description`, etc.) — elles restent en lecture pour compatibilité transitoire.
- [ ] Mettre à jour `application/config/migration.php` → `144`.

**Validation** :
- [ ] `php -l` sur le fichier de migration.
- [ ] Toutes les lignes de `tarifs` ont un `produit_id` valide (requête de contrôle).
- [ ] `./run-all-tests.sh` — aucune régression (le code applicatif ne lit pas encore `produit_id`, donc doit être 100 % vert sans changement).

- [ ] ☑ Étape 5 terminée et validée — **point de contrôle** : à partir d'ici, les deux schémas (plat + relationnel) coexistent, rollback possible en désactivant simplement l'usage de `produit_id`.

---

### Étape 6 — Modèle `Produits_model`

Fichier (nouveau) : `application/models/produits_model.php`

- [ ] CRUD standard sur `produits` (calqué sur `Common_Model`, cf. `tarifs_model.php` actuel comme référence de style).
- [ ] `image($id)` → `reference` ou `description`.
- [ ] `selector($where = array(), $order = "asc", $filter_section = false)` → remplace l'actuel `Tarifs_model::selector()` pour tous les sélecteurs de produit (dropdown avion/planeur/achats/config/compta/vols_decouverte).
- [ ] Conserver le comportement de filtrage par section (`$this->section` / `$this->section_id`) identique à l'existant.

**Validation** : tests unitaires du nouveau modèle (`application/tests/unit/` ou `integration/`), couvrant `selector()`, `image()`, CRUD de base.

---

### Étape 7 — `Tarifs_model` en façade de compatibilité

Fichier : `application/models/tarifs_model.php` (modifié, pas remplacé)

Objectif : **toutes les méthodes publiques existantes gardent leur signature et la forme de
leur résultat** (mêmes clés de tableau), pour que les ~15 appelants n'aient rien à changer.

- [ ] `get_tarif($reference, $date)` : réécrit en jointure `tarifs JOIN produits ON produit_id = produits.id WHERE produits.reference = ... AND produits.club = ... AND tarifs.date <= ...`, retourne les mêmes clés qu'aujourd'hui (`prix`, `description`, `compte`, ...).
- [ ] `get_by_id('reference', $x)` et `get_by_id('id', $x)` : `Common_Model::get_by_id` générique ne suffit plus — surcharger pour joindre `produits` quand la recherche porte sur `reference`, et conserver l'accès direct par `id` (compat point B, tant que les colonnes existent encore sur `tarifs`).
- [ ] `selector(...)` : déléguer à `Produits_model::selector()` en conservant la clé de retour `reference` (comportement actuel, cf. commentaire "le sélecteur de tarif... travaille sur la reference").
- [ ] `get_cotisation_products_for_section()` / `get_cotisation_product_by_id()` : réécrites en jointure, mêmes alias de sortie (`libelle`, `annee`, `montant`, `compte_cotisation_id`, `section_id`, `actif`).
- [ ] `select_page()` (vue `vue_tarifs`) : réécrite en jointure `produits`/`tarifs`, mêmes colonnes de sortie qu'aujourd'hui.
- [ ] `create()` / `update()` : n'écrivent plus que les colonnes de prix (`produit_id`, `date`, `date_fin`, `prix`, `nb_tickets`) + audit ; suppression de la gestion de `saisie_par`.
- [ ] `clone_elt()` (contrôleur `tarifs.php`) : vérifié compatible tel quel (copie la ligne avec nouvelle date), car il ne touche pas aux champs produit.

**Validation** :
- [ ] Tests unitaires/intégration existants sur `tarifs_model` (chercher dans `application/tests/`) verts.
- [ ] Nouveaux tests : `get_tarif()` retourne un résultat identique avant/après refactor sur un jeu de références connues (comparaison automatisée, cf. Étape 11).

- [ ] ☑ Étape 7 terminée et validée

---

### Étape 8 — Métadonnées (`Gvvmetadata.php`)

- [ ] Déplacer les définitions de champs produit vers `$this->field['produits'][...]` :
      `compte` (selector), `type_ticket` (selector), `public` (boolean), `is_cotisation` (boolean),
      `nb_personnes_max` (int, min 1, défaut 1), `reference` (Name = 'Produit').
- [ ] Garder sur `$this->field['tarifs'][...]` uniquement : `prix` (currency), `date` (défaut = today), `date_fin`, `nb_tickets`.
- [ ] Ajouter les champs `$this->field['produits']['...']` nécessaires à l'affichage table (`vue_produits` si retenu en §6, sinon adapter `vue_tarifs`).
- [ ] Décision de l'étape 0 (§6) appliquée : nom(s) de vue(s) définitif(s).

**Validation** : formulaire produit et formulaire tarif s'affichent sans warning `DEBUG - GVV: input_field(...)` dans les logs (`application/logs`).

---

### Étape 9 — Contrôleurs et vues

#### 8.1 Nouveau contrôleur `Produits`

Fichier (nouveau) : `application/controllers/produits.php` (calqué sur `application/controllers/tarifs.php` actuel pour la structure CRUD/`Gvv_Controller`).

- [ ] Actions standard : `page`, `create`, `edit`, `delete`, `formValidation`.
- [ ] Bouton/action **« Tarifs »** sur chaque ligne produit → redirige vers `tarifs/page/<produit_id>` (liste des tarifs filtrée sur ce produit).
- [ ] Vues (nouvelles) : `application/views/produits/bs_tableView.php`, `application/views/produits/bs_formView.php` (calquées sur `application/views/tarifs/bs_tableView.php` / `bs_formView.php` actuels, champs produit uniquement).

#### 8.2 Contrôleur `Tarifs` remanié en sous-CRUD

Fichier : `application/controllers/tarifs.php` (modifié)

- [ ] `page($produit_id, ...)` : liste uniquement les tarifs du produit donné (au lieu de tous les tarifs toutes références confondues) ; fil d'ariane retour vers `produits/page`.
- [ ] Formulaire tarif : `produit_id` fixe (caché), champs `date`, `date_fin`, `prix`, `nb_tickets` uniquement. Suppression du champ caché `saisie_par` (`bs_formView.php:44`).
- [ ] `clone_elt($id)` : inchangé dans son principe (duplique avec date du jour), vérifié compatible avec `produit_id`.
- [ ] Le filtrage global "Afficher tout / par date / public" existant (`filterValidation`) : à réévaluer — probablement simplifié car le filtre s'applique maintenant à l'intérieur d'un seul produit. **Décision à confirmer avec l'utilisateur si le comportement doit changer** (hors périmètre initial sinon, conserver tel quel adapté au sous-ensemble).

#### 8.3 Menu et sélecteurs

- [ ] `application/views/bs_menu.php:201` : le lien "Tarifs" pointe vers `produits/page` (nouvelle entrée de premier niveau).
- [ ] Tous les appels `$this->tarifs_model->selector(...)` dans les contrôleurs suivants basculent vers `$this->produits_model->selector(...)` :
  - `application/controllers/avion.php:78`
  - `application/controllers/planeur.php:89` (variante avec `"nom"` en 3ᵉ argument)
  - `application/controllers/achats.php:66`
  - `application/controllers/config.php:166`
  - `application/controllers/compta.php:1972`
  - `application/controllers/vols_decouverte.php:537,607`

**Validation** :
- [ ] Test manuel navigateur : liste produits → bouton Tarifs → liste des tarifs du produit → création/édition/suppression d'un tarif → retour liste produits inchangée.
- [ ] Test manuel : formulaires avion/planeur/achats/compta affichent toujours le sélecteur de produit correctement peuplé.

- [ ] ☑ Étape 9 terminée et validée

---

### Étape 10 — Requêtes SQL directes hors modèle

Réécrire chacun des 8 accès directs à `tarifs` identifiés (point C) en jointure
`tarifs JOIN produits ON tarifs.produit_id = produits.id`, en conservant exactement les
mêmes filtres et le même résultat :

- [ ] `application/controllers/reservations.php:956-970` (`_get_tarif_price`)
- [ ] `application/controllers/welcome.php:245-255` (compteur cotisation section)
- [ ] `application/controllers/vols_decouverte.php:224-231` (lookup produit VD par référence)
- [ ] `application/controllers/vols_decouverte.php:920-931` (description tarif pour bon cadeau PDF)
- [ ] `application/controllers/vols_decouverte.php:1335-1345` (requête produit VD publique, achat)
- [ ] `application/controllers/vols_decouverte.php:1478-1490` (liste produits VD publique)

**Validation** :
- [ ] Pour chacune, comparer le résultat de la requête avant/après sur un jeu de données identique (script de comparaison, cf. Étape 11).
- [ ] Test Playwright `vols-decouverte-public.spec.js` vert.
- [ ] Test PHPUnit `ReservationsBalanceCheckTest` vert.

- [ ] ☑ Étape 10 terminée et validée

---

### Étape 11 — Non-régression facturation (critère de succès principal)

- [ ] Écrire un script de contrôle (jetable, non conservé — ou test PHPUnit dédié si jugé pertinent après discussion) qui, sur l'environnement de test (`gvv.net`) :
  1. prend un échantillon représentatif de vols avion/planeur et d'achats déjà facturés,
  2. calcule un hash/dump des écritures (`ecritures`) associées avant refactoring,
  3. après refactoring, **refacture** ces mêmes vols/achats (fonction de refacturation existante) et recalcule le même dump,
  4. compare : doit être strictement identique (compte, montant, date, description).
- [ ] Exécuter ce contrôle après l'étape 7 (façade modèle), après l'étape 10 (SQL directs), et après l'étape 12 (nettoyage final).
- [ ] `./run-all-tests.sh --coverage` intégralement vert, en particulier :
  - `VolsCreationTarifManquantTest`
  - `VolsAvionVolsPlaneurAuditMySqlTest`
  - `VolsPlaneurePayeurFacturationTest`
  - `TarifsIsCotisationMigrationTest`
  - `PaiementsEnLigneCotisationPiloteTest`
  - `AuditFinancesMigrationTest`
- [ ] Playwright complet : `ulm-billing-scenarios.spec.js`, `vols-decouverte-public.spec.js`, suites d'autorisation par club.
- [ ] Ajouter un nouveau test PHPUnit couvrant le CRUD Produits + Tarifs (smoke test), conservé dans la base de tests.
- [ ] Ajouter un nouveau test Playwright couvrant le parcours produits → bouton Tarifs → CRUD tarifs, conservé dans la base de tests.

- [ ] ☑ Étape 11 terminée et validée

---

### Étape 12 — Nettoyage final (point de non-retour)

Fichier : `application/migrations/145_tarifs_drop_legacy_columns.php`

À exécuter **uniquement après validation complète des étapes 7 à 11** sur l'environnement de test, et après confirmation explicite de l'utilisateur.

- [ ] `ALTER TABLE tarifs DROP COLUMN reference, DROP COLUMN description, DROP COLUMN compte, DROP COLUMN club, DROP COLUMN is_cotisation, DROP COLUMN nb_personnes_max, DROP COLUMN public, DROP COLUMN type_ticket, DROP COLUMN saisie_par;`
- [ ] Mettre à jour `application/config/migration.php` → `145`.
- [ ] Vérifier qu'aucun code applicatif ne référence plus ces colonnes sur `tarifs` (`grep` de contrôle sur `tarifs.reference`, `tarifs.compte`, etc.).
- [ ] Relancer l'intégralité des suites de tests (§ Étape 11).

**Validation** :
- [ ] `./run-all-tests.sh --coverage` vert.
- [ ] Playwright complet vert.
- [ ] Contrôle de non-régression facturation (Étape 11) rejoué une dernière fois → identique.

- [ ] ☑ Étape 12 terminée et validée — **refactoring complet**

---

## 8. Plan de rollback

- Entre l'étape 5 et l'étape 12 (inclus jusqu'à la migration 144 posée), rollback possible en
  revenant au code précédent : les anciennes colonnes existent toujours sur `tarifs`, aucune
  donnée n'est perdue.
- Après la migration 145 (suppression des colonnes), rollback = restauration depuis sauvegarde
  MySQL antérieure à la migration 145 (prendre un dump complet juste avant cette étape).
- [ ] Dump MySQL complet de sauvegarde pris immédiatement avant l'exécution de la migration 145.

## 9. Checklist de complétion globale

- [ ] Étape 1 — Design note
- [ ] Étape 2 — Audit des données
- [ ] Étape 3 — Affichage détaillé des erreurs de migration (correctif générique)
- [ ] Étape 4 — Migration 143 (`produits`)
- [ ] Étape 5 — Migration 144 (`tarifs.produit_id`)
- [ ] Étape 6 — `Produits_model`
- [ ] Étape 7 — `Tarifs_model` façade
- [ ] Étape 8 — Métadonnées
- [ ] Étape 9 — Contrôleurs et vues (Produits + Tarifs + menu + sélecteurs)
- [ ] Étape 10 — Requêtes SQL directes
- [ ] Étape 11 — Non-régression facturation
- [ ] Étape 12 — Migration 145 (nettoyage final)
