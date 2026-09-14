# Plan de correction — Erreurs relevées par les suites PHPUnit et Playwright (2026-09-14)

> Document de triage généré après exécution complète de `./run-all-tests.sh` (PHPUnit,
> 2006 tests) et `npx playwright test --reporter=line` (Playwright, 823 tests) sur
> gvv.net, et analyse du log applicatif `application/logs/log-2026-09-14.php` généré
> pendant ces exécutions.
>
> **Statut : corrections 1 à 4 implémentées et validées** (`./run-all-tests.sh` → 0 échec,
> `playwright test` → 823/823 hors skips, volume `Could not find the language line` retombé
> à 0 sur les clés visées). Voir §6 pour le détail de ce qui a été fait, y compris deux
> découvertes faites en cours de correction et non anticipées par le triage initial.

## 0. Résumé

| Suite | Tests | Échecs | Skips | Remarque |
|---|---|---|---|---|
| PHPUnit (`run-all-tests.sh`) | 2006 | **0** | 62 | Skips légitimes (schéma/données), voir §3 |
| Playwright (`playwright test`) | 823 | **3** | 34 | 2 causes racines distinctes, voir §1 |

En complément des échecs de tests eux-mêmes, l'exécution des deux suites a fait
apparaître dans `application/logs/` un volume important d'erreurs applicatives
(`log_message('error', ...)`) qui n'empêchent aucun test de passer mais qui polluent
le log de production et signalent de vrais défauts (clés de traduction manquantes,
bug fonctionnel de filtrage par section). Voir §2.

---

## 1. Échecs Playwright (3)

### 1.1 `maintenance-equipements-smoke.spec.js` — bug fonctionnel réel

**Test** : *mecano can create, edit, transfer and deactivate an equipement*
**Symptôme** : après création d'un équipement (message flash « Équipement créé avec
succès. » bien affiché), l'équipement n'apparaît **pas** dans la liste — qui affiche
« Aucun équipement défini. ».

**Cause racine** — `application/controllers/maintenance_equipements.php` :
- `index()` (L52-61) filtre la liste par section active :
  `get_all(false, $this->session->userdata('section'))`.
- Mais `create()` (L75), `edit()` (L132), `update()` en cas d'erreur (L161),
  `transfer()` (L225) et `transfer_store()` en cas d'erreur (L253) appellent tous
  `get_aeronef_selector()` **sans** section — le menu déroulant des aéronefs liste donc
  la flotte entière, toutes sections confondues.
- Résultat : un mécano en section « Planeur » peut créer/transférer un équipement vers
  un aéronef d'une autre section (ex. `F-JHRV`, club 2, alors que le mécano est en
  section 1) ; l'équipement est bien créé en base mais disparaît silencieusement de la
  liste filtrée par section. Aucun message d'erreur n'est montré à l'utilisateur
  (violation de la règle CLAUDE.md « Never reject an action silently »).
- Confirmé en base (`machinesa`) : le premier aéronef listé par ordre alphabétique
  (`F-JHRV`, club 2) n'est pas dans la section active du test (section 1) — c'est
  exactement lui que le formulaire propose en premier.

**Correction proposée** : passer `$this->session->userdata('section')` à
`get_aeronef_selector()` dans les 5 appels du contrôleur, comme le fait déjà `index()`
pour `get_all()`. Pas de changement de modèle nécessaire (le paramètre existe déjà et
est ignoré si absent — signature déjà prête pour ce cas).

**Risque de régression** : faible. Effet visible : la liste déroulante des aéronefs
dans les formulaires Équipements ne proposera plus que les aéronefs de la section
active (comportement déjà celui de `index()` et de `maintenance_synthese`). À vérifier :
que le formulaire de transfert doit bien continuer à ne proposer QUE les aéronefs de la
section active (à confirmer avec l'utilisateur — un transfert inter-section pourrait
être un besoin légitime, auquel cas la correction doit plutôt afficher explicitement
l'équipement transféré même hors section, ou avertir l'utilisateur).

### 1.2 / 1.3 `maintenance-synthese-smoke.spec.js` — tests obsolètes (pas un bug applicatif)

**Tests** : *fleet view lists aircraft...* et *tableau des potentiels lists aircraft...*
**Symptôme** : `TimeoutError: page.selectOption: Timeout 15000ms exceeded` sur
`locator('#section_select')` — l'élément n'existe pas dans la page.

**Cause racine** : les deux tests attendent un filtre de section **local** à la page
(`#section_select` + `#btn-filtrer-section`, avec vérification d'URL
`/maintenance_synthese/index/1`). Or ni la vue (`application/views/maintenance_synthese/
index.php`, `tableau.php`) ni le contrôleur ne définissent un tel filtre. Le
contrôleur (`maintenance_synthese.php` L60-62) porte d'ailleurs un commentaire explicite :
« filtree sur la section active (selecteur de section habituel du menu, **pas de filtre
local** — PRD EF7.3) ». Le filtrage se fait bien (`get_aeronefs_by_section($this->
session->userdata('section'))`), mais via le sélecteur de section global du menu, pas
un contrôle sur la page.

