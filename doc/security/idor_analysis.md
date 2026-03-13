# Analyse des vulnérabilités IDOR dans GVV

> **IDOR** = Insecure Direct Object Reference (OWASP A01:2021 – Broken Access Control)  
> Date d'analyse : mars 2026

---

## 1. Contexte et définition du problème

Une vulnérabilité IDOR survient lorsqu'une URL expose un identifiant numérique séquentiel permettant à un utilisateur malveillant de substituer cet identifiant pour accéder à des données d'un autre utilisateur. Exemples signalés :

```
https://gvv.planeur-abbeville.fr/index.php/archived_documents/preview/56
https://gvv.planeur-abbeville.fr/index.php/archived_documents/view/16
```

En incrémentant ou en devinant l'ID, un attaquant peut potentiellement accéder à des documents personnels (licences, certificats médicaux, attestations), des enregistrements de vols ou d'autres données sensibles d'autres membres.

Il faut également considérer un besoin légitime : pouvoir **partager un document** à une personne externe au club (assureur, fédération) sans lui donner un accès complet à l'application.

---

## 2. Mécanisme d'autorisation existant dans GVV

L'architecture d'autorisation de GVV est **fragmentée** et repose sur plusieurs couches non uniformes :

| Couche | Fichier | Fonctionnement | Limites |
|---|---|---|---|
| `DX_Auth::check_uri_permissions()` | `application/libraries/DX_Auth.php` | Table de permissions par URI | Pas appelée automatiquement — seulement dans ~5 contrôleurs |
| `modification_level` / `view_level` | Propriété de `Gvv_Controller` | Cache les boutons Modifier/Supprimer dans les vues | **Cosmétique uniquement** — ne bloque pas l'accès URL direct |
| `Gvv_Authorization` | `application/core/Gvv_Controller.php` | Système de rôles v2.0 en cours de déploiement | Conditionnel : uniquement si `use_new_auth = TRUE` pour l'utilisateur |
| Vérifications manuelles par méthode | Chaque contrôleur | `_is_admin()`, `dx_auth->is_role()`, comparaison propriétaire | Inconsistant — certaines méthodes l'omettent |

### Conséquence critique

Les méthodes `edit()` et `delete()` du contrôleur de base `Gvv_Controller` **n'effectuent aucune vérification d'autorisation**. Tout contrôleur qui hérite sans surcharger ces méthodes est potentiellement vulnérable.

---

## 3. Inventaire des vulnérabilités IDOR

### 🔴 CRITIQUE — `procedures/download` : aucune authentification

**Fichier** : [application/controllers/procedures.php](../controllers/procedures.php) (ligne 418)

```php
function download($id, $filename) {
    $procedure = $this->procedures_model->get_by_id('id', $id);
    // ... verification d'existence du fichier uniquement
    force_download($filename, file_get_contents($file_path));
    // AUCUNE vérification de connexion, de rôle, ou de propriété
}
```

