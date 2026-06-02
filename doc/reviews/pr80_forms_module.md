# Code Review — PR #80 : Module Formulaires (Forms)

**Date** : 2026-06-02  
**Reviewer** : Claude Code (Sonnet 4.6)  
**Commit HEAD** : `4cabe5f7093524ffdead5a54b4c4640cfa294256`  
**Branch** : `feature/forms`

---

## Résumé

La PR ajoute un module complet de formulaires HTML natifs (inspiré Google Forms) : administration CRUD, rendu public multi-pages, soumission anonyme, gestion des fichiers uploadés, génération PDF/impression. L'architecture générale est saine et cohérente avec les conventions CodeIgniter 2.x du projet. Cependant, plusieurs problèmes de sécurité allant du critique au mineur ont été identifiés.

---

## Todo list par criticité décroissante

### CRITIQUE

#### 1. XSS via injection CSS dans `global_css` (publique, admin, prévisualisation)

**Fichiers concernés** :
- `application/views/forms_public/bs_show.php`, ligne 34
- `application/views/forms_admin/bs_css_preview.php`, ligne 31
- `application/controllers/forms_admin.php`, ligne 732 (submission_view)

Le champ `global_css` (bloc `<style>`) est injecté **sans aucune échappement** dans le HTML généré. Un administrateur malveillant peut y injecter du CSS avec `</style><script>alert(1)</script>`, ce qui aboutit à un XSS persistent. Même si seuls les admins peuvent éditer ce champ, la page `forms_public/bs_show.php` est publique et affiche ce CSS à tous les visiteurs.

```php
// views/forms_public/bs_show.php ligne 34 — DANGEREUX
<?= $form['global_css'] ?>
```

**Correction** : Au minimum, neutraliser `</style>` dans le contenu CSS en remplaçant `</style>` par `<\/style>` avant injection, ou supprimer complètement les balises HTML du CSS. Envisager HTMLPurify ou une validation stricte à la sauvegarde.

---

#### 2. XSS via `$error` affiché sans échappement dans `bs_show.php`

**Fichier** : `application/views/forms_public/bs_show.php`, ligne 46

```php
<div class="alert alert-danger"><?= $error ?></div>
```

La variable `$error` provient de `$this->session->flashdata('forms_public_error')` qui contient le résultat de `implode('<br>', $errors)`. Les messages d'erreur de `Forms_validation::validate_field_value()` échappent bien les labels (`html_escape($field_label)`), mais le message d'erreur d'upload contient `strip_tags($this->upload->display_errors('', ''))` — non échappé avant mise en flashdata. Un nom de fichier malicieux pourrait provoquer un XSS.

**Correction** : `<?= html_escape($error) ?>` ou utiliser `<?= $error ?>` uniquement si toutes les sources de l'erreur sont garanties échappées. Comme `<br>` est voulu, utiliser `nl2br(html_escape($error))` et remplacer `implode('<br>', ...)` par `implode("\n", ...)`.

---

#### 3. Actions destructives via GET sans CSRF : `delete`, `publish`, `duplicate`, `page_delete`, `field_delete`

**Fichier** : `application/controllers/forms_admin.php`

Les méthodes `delete()` (ligne 226), `publish()` (ligne 257), `duplicate()` (ligne 238), `page_delete()` (ligne 515) et `field_delete()` (ligne 1310) sont accessibles via des liens `<a href="...">` (GET), sans token CSRF ni vérification que la requête est un POST. La protection CSRF globale CodeIgniter est désactivée (`$config['csrf_protection'] = FALSE` dans `config.php`).

Un lien forgé dans un email ou une page externe suffit à supprimer un formulaire ou à le publier involontairement (CSRF classique).

```php
// views/forms_admin/bs_index.php ligne 83
<a href="<?= site_url('forms_admin/delete/' . $form['id']) ?>" onclick="return confirm(...)">Supprimer</a>
```

**Correction** : Exiger POST pour toutes les actions destructives ou modifiantes. Utiliser un formulaire `<form method="post">` avec un token CSRF manuel (champ caché signé avec la session) puisque la protection globale est désactivée.

---

### MAJEUR

#### 4. Injection HTML/XSS via `$raw_html` dans la vue publique

**Fichier** : `application/views/forms_public/bs_show.php`, lignes 62-64

```php
<?= $raw_html ?>
```

