# Plan d'implémentation : Accès aux fichiers journaux

**Fonctionnalité :** Consultation et téléchargement des fichiers de log depuis le dashboard admin  
**Statut :** À implémenter

---

## Périmètre

Ajouter dans le dashboard **Administration système** (`admin/`) une nouvelle carte "Accès aux fichiers journaux". Elle ouvre une page listant les fichiers de `application/logs/` dans un datatable Bootstrap avec pagination et recherche. Chaque ligne propose une icône de téléchargement.

---

## Fichiers concernés

| Fichier | Modification |
|---------|-------------|
| `application/controllers/admin.php` | Ajouter méthodes `logs()` et `download_log($filename)` |
| `application/views/admin/bs_admin.php` | Ajouter la carte dans la section "Administration système" |
| `application/views/admin/bs_logs.php` | Nouvelle vue : datatable des fichiers de log |
| `application/language/french/admin_lang.php` | Nouvelles clés de traduction |
| `application/language/english/admin_lang.php` | Idem |
| `application/language/dutch/admin_lang.php` | Idem |

---

## Étapes d'implémentation

### Étape 1 — Contrôleur : méthode `logs()`

Dans `admin.php`, ajouter une méthode publique `logs()` :

- Vérifie que l'utilisateur est admin (pattern existant : `$this->dx_auth->is_admin()`)
- Lit le répertoire `application/logs/` avec `glob()`
- Pour chaque fichier `.php` trouvé, collecte : nom, taille, date de modification
- Trie par date décroissante (le plus récent en premier)
- Charge la vue `admin/bs_logs` avec la liste

### Étape 2 — Contrôleur : méthode `download_log($filename)`

- Vérifie que l'utilisateur est admin
- Valide que `$filename` ne contient pas de `..` ni `/` (protection path traversal)
- Vérifie que le fichier existe dans `application/logs/`
- Utilise la méthode privée existante `stream_file_download()` pour envoyer le fichier

### Étape 3 — Vue : `bs_logs.php`

Datatable Bootstrap avec les colonnes :

| Colonne | Contenu |
|---------|---------|
| Fichier | Nom du fichier (sans extension `.php`) |
| Date | Date de modification formatée |
| Taille | Taille en Ko |
| Actions | Bouton icône téléchargement (`fas fa-download`) |

- DataTables activé avec pagination (25 lignes par défaut) et boîte de recherche
- Tri initial par date décroissante

### Étape 4 — Dashboard : nouvelle carte

Dans `bs_admin.php`, section "Administration système" (`.section-card.admin`), ajouter une carte à côté de celle des paiements en ligne :

```
icône : fas fa-file-alt  couleur : text-secondary
titre : clé lang gvv_admin_menu_logs
description : clé lang gvv_admin_menu_logs_desc
bouton : clé lang gvv_admin_menu_open → admin/logs
```

### Étape 5 — Traductions

Nouvelles clés dans les trois fichiers de langue :

| Clé | Français | Anglais | Néerlandais |
|-----|----------|---------|-------------|
| `gvv_admin_menu_logs` | Fichiers journaux | Log files | Logbestanden |
| `gvv_admin_menu_logs_desc` | Consulter et télécharger les logs | View and download logs | Logbestanden bekijken |
| `gvv_logs_title` | Fichiers journaux | Log files | Logbestanden |
| `gvv_logs_col_file` | Fichier | File | Bestand |
| `gvv_logs_col_date` | Date | Date | Datum |
| `gvv_logs_col_size` | Taille | Size | Grootte |
| `gvv_logs_col_actions` | Actions | Actions | Acties |
| `gvv_logs_download` | Télécharger | Download | Downloaden |

---

## Sécurité

- Accès strictement limité aux admins (`is_admin()`)
- Validation du nom de fichier dans `download_log()` : refus de tout `..`, `/`, `\`
- Seuls les fichiers présents dans `application/logs/` peuvent être téléchargés (pas de traversal)
- Pas d'affichage du contenu en ligne (téléchargement uniquement)

---

## Tests

### PHPUnit

Ajouter dans la suite d'intégration (`application/tests/integration/`) :

- `test_logs_requires_admin()` : accès refusé pour un utilisateur non-admin
- `test_logs_returns_file_list()` : la méthode retourne bien une liste de fichiers
- `test_download_log_rejects_path_traversal()` : `download_log('../config/database')` → erreur 403/400
- `test_download_log_rejects_unknown_file()` : fichier inexistant → erreur 404

### Playwright (smoke test)

- Connexion admin → dashboard admin → clic sur la carte "Fichiers journaux"
- Vérification que le datatable s'affiche avec au moins un fichier
- Clic sur le bouton télécharger → vérification que le téléchargement se déclenche (response header `Content-Disposition`)

---

## Suivi des tâches

| # | Tâche | Statut |
|---|-------|--------|
| 1 | Méthode `logs()` dans admin.php | ✅ |
| 2 | Méthode `download_log()` dans admin.php | ✅ |
| 3 | Vue `bs_logs.php` | ✅ |
| 4 | Carte dans `bs_admin.php` | ✅ |
| 5 | Traductions (FR/EN/NL) | ✅ |
| 6 | Tests PHPUnit | ⬜ |
| 7 | Smoke test Playwright | ⬜ |
