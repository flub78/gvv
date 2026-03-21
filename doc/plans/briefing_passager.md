# Plan d'implémentation — Briefing Passager

Date : 21 mars 2026
PRD : [doc/prds/briefing_passager_prd.md](../prds/briefing_passager_prd.md)
Référence : [FichePassager.pdf](../prds/references/FichePassager.pdf)

## Ordre d'implémentation

UC1 (upload papier scanné) → UC3 (consultation admin) → UC2 (signature numérique)

---

## Phase 1 — UC1 : Upload d'un document signé

### Étape 1.1 — Migration : champ `aerodrome` dans `vols_decouverte`

- [ ] Créer `085_vols_decouverte_aerodrome.php` : ajout colonne `aerodrome VARCHAR(100) NULL DEFAULT NULL`
- [ ] Mettre à jour `application/config/migration.php` → version 85
- [ ] Ajouter `aerodrome` dans `Gvvmetadata.php` (type string)
- [ ] Ajouter `aerodrome` dans les fichiers de langue (français, anglais, néerlandais)
- [ ] Ajouter `aerodrome` dans `select_page()` de `vols_decouverte_model.php`

**Validation** : le champ `aerodrome` apparaît dans le formulaire VLD et dans la liste.

**Test PHPUnit** : `application/tests/integration/VolsDecouverteMigrationTest.php`
- Vérifie que la colonne existe en base après migration
- Vérifie que la migration est réversible (rollback puis re-up)

---

### Étape 1.2 — Migration : lien VLD dans `archived_documents`

- [ ] Créer `086_archived_documents_vld.php` : ajout colonne `vld_id INT NULL DEFAULT NULL`, clé étrangère vers `vols_decouverte(id)` avec `ON DELETE SET NULL`
- [ ] Mettre à jour `application/config/migration.php` → version 86

**Validation** : la colonne existe en base. Un document archivé peut être lié à un VLD sans rompre les documents existants.

**Test PHPUnit** : inclure dans `VolsDecouverteMigrationTest.php`
- Vérifie que `vld_id` est nullable et n'affecte pas les documents existants

---

### Étape 1.3 — Type de document « Briefing Passager »

- [ ] Créer `087_briefing_passager_document_type.php` : insertion du type de document `briefing_passager` (scope=`section`, code=`briefing_passager`, has_expiration=0, required=0)
- [ ] Mettre à jour `application/config/migration.php` → version 87
- [ ] Ajouter la traduction du type dans les fichiers de langue

**Validation** : le type apparaît dans la liste des types de documents.

---

### Étape 1.4 — Modèle : méthodes briefing dans `archived_documents_model.php`

- [ ] Ajouter `get_briefing_by_vld($vld_id)` : retourne le briefing lié à un VLD (ou NULL)
- [ ] Ajouter `get_briefings_recent($days = 90)` : retourne tous les briefings des N derniers jours (basé sur `uploaded_at`), jointure avec `vols_decouverte`
- [ ] Modifier `select_page()` et les requêtes existantes pour inclure `vld_id` sans casser l'existant

**Test PHPUnit** : `application/tests/integration/BriefingPassagerModelTest.php`
- `get_briefing_by_vld()` retourne NULL si aucun briefing
- `get_briefing_by_vld()` retourne le document après insertion
- `get_briefings_recent(90)` retourne les briefings des 90 derniers jours uniquement

---

### Étape 1.5 — Contrôleur : `briefing_passager.php`

- [ ] Créer `application/controllers/briefing_passager.php` étendant `Gvv_Controller`
- [ ] `index()` : formulaire standalone de recherche de VLD + upload
- [ ] `search_vld()` : endpoint AJAX retournant les VLD correspondant à la saisie (nom, numéro partiel, téléphone) — format JSON
- [ ] `upload($vld_id)` : traitement du formulaire d'upload (validation fichier, archivage, liaison `vld_id`)
- [ ] `view($id)` : affichage du document briefing archivé
- [ ] Ajouter la route dans `application/config/routes.php` si nécessaire

**Validation** : accès à `/briefing_passager`, recherche AJAX fonctionnelle, upload d'un fichier PDF crée bien un document archivé lié au VLD.

