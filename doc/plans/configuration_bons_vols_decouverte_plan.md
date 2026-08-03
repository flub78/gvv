# Plan d'implémentation — Configuration des Bons de Vol de Découverte

Date : 3 août 2026
Source PRD : `doc/prds/configuration_bons_vols_decouverte_prd.md`

## Décisions de conception

| Point | Décision |
|-------|----------|
| Moteur PDF | Nouvelle classe `Vd_bon_pdf extends TCPDF`, format A5 paysage. Le moteur `Cartes_membre_pdf` est conçu pour un gabarit multi-cartes A4 (Avery C32016-10) et n'est pas réutilisable tel quel pour un document plein format A5 ; le **format JSON du layout** (champs variables/statiques positionnables) est en revanche repris à l'identique pour rester cohérent avec les cartes de membre. |
| QR code | Nouveau type de champ dédié `qr_field` (x, y, taille, activé) dans le layout, distinct des `variable_fields` : le QR code est généré à la volée (`QRcode::png()`, comme aujourd'hui) à partir de l'URL `vols_decouverte/action/{id}`, ce n'est pas une image statique. |
| Stockage des looks | Nouvelle table `vols_decouverte_looks` (et non la table générique `configuration` utilisée par les cartes), car il faut gérer plusieurs configurations nommées simultanément, alors que les cartes ne versionnent qu'une configuration active par saison. |
| Association section → look | Nouvelle table de correspondance `vols_decouverte_look_sections` (section_id, look_id). Une section sans ligne associée utilise le look marqué `is_default`. |
| Stockage du PDF généré | Colonne `pdf_path` ajoutée à `vols_decouverte` ; fichier physique sous `uploads/vols_decouverte/`. Généré à la création et régénéré à la modification (hooks `post_create()` / `post_update()` du contrôleur), jamais à l'impression/l'envoi. |
| Contacts | Remplacent la liste fixe actuelle (avion/planeur/ULM) par des champs fixes libres (mêmes principes que les `static_fields` des cartes), en nombre et libellés paramétrables par look. |
| Coexistence | Bascule pilotée par une clé de configuration générique `vd.new_pdf_engine.enabled` (table `configuration`). Tant qu'elle est à `false`, `post_create/post_update` n'appellent pas le nouveau moteur et le comportement actuel est inchangé. |
| Contrôle d'accès | Configuration des looks et associations réservée aux mêmes rôles que les autres actions d'administration de `vols_decouverte` (`club-admin`, `gestion_vd`, `tresorier`, `bureau` via `has_full_vd_rights()`). |

## Design technique

### Nouvelles tables

```
vols_decouverte_looks
├── id
├── nom                    varchar
├── layout_json            text   -- { recto: {variable_fields[], static_fields[], qr_field}, verso: {...} }
├── fond_recto_path        varchar   -- uploads/configuration/vd/
├── fond_verso_path        varchar
├── is_default             tinyint(1)
├── created_at, updated_at, created_by, updated_by

vols_decouverte_look_sections
├── id
├── section_id             int  (FK sections)
├── look_id                int  (FK vols_decouverte_looks)
├── created_at, updated_at, created_by, updated_by
```

### Colonne ajoutée à `vols_decouverte`

```
pdf_path   varchar(255) NULL   -- chemin du PDF stocké, régénéré à la création/modification
```

### Champs variables disponibles (résolveur `Vd_bon_pdf::resolve_variable`)

| id | Source |
|----|--------|
| `numero` | `id` du vol de découverte |
| `date_vente` | `date_vente` |
| `date_validite` | `date_validite` (ou `date_vente` + 1 an si absent, comme aujourd'hui) |
| `beneficiaire` | `beneficiaire` |
| `occasion` | `occasion` |
| `de_la_part` | `de_la_part` |
| `type_vol` | Description produit/tarif résolue comme dans `generate_pdf()` actuel |

Champs fixes (statiques) : texte libre, nom du club/section, contacts — un champ par contact avec libellé + valeur, saisis librement dans le look (pas de liste fixe avion/planeur/ULM en dur).

### Architecture

```
Controller : vols_decouverte_looks (nouveau)
├── index()                     → liste des looks (admin)
├── edit($id)                   → création/édition d'un look (fonds + mise en page)
├── layout_save()                → sauvegarde JSON (POST)
├── layout_export($id) / layout_import()
├── delete($id)
└── assign_section()             → associer un look à une section, ou "look par défaut"

Model : vols_decouverte_looks_model (nouveau)
├── get_look($id)
├── get_default_look()
├── get_look_for_section($section_id)   → look associé, sinon look par défaut
├── save_look($id, $data)
├── delete_look($id)
└── assign_section($section_id, $look_id)

Library : Vd_bon_pdf extends TCPDF (nouveau)
├── resolve_variable($id, $data)
├── render_recto($data, $layout, $fond)
├── render_verso($data, $layout, $fond)
├── render_qr($qr_field, $url)
└── generate($vd_data, $look)          → PDF complet 2 pages (fond+QR / contenu)

Contrôleur vols_decouverte (existant, modifié)
├── post_create($data)   → si moteur activé : génère le PDF via Vd_bon_pdf::generate() et enregistre pdf_path
├── post_update($data)   → idem, régénère et remplace le fichier
├── regenerate($obfuscated_id)  (nouveau, admin) → force la régénération manuelle (EF9)
├── print_vd() / email_vd()      → servent le fichier pdf_path stocké au lieu d'appeler generate_pdf() à la volée
└── generate_pdf()                → conservé tel quel, utilisé en fallback si moteur non activé ou pdf_path absent (bons historiques)

paiements_en_ligne.php::_generate_vd_pdf() → inchangé dans ce plan (bascule traitée au Lot 5)
```

### Layout par défaut embarqué

Le layout par défaut de `vols_decouverte_looks_model` doit reproduire **exactement** la mise en page actuelle de `generate_pdf()` (mêmes positions, même fond `Bon-Bapteme.png`, même position de QR code à 175,5mm/30mm) pour garantir zéro régression visuelle tant qu'aucun look personnalisé n'est configuré.

---

## Stratégie de livraison

| Lot | Contenu | Statut |
|-----|---------|--------|
| Lot 1 | Migrations + modèles (tables looks, association, `pdf_path`) | ✅ Livré |
| Lot 2 | Moteur `Vd_bon_pdf` (layout par défaut = reproduction de l'existant) | ✅ Livré |
| Lot 3 | UI admin de configuration des looks + association section → look | ✅ Livré |
| Lot 4 | Génération/stockage à la vente et à la modification, régénération manuelle | ✅ Livré |
| Lot 5 | Bascule différée de l'ancien mécanisme (après période de validation club) | À faire |

Chaque lot se termine par une **gate de validation** : pas de passage au lot suivant tant que la gate n'est pas verte.

## Definition of Done globale

- EF1 à EF9 du PRD couverts et démontrables.
- PDF A5 recto/verso généré à la vente, stocké, servi tel quel en réimpression/renvoi.
- Layout par défaut visuellement identique à l'ancien mécanisme (non-régression).
- Ancien mécanisme intact et fonctionnel en parallèle jusqu'à la bascule (Lot 5).
- Tests PHPUnit et smoke Playwright ajoutés et passants, aucune régression sur la suite existante.

---

## Lot 1 — Migrations et modèles de données ✅

Réalisations :
- Migrations 152 (`vols_decouverte_looks`), 153 (`vols_decouverte_look_sections`, FK vers 152 et vers `sections`, contrainte UNIQUE sur `section_id`), 154 (colonne `pdf_path` sur `vols_decouverte`).
- `application/config/migration.php` mis à jour (version 154).
- `Vols_decouverte_looks_model` (extends `Common_Model`) : `save_look()`, `get_layout()`, `get_default_look()` (fallback sur un layout par défaut embarqué non persisté si aucun look `is_default` n'existe), `set_default()`, `get_look_for_section()`.
- `Vols_decouverte_look_sections_model` (extends `Common_Model`) : `assign()` (upsert), `clear()`, `get_look_id_for_section()`.
- Le layout par défaut embarqué (positions verso : numero/beneficiaire/occasion/de_la_part/date_validite/type_vol + qr_field recto) est une approximation structurelle ; sa fidélité pixel-perfect avec `generate_pdf()` est garantie par le moteur du Lot 2, pas par ce modèle.

**Gate de fin Lot 1** ✅ :
- `php -l` sur tous les fichiers créés : OK.
- Tests PHPUnit migration (`VolsDecouverteLooksMigrationTest`, 5 tests : colonnes, contrainte UNIQUE, nullabilité, réversibilité up/down) et modèle (`VolsDecouverteLooksModelMySqlTest`, 9 tests : round-trip `save_look`/`get_by_id`, `get_layout`, `get_default_look`, `set_default` un seul défaut à la fois, résolution section → look avec fallback, upsert d'association, `clear()`).
- `./run-all-tests.sh` : 1687 tests, 0 échec (61 skips préexistants sans lien avec ce lot).

## Lot 2 — Moteur de rendu PDF (`Vd_bon_pdf`) ✅

Réalisations :
- `Vd_bon_pdf extends TCPDF` (A5 paysage) : `generate($data, $layout, $fond_recto, $fond_verso)`, `render_face()`, `render_field()`, `render_background()`, `render_qr()`, `resolve_variable()`. Aucune position codée en dur : tout vient du layout injecté. Résolution des fonds (y compris repli sur l'image historique) laissée à l'appelant, comme pour `Cartes_membre_pdf`.
- Génération du QR code identique à l'existant : `QRcode::png($qr_url, $path, QR_ECLEVEL_L, 10, 1)`.
- Répertoire `application/third_party/phpqrcode/cache/` créé (attendu par `qrconfig.php`, absent de l'environnement, gitignored) : sans lui, `QRcode::png()` échoue silencieusement (déjà vrai pour l'ancien mécanisme, pas une régression introduite par ce lot).

**Gate de fin Lot 2** ✅ :
- Non-régression : `VdBonPdfMySqlTest` vérifie que le layout par défaut de `Vols_decouverte_looks_model` reproduit exactement la position de QR code codée en dur dans `vols_decouverte::generate_pdf()` (x=175, y=5, taille=30, recto uniquement) et expose tous les champs variables historiques (numero, beneficiaire, occasion, de_la_part, date_validite, type_vol) ; l'exécution du moteur avec ce layout et le fond historique (`Bon-Bapteme.png`) produit un PDF valide.
- Tests unitaires purs (`VdBonPdfTest`, 7 tests, sans DB) : layout vide, fond présent/absent, tous les champs variables, look personnalisé (champ déplacé, police/couleur/alignement modifiés, QR désactivé), génération et placement du QR code.
- `./run-all-tests.sh` : 1698 tests, 0 échec (mêmes 61 skips préexistants).

## Lot 3 — UI de configuration des looks (admin) ✅

Réalisations :
- Contrôleur `Vols_decouverte_looks` (extends `MY_Controller`) : `index()`, `create()`, `edit($id)`, `upload_fond($id)`, `layout_save($id)`, `layout_export($id)`, `layout_import($id)`, `set_default($id)`, `delete($id)`, `sections()`. Contrôle d'accès dupliqué de `Vols_decouverte::has_full_vd_rights()` (club-admin, gestion_vd, tresorier, bureau) — même choix de duplication que `paiements_en_ligne.php` pour ce même contrôle.
- Vues Bootstrap 5 : `bs_index.php` (liste + création + définir par défaut + suppression), `bs_edit.php` (fonds recto/verso + mise en page à onglets recto/verso, calqué sur `cartes_membre/bs_config.php`, avec une section « QR code » à la place de la section « Photo »), `bs_sections.php` (association section → look par menu déroulant).
- Fonds stockés sous `uploads/configuration/vd/`, nommés `look_{id}_{face}`.
- Lien de menu « Configuration des bons » ajouté dans `bs_menu.php`, sous-menu vols de découverte, réservé à `club-admin`.
- Traductions fr/en/nl (`gvv_vd_looks_*`, `gvv_menu_vd_looks`) dans `gvv_lang.php`.
- `Vols_decouverte_looks_model::default_layout()` rendue publique (au lieu de protégée) pour permettre au contrôleur de l'utiliser à la création d'un nouveau look.
- Répertoire `uploads/configuration/vd/` créé et rendu inscriptible par le groupe web (`uploads/configuration/` ne l'était pas), sinon l'upload de fond échoue silencieusement à la création du sous-répertoire.

**Gate de fin Lot 3** ✅ :
- Smoke Playwright (`vd-looks-smoke.spec.js`, 9 tests) : accès à la liste (admin), création d'un look, upload d'un fond recto, déplacement d'un champ verso + sauvegarde de la mise en page, export puis import du JSON produisant un résultat identique, association d'un look à une section, 404 sur un id inconnu, refus d'accès pour un utilisateur sans droit vd (`testuser`), redirection vers le login pour un utilisateur non authentifié. 9/9 passants.
- `./run-all-tests.sh` : 1698 tests, 0 échec (mêmes 61 skips préexistants).
- Données de test créées sur gvv.net pendant la campagne Playwright nettoyées après coup (looks, association section, fichier de fond uploadé).

## Lot 4 — Génération et stockage à la vente/modification ✅

Réalisations :
- `Vols_decouverte::post_create($data)` / `post_update($data)` : si `vd.new_pdf_engine.enabled` (table `configuration`) vaut `'1'`, résolvent le look de la section (`Vols_decouverte_looks_model::get_look_for_section()`), génèrent le PDF via `Vd_bon_pdf::generate()` et stockent le fichier sous `uploads/vols_decouverte/vol_decouverte_{id}.pdf`, en mettant à jour `pdf_path`.
- `regenerate($obfuscated_id)` (admin, `has_full_vd_rights()`) : force la régénération (EF9), ignore le flag d'activation.
- `print_vd()` / `email_vd()` : servent `pdf_path` s'il existe et que le fichier est présent ; sinon, appellent l'ancien `generate_pdf()` inchangé (bons historiques ou moteur non activé) — coexistence intacte.
- Type de vol résolu par la même requête produits/tarifs que l'ancien `generate_pdf()`, pour rester fidèle au contenu historique.

**Bug pré-existant découvert et corrigé** (bloquant pour ce lot) : `Vols_decouverte_model::create()` appelait `parent::create($data)` sans retourner sa valeur — `Gvv_Controller::formValidation()` recevait donc toujours `null` comme id après création, empêchant `post_create()` de connaître l'id du bon à générer. Corrigé par un simple `return`. Sans impact fonctionnel visible avant ce lot (le hook `post_create()` de base ne fait que logguer), mais bloquant pour la génération immédiate à la vente. Régression couverte par `VolsDecouverteModelCreateReturnsIdTest`.

**Gate de fin Lot 4** ✅ :
- `VolsDecouverteModelCreateReturnsIdTest` (mysql) : `create()` retourne bien l'id de la ligne créée — verrouille la correction ci-dessus.
- Smoke Playwright (`vd-pdf-storage-smoke.spec.js`, 3 tests, flag activé le temps du fichier) :
  - création via le formulaire réel → `pdf_path` renseigné, fichier présent, PDF valide ;
  - modification d'un champ (bénéficiaire) → fichier régénéré (contenu différent, toujours un PDF valide) ;
  - modification du look d'une section après la vente d'un bon, puis réimpression (`print_vd`) de ce même bon → octets strictement identiques à l'original (stabilité de l'apparence historique, EF1.3).
- `./run-all-tests.sh` : 1699 tests, 0 échec (mêmes skips préexistants).
- Effet de bord découvert et corrigé dans le mock `RealDatabase` des tests (`application/tests/integration_bootstrap.php`, utilisé uniquement par la suite `mysql`) : `select_max()` manquant, et `row()` qui consommait le curseur au lieu d'être idempotent comme `CI_DB_result::row()` — corrigés, sans régression sur le reste de la suite.
- Flag `vd.new_pdf_engine.enabled` remis à `'0'` après la campagne de tests (comportement par défaut inchangé pour les clubs).

## Lot 5 — Bascule différée de l'ancien mécanisme

Étapes :
1. Activation de `vd.new_pdf_engine.enabled` pour un club pilote.
2. Période de validation avec ce club (retours sur le rendu, les looks).
3. Décision explicite (hors code) de désactivation de l'ancien mécanisme pour les nouveaux bons ; `generate_pdf()` reste en place comme fallback pour les bons historiques sans `pdf_path`.
4. Alignement de `paiements_en_ligne.php::_generate_vd_pdf()` sur le même mécanisme de stockage, si retenu à ce stade.

**Gate de fin Lot 5** :
- Validation explicite du club pilote.
- Aucune régression constatée sur les bons émis avant bascule.

---

## Plan de tests transverse

```bash
source setenv-php8.sh
./run-all-tests.sh
cd playwright && npx playwright test --reporter=line
```

## Risques et parades

- **Régression visuelle vs bons historiques** : mitigée par le layout par défaut reproduisant exactement l'existant (Lot 2), et par le stockage à la vente qui fige l'apparence (Lot 4).
- **Deux mécanismes actifs en parallèle → confusion** : feature flag unique (`vd.new_pdf_engine.enabled`), logs explicites indiquant quel moteur a généré chaque PDF.
- **Import JSON de look invalide** : validation du JSON et des champs obligatoires avant acceptation, message d'erreur explicite (même principe que l'import de layout des cartes membre).
- **Nombre de contacts variable d'un club à l'autre** : traité comme des champs fixes libres (pas de schéma figé avion/planeur/ULM), cohérent avec les `static_fields` des cartes.
- **Espace de stockage des PDF** : fichiers A5 de faible poids, volumétrie non critique à l'échelle d'un club ; à surveiller si le nombre de vols de découverte devient très élevé.
