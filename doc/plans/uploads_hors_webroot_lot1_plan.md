# Plan de développement — Sortie de `uploads/` et `backups/` de l'arborescence web

## Lot 1 : configuration centrale, helpers de chemin, routes de service de fichiers

---

## 1. Contexte et documents de référence

| Document | Rôle |
|---|---|
| `doc/security/exposition_directe_uploads.md` | **Source des exigences** — advisory de sécurité (faille critique, correctifs intérimaires déjà déployés) |
| `doc/features/Backup.md`, `doc/design_notes/sauvegarde_hors_site_design.md` | Fonctionnement des sauvegardes locales et hors-site (impacté par le lot 2) |
| `doc/plans/attachments_improvement_plan.md` | Historique du sous-système pièces jointes |

Il n'y a pas de PRD dédié : il s'agit d'une remédiation de sécurité en mode maintenance,
pas d'une fonctionnalité produit. Les exigences fonctionnelles sont celles de l'advisory,
résumées en §3.

Les correctifs intérimaires sont **déjà en place** (à ne pas refaire) : `.htaccess` dans
`uploads/`, `uploads/documents/`, `uploads/restore/`, `backups/` ; règle nginx
`location ^~` en production. Ce lot les rend **redondants** (défense en profondeur
conservée) plutôt que critiques.

---

## 2. Problème à résoudre

`uploads/` et `backups/` sont physiquement sous la racine web. Le serveur sert tout
fichier existant sans passer par l'application → les contrôles d'accès PHP
(`archived_documents::preview()` etc.) sont contournables par URL directe, et
`backups/` expose des sauvegardes complètes de la base.

Deux obstacles empêchent de simplement déplacer les répertoires :

1. **~95 références de chemin codées en dur** (`'./uploads/…'`, `FCPATH.'uploads/…'`,
   `getcwd().'/backups/'`) réparties sur ~40 fichiers, sans point de configuration.
2. **7 emplacements génèrent des URL web directes** (`<img src="base_url()/uploads/…">`)
   pour des vignettes et images ; les déplacer casse l'affichage.

Conséquences déjà visibles du correctif intérimaire : vignettes des documents archivés
cassées, et XSS stockée possible via `preview()` (upload `.html` rendu `inline`).

---

## 3. Objectifs du lot

### Dans le périmètre

- **O1** — Introduire `$config['gvv_var_path']` + helpers `gvv_upload_path()` /
  `gvv_backup_path()` ; valeur par défaut rétro-compatible (aucune install cassée).
- **O2** — Remplacer les ~95 littéraux de chemin par les helpers.
- **O3** — Remplacer les 7 emplacements d'URL directe par des routes contrôleur
  appliquant les mêmes contrôles d'accès que le reste du module concerné.
- **O4** — Rétablir l'affichage des vignettes de documents archivés (régression du
  correctif intérimaire).
- **O5** — Fermer la XSS stockée : retirer `html|htm` de `allowed_types`, forcer
  `Content-Disposition: attachment` + type non exécutable pour tout ce qui n'est pas
  image ou PDF dans les routes de service.

### Hors périmètre (lots ultérieurs)

- Déplacement physique de `uploads/` et `backups/` hors de la racine web (lot 2).
- Adaptation des scripts `tools/autobackup*.py`, `bin/restore_media.sh`, sauvegarde
  hors-site, scripts d'installation (lot 2 — essentiellement variables d'environnement
  et documentation).
- Déplacement de `application/logs/` (lot 2, même principe via `$config['log_path']`).
- Durcissement des autres sous-répertoires `uploads/` (`email_lists/`,
  `forms_submissions/`, `reponses/`).

---

## 4. État des lieux technique

### 4.1 Littéraux de chemin à refactorer (O2)