---

### Étape 1.6 — Icône briefing dans la liste des VLD

- [ ] Dans la vue liste des VLD (`vols_decouverte`), ajouter une colonne icône briefing
  - Icône neutre cliquable → `/briefing_passager/upload/{vld_id}` si aucun briefing
  - Icône validée cliquable → `/briefing_passager/view/{id}` si briefing présent
- [ ] Adapter le contrôleur `vols_decouverte.php` pour passer le statut briefing à la vue

**Validation** : dans la liste VLD, l'icône change selon la présence ou l'absence d'un briefing.

---

### Étape 1.7 — Test Playwright UC1

- [ ] Créer `playwright/tests/briefing-passager-smoke.spec.js`
  - Se connecter en tant que pilote VLD
  - Naviguer vers la liste VLD
  - Vérifier la présence de l'icône briefing sur une ligne
  - Cliquer sur l'icône → vérifier l'ouverture du formulaire d'upload
  - Uploader un PDF de test → vérifier la confirmation
  - Vérifier que l'icône est maintenant « validée » sur la ligne

---

## Phase 2 — UC3 : Consultation administrative

### Étape 2.1 — Vue liste des briefings

- [ ] Ajouter dans `briefing_passager.php` la méthode `admin_list()` :
  - Filtre par défaut : 90 derniers jours (modifiable via formulaire)
  - Colonnes : date du vol, aérodrome, immatriculation, bénéficiaire, mode (upload / numérique), date de signature, statut
  - Icône de visualisation par ligne
  - Filtre : présent / absent
- [ ] Ajouter l'accès menu pour les administrateurs

**Validation** : un administrateur accède à la liste, voit les briefings des 90 derniers jours, peut filtrer.

---

### Étape 2.2 — Export PDF de la liste

- [ ] Dans `admin_list()`, ajouter un bouton « Exporter en PDF »
- [ ] Méthode `export_pdf()` : génère un PDF TCPDF de la liste filtrée (date, aérodrome, immat, bénéficiaire, mode, date signature)

**Validation** : l'export PDF télécharge un fichier avec les données correctes.

---

### Étape 2.3 — Test PHPUnit UC3

- [ ] `application/tests/integration/BriefingPassagerAdminTest.php`
  - Vérifie que `get_briefings_recent()` retourne bien les documents dans la fenêtre de temps
  - Vérifie que les documents hors fenêtre sont exclus

---

### Étape 2.4 — Test Playwright UC3

- [ ] Dans `briefing-passager-smoke.spec.js`, ajouter un bloc :
  - Se connecter en tant qu'administrateur
  - Naviguer vers la liste admin des briefings
  - Vérifier la présence du briefing créé en UC1
  - Cliquer sur le briefing → vérifier l'affichage du document
  - Déclencher l'export PDF → vérifier le téléchargement

---

## Phase 3 — UC2 : Signature numérique

### Étape 3.1 — Consignes de sécurité par section

Les consignes sont archivées dans le système documentaire existant avec `scope=section` et un type dédié `consignes_securite`. Cela réutilise l'upload, le stockage et la consultation déjà implémentés.

- [ ] Créer `088_consignes_securite_document_type.php` : insertion du type de document `consignes_securite` (scope=`section`, code=`consignes_securite`, has_expiration=0, required=0)
- [ ] Mettre à jour `application/config/migration.php` → version 88
- [ ] Ajouter dans `archived_documents_model.php` la méthode `get_consignes_by_section($section_id)` : retourne le document actif de type `consignes_securite` pour une section
- [ ] Vérifier que l'upload d'un document de type `consignes_securite` fonctionne via l'interface d'archivage existante (section documents)

**Validation** : un administrateur peut uploader un PDF de consignes depuis la gestion documentaire de sa section. Le document est récupérable via `get_consignes_by_section()`.

---

### Étape 3.2 — Migration : table `briefing_tokens`