Ces tests ciblent donc une fonctionnalité qui n'a jamais été prévue par le PRD —
ils ont vraisemblablement été écrits par anticipation ou copiés depuis un autre module
qui a ce filtre local, sans être adaptés.

**Correction proposée** : réécrire les deux tests pour supprimer l'interaction avec
`#section_select`/`#btn-filtrer-section` et vérifier à la place que le contenu affiché
respecte bien la section active déjà positionnée par `switchToPlaneurSection()` (ex.
tous les aéronefs listés appartiennent à la section Planeur), conformément au
comportement réellement spécifié par le PRD EF7.3.

**Risque de régression** : aucun (modification des tests uniquement, pas du code
applicatif).

---

## 2. Pollution du log applicatif — clés de traduction manquantes

`grep -c "Could not find the language line" application/logs/log-2026-09-14.php` →
**44 029** occurrences pendant l'exécution des suites de tests (contre 427 sur une
journée normale de trafic, `log-2026-09-10.php`). Même cause racine, largement amplifiée
parce que Playwright visite en une seule exécution des pages/rôles que l'usage courant
ne couvre que rarement. Chaque occurrence correspond à un `log_message('error', ...)`
déclenché par CodeIgniter dans `CI_Lang::line()`, donc à une vraie clé de traduction
absente ou non chargée — indépendamment du fait que l'utilisateur voie ou non un défaut
visuel (souvent masqué par un fallback).

| Clé manquante | Occurrences/jour (pic test) | Origine | Diagnostic |
|---|---:|---|---|
| `gvv_button_edit` | 11 859 | `application/helpers/balance_helper.php:112` | Clé jamais définie dans aucune langue |
| `AEROWEB` | 4 379 | `application/views/bs_menu_accabs.php:41` | Clé jamais définie (nom propre, pas forcément à traduire) |
| `gvv_vue_comptes_field_solde_debit` / `_solde_credit` / `_nom` / `_codec` / `_section_name` | ~3 606 chacune | `MetaData::field_long_name('vue_comptes', ...)` (via `comptes.php` export CSV/PDF, `Document.php`) | Clés jamais définies ; `field_long_name()` interroge toujours `lang->line()` avant de retomber sur `Comment`/`field_name()`, donc l'erreur est loggée même quand l'affichage reste correct grâce au fallback |
| `gvv_transfert_menu` / `gvv_import_title` | 2 811 chacune | `application/views/bs_menu.php:220-221` (menu global, rendu sur **toute** page) | Clés bien définies dans `compta_lang.php`, mais ce fichier n'est chargé que par les contrôleurs Compta — `bs_menu.php` y fait référence sans que le fichier de langue soit garanti chargé sur les pages non-Compta |
| `gvv_str_actions` | 1 863 | à confirmer (probablement colonne « Actions » d'un tableau générique) | À investiguer précisément avant correction |
| `comptes_label_comptes` | 126 | `comptes_model.php:749,1945` | À vérifier : chargement de `comptes_lang` |
| `gvv_tarifs_field_prix` / `_date` | 112 chacune | `application/views/produits/bs_formView.php` (`field_long_name('tarifs', ...)`) | Clés bien définies dans `tarifs_lang.php`, mais `produits.php` (contrôleur) ne charge que `$this->lang->load('produits')`, jamais `tarifs` |
| `gvv_produits_field_id` / `_created_by` / `_created_at` / `_updated_by` / `_updated_at` / `_club` | 73 chacune | Champs d'audit ajoutés par le refactoring produits/tarifs (migration 149, cf. `doc/plans/refactoring_produits_tarifs_plan.md`) | Clés jamais définies — les 4 champs d'audit obligatoires (CLAUDE.md règle 8) n'ont pas reçu d'entrée de langue lors du refactoring |

**Correction proposée** (peut se faire en un seul lot, faible risque) :
1. Ajouter les clés manquantes (`gvv_button_edit`, `AEROWEB`, les `gvv_vue_comptes_field_*`,
   les `gvv_produits_field_{id,created_at,created_by,updated_at,updated_by,club}`) dans
   les 3 fichiers de langue (`french`, `english`, `dutch`), avec le même libellé que les
   champs d'audit déjà traduits ailleurs dans le projet (réutiliser les libellés
   génériques existants type « Créé le », « Créé par », etc. pour rester cohérent).
2. Charger `tarifs_lang` dans `produits.php::__construct()` (à côté de
   `$this->lang->load('produits')`).
