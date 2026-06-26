# Plan de migration : Actif par section

## Objectif

Remplacer le booléen `membres.actif` (filtre global) par le rôle "Utilisateur" de `user_roles_per_section` (filtre par section) pour masquer les membres inactifs dans les sélecteurs.

Un membre est considéré **actif** s'il possède le rôle "Utilisateur" dans la section courante. En mode "Toutes sections" (aucune section active en session), un membre est actif s'il possède le rôle "Utilisateur" dans **au moins une section**.

## Contexte technique

### Jointure membres → rôles

```
membres.mlogin = users.username → users.id = user_roles_per_section.user_id
user_roles_per_section.types_roles_id = types_roles.id WHERE types_roles.nom = 'Utilisateur'
```

### Périmètre des usages de `actif` sur la table `membres`

| Lieu | Nature | Priorité |
|------|--------|----------|
| `membres_model.php` — méthodes spécialisées (~7) | `WHERE membres.actif = 1` + JOIN users déjà présent | 1 — Faible risque |
| Controllers via `selector(array('actif'=>1))` (23) | Filtre passé à `common_model::selector()` | 2 — Zéro si override |
| `cartes_membre_model.php` (3) | Joint déjà `user_roles_per_section` | 3 — Faible |
| `licences_model.php` (2) | Contexte liste licences, pas de section | 4 — Moyen |
| `email_lists_model.php` (2) | Envoi emails membres actifs | 4 — Moyen |
| `comptes_model.php` (1) | Contexte comptable | 4 — Moyen |
| `admin.php` controller (3) | Requêtes SQL brutes, vue admin globale | 5 — Élevé |

---

## Étapes du plan

### [x] Étape 1 — Renommage UI "Utilisateur" → "Actif"

Modification uniquement dans la couche d'affichage, **sans changer les noms en base de données**.

- [x] Localiser dans les vues et les fichiers de langue l'affichage du type de rôle "Utilisateur"
- [x] Remplacer l'affichage par "Actif" dans les fichiers de langue (français, anglais, néerlandais)
- [x] Vérifier que la valeur `types_roles.nom = 'Utilisateur'` reste inchangée en base

### [x] Étape 2 — Méthodes spécialisées de `membres_model`

Ces méthodes reçoivent déjà un `$section_id` et font déjà le JOIN `membres → users`. C'est le point le plus facile et le moins risqué.

Méthodes à modifier :
- [x] `section_pilots($section_id, $only_actif)`
- [x] `inst_selector($section_id, $only_actif)`
- [x] `treuillard_selector($section_id, $only_actif)`
- [x] `inst_selector_all($only_actif)`
- [x] `pilrem_selector($section_id, $only_actif)`
- [x] `get_selector($section_id, $only_actif)`
- [x] `select_licences()`

Remplacer `$this->db->where('membres.actif', 1)` par la jointure :
```php
$this->db->join('user_roles_per_section urps', 'urps.user_id = users.id', 'inner');
$this->db->join('types_roles tr', 'tr.id = urps.types_roles_id', 'inner');
$this->db->where('tr.nom', 'Utilisateur');
if ($section_id) {
    $this->db->where('urps.section_id', $section_id);
}
// mode Toutes : pas de filtre section → retourne actif dans au moins une section
// (le DISTINCT sur mlogin est déjà assuré par la structure des sélecteurs)
```

### [x] Étape 3 — Override de `selector()` dans `membres_model`

Surcharger `selector()` hérité de `common_model` pour intercepter `array('actif' => 1)` et le traduire en filtre `user_roles_per_section`. Les 23 controllers restent inchangés.

```php
public function selector($where = array(), $order = "asc", $filter_section = FALSE) {
    if (array_key_exists('actif', $where)) {
        unset($where['actif']);
        // Injecter la logique roles ici avec la section de session
    }
    return parent::selector($where, $order, $filter_section);
}
```

La section courante est récupérée via `$this->session` (pattern déjà utilisé dans le modèle).

### [x] Étape 4 — `cartes_membre_model`

Trois méthodes filtrent encore sur `actif = 1` sans jointure sur les rôles :

- `get_membres_actifs_annee($year)` (ligne 72)
- `get_membres_actifs_deux_annees($year)` (ligne 91)
- `get_all_membres_actifs()` (ligne 182)

Aucune de ces méthodes n'a de contexte section : appliquer la règle "actif dans au moins une section" (JOIN sans filtre `section_id`).

Remplacer `WHERE m.actif = 1` / `WHERE actif = 1` par :
```php
->join('users u', 'u.username = m.mlogin', 'inner')
->join('user_roles_per_section urps', 'urps.user_id = u.id', 'inner')
->join('types_roles tr', 'tr.id = urps.types_roles_id', 'inner')
->where('tr.nom', 'Utilisateur')
->group_by('m.mlogin')   // évite les doublons si plusieurs sections actives
```

Note : `get_membres_actifs_annee` et `get_membres_actifs_deux_annees` joignent déjà `licences` via `m.mlogin` — utiliser l'alias `m` cohérent avec les requêtes existantes. `get_all_membres_actifs` n'a pas d'alias — en ajouter un (`membres m`).

Point de vigilance : la sous-requête `activites` dans `get_membre()` (ligne 30) utilise `urps.types_roles_id = 1` en dur. À corriger en même temps pour utiliser `tr.nom = 'Utilisateur'` via une sous-requête imbriquée, pour cohérence et robustesse.

### [ ] Étape 5 — Autres modèles (licences, email_lists, comptes)

Traitement cas par cas. Règle commune pour le mode sans section : actif dans au moins une section.

- [ ] `licences_model.php` : contexte liste des licences de l'année — appliquer la règle "au moins une section"
- [x] `email_lists_model.php` : `get_users_by_role_and_section()` — filtre `active` remplacé par un second JOIN sur `user_roles_per_section` vérifiant le rôle 'Utilisateur' dans **la même section que le critère** ; si `section_id` est NULL, actif dans au moins une section. Filtre `inactive` remplacé par NOT IN subquery sur le même périmètre.
- [ ] `comptes_model.php` : contexte comptable — même règle

### [ ] Étape 6 — `admin.php` controller

Requêtes SQL brutes avec vue admin globale (toutes sections). À traiter en dernier avec tests explicites. Le comportement admin bypass les sections par définition.

---

## Stratégie de tests

- [ ] Tests unitaires sur les méthodes spécialisées modifiées (étape 2) : vérifier que les sélecteurs retournent les bons membres selon la section
- [ ] Test d'intégration sur `selector()` surchargé (étape 3) : vérifier que les controllers ne voient aucune régression
- [ ] Smoke test Playwright : accéder à une page avec sélecteur de pilote en mode section et en mode toutes sections

---

## Non-objectifs

- Ne pas modifier `types_roles.nom = 'Utilisateur'` en base de données
- Ne pas supprimer la colonne `membres.actif` (dépréciation future séparée)
- Ne pas refactorer `users` / `membres` au-delà du périmètre actif/section

---

## Gel du champ `actif` dans l'interface

La colonne `membres.actif` reste en base mais n'est plus modifiable par l'utilisateur.

- **Liste des membres** : la colonne `actif` de l'affichage est rendue en lecture seule (non cliquable), elle affiche le résultat de is_actif() (rôle "Utilisateur" dans la section courante ou au moins une section)
- **Formulaire d'édition d'un membre** : retirer le champ `actif` du formulaire (ne plus l'afficher ni le soumettre)
- La valeur existante en base est conservée telle quelle — elle n'est plus une source de vérité pour l'activité mais reste présente pour compatibilité descendante
