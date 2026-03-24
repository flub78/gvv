# Plan d'implémentation — Briefing Passager

Date : 21 mars 2026
PRD : [doc/prds/briefing_passager_prd.md](../prds/briefing_passager_prd.md)
Référence : [FichePassager.pdf](../prds/references/FichePassager.pdf)

## Ordre d'implémentation

UC1 (upload papier scanné) → UC3 (consultation admin) → UC2 (signature numérique)

---

## Phase 1 — UC1 : Upload d'un document signé

### Étape 1.1 — Migration : champ `aerodrome` dans `vols_decouverte` ✅

- [x] Créer `085_vols_decouverte_aerodrome.php` : ajout colonne `aerodrome VARCHAR(100) NULL DEFAULT NULL`
- [x] Mettre à jour `application/config/migration.php` → version 85
- [x] Ajouter `aerodrome` dans `Gvvmetadata.php` (type string)
- [x] Ajouter `aerodrome` dans les fichiers de langue (français, anglais, néerlandais)
- [x] Ajouter `aerodrome` dans `select_page()` de `vols_decouverte_model.php`

**Validation** : le champ `aerodrome` apparaît dans le formulaire VLD et dans la liste.

**Test PHPUnit** : `application/tests/integration/VolsDecouverteMigrationTest.php`
- Vérifie que la colonne existe en base après migration
- Vérifie que la migration est réversible (rollback puis re-up)

---

### Étape 1.2 — Migration : lien VLD dans `archived_documents` ✅

- [x] Créer `086_archived_documents_vld.php` : ajout colonne `vld_id INT NULL DEFAULT NULL`, clé étrangère vers `vols_decouverte(id)` avec `ON DELETE SET NULL`
- [x] Mettre à jour `application/config/migration.php` → version 86

**Validation** : la colonne existe en base. Un document archivé peut être lié à un VLD sans rompre les documents existants.

**Test PHPUnit** : inclure dans `VolsDecouverteMigrationTest.php`
- Vérifie que `vld_id` est nullable et n'affecte pas les documents existants

---

### Étape 1.3 — Types de documents « Briefing Passager » et « Consignes de sécurité » ✅

- [x] Créer `087_briefing_passager_document_types.php` : correction scope `briefing_passager` → `section`, création `consignes_securite`
- [x] Mettre à jour `application/config/migration.php` → version 87
- [x] Ajouter les fichiers de langue `briefing_passager_lang.php` (français, anglais, néerlandais)

**Validation** : le type apparaît dans la liste des types de documents.

---

### Étape 1.4 — Modèle : méthodes briefing dans `archived_documents_model.php` ✅

- [x] Ajouter `get_briefing_by_vld($vld_id)` : retourne le briefing lié à un VLD (ou NULL)
- [x] Ajouter `get_briefings_recent($days = 90)` : retourne tous les briefings des N derniers jours (basé sur `uploaded_at`), jointure avec `vols_decouverte`
- [x] Ajouter `get_consignes_by_section($section_id)` : retourne le document consignes actif pour une section

**Test PHPUnit** : `application/tests/integration/BriefingPassagerModelTest.php`
- `get_briefing_by_vld()` retourne NULL si aucun briefing
- `get_briefing_by_vld()` retourne le document après insertion
- `get_briefings_recent(90)` retourne les briefings des 90 derniers jours uniquement

---

### Étape 1.5 — Contrôleur : `briefing_passager.php` ✅

- [x] Créer `application/controllers/briefing_passager.php` étendant `Gvv_Controller`
- [x] `index()` + `bs_indexView.php` : formulaire standalone de recherche de VLD + upload
- [x] `search_vld()` : endpoint AJAX retournant les VLD correspondant à la saisie (nom, numéro partiel, téléphone) — format JSON
- [x] `upload($vld_id)` + `upload_submit($vld_id)` + `bs_uploadView.php` : formulaire + traitement upload
- [x] `view($id)` + `bs_viewView.php` : affichage du document briefing archivé
- [x] `admin_list()` + `export_pdf()` + `bs_adminListView.php` : UC3 complet

**Validation** : accès à `/briefing_passager`, recherche AJAX fonctionnelle, upload d'un fichier PDF crée bien un document archivé lié au VLD.

---