**Impact** : Toute personne connaissant l'URL peut télécharger n'importe quel fichier attaché à une procédure **sans être connectée**. Les procédures peuvent contenir des documents opérationnels sensibles (procédures d'urgence, plans de site, documents réglementaires).

**Note** : Le constructeur applique `require_roles(['user'])` uniquement si `$this->use_new_auth` est `TRUE`, ce qui est une migration progressive non encore complète.

---

### 🔴 HAUTE — `acceptance_admin/download` : escalade de privilèges

**Fichier** : [application/controllers/acceptance_admin.php](../controllers/acceptance_admin.php) (ligne 421)

```php
function download($id) {
    $item = $this->gvv_model->get_by_id('id', $id);
    // ... verification d'existence uniquement
    force_download($filename, file_get_contents($file_path));
    // Toutes les autres méthodes du contrôleur vérifient _is_admin()
    // CETTE méthode est la seule exception — aucune vérification
}
```

**Impact** : Les documents d'acceptation peuvent contenir des **certificats médicaux, des attestations de formation, des autorisations de vol** — données à caractère personnel et médical. Tout utilisateur authentifié (voire non authentifié si le routage le permet) peut télécharger le fichier PDF de n'importe quel dossier d'acceptation.

**Note** : Il s'agit d'une incohérence claire — toutes les autres méthodes du contrôleur implémentent `_is_admin()`.

---

### 🟠 HAUTE — `procedures/edit` : modification non autorisée

**Fichier** : [application/controllers/procedures.php](../controllers/procedures.php) (ligne 155)

```php
function edit($id = "", $load_view = true, $action = MODIFICATION) {
    $record = $this->procedures_model->get_by_id('id', $id);
    // Aucune vérification de rôle — rendu du formulaire pour tout utilisateur connecté
    return load_last_view($this->form_view, $this->data, $this->unit_test);
}
```

**Impact** : La propriété `modification_level = 'ca'` cache le bouton « Modifier » dans l'interface, mais n'empêche pas d'accéder directement à l'URL `/procedures/edit/42`. Tout membre authentifié peut modifier n'importe quelle procédure.

---

### 🟡 MOYENNE — `procedures/view` : enumération sans barrière

**Fichier** : [application/controllers/procedures.php](../controllers/procedures.php) (ligne 114)

```php
public function view($id) {
    $procedure = $this->procedures_model->get_by_id('id', $id);
    // Aucune vérification de rôle ou statut publié/brouillon
    load_last_view('procedures/view', $data);
}
```

**Impact** : Un utilisateur peut afficher les procédures en statut « brouillon » ou « archivé » en devinant l'ID. Combiné à la vulnérabilité `download`, il peut reconstruire les noms de fichiers et les télécharger.

---

### 🟡 MOYENNE — Héritage sans protection dans `Gvv_Controller`

**Fichier** : `application/libraries/Gvv_Controller.php` (lignes 280–330)

La méthode `edit()` et `delete()` de la classe de base exécutent les opérations sans aucune vérification d'autorisation. Les contrôleurs qui n'ont pas de surcharge explicite et qui ne font que définir `modification_level` comme protection cosmétique sont affectés, notamment :

- `formation_types_seances/edit` et `/delete`
- `meteo/edit` et `/delete`
- `comptes/delete`
- `sections/delete`
- `configuration/delete`
- `calendar/delete`

---

### ✅ Endpoints correctement protégés

Pour référence, les endpoints qui implémentent correctement la vérification d'accès :

| Endpoint | Mécanisme de protection |
|---|---|
| `archived_documents/view`, `/download`, `/preview` | `admin OR owner + vérification accès privé` |
| `archived_documents/delete` | `admin OR owner` |
| `vols_planeur/edit` | `planchiste OR propriétaire du vol` |
| `vols_planeur/delete` | `rôle planchiste requis` |
| `membre/edit` | `rôle CA vérifié dans la méthode` |
| `attachments/edit`, `/delete` | `has_role('tresorier')` |
| `procedures/delete` | `is_role('admin')` |
| `acceptance_admin` (toutes sauf download) | `_is_admin()` (CA+) |

---

## 4. Solution recommandée : URL signées par jeton

### Principe

Pour les endpoints qui doivent être **partageables en dehors du cercle des membres** (archived_documents uniquement pour l'instant), remplacer ou compléter l'ID numérique par un **jeton opaque à usage contrôlé** (capability URL).

Pour les endpoints strictement **internes** (procédures, acceptations), la correction prioritaire est d'ajouter les vérifications d'autorisation manquantes — les URL signées ne sont pas nécessaires.

### Architecture proposée pour le partage de documents

#### Option A — Colonne `share_token` dans la table des documents (recommandée)

```sql
ALTER TABLE archived_documents 
    ADD COLUMN share_token VARCHAR(64) NULL UNIQUE,
    ADD COLUMN share_token_expires_at DATETIME NULL,
    ADD COLUMN share_token_created_by VARCHAR(80) NULL;
```

- Généré à la demande par un admin/bureau (jamais automatiquement)
- Valeur : `hash('sha256', random_bytes(32))` — 64 caractères hexadécimaux
- Durée de validité configurable (30 jours par défaut)
- URL résultante : `/archived_documents/shared/{share_token}`

```
https://gvv.planeur-abbeville.fr/index.php/archived_documents/shared/
    a3f7c2d891e4b506...
```

Cette URL peut être envoyée par email à un tiers (assureur, fédération) sans qu'il ait besoin d'un compte GVV.

#### Option B — Obfuscation d'ID par hash (partielle)

Remplacer `preview/56` par `preview/a3f7c2d8` en calculant `HMAC(id, secret_key)`. Cette approche masque l'ID mais ne résout pas le contrôle d'accès ni ne permet le partage externe — **non recommandée** comme solution principale.

### Ce que la solution NE fait PAS

- Elle ne change pas les URLs des endpoints internes (qui doivent rester protégés par authentification)
- Elle ne remplace pas les vérifications d'autorisation manquantes
- Un jeton de partage ne donne accès qu'au fichier spécifique, pas à l'ensemble du compte

---

## 5. Plan de correction par priorité

### P0 — Corrections immédiates (sans conception)

| Fichier | Méthode | Correction |
|---|---|---|
| `procedures.php` | `download()` | Ajouter vérification `is_logged_in()` + `is_role('user')` |
| `acceptance_admin.php` | `download()` | Ajouter `_is_admin()` comme toutes les autres méthodes |
| `procedures.php` | `edit()` | Ajouter `if (!$this->dx_auth->is_role('ca') && !$this->dx_auth->is_admin()) show_404()` |
| `procedures.php` | `view()` | Vérifier le statut `published` pour les non-admins |

### P1 — Correction systémique

Auditer tous les contrôleurs héritant de `Gvv_Controller` qui ne surchargent pas `edit()` / `delete()` et qui utilisent uniquement `modification_level` comme protection. Ajouter une vérification dans les méthodes de base du contrôleur parent.

### P2 — Partage de documents (nouvelle fonctionnalité)

Implémenter le mécanisme de jeton de partage pour `archived_documents` (Option A ci-dessus) afin de permettre le partage avec des tiers sans compte.

---

## 6. Résumé exécutif

| Gravité | Nombre | Exemples |
|---|---|---|
| 🔴 Critique | 1 | `procedures/download` — accès non authentifié |
| 🔴 Haute | 2 | `acceptance_admin/download`, `procedures/edit` |
| 🟡 Moyenne | 3+ | `procedures/view`, héritage `Gvv_Controller` |
| ✅ Protégé | 8+ | `archived_documents/*`, `vols_planeur/*` |

Les URLs pointées dans le rapport initial (`archived_documents/preview/56`, `archived_documents/view/16`) sont en réalité **correctement protégées** — elles redirigent vers `my_documents` si l'utilisateur n'est pas propriétaire ou admin. La vulnérabilité réelle se situe principalement dans les contrôleurs `procedures` et `acceptance_admin`.

La priorité immédiate est de corriger les 4 méthodes identifiées en **P0**, qui représentent des risques d'exposition de données personnelles (médicales, de formation) sans authentification ni contrôle d'accès.
