# Plan — Pièces jointes aux séances théoriques

**Fonctionnalité** : Attacher des documents (supports de cours, PDF, images…) aux séances théoriques (`formation_seances_theoriques`).  
**Inspiration** : mécanisme de justificatifs des écritures comptables (`compta/bs_attachments_modal.php` + table `attachments`).  
**Stockage** : `uploads/formation/` (distinct de `uploads/attachments/`).

---

## Architecture retenue

- **Table** : `attachments` existante, via `referenced_table = 'formation_seances'` + `referenced_id = seance_id`. Aucune nouvelle table.
- **Endpoints AJAX** : nouveaux endpoints dans `formation_seances_theoriques` controller (pattern identique à `compta`).
- **Vue** : nouvelle vue partielle `formation_seances_theoriques/attachments_section.php`, incluse dans `detail.php`.
- **Upload path** : `uploads/formation/YYYY/` (pas de sous-dossier section, les séances théoriques sont multi-sections).
- **Réutilisation** : `Attachments_model`, librairies `File_compressor`, `Pdf_thumbnail`, config `attachments.php`.

---

## Étapes

### Étape 1 — Créer le répertoire de stockage et valider les permissions
**Statut** : ✅ Complété

- `uploads/formation/` créé avec `chmod +wx`
- Couvert par `uploads/*` dans `.gitignore`
- Validation OK : création sous-répertoire annuel, écriture/lecture, nettoyage

---

### Étape 2 — Endpoints AJAX dans le contrôleur
**Statut** : ✅ Complété

Ajouter dans `application/controllers/formation_seances_theoriques.php` :

- `ajax_upload_attachment($seance_id)` — POST multipart : upload fichier, insert en base, retourne JSON `{id, url, filename, description}`
- `ajax_get_attachments($seance_id)` — GET : retourne HTML de la liste des pièces jointes
- `ajax_delete_attachment($attachment_id)` — POST : supprime fichier + enregistrement, retourne JSON `{success}`
- `ajax_update_attachment($attachment_id)` — POST : met à jour la description, retourne JSON `{success}`

Logique d'upload (copie de `compta::create_attachment`) :
- Répertoire cible : `uploads/formation/YYYY/`
- Préfixe aléatoire sur le nom de fichier
- Compression optionnelle via `File_compressor`
- Thumbnail PDF via `Pdf_thumbnail`
- Insert dans `attachments` avec `referenced_table = 'formation_seances'`, `referenced_id = seance_id`

**Validation** : tests Playwright smoke (upload + liste + suppression)

---

### Étape 3 — Vue partielle attachments_section
**Statut** : ✅ Complété

Créer `application/views/formation_seances_theoriques/attachments_section.php` :

- Liste des pièces jointes existantes (nom, description, icône type, bouton voir, bouton supprimer)
- Zone d'upload drag-and-drop (inspirée de `compta/bs_attachments_modal.php`)
- Tout en AJAX, pas de rechargement de page
- Gestion des erreurs visible pour l'utilisateur (toast ou alerte inline)

**Validation** : rendu visuel correct dans la page detail

---

### Étape 4 — Intégration dans la page de détail
**Statut** : ✅ Complété

Dans `application/views/formation_seances_theoriques/detail.php` :

- Ajouter une carte "Documents" après les informations générales
- Inclure la vue partielle `attachments_section`
- Passer `seance_id` à la vue

Dans le contrôleur `detail($id)` :

- Charger `attachments_model`
- Récupérer les pièces jointes existantes via `attachments_model` avec `referenced_table = 'formation_seances'` et `referenced_id = $id`
- Les passer à la vue

**Validation** : page detail charge sans erreur, section visible

---

### Étape 5 — Tests PHPUnit
**Statut** : ✅ Complété

Créer `application/tests/integration/formation_seance_attachments_test.php` :

- `test_upload_directory_exists_and_writable()` — vérifie que `uploads/formation/` existe et est accessible en écriture
- `test_attachment_insert_and_retrieve()` — insère un enregistrement dans `attachments` avec `referenced_table = 'formation_seances'`, le retrouve via le modèle
- `test_attachment_delete_removes_record()` — suppression logique via le modèle
- `test_attachment_references_valid_seance()` — vérifie l'intégrité référentielle (seance_id existe)

**Validation** : `./run-all-tests.sh` passe sans régression

---

### Étape 6 — Test Playwright end-to-end
**Statut** : ✅ Complété

Ajouter dans `playwright/tests/formation/seances_theoriques.spec.js` :

- `can upload document to seance theorique` — navigue vers detail d'une séance, upload un fichier PDF, vérifie qu'il apparaît dans la liste
- `can delete document from seance theorique` — supprime la pièce jointe, vérifie disparition
- `upload without instructor works` — vérifie que la création sans instructeur fonctionne (fix précédent)

**Validation** : `npx playwright test formation/seances_theoriques.spec.js --reporter=line` passe

---

## Fichiers à créer / modifier

| Fichier | Action |
|---|---|
| `uploads/formation/` | Créer (chmod +wx) |
| `application/controllers/formation_seances_theoriques.php` | Ajouter 4 endpoints AJAX |
| `application/views/formation_seances_theoriques/attachments_section.php` | Créer |
| `application/views/formation_seances_theoriques/detail.php` | Inclure la section |
| `application/tests/integration/formation_seance_attachments_test.php` | Créer |
| `playwright/tests/formation/seances_theoriques.spec.js` | Étendre |

## Fichiers réutilisés sans modification

| Fichier | Rôle |
|---|---|
| `application/models/attachments_model.php` | Accès table `attachments` |
| `application/libraries/File_compressor.php` | Compression images/PDF |
| `application/libraries/Pdf_thumbnail.php` | Miniature PDF |
| `application/config/attachments.php` | Limites upload, types autorisés |
| `application/language/*/attachments_lang.php` | Labels IHM |

---

## Points d'attention

- Le répertoire `uploads/formation/` doit être exclu du versionning (`.gitignore`)
- Les fichiers supprimés doivent l'être du filesystem ET de la base (pas de soft-delete)
- La compression est optionnelle (dépend de la config `attachments.php`)
- Les thumbnails PDF sont optionnels (dépend de Ghostscript installé sur le serveur)
- Vérifier que le MIME type est dans la liste autorisée de `attachments.php` avant d'accepter l'upload