Le contenu `content_html` saisi par l'administrateur est injecté directement dans la page publique. Ce comportement est probablement voulu (l'admin compose le formulaire en HTML natif), mais il doit être documenté comme une confiance explicite accordée aux administrateurs. Si un compte admin est compromis, un attaquant peut injecter du JavaScript dans tous les formulaires publics.

**Recommandation** : Ajouter un commentaire explicatif et envisager une politique CSP (`Content-Security-Policy`) pour limiter l'impact.

---

#### 5. Pas de transaction DB lors de la soumission d'un formulaire avec fichiers

**Fichier** : `application/controllers/forms_public.php`, lignes 193-211

```php
$submission_id = $this->form_submissions_model->create_submission(...);
// ... si $submission_id est valide ...
$this->form_submissions_model->save_submission_files($submission_id, $uploaded_files);
```

La création de la soumission (`create_submission`) et la sauvegarde des fichiers (`save_submission_files`) sont deux opérations DB séparées sans transaction englobante. Si `save_submission_files` échoue (ex. erreur disque ou DB), les fichiers physiques sont déjà uploadés mais pas enregistrés en base. La soumission existe sans ses fichiers associés.

**Correction** : Envelopper les deux opérations dans `$this->db->trans_start()` / `$this->db->trans_complete()`. En cas d'échec, supprimer les fichiers physiques déjà copiés.

---

#### 6. `import_format` non validé contre une liste blanche

**Fichier** : `application/controllers/forms_admin.php`, lignes 552-556

```php
$format = (string) $this->input->post('import_format');
// Validation : seulement 'required', pas de in_list
$content_html = $format === 'text'
    ? nl2br(html_escape($raw_content))
    : html_entity_decode($raw_content, ENT_QUOTES | ENT_HTML5, 'UTF-8');
```

Si `import_format` n'est pas `'text'`, n'importe quelle valeur passée en POST (ex. `'html'`, `'script'`, ...) déclenche le chemin `html_entity_decode`. La validation `set_rules('import_format', 'Format', 'required')` ne vérifie pas que la valeur est dans `['text', 'html']`.

**Correction** : Ajouter `in_list[text,html]` à la règle de validation. Traiter explicitement les deux cas attendus.

---

#### 7. `Content-Disposition` : injection de CRLF via `original_name`

**Fichier** : `application/controllers/forms_admin.php`, lignes 1103-1111

```php
$safe_name = basename((string) $file['original_name']);
header('Content-Disposition: attachment; filename="' . $safe_name . '"');
```

`original_name` est stocké en base depuis le nom de fichier original soumis par le client. Bien que la bibliothèque CI Upload nettoie certains caractères via `clean_file_name()`, cette fonction ne supprime pas `\r` et `\n`. Un nom de fichier contenant une séquence CRLF pourrait permettre une injection d'en-têtes HTTP.

**Correction** : Avant de construire l'en-tête, filtrer CRLF explicitement :
```php
$safe_name = str_replace(["\r", "\n", '"'], ['', '', ''], basename((string) $file['original_name']));
```

---

#### 8. Suppression de soumission sans vérification d'ownership de la section

**Fichier** : `application/controllers/forms_admin.php`, méthode `submission_delete()` (ligne 1036)

La méthode vérifie que `$submission['form_id'] === $form['id']`, et que le formulaire existe, mais ne vérifie pas que le formulaire appartient à la section de l'administrateur connecté. Un admin de section A peut supprimer les soumissions d'un formulaire global ou de la section B s'il connaît les IDs.

**Correction** : Dans `load_form_or_redirect()`, vérifier que le formulaire est accessible à la section de l'utilisateur courant, en cohérence avec `list_forms()` qui filtre par `section_context`.

---

### MINEUR

#### 9. Pagination côté serveur absente dans `submissions()`

**Fichier** : `application/controllers/forms_admin.php`, ligne 626

```php
'submissions' => $this->form_submissions_model->get_form_submissions((int) $form['id'], 200, 0),
```

La limite est fixée à 200 en dur. Pour des formulaires avec beaucoup de réponses, cela peut causer des problèmes de mémoire et de performance.

---

#### 10. Dead code : `set_submission_status()` dans `Form_submissions_model`

**Fichier** : `application/models/form_submissions_model.php`, lignes 171-188

La méthode `set_submission_status()` existe mais n'est appelée nulle part dans les contrôleurs de la PR.

---

#### 11. Méthodes `_fill_html_values_readonly()` et `_fill_html_values()` dupliquées