3. Charger `compta_lang` (ou au minimum les 2 clés utilisées) de façon plus globale
   pour que `bs_menu.php` fonctionne sur toute page — par exemple en l'ajoutant à la
   liste de langues chargées par le contrôleur de base, si c'est la convention déjà
   utilisée pour d'autres clés de `bs_menu.php` (à vérifier avant de choisir entre
   autoload global vs. chargement ciblé, pour ne pas alourdir inutilement chaque page).
4. Investiguer `gvv_str_actions` et `comptes_label_comptes` avant correction (origine
   pas encore confirmée avec certitude).

**Risque de régression** : très faible — ajout de traductions et d'un chargement de
fichier de langue supplémentaire, aucun changement de logique. À vérifier après coup
que le volume de `Could not find the language line` retombe à un niveau proche de zéro
sur un nouveau run complet des deux suites.

---

## 3. PHPUnit — 0 échec, 62 skips (pas d'action corrective requise, sauf point de dette technique)

Aucun test PHPUnit n'échoue. Les 62 skips sont des exclusions volontaires et
explicites (assertions `markTestSkipped()` avec message), pour deux catégories :

- **Données insuffisantes sur cette instance de test** (ex. *No users found for the
  selected role*, *Solde pilote insuffisant*, *Aucune section avec enabled=0*) — non
  bloquant, dépend des données présentes sur gvv.net à l'instant du run.
- **Tests de migration devenus impossibles à rejouer isolément** suite au refactoring
  produits/tarifs (migration 149) : `BackfillAuditHistoriqueMigrationTest` (migration
  142), `TarifsIsCotisationMigrationTest` (migration 099) — les colonnes qu'ils
  testent (`tarifs.saisie_par`, `type_ticket`) ont été déplacées/supprimées par la
  migration 149, postérieure. Ce sont des tests de non-régression sur des migrations
  historiques, dont l'utilité diminue une fois le refactoring en place et testé par
  ailleurs.

**Aucune correction requise pour faire passer la suite** (elle passe déjà). Point de
dette technique à trancher séparément, hors périmètre de ce plan : décider si ces tests
de migration obsolètes doivent être retirés, adaptés pour s'auto-`skip` proprement (déjà
le cas), ou remplacés par un test couvrant directement l'état final post-migration 149.

---

## 4. Ordre de correction proposé

1. **§1.1** — bug fonctionnel `maintenance_equipements` (scope de section manquant) :
   correction courte, 5 lignes dans un seul contrôleur, comportement observable
   immédiatement via le smoke test existant une fois corrigé.
2. **§2** — clés de traduction manquantes/mal chargées : correction mécanique et sans
   risque, assainit fortement le log applicatif (44 029 → proche de 0 sur un run complet).
3. **§1.2/1.3** — réécriture des 2 tests Playwright obsolètes pour coller au
   comportement réellement spécifié (PRD EF7.3).
4. **§3** — décision différée sur les tests de migration obsolètes (hors urgence).

## 5. Validation (non-régression)

Après implémentation de 1 à 3 :
- `./run-all-tests.sh` → toujours 0 échec attendu.
- `cd playwright && npx playwright test --reporter=line` → 0 échec attendu (823/823,
  hors skips existants).
- `grep -c "Could not find the language line" application/logs/log-$(date +%Y-%m-%d).php`
  avant/après le même run complet, pour confirmer la baisse du volume d'erreurs.

---

## 6. Réalisé (2026-09-14)

### §1.1 — `maintenance_equipements` (corrigé)

Les 6 appels à `get_aeronef_selector()` dans `application/controllers/
maintenance_equipements.php` (`create`, `store` en cas d'erreur, `edit`, `update` en cas
d'erreur, `transfer`, `transfer_store` en cas d'erreur) passent maintenant
`$this->session->userdata('section')`.

**Découverte en cours de correction, hors périmètre initial** : une fois le sélecteur du
formulaire de **transfert** restreint à la section active, il proposait toujours l'aéronef
*actuel* de l'équipement comme cible — un « transfert » vers soi-même n'a pas de sens.
`Maintenance_equipement_model::get_aeronef_selector()` a été étendu avec un second
paramètre `$exclude_aeronef_id`, passé par le contrôleur dans les deux appels de
`transfer()`/`transfer_store()`. Conséquence directe (déjà anticipée dans la version
précédente de ce document, §1.1 dernier paragraphe) : une section ne comptant qu'un seul
aéronef actif n'a alors plus aucune cible de transfert possible. Question posée à
l'utilisateur : restreindre au périmètre section (retenu) ou autoriser le transfert
inter-section. Suite à ce choix, `application/views/maintenance_equipements/transfer.php`
affiche désormais un message explicite (`maintenance_transfert_aucun_aeronef`, ajouté aux
3 langues) et masque le formulaire quand aucun aéronef cible n'est disponible, au lieu de
présenter un formulaire non exploitable — conforme à la règle CLAUDE.md « never fail
silently ».

