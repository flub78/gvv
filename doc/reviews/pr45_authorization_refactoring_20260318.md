# Code Review — PR #45 : Uniformise les mécanismes d'autorisation

**Date** : 2026-03-18
**Fichiers** : `application/core/MY_Controller.php` (nouveau), `application/core/Gvv_Controller.php`, `application/libraries/Gvv_Controller.php`, `application/controllers/reservations.php`, `calendar.php`, `presences.php`

## Contexte

La PR crée `MY_Controller` comme ancêtre commun de tous les contrôleurs GVV, afin d'uniformiser la vérification d'autorisation. Avant, `reservations.php`, `calendar.php` et `presences.php` utilisaient une table incorrecte (`authorization_migration_status`) et appelaient `$this->dx_auth->require_roles()` qui n'existe pas sur `DX_Auth` — la vérification était silencieusement ignorée.

---

## Problèmes identifiés

### 🔴 Aucun problème critique

### 🟠 Moyen

#### M1 — `core/Gvv_Controller.php` ne protège pas `MY_Controller` contre une définition manquante

`welcome.php` et `login_as.php` font un `require_once(APPPATH . 'core/Gvv_Controller.php')` explicite. Le nouveau `core/Gvv_Controller.php` fait `class Gvv_Controller extends MY_Controller` sans vérifier que `MY_Controller` est défini.

En fonctionnement normal CI2, `MY_Controller` est chargé automatiquement avant tout contrôleur. Mais un script ou test qui charge directement `core/Gvv_Controller.php` sans passer par CI (comme le fait la librairie) échouerait. La librairie, elle, a ce guard :
```php
if (!class_exists('MY_Controller')) {
    require_once(APPPATH . 'core/MY_Controller.php');
}
```
Le fichier core devrait avoir le même mécanisme de sécurité.

#### M2 — `_is_dual_mode_logging_enabled()` est du code mort

La méthode vérifie `$this->migration_status === 'in_progress' || ... === 'completed'`, mais `_init_auth()` ne positionne jamais ces valeurs : seuls `'per_user_pilot'`, `'global_enabled'`, `'legacy'` et `NULL` sont possibles. La condition ne peut jamais être vraie. Le logging comparatif dual-mode n'est donc jamais exécuté. Problème pré-existant migré tel quel dans `MY_Controller`.

#### M3 — `_check_login_permission()` s'exécute avant `restore_missing_section()` pour les contrôleurs CRUD

Pour les contrôleurs héritant de `libraries/Gvv_Controller`, l'ordre d'exécution est maintenant :
1. `MY_Controller::__construct()` → `_init_auth()` → `_check_login_permission()` (auto-sélection de section depuis la DB)
2. `Gvv_Controller(lib)::__construct()` → `restore_missing_section()` (restauration depuis le cookie)

Pour un utilisateur du nouveau système d'auth sans section en session mais avec un cookie `gvv_remembered_section`, la section auto-sélectionnée par DB peut différer de celle mémorisée dans le cookie. Ce comportement existait déjà dans `core/Gvv_Controller`, mais il s'applique désormais aussi aux contrôleurs CRUD. Faible probabilité d'occurrence dans la pratique.

---

### 🟡 Faible

#### F1 — `$row_count` calculé deux fois inutilement

```php
$row_count = $query ? $query->num_rows() : 0;          // pour le log
log_message('debug', "... {$row_count} rows");
if ($query && $query->num_rows() > 0) {                 // num_rows() rappelé
```
`num_rows()` est appelé deux fois. Stocker dans `$row_count` et réutiliser suffirait.

#### F2 — `_check_access()` et `_check_legacy_access()` sont du code mort

Aucun contrôleur n'appelle `$this->_check_access()`. Ces méthodes, présentes dans l'ancien `core/Gvv_Controller` et migrées dans `MY_Controller`, ne sont jamais utilisées. Pré-existant.

#### F3 — `calendar.php` charge `unit_test` en production

```php
$this->load->library('unit_test');
```
Reste dans le constructeur de production. Pré-existant, non introduit par cette PR.

#### F4 — Override silencieux de `require_roles()` dans la librairie

`libraries/Gvv_Controller::require_roles()` surcharge `MY_Controller::require_roles()` avec une logique différente (`$this->section_id` au lieu de la session). C'est intentionnel et correct, mais non documenté dans le code surchargé.

---

## Ce qui est correct

- ✅ La table `use_new_authorization` est désormais utilisée de manière cohérente partout
- ✅ `_check_login_permission()` s'applique maintenant à `reservations`, `calendar`, `presences`
- ✅ Le guard `class_exists('MY_Controller')` dans la librairie évite le double chargement en test
- ✅ La surcharge `require_roles()` dans la librairie préserve le comportement existant des contrôleurs CRUD
- ✅ 1071 tests passent, aucune régression

---

## Todo (par criticité décroissante)

- [ ] **M1** — Ajouter le guard `class_exists('MY_Controller')` dans `core/Gvv_Controller.php`
- [ ] **M2** — Corriger `_is_dual_mode_logging_enabled()` pour utiliser les valeurs réelles (`'per_user_pilot'`, `'global_enabled'`) ou supprimer le mécanisme de comparaison s'il n'est plus nécessaire
- [ ] **M3** — Évaluer l'impact de l'ordre `_check_login_permission()` / `restore_missing_section()` sur les utilisateurs du nouveau système d'auth ; envisager de passer `section_id` depuis le cookie dans `_check_login_permission()` avant l'auto-sélection DB
- [ ] **F1** — Remplacer le double appel `num_rows()` par la variable `$row_count` dans le `if`
- [ ] **F2** — Supprimer `_check_access()` et `_check_legacy_access()` si définitivement inutilisés
- [ ] **F3** — Retirer `$this->load->library('unit_test')` du constructeur de `calendar.php`
- [ ] **F4** — Documenter l'override de `require_roles()` dans `libraries/Gvv_Controller`