- [ ] Créer `089_briefing_tokens.php` :
  ```sql
  CREATE TABLE briefing_tokens (
      id INT AUTO_INCREMENT PRIMARY KEY,
      vld_id INT NOT NULL,
      token VARCHAR(64) NOT NULL UNIQUE,
      created_at DATETIME NOT NULL,
      expires_at DATETIME NULL,
      used_at DATETIME NULL,
      ip_address VARCHAR(45) NULL,
      FOREIGN KEY (vld_id) REFERENCES vols_decouverte(id) ON DELETE CASCADE
  )
  ```
- [ ] Mettre à jour `application/config/migration.php` → version 89

---

### Étape 3.3 — Génération du lien de signature

- [ ] Ajouter dans `briefing_passager.php` la méthode `generate_link($vld_id)` :
  - Génère un token aléatoire sécurisé (64 caractères hex)
  - Insère dans `briefing_tokens`
  - Redirige vers `sign($token)` (la page de signature elle-même)
- [ ] Ajouter dans le formulaire de briefing un bouton « Générer un lien de signature numérique » qui appelle `generate_link()`

**Validation** : un pilote peut générer un lien. La redirection aboutit à la page de signature avec le QRCode en tête.

---

### Étape 3.4 — Page de signature publique

- [ ] Ajouter dans `briefing_passager.php` la méthode `sign($token)` (accessible sans authentification) :
  - Valide le token (existe, non utilisé)
  - Charge les données du VLD associé
  - Charge les consignes PDF de la section associée au VLD
  - Affiche le formulaire de signature
- [ ] Créer la vue `application/views/briefing_signature.php`, éléments dans l'ordre :
  1. QRCode de l'URL courante (bibliothèque `phpqrcode` existante) + texte « Scannez pour ouvrir sur votre téléphone »
  2. Informations du vol (date, aérodrome, immatriculation) — lecture seule
  3. PDF des consignes de la section affiché inline (`<iframe>` ou `<object>`) + lien de téléchargement
  4. Formulaire passager pré-rempli depuis le VLD (nom, prénom, date de naissance, poids déclaré, personne à prévenir) — modifiable
  5. Zone de signature tactile (*signature_pad* standalone dans `assets/js/`)
  6. Alternative si tactile indisponible : case à cocher + bouton de confirmation

**Validation** : accès au lien généré → page affichée. PDF de consignes visible. Formulaire pré-rempli.

---

### Étape 3.5 — Traitement de la soumission

- [ ] Ajouter dans `briefing_passager.php` la méthode `sign_submit($token)` (POST) :
  - Valide le token (non utilisé)
  - Récupère les données passager soumises
  - Compare avec le VLD : si différences, met à jour `vols_decouverte` (nom, prénom, date de naissance, poids, urgence)
  - Marque le token comme utilisé (`used_at`, `ip_address`)
  - Génère le PDF récapitulatif (TCPDF) : informations vol + informations passager + signature ou acceptation + date/heure/IP
  - Archive le PDF dans `archived_documents` avec `vld_id` et type `briefing_passager`
  - Envoie le PDF par email au passager (`beneficiaire_email`) si l'adresse est renseignée
  - Affiche une page de confirmation

**Validation** : soumission du formulaire → token invalidé, VLD mis à jour, PDF archivé, email envoyé, page de confirmation affichée. Deuxième soumission avec le même lien → page d'erreur.

---

### Étape 3.6 — Tests PHPUnit UC2

- [ ] `application/tests/integration/BriefingSignatureTest.php`
  - Génération d'un token : unicité, format
  - Token invalide → erreur HTTP 404/403
  - Token déjà utilisé → page d'erreur
  - Soumission valide : token marqué utilisé, VLD mis à jour, document archivé créé avec `vld_id`

---

### Étape 3.7 — Test Playwright UC2

- [ ] Dans `briefing-passager-smoke.spec.js`, ajouter un bloc :
  - Se connecter en tant que pilote VLD
  - Générer un lien de signature numérique pour un VLD
  - Vérifier l'affichage du lien et du QRCode
  - Ouvrir le lien en navigation anonyme
  - Vérifier l'affichage des consignes et du formulaire pré-rempli
  - Remplir le formulaire, signer (ou cocher la case), soumettre
  - Vérifier la page de confirmation
  - Vérifier en admin que le briefing est présent et consultable

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