**Fichier** : `application/controllers/forms_admin.php`, lignes 828-993

Les deux méthodes partagent 80% de leur logique (parcours DOM, substitution des champs). La différence est le rendu final (champs read-only vs remplacement par spans). Une refactorisation avec un paramètre de mode réduirait le risque de divergence.

---

#### 12. Le code HTML de la page est nettoyé par regex au lieu du DOM

**Fichiers** : `application/controllers/forms_admin.php` (lignes 687-697, 790-800) et `application/views/forms_public/bs_show.php` (lignes 20-30)

La suppression des balises `<html>`, `<head>`, `<form>`, `<button type="submit">` etc. est faite par une série de `preg_replace`. Ces regex sont fragiles face à du HTML non standard (attributs imbriqués, casse mixte). Utiliser `DOMDocument` (déjà utilisé dans `_fill_html_values_readonly`) serait plus robuste.

---

#### 13. `affected_rows() >= 0` n'est pas un indicateur de succès fiable

**Fichiers** : `application/models/form_submissions_model.php` (ligne 187, 274), `application/models/forms_model.php` (ligne 118), `application/models/form_fields_model.php` (ligne 113)

`affected_rows() >= 0` est toujours vrai (même en cas d'erreur ou de mise à jour sans modification). Le test correct pour vérifier qu'une mise à jour a eu lieu est `affected_rows() > 0`. Pour un `update`, `0` signifie "aucune ligne modifiée" (la valeur était déjà identique ou l'ID n'existe pas), ce qui peut masquer des erreurs silencieuses.

---

#### 14. UUID de soumission généré par `uniqid()` — non cryptographiquement sûr

**Fichier** : `application/models/form_submissions_model.php`, ligne 289

```php
return uniqid('sub_', true);
```

`uniqid()` avec microtime n'est pas cryptographiquement aléatoire. Pour une application où l'UUID est exposé (par exemple dans des liens de confirmation), `bin2hex(random_bytes(16))` serait préférable.

---

#### 15. `global_css` non validé côté serveur pour la longueur dans store/update

**Fichier** : `application/controllers/forms_admin.php`, lignes 122, 178

La validation `max_length[65535]` est définie pour `global_css`, mais le champ est stocké en `MEDIUMTEXT` (jusqu'à 16 MB). La validation est correcte dans son principe mais la limite devrait être documentée ou alignée avec la taille réelle du type DB.

---

## Synthèse

| # | Criticité | Fichier | Description |
|---|-----------|---------|-------------|
| 1 | ~~CRITIQUE~~ CORRIGÉ | `views/forms_public/bs_show.php:34` | XSS via injection `global_css` — `str_ireplace('</style>', ...)` |
| 2 | ~~CRITIQUE~~ CORRIGÉ | `views/forms_public/bs_show.php:46` | XSS `$error` — source html_escape dans `forms_public.php` |
| 3 | ~~CRITIQUE~~ CORRIGÉ | `controllers/forms_admin.php:226,257,238,515,1310` | CSRF — POST obligatoire + formulaires dans les vues |
| 4 | MAJEUR | `views/forms_public/bs_show.php:63` | Injection HTML brut (confiance admin) |
| 5 | MAJEUR | `controllers/forms_public.php:193-211` | Pas de transaction DB submit + fichiers |
| 6 | MAJEUR | `controllers/forms_admin.php:552` | `import_format` non validé contre liste blanche |
| 7 | MAJEUR | `controllers/forms_admin.php:1109-1111` | Injection CRLF dans `Content-Disposition` |
| 8 | MAJEUR | `controllers/forms_admin.php:1036` | Ownership de section non vérifié à la suppression |
| 9 | MINEUR | `controllers/forms_admin.php:626` | Pagination submissions limitée à 200 en dur |
| 10 | MINEUR | `models/form_submissions_model.php:171` | Dead code : `set_submission_status()` |
| 11 | MINEUR | `controllers/forms_admin.php:828,920` | Duplication `_fill_html_values*` |
| 12 | MINEUR | `controllers/forms_admin.php:687-697` | Nettoyage HTML par regex fragile |
| 13 | MINEUR | `models/form_submissions_model.php:187` | `affected_rows() >= 0` toujours vrai |
| 14 | MINEUR | `models/form_submissions_model.php:289` | UUID non cryptographique (`uniqid`) |
| 15 | MINEUR | `controllers/forms_admin.php:122` | Limite `global_css` à documenter/aligner |