| Zone | Fichiers principaux (occurrences) |
|---|---|
| Documents archivés | `controllers/archived_documents.php` (3) |
| Membres / photos | `controllers/membre.php` (8), `models/membres_model.php` (1) |
| Sauvegarde / restauration | `controllers/admin.php` (7 uploads + 9 backups), `libraries/Database.php` (2), `controllers/openflyers.php` (2) |
| Comptabilité (justificatifs) | `controllers/compta.php` (6), `controllers/rapprochements.php` (1) |
| Cartes membres | `controllers/cartes_membre.php` (2), `models/cartes_membre_model.php` (4), `views/cartes_membre/bs_config.php` (4) |
| Procédures | `models/procedures_model.php` (4), `controllers/procedures.php` (1) |
| Métadonnées / rendu générique | `libraries/MetaData.php` (6), `libraries/File_manager.php` (1) |
| Formulaires | `controllers/forms_admin.php` (3), `controllers/forms_public.php` (2), `libraries/Forms_file_storage.php` (1) |
| Divers | `controllers/meteo.php` (4), `controllers/configuration.php` (4), `controllers/config.php` (2), `controllers/partage.php` (3), `models/email_lists_model.php` (3), `controllers/email_lists.php` (1), `controllers/attachments.php` (2), `controllers/acceptance_admin.php` (2), `controllers/briefing_passager.php` (1), `controllers/maintenance_*.php` (3), `controllers/formation_seances_theoriques.php` (1), `controllers/vols_decouverte*.php` (4) |
| Config | `config/attachments.php` (2 — `temp_upload_path`, `batch_compression_temp_backup`) |

À **ne pas toucher** : `application/migrations/044_procedures.php`, `config/club*.php`
(non versionné), tout `application/tests/` (traité en §7).

### 4.2 Emplacements générant des URL web directes (O3 / O4)