### Étape 1.6 — Icône briefing dans la liste des VLD ✅

- [x] Ajouter action `briefing_vd` dans `MetaData.php` → lien vers `briefing_passager/upload/{id}`
- [x] Ajouter `briefing_vd` dans `actions` du `bs_tableView.php` VLD
- [x] Charger la langue `briefing_passager` dans la vue VLD

**Validation** : dans la liste VLD, l'icône change selon la présence ou l'absence d'un briefing.

---

### Étape 1.7 — Test Playwright UC1 ✅

- [x] Créer `playwright/tests/briefing-passager-smoke.spec.js`
  - Accès formulaire standalone (testuser/membre)
  - Liste VLD avec icône briefing (testadmin)
  - Clic sur icône → formulaire upload
  - Recherche AJAX JSON fonctionnelle
  - Correction : ajout de `$model`, `$controller`, `$modification_level` dans le contrôleur
  - Correction : `$this->load->lang()` → `$this->lang->load()`
  - Correction : `group_start()` non disponible en CI2 → requête SQL brute
  - Correction : ajout de `/briefing_passager/` et `/vols_decouverte/` dans les permissions rôle membre et ca

---

## Phase 2 — UC3 : Consultation administrative ✅

### Étape 2.1 — Vue liste des briefings ✅

- [x] Méthode `admin_list()` dans `briefing_passager.php` :
  - Filtre par défaut : 90 derniers jours (modifiable via formulaire)
  - Colonnes : date du vol, aérodrome, immatriculation, bénéficiaire, mode (upload / numérique), date de signature, statut
  - Icône de visualisation par ligne
  - Filtre : présent / absent
- [x] Accès menu « Briefings passagers » dans le sous-menu VLD (visible pour `has_vd_role()`)
  - Langue chargée dans `bs_menu.php`
  - Clé `briefing_passager_menu` ajoutée (FR/EN/NL)

**Validation** : un administrateur accède à la liste, voit les briefings des 90 derniers jours, peut filtrer.

---

### Étape 2.2 — Export PDF de la liste ✅

- [x] Bouton « Exporter en PDF » dans `bs_adminListView.php`
- [x] Méthode `export_pdf()` avec génération TCPDF

**Validation** : l'export PDF télécharge un fichier avec les données correctes.

---

### Étape 2.3 — Test PHPUnit UC3 ✅

- [x] Tests couverts dans `application/tests/mysql/BriefingPassagerModelTest.php` (mysql suite) :
  - `testGetBriefingsRecent_ReturnsRecentBriefings` — documents dans la fenêtre de temps
  - `testGetBriefingsRecent_ExcludesOldBriefings` — documents hors fenêtre exclus
  - `testGetBriefingsRecent_ReturnsFlightContext` — jointure VLD correcte
  - Note : un fichier `integration/` séparé ne ferait pas sens car ces tests nécessitent une vraie base de données (mysql suite)

---

### Étape 2.4 — Test Playwright UC3 ✅

- [x] Dans `briefing-passager-smoke.spec.js`, blocs UC3 :
  - Se connecter en tant qu'administrateur
  - Naviguer vers la liste admin des briefings
  - Vérifier la présence du briefing créé en UC1
  - Cliquer sur le briefing → vérifier l'affichage du document
  - Déclencher l'export PDF → vérifier le téléchargement

---

## Phase 3 — UC2 : Signature numérique

### Étape 3.1 — Consignes de sécurité par section ✅

- [x] Type `consignes_securite` créé dans migration 087 (scope=section, has_expiration=0, required=0)
- [x] Méthode `get_consignes_by_section($section_id)` déjà dans `archived_documents_model.php`
- [x] Migration 088 non nécessaire (intégré dans 087)

---

### Étape 3.2 — Migration : table `briefing_tokens` ✅

- [x] Créer `088_briefing_tokens.php` : table `briefing_tokens` (id, vld_id FK→vols_decouverte CASCADE, token UNIQUE 64 char, created_at, expires_at, used_at, ip_address)
- [x] Mettre à jour `application/config/migration.php` → version 88

---

### Étape 3.3 — Génération du lien de signature ✅