### §2 — Clés de traduction manquantes/mal chargées (corrigé)

Toutes les clés listées au tableau du §2 ajoutées aux 3 langues (`gvv_lang.php`,
`comptes_lang.php`, `produits_lang.php`), `tarifs` chargé dans `Produits::__construct()`,
`compta` chargé dans `bs_menu.php`. Validé : 0 occurrence des clés visées dans les ~20 Mo
de log les plus récents (couvrant les runs complets PHPUnit + Playwright post-correction),
contre 44 029/run avant correction. `gvv_str_actions` et `comptes_label_comptes` ont été
tracés précisément en cours de correction (`balance_helper.php` et `comptes_model.php`
respectivement) et ajoutés eux aussi, plutôt que laissés « à investiguer ».

### §1.2/1.3 — Tests `maintenance-synthese-smoke.spec.js` obsolètes (corrigé)

Les deux tests réécrits : suppression de l'interaction avec `#section_select`/
`#btn-filtrer-section` (jamais implémenté, cf PRD EF7.3), remplacée par une assertion que
la liste ne contient que des aéronefs de la section active (`not.toContainText('F-JHRV')`,
aéronef d'une autre section).

### Découverte non anticipée : régression sur `maintenance-smoke.spec.js`

Un test e2e plus large (non listé dans le triage initial car il passait encore à ce
moment-là) échouait pour la même cause racine : il vérifie un vrai transfert
(création → dossier → opération → transfert → historique préservé) avec `obelix` en
section Planeur, qui n'a qu'un seul aéronef actif dans les fixtures — donc plus aucune
cible possible une fois le sélecteur restreint à la section. Contrairement à §1.1, ce test
ne peut pas se contenter du message « aucune cible » : c'est justement un transfert réel
qui est testé. Décision utilisateur : donner à `obelix` le rôle `mecano` sur la section ULM
en plus de Planeur (ULM a plusieurs aéronefs actifs), plutôt que retirer la restriction.

Mis en œuvre :
- `bin/create_test_users.sh` : `obelix` a désormais `mecano` sur ULM en plus de Planeur.
- `application/controllers/admin.php::_create_test_gaulois_users()` — **second script
  de création des mêmes utilisateurs de test**, mis à jour à l'identique (sinon
  `TestUsersCoherenceTest` échoue, cf plus bas).
- `application/tests/integration/TestUsersCoherenceTest.php` — **troisième source
  canonique** (les rôles attendus pour `obelix` y sont dupliqués en dur), mise à jour de
  même. Ce test existe précisément pour détecter un désync entre les deux scripts ; il a
  bien fait son travail.
- `playwright/tests/maintenance-smoke.spec.js` : parcours basculé de la section Planeur
  vers la section ULM (renommage `PLANEUR_SECTION`/`switchToPlaneurSection` →
  `ULM_SECTION`/`switchToUlmSection`), pour disposer d'un vrai second aéronef.

**Effet de bord traité** : `bin/create_test_users.sh` (`delete_user()`) supprime les
tables `membres`/`comptes`/`user_roles_per_section`/`users` par nom d'utilisateur, mais pas
`maintenance_operations` (FK `RESTRICT` sur `mecano_id`) ni les autres tables
`maintenance_*`. Les runs Playwright précédents (avant et pendant cette session) avaient
laissé des lignes `maintenance_operations`/`dossiers`/`equipements`/`programmes` +
`archived_documents` référençant `obelix` (ces specs ne nettoient pas leurs propres
fixtures — dette de test préexistante, hors périmètre de ce plan). Au premier re-run du
script, cela a bloqué la suppression de `membres` et laissé **3 lignes dupliquées** dans
`users` pour `obelix`, avec 0 rôle — login cassé. Corrigé en supprimant manuellement les
lignes `maintenance_*`/`archived_documents` de test (identifiées par préfixe `SMOKE*`,
`created_by`/`mecano_id`='obelix', date du jour) et leurs fichiers physiques sous
`uploads/documents/club/`, puis en relançant `bin/create_test_users.sh` proprement
(`obelix` recréé avec un id unique et les rôles attendus). Toutes les lignes de test créées
pendant les runs de validation de cette session ont été nettoyées de la même façon après
chaque run final.

### Validation finale

- `./run-all-tests.sh` : 2006 tests, **0 échec**, 62 skips (inchangé).
- `cd playwright && npx playwright test --reporter=line` : **823/823** (789 passed + 34
  skipped, 0 failed).
- Volume `Could not find the language line` sur les clés visées : **0** sur le run complet
  final (vs 44 029 avant correction).

### §3 — non traité (comme prévu, décision différée)

Aucune action : la suite PHPUnit passe déjà sans ces migrations rejouées. Reste une dette
technique à trancher séparément.
