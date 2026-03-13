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

### Mise à jour (mars 2026)

L'envoi d'URL de visualisation de documents aux utilisateurs a été supprimé. Cela réduit la surface d'exposition (moins de liens sensibles qui circulent), mais **ne supprime pas** le risque IDOR si des endpoints restent accessibles par URL directe et sans contrôle d'accès robuste.

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

## 4. Position de sécurité après suppression de l'envoi d'URL

### Principe

Depuis la suppression de l'envoi des URL de visualisation, la priorité n'est plus la diffusion contrôlée de liens, mais le **renforcement systématique de l'autorisation côté serveur**.

Pour les endpoints strictement **internes** (procédures, acceptations, documents membres), la correction prioritaire reste d'ajouter les vérifications d'autorisation manquantes.

### Impacts concrets de cette suppression

- Réduction du risque de fuite par transfert d'URL (email, messagerie, copier-coller)
- Diminution du besoin fonctionnel de mécanisme de liens partageables
- Maintien d'un risque technique si un utilisateur peut appeler une URL interne qu'il ne devrait pas atteindre

### Option future (si partage externe réintroduit)

Si un besoin métier de partage externe réapparaît, réintroduire un mécanisme de lien signé à portée limitée (jeton opaque, expiration, révocation, journalisation), sans jamais exposer d'ID séquentiel.

### Ce que la solution NE fait PAS

- Elle ne change pas les URLs des endpoints internes (qui doivent rester protégés par authentification)
- Elle ne remplace pas les vérifications d'autorisation manquantes
- La suppression de l'envoi d'URL ne remplace pas le contrôle d'accès côté serveur

---

## 5. Plan de correction par priorité

### P0 — Corrections immédiates (sans conception)

| Fichier | Méthode | Correction |
|---|---|---|
| `procedures.php` | `download()` | ✅ Implémenté : vérification `is_logged_in()` + `is_role('user')` |
| `acceptance_admin.php` | `download()` | ✅ Implémenté : ajout de `_is_admin()` comme les autres méthodes |
| `procedures.php` | `edit()` | ✅ Implémenté : garde `ca/admin` avant affichage du formulaire |
| `procedures.php` | `view()` | ✅ Implémenté : accès non privilégié limité au statut `published` |

### P1 — Correction systémique

Auditer tous les contrôleurs héritant de `Gvv_Controller` qui ne surchargent pas `edit()` / `delete()` et qui utilisent uniquement `modification_level` comme protection. Ajouter une vérification dans les méthodes de base du contrôleur parent.

✅ **Implémenté** dans `application/libraries/Gvv_Controller.php` :

- ajout d'un helper central `ensure_modification_rights()`
- appel au début de `edit()`
- appel au début de `delete()`
- exemption explicite pour `VISUALISATION` (lecture seule)

### P2 — Durcissement complémentaire

Ajouter des tests de non-régression pour vérifier qu'aucune fonctionnalité n'envoie d'URL de visualisation de documents aux utilisateurs, et que tout accès direct reste soumis aux contrôles d'autorisation.

✅ **Implémenté** dans `application/tests/integration/ArchivedDocumentsEmailTest.php` :

- test de non-régression : absence d'URL `archived_documents/view|preview|download` dans le template de corps d'email
- test de non-régression : présence des gardes d'autorisation sur les endpoints directs (`view`, `download`, `preview`)

---

## 6. Résumé exécutif

| État | Détail |
|---|---|
| ✅ P0 corrigé | `procedures/download`, `acceptance_admin/download`, `procedures/edit`, `procedures/view` |
| ✅ P1 corrigé | contrôle systémique ajouté dans `Gvv_Controller::edit()` et `Gvv_Controller::delete()` |
| ✅ P2 corrigé | tests de non-régression ajoutés pour URL email et contrôles d'accès directs |

Les URLs pointées dans le rapport initial (`archived_documents/preview/56`, `archived_documents/view/16`) sont en réalité **correctement protégées** — elles redirigent vers `my_documents` si l'utilisateur n'est pas propriétaire ou admin. La vulnérabilité réelle se situe principalement dans les contrôleurs `procedures` et `acceptance_admin`.

La suppression de l'envoi des URL de visualisation est une amélioration défensive utile, mais elle ne doit pas être considérée comme une correction IDOR en soi : l'autorisation doit toujours être validée côté serveur pour chaque endpoint.

La priorité immédiate (P0) a été traitée, puis consolidée par une correction systémique (P1) et des tests de non-régression (P2).

---

## 7. Validation des modifications

### Validation technique effectuée

- lint PHP des contrôleurs modifiés (`procedures.php`, `acceptance_admin.php`) : OK
- lint PHP du contrôleur parent (`Gvv_Controller.php`) : OK
- exécution de `phpunit` sur `application/tests/integration/ArchivedDocumentsEmailTest.php` avec `phpunit_integration.xml` : **OK (9 tests, 20 assertions)**

### Risque résiduel

Le risque IDOR identifié dans cette analyse est désormais réduit sur les points P0/P1/P2 traités. Un audit complémentaire reste recommandé lors de l'évolution d'autres contrôleurs hérités si de nouvelles actions d'édition/suppression sont ajoutées hors du flux parent.