- [x] Méthode `generate_link($vld_id)` dans `briefing_passager.php` : génère token 64-char hex, insère dans `briefing_tokens`, affiche vue `bs_linkView`
- [x] Vue `bs_linkView.php` : QR code (phpqrcode), URL copiable (#sign_url), infos VLD

---

### Étape 3.4 — Page de signature publique ✅

- [x] Contrôleur `briefing_sign.php` (extend `CI_Controller`, pas `Gvv_Controller` — accès sans auth)
- [x] Route `briefing_sign/(:any)` → `briefing_sign/index/$1` (submit avant index dans routes.php)
- [x] `index($token)` : validation token, QR code, consignes, formulaire pré-rempli, signature_pad
- [x] Vue `bs_signView.php` : standalone HTML (sans header/footer GVV), signature_pad v4.1.7

---

### Étape 3.5 — Traitement de la soumission ✅

- [x] `submit($token)` : valide token, met à jour VLD si différences, marque token utilisé, génère PDF (TCPDF, chemin absolu via FCPATH), archive dans `archived_documents`, envoie email si `beneficiaire_email` renseigné
- [x] Vue `bs_signConfirmView.php` : page de confirmation standalone
- [x] Vue `bs_signErrorView.php` : page d'erreur standalone

---

### Étape 3.6 — Tests PHPUnit UC2 ✅

- [x] `application/tests/integration/BriefingSignatureTest.php` (9 tests, 23 assertions)
  - Format token 64-char hex
  - Insertion et récupération en base
  - Unicité enforced par contrainte DB
  - Détection token expiré / déjà utilisé / inconnu
  - Marquage `used_at` + `ip_address`

---

### Étape 3.7 — Test Playwright UC2 ✅

- [x] Dans `briefing-passager-smoke.spec.js` (3 tests ajoutés, total 10 tests) :
  - Admin génère lien → QR code et `#sign_url` visibles
  - Utilisateur anonyme accède à la page de signature via le token → formulaire et canvas affiché
  - Token invalide → page d'erreur avec h3

---

## Phase 4 — Migration vers l'orchestrateur documentaire

> Cette phase sera exécutée après la livraison du Module 4 (Orchestrateur). Le briefing passager existant reste fonctionnel pendant toute la durée de cette migration.

- [ ] Créer l'entrée `briefing_passager` dans `application/config/workflows.json` avec les paramètres correspondant à l'implémentation actuelle (source PDF consignes section, action `sign_external`, archivage dans `archived_documents`)
- [ ] Valider le workflow via `IDocumentWorkflow::validate('briefing_passager')` — aucune erreur attendue
- [ ] Rediriger le contrôleur `briefing_passager.php` vers `DocumentWorkflow::execute('briefing_passager', ...)` en conservant les paramètres contextuels (vld_id, section_id)
- [ ] Supprimer le code spécifique résiduel du contrôleur (logique de token, génération QR, archivage direct) remplacé par l'orchestrateur
- [ ] Adapter les tests Playwright existants (`briefing-passager-smoke.spec.js`) au nouveau point d'entrée
- [ ] Valider : les 10 tests Playwright passent, l'archivage dans `archived_documents` fonctionne comme avant

---

## Récapitulatif des migrations

| N° | Fichier | Contenu |
|----|---------|---------|
| 085 | `085_vols_decouverte_aerodrome.php` | Ajout `aerodrome` à `vols_decouverte` |
| 086 | `086_archived_documents_vld.php` | Ajout `vld_id` à `archived_documents` |
| 087 | `087_briefing_passager_document_type.php` | Type de document `briefing_passager` |
| 088 | `088_consignes_securite_document_type.php` | Type de document `consignes_securite` (scope=section) |
| 089 | `089_briefing_tokens.php` | Création table `briefing_tokens` |

## Récapitulatif des tests

| Fichier | Type | Couvre |
|---------|------|--------|
| `VolsDecouverteMigrationTest.php` | PHPUnit intégration | Migrations 085, 086 |
| `BriefingPassagerModelTest.php` | PHPUnit intégration | Modèle, get_briefing_by_vld, get_briefings_recent |
| `BriefingPassagerAdminTest.php` | PHPUnit intégration | Filtrage temporel UC3 |
| `BriefingSignatureTest.php` | PHPUnit intégration | Tokens, soumission UC2 |
| `briefing-passager-smoke.spec.js` | Playwright E2E | UC1, UC2, UC3 complets |
