# Code Review — Briefing Passager

**Date** : 2026-03-22
**Fichiers** : `controllers/briefing_passager.php`, `controllers/briefing_sign.php`, `views/briefing_passager/bs_*`

---

## Problèmes par ordre de criticité

### [MOYEN] 1. Token marqué "used" avant la confirmation du succès PDF

**Fichier** : `briefing_sign.php` lignes 143–156
Le token est marqué `used_at` avant que la génération du PDF ne réussisse. Si `_generate_pdf()` échoue, le lien est consommé mais aucun document n'est archivé. Le passager ne peut pas réessayer.

**Correction** : marquer le token utilisé uniquement après la création du document dans la base.

---

### [MOYEN] 2. Boutons de suppression en GET (risque CSRF)

**Fichiers** : `bs_uploadView.php` ligne 77, `bs_viewView.php` ligne 77
Les boutons de suppression sont des liens `<a href="...">` qui déclenchent une action destructive via GET. Un lien forgé dans un email ou une page externe peut supprimer le document à l'insu d'un utilisateur dev connecté.

**Correction** : remplacer par un `<form method="post">` avec un champ `_method` ou utiliser une route POST dédiée.

---

### [MOYEN] 3. `is_dev_user` absent lors du réaffichage sur erreur de validation

**Fichier** : `briefing_passager.php` lignes 144–169
Quand `upload_submit()` détecte des champs manquants et réaffiche le formulaire, `$this->data['is_dev_user']` n'est pas défini. La vue `bs_uploadView.php` lit cette variable — cela génère une notice PHP et le bouton de suppression n'apparaît pas.

**Correction** : ajouter `$this->data['is_dev_user']` dans le bloc d'erreur, comme dans `upload()`.

---

### [MOYEN] 4. `mime_content_type()` sur un chemin relatif

**Fichier** : `bs_viewView.php` ligne 53
```php
$mime = $file ? (mime_content_type($file) ?: '') : '';
```
`$file` est un chemin relatif stocké en base (`uploads/...`). Le répertoire de travail du serveur web n'est pas garanti d'être la racine du projet, ce qui peut rendre `mime_content_type()` silencieux ou incorrect.

**Correction** : utiliser `mime_content_type(FCPATH . $file)` (en vue PHP, `FCPATH` est disponible).

---

### [BAS] 5. Logs de debug laissés en production

**Fichier** : `briefing_sign.php` lignes 291–301
Trois `log_message('debug', 'BP_SIGN: ...')` ajoutés pendant le débogage de la signature sont toujours présents. Avec `log_threshold = 4`, ils s'écrivent à chaque signature.

**Correction** : supprimer ces lignes.

---

### [BAS] 6. Fichiers QR code temporaires jamais nettoyés

**Fichier** : `briefing_sign.php` lignes 64–68
Le fichier QR (`/tmp/bp_sign_qr_{md5}.png`) est créé une fois et mis en cache, mais jamais supprimé. Un token par VLD → accumulation indéfinie dans `/tmp`.

**Correction** : supprimer le fichier après lecture, ou le régénérer sans mise en cache.

---

### [BAS] 7. Chaînes françaises codées en dur hors des fichiers de langue

**Fichiers** : `briefing_passager.php` lignes 190, 203 ; `bs_viewView.php` ligne 79
- `'Type de document briefing_passager introuvable.'`
- `'Impossible de créer le répertoire de stockage.'`
- `'Supprimer ce briefing ?'` (confirm JS)

Ces messages ne passent pas par `$this->lang->line()` et ne sont pas traduisibles.

---

### [BAS] 8. Code mort dans `search_vld()`

**Fichier** : `briefing_passager.php` lignes 287–288
```php
$escaped_id  = $this->db->escape($escaped);
$escaped_val = $this->db->escape($escaped);
```
`$escaped_id` et `$escaped_val` sont identiques. La variable `$escaped` était déjà la valeur correcte. L'une des deux affectations est inutile.

---

### [BAS] 9. Chargement des sélecteurs dupliqué

**Fichier** : `briefing_passager.php`
Le chargement de `terrain_selector`, `machine_selector`, `pilote_selector` est copié-collé entre `upload()` (lignes 84–94) et le bloc d'erreur de `upload_submit()` (lignes 161–167). Devrait être extrait en méthode privée `_load_upload_selectors()`.

---

### [BAS] 10. Date de naissance non validée

**Fichier** : `briefing_sign.php` ligne 110
`$ddn` est lu et inséré en base sans vérification de format. Une valeur invalide (`'abc'`) serait stockée telle quelle.

**Correction** : valider avec `preg_match('/^\d{4}-\d{2}-\d{2}$/', $ddn)` avant usage.

---

## Todo

- [x] **[MOYEN]** Marquer le token après succès de l'archivage PDF (#1)
- [x] **[MOYEN]** Convertir les boutons de suppression en POST (#2)
- [x] **[MOYEN]** Ajouter `is_dev_user` dans le chemin d'erreur de `upload_submit()` (#3)
- [x] **[MOYEN]** Corriger `mime_content_type()` avec `FCPATH` (#4)
- [x] **[BAS]** Supprimer les `log_message` de débogage BP_SIGN (#5)
- [x] **[BAS]** Nettoyer les fichiers QR temporaires (#6)
- [x] **[BAS]** Externaliser les chaînes codées en dur vers les fichiers de langue (#7)
- [x] **[BAS]** Supprimer le code mort dans `search_vld()` (#8)
- [x] **[BAS]** Extraire le chargement des sélecteurs en méthode privée (#9)
- [x] **[BAS]** Valider le format de `$ddn` (#10)