| # | Emplacement | Contenu | Route contrôleur cible | Contrôle d'accès |
|---|---|---|---|---|
| U1 | `helpers/MY_html_helper.php::attachment()` branche PDF (l.635-641) | vignette `thumb_*.jpg` de doc archivé | nouveau param `$thumb_url` passé par l'appelant | celui de l'appelant |
| U2 | `controllers/archived_documents.php::generate_thumbnail()` (retour AJAX) + `views/archived_documents/bs_pdf_thumbnails_js.php` | vignette PDF générée à la volée | `archived_documents/thumbnail/$id` | `_can_access_private_file()` + propriétaire/CA |
| U3 | `libraries/MetaData.php` rendu `image`/`upload_image` (l.~1264) et photo membre (l.~1940) | images pilotées par métadonnées, photos membres, `vue_configuration` | route générique `attachments/serve/...` **ou** délégation au contrôleur courant | selon table/champ |
| U4 | `models/membres_model.php:124` (HTML photo dans la liste) | photo membre | `membre/photo/$mlogin` | connecté ; visibilité liste membres |
| U5 | `controllers/compta.php::_attachment_url()` (l.3973) — 3 appels | justificatifs comptables | route de prévisualisation compta (vérifier l'existant `compta/...`) | droits compta de la section |
| U6 | `views/procedures/bs_view.php:122`, `views/procedures/bs_attachments.php:146,200` | vignettes + aperçus des pièces jointes de procédures | `procedures/attachment_view/$id/$file` + `.../thumb/...` (réutiliser `procedures/download`) | droits de consultation de la procédure |
| U7 | `views/cartes_membre/bs_config.php:115,155` | fonds recto/verso de carte | `cartes_membre/fond/{recto|verso}/$annee` | admin (config cartes) |

**Déjà servis par contrôleur — aucune modification** : `motd/media/$id`,
`forms_admin/submission_file/...`, `procedures/download/...`,
`archived_documents/preview|download/...`.

### 4.3 XSS stockée (O5)

- `controllers/archived_documents.php` : `allowed_types` contient `html|htm`
  (`formValidation` l.548, `edit_docValidation` l.785).
- `preview()` (l.1126-1132) émet `Content-Type` détecté + `Content-Disposition: inline`
  → un `.html` téléversé s'exécute dans l'origine du site.

---

## 5. Architecture cible du lot

### 5.1 Configuration et helpers

- `application/config/paths.php` (nouveau, **non versionné** — ajouté à `.gitignore`,
  avec un `paths.example.php` versionné) :
  - `$config['gvv_var_path']` : chemin absolu, défaut `''`.
- `application/helpers/gvv_paths_helper.php` (nouveau) :
  - `gvv_var_path()` : renvoie `gvv_var_path` configuré, sinon `FCPATH` (comportement
    actuel — rétro-compatibilité totale tant que le lot 2 n'a pas déplacé les dossiers).
  - `gvv_upload_path($rel = '')` → `gvv_var_path() . 'uploads/' . $rel`
  - `gvv_backup_path($rel = '')` → `gvv_var_path() . 'backups/' . $rel`
  - Chargé via `config/autoload.php`.
- **Invariant** : après refactor et sans définir `gvv_var_path`, tous les chemins
  résolvent exactement comme aujourd'hui.

### 5.2 Service de fichiers — brique commune

Un trait ou une petite librairie `File_response` centralisant :

- résolution `gvv_upload_path()` + `realpath()` + contrôle de préfixe (le fichier
  demandé doit être sous la racine uploads) ;
- `is_file()` / 404 ;
- en-têtes : `Content-Type` explicite ; `Content-Disposition: inline` **uniquement**
  pour `image/*` et `application/pdf`, sinon `attachment` ; `Cache-Control: private`,
  `ETag` sur `filemtime+size`, réponse `304` si `If-None-Match`.

Chaque contrôleur concerné expose ses routes en réutilisant cette brique **après** son
propre contrôle d'autorisation métier.

### 5.3 Adaptation du helper `attachment()`

Signature étendue : `attachment($id, $filename, $url = "", $thumb_url = null)`.
La branche PDF utilise `$thumb_url` s'il est fourni, sinon l'icône générique (plus de
construction d'URL via `base_url()`). `get_pdf_thumbnail_path()` (résolution disque)
est conservé.

---

## 6. Découpage en étapes

> Estimations en jours-personne (j). Développeur unique connaissant GVV.

### Étape 0 — Cadrage et filet de tests (0,5 j)

- Note de conception courte dans `doc/design_notes/` (helpers + brique `File_response`
  + tableau des routes) — pas de nouveau document de suivi, ce plan fait foi.
- Recenser les tests PHPUnit/Playwright existants touchant `uploads/` (grep) pour
  mesurer la couverture de départ.

### Étape 1 — Configuration + helpers (0,5 j)

- `config/paths.php` + `paths.example.php` + entrée `.gitignore` + autoload.
- `gvv_paths_helper.php` avec `gvv_var_path()` / `gvv_upload_path()` /
  `gvv_backup_path()`.
- Tests unitaires du helper (défaut = FCPATH ; valeur explicite ; concaténation).
- **Validation** : suite complète inchangée (aucun appelant encore modifié).

### Étape 2 — Priorité sécurité : documents archivés + XSS (1,5 j) ⭐

Livre O4 (régression vignettes) et O5 (XSS).

- `archived_documents/thumbnail/$id` : sert `thumb_*.jpg` via la brique commune, après
  `_can_access_private_file()` + contrôle propriétaire/CA (mêmes règles que `preview()`).
- `attachment()` : nouveau param `$thumb_url` ; vues `bs_my_documents.php`,
  `bs_view.php`, `bs_pending.php`, `bs_uploadView.php` (briefing passager) et
  `archived_documents.php::page()` passent `site_url('archived_documents/thumbnail/'.$id)`.
- `generate_thumbnail()` (AJAX) : renvoie l'URL de route au lieu de `base_url()+chemin` ;
  `bs_pdf_thumbnails_js.php` adapté.
- `preview()` : `Content-Disposition: inline` seulement pour image/pdf, sinon
  `attachment` + `Content-Type: application/octet-stream`.
- `allowed_types` : retirer `html|htm` (`formValidation`, `edit_docValidation`).
- Refactor des 3 littéraux `./uploads/…` de `archived_documents.php` vers le helper.
- **Tests** : voir §7.1.

### Étape 3 — Rendu générique piloté par métadonnées (2 j)

- Route générique `attachments/serve` (ou délégation) couvrant les cas `MetaData.php`
  `image`/`upload_image` : membres/photo, `vue_configuration`, fichiers de config.
  Contrôle d'accès par table/champ (réutiliser la logique de visibilité existante ;
  photo membre = connecté + droit de voir la fiche).
- `MetaData.php` : `attachment_field()` et le rendu photo n'émettent plus d'URL
  `base_url()` directe.
- `membres_model.php:124` : l'URL photo passe par `membre/photo/$mlogin`.
- Refactor des littéraux `membre.php` (8) et `MetaData.php` (6).
- **Tests** : §7.2.

### Étape 4 — Comptabilité, procédures, cartes membres (2 j)

- **Compta** (U5) : route de prévisualisation des justificatifs (vérifier si
  `compta/` en a déjà une exploitable ; `_attachment_url()` renvoie la route).
  Refactor des 6 littéraux `compta.php` + `rapprochements.php`.
- **Procédures** (U6) : `procedures/attachment_view` + `.../thumb` réutilisant
  `procedures/download`. Vues `bs_view.php`, `bs_attachments.php`. Refactor
  `procedures_model.php` (4) + `procedures.php` (1).
- **Cartes membres** (U7) : `cartes_membre/fond/{recto|verso}/$annee` (admin).
  Vue `bs_config.php`. Refactor `cartes_membre.php` (2) + `cartes_membre_model.php` (4).
- **Tests** : §7.3.

### Étape 5 — Refactor résiduel des littéraux (1,5 j)

Fichiers restants de §4.1 : `admin.php` (uploads + backups + `Database.php`),
`openflyers.php`, `meteo.php`, `configuration.php`, `config.php`, `partage.php`,
`email_lists*`, `attachments.php`, `acceptance_admin.php`, `briefing_passager.php`,
`maintenance_*`, `formation_seances_theoriques.php`, `vols_decouverte*`,
`forms_admin.php`, `forms_public.php`, `Forms_file_storage.php`, `File_manager.php`,
`config/attachments.php`.

- Purement mécanique ; `admin.php`/`Database.php` : `getcwd().'/backups/'` →
  `gvv_backup_path()`.
- **Validation** : `grep` de contrôle → plus aucun littéral `./uploads/` ni
  `getcwd().*backups` hors tests/migrations ; `php -l` sur tous les fichiers modifiés.

### Étape 6 — Tests de non-régression, documentation, revue (1,5 j)

- Compléter la suite (§7.4), `./run-all-tests.sh --coverage`.
- Smoke test Playwright (§7.5).
- Documentation (§8).
- `/code-review` du diff complet ; correctifs.

**Total estimé : ~11 j** (hors aléas). Étape 2 livrable et déployable seule.

---

## 7. Stratégie de validation et de test

Principe (CLAUDE.md) : tout test touchant la base ou le disque crée ses propres
fixtures et nettoie en teardown, y compris sur échec ; aucun `.htaccess` ni fichier
laissé derrière.

### 7.1 Documents archivés (Étape 2)

- **PHPUnit** `ArchivedDocumentsThumbnailAccessTest` :
  - propriétaire → `thumbnail/$id` = 200, `Content-Type: image/jpeg` ;
  - autre pilote non admin → redirection / 403 ;
  - document de type privé, non bureau → refus ;
  - `preview()` sur une pièce non image/pdf → en-tête `attachment` ;
  - upload `.html` refusé par `allowed_types` (message d'erreur attendu).
- Conserver ces tests (démontrent la correction de C1/C2).

### 7.2 Rendu générique / photos (Étape 3)

- `MemberPhotoServeTest` : `membre/photo/$mlogin` accessible connecté, 404 si pas de
  photo, refus si non authentifié.
- Test i18n/rendu : `MetaData` `image` field ne contient plus `base_url()` + `uploads/`
  dans le HTML généré (assertion sur la sortie).

### 7.3 Compta / procédures / cartes (Étape 4)

- `ProcedureAttachmentAccessTest` : accès autorisé selon droits procédure ; parcours
  d'ID d'un autre fichier → refus.
- `ComptaAttachmentAccessTest` : justificatif accessible avec droit compta de la
  section, refusé sinon.
- `CarteFondAccessTest` : `cartes_membre/fond/recto/$annee` réservé admin.

### 7.4 Non-régression transverse

- `UploadsNoDirectUrlTest` (garde-fou) : `grep` applicatif — échoue si un
  `base_url()`/`src=` pointe encore `uploads/` hors routes autorisées.
- `GvvPathsHelperTest` : invariance du défaut (= FCPATH).
- Suite complète `./run-all-tests.sh --coverage` verte, couverture ≥ baseline.

### 7.5 Smoke test Playwright

Scénario `uploads_serving.spec` :
1. login pilote → `archived_documents/my_documents` : la vignette d'un PDF **s'affiche**
   (requête `thumbnail/<id>` = 200).
2. login admin → `archived_documents/page` : idem sur la liste filtrée.
3. `membre` : la photo d'un membre s'affiche dans la liste.
4. tentative directe `GET /uploads/documents/<chemin>` (contexte navigateur connecté) →
   403/redirection.

En cas d'échec : activer le mode développement dans `index.php`, reproduire, corriger,
garder le test.

---

## 8. Documentation

- **`doc/security/exposition_directe_uploads.md`** : mettre à jour §7 (la régression
  vignettes est résolue) et §8 (XSS `.html` résolue) ; ajouter un renvoi à ce plan et
  au lot 2.
- **`doc/design_notes/uploads_service_fichiers.md`** (nouveau, minimal) : brique
  `File_response`, helpers de chemin, tableau des routes, diagramme PlantUML du flux
  « vue → route contrôleur → contrôle d'accès → fichier disque ».
- **`doc/features/`** : note sur le fait que les fichiers `uploads/` ne sont plus
  accessibles par URL directe.
- **Documentation utilisateur** : aucun changement fonctionnel visible attendu
  (mêmes écrans, mêmes vignettes, mêmes téléchargements) → pas de mise à jour des
  guides `doc/users/`. À confirmer au smoke test ; si un écran change, mettre à jour
  le guide concerné.
- **Pas de nouveau document de suivi** : l'avancement est tenu dans ce fichier
  (cases à cocher §10).

---

## 9. Critères de succès

- **CS1** — Sans `gvv_var_path` défini, comportement disque identique à l'avant-lot
  (suite de tests verte, couverture ≥ baseline).
- **CS2** — Aucun littéral `./uploads/`, `FCPATH.'uploads'`, `getcwd().*backups` dans
  le code applicatif (hors `tests/`, `migrations/`, `config/club*`).
- **CS3** — Aucune balise `<img>`/lien vers `uploads/` construit avec `base_url()` :
  toutes les images et vignettes transitent par une route contrôleur.
- **CS4** — Les vignettes des documents archivés s'affichent à nouveau (régression
  intérimaire levée) — vérifié Playwright.
- **CS5** — Upload `.html` refusé ; `preview()` ne rend jamais `inline` un contenu
  non image/pdf — vérifié PHPUnit.
- **CS6** — Un accès direct `GET /uploads/documents/<fichier>` en contexte navigateur
  connecté est refusé par l'application (indépendamment de la config serveur).
- **CS7** — `/code-review` du diff sans finding bloquant.

---

## 10. Suivi

- [ ] Étape 0 — cadrage + note de conception + inventaire tests
- [ ] Étape 1 — `config/paths.php`, helpers, tests unitaires
- [ ] Étape 2 — `archived_documents/thumbnail`, `attachment($thumb_url)`, `generate_thumbnail`, `preview()` durci, `allowed_types` sans `html`
- [ ] Étape 3 — route générique métadonnées + `membre/photo`, refactor `membre.php` / `MetaData.php`
- [ ] Étape 4 — compta, procédures, cartes membres
- [ ] Étape 5 — refactor résiduel des littéraux + `grep` de contrôle
- [ ] Étape 6 — non-régression, Playwright, doc, `/code-review`
- [ ] Advisory mis à jour ; renvoi vers le lot 2 (déplacement physique + scripts + install)

---

## 11. Risques

| Risque | Impact | Mitigation |
|---|---|---|
| Sites d'URL directe oubliés (vues rarement rendues) | image cassée en prod | test garde-fou `UploadsNoDirectUrlTest` (grep) ; smoke Playwright multi-écrans ; recette sur `gvv.net` |
| `MetaData.php` central → régression large (tous formulaires à champ fichier) | affichage KO sur plusieurs modules | Étape 3 isolée, tests de rendu sur plusieurs tables (`membres.photo`, `vue_configuration.file`) |
| Contrôle d'accès d'une nouvelle route trop laxiste ou trop strict | fuite ou blocage fonctionnel | réutiliser les helpers d'autorisation existants du module ; test d'accès négatif systématique |
| Performance : passage de fichiers statiques par PHP | charge accrue sur vignettes de listes | `ETag`/`304`, `Cache-Control: private`, vignettes déjà petites ; mesurer sur la liste admin |
| Fichiers `uploads/formulaires/` (déjà `deny`) rendus dans des pages de formulaire | images de formulaire cassées | vérifier en Étape 5 le rendu `forms_public` ; router si nécessaire |
| Chevauchement avec un autre agent sur `gvv.net` | conflits | voir mémoire projet ; coordination avant recette |

---

## 12. Ressources

- 1 développeur familier de GVV (CodeIgniter 2, sous-système pièces jointes, MetaData).
- Accès `gvv.net` pour recette (copie de production).
- PHP 7.4 **et** 8.4 pour la validation croisée (`setenv-php*.sh`).
- Utilisateurs de test (`bin/create_test_users.sql`).
- Revue via `/code-review` puis `/create-pr` (le lot génère > 5 fichiers modifiés et
  touche la sécurité → PR obligatoire).
