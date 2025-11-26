# PRD - Sous-listes d'emails

**Projet:** GVV - Gestion Vol à voile
**Fonctionnalité:** Support des sous-listes dans les listes d'emails
**Version:** 1.0
**Date:** 2025-11-25
**Statut:** Proposition
**Auteur:** Fred + Claude Code

---

## 1. Vue d'ensemble

### 1.1 Objectif

Permettre l'inclusion de **sous-listes** dans une liste d'emails, avec dédoublonnage automatique des destinataires. Les sous-listes deviennent une 4ème source d'adresses, au même titre que les critères, la sélection manuelle et les adresses externes.

### 1.2 Problème résolu

**Situation actuelle :**
- Pour envoyer un email aux "Membres du bureau + Instructeurs + Trésoriers", l'utilisateur doit :
  1. Exporter chaque liste séparément
  2. Fusionner manuellement les adresses
  3. Supprimer les doublons manuellement
  4. Répéter cette opération à chaque envoi

**Avec cette fonctionnalité :**
- L'utilisateur crée une liste "Bureau étendu" et ajoute 3 sous-listes dans l'onglet "Sous-listes"
- Le système gère automatiquement l'agrégation et le dédoublonnage
- La liste est réutilisable et se met à jour automatiquement

### 1.3 Bénéfices

- ✅ **Gain de temps** : Plus besoin de fusionner manuellement les listes
- ✅ **Cohérence** : Dédoublonnage automatique garanti
- ✅ **Réutilisabilité** : Les listes peuvent être sauvegardées et réutilisées
- ✅ **Maintenance** : Modification d'une sous-liste = mise à jour automatique de la liste parente
- ✅ **Simplicité** : Intégration naturelle avec les 3 sources existantes

---

## 2. Concepts clés

### 2.1 Les 4 sources d'adresses

Une liste d'emails peut maintenant combiner **4 sources** d'adresses :

1. **Par critères** (rôles × sections) - *existant*
2. **Sélection manuelle** de membres - *existant*
3. **Adresses externes** - *existant*
4. **Sous-listes** - *nouveau*

Toutes ces sources sont combinées et dédoublonnées automatiquement.

### 2.2 Contrainte unique

**Une sous-liste ne peut pas elle-même contenir de sous-listes.**

Cette règle simple garantit :
- ✅ **Profondeur fixe** : 1 niveau uniquement (pas de récursion)
- ✅ **Pas de cycles** : Impossible d'avoir A → B → A
- ✅ **Performance** : Résolution linéaire sans récursion
- ✅ **Simplicité conceptuelle** : Facile à comprendre et à implémenter

### 2.3 Détection automatique

Le système détecte automatiquement si une liste contient des sous-listes :
- Si **oui** : elle ne peut pas être incluse comme sous-liste dans une autre liste
- Si **non** : elle peut être utilisée comme sous-liste

Pas de conversion ni de type spécial, juste une vérification lors de la sélection.

---

## 3. Spécifications fonctionnelles

### 3.1 Interface utilisateur

#### 3.1.1 Nouvel onglet "Sous-listes"

Dans l'interface de modification d'une liste, un **4ème onglet** est ajouté :
Les listes qui contiennent des sous-listes ne sont pas proposées.

```
┌─────────────────────────────────────────────────────────┐
│ Onglets:                                                │
│ ◉ Par critères (3)  ○ Manuel (2)  ○ Externes (5)       │
│ ○ Sous-listes (2)                                       │
├─────────────────────────────────────────────────────────┤
│ Sélectionner des listes à inclure:                      │
│                                                         │
│ ☑ Membres CA (87 destinataires)                        │
│ ☑ Instructeurs actifs (12 destinataires)               │
│ ☐ Trésoriers (5 destinataires)                         │
│ ☐ Liste externe pilotes (23 destinataires)             │
│                                                         │
└─────────────────────────────────────────────────────────┘
```

**Comportement :**
- Liste toutes les listes visibles par l'utilisateur (saufs celles qui contiennent des sous-listes)
- Badge indiquant le nombre de destinataires

#### 3.1.2 Indicateur dans la liste principale

Dans la vue `index`, afficher l'icône 📂 pour les listes contenant des sous-listes :

```
┌─────────────────────────────────────────────────────────┐
│ Mes listes d'emails                          [+ Nouveau]│
├─────────────────────────────────────────────────────────┤
│ Nom                            Sources      Destinataires│
├─────────────────────────────────────────────────────────┤
│ 📋 Instructeurs actifs         C, M               12    │
│ 📋 Membres CA                  C                  87    │
│ 📂 Bureau étendu               S (3 listes)      142    │
│ 📋 Trésoriers                  C                   5    │
│ 📋 Liste pilotes externes      E                  23    │
└─────────────────────────────────────────────────────────┘
```

**Légende sources :**
- C = Critères, M = Manuel, E = Externes, S = Sous-listes

#### 3.1.3 Vue de prévisualisation

La vue de prévisualisation (`view.php`) affiche toutes les sources :

```
┌─────────────────────────────────────────────────────────┐
│ Liste: Bureau étendu                                    │
├─────────────────────────────────────────────────────────┤
│ Sources configurées:                                    │
│  • Critères: 2 rôles × 1 section = 15 dest.            │
│  • Sélection manuelle: 8 membres                        │
│  • Adresses externes: 3 adresses                        │
│  • Sous-listes: 3 listes                                │
│    - Membres CA (87 dest.)                              │
│    - Instructeurs actifs (12 dest.)                     │
│    - Trésoriers (5 dest.)                               │
│                                                         │
│ Total brut: 130 adresses                                │
│ Après dédoublonnage: 118 adresses uniques              │
│                                                         │
├─────────────────────────────────────────────────────────┤
│ Destinataires:                                          │
│  jean.dupont@example.com (Jean DUPONT)                  │
│  marie.martin@example.com (Marie MARTIN)                │
│  pierre.durant@example.com (Pierre DURANT)              │
│  ...                                                    │
│                                                         │
│ [Copier dans le presse-papier]                          │
│ [Télécharger TXT]                                       │
│ [Télécharger Markdown]                                  │
│ [Ouvrir client email]                                   │
└─────────────────────────────────────────────────────────┘
```

### 3.2 Gestion de la suppression

#### 3.2.1 Suppression d'une liste utilisée comme sous-liste

**Scénario :** L'utilisateur tente de supprimer "Instructeurs actifs" qui est sous-liste de "Bureau étendu" et "Tous les responsables".

**Comportement :**
```
┌─────────────────────────────────────────────────────────┐
│ ⚠️ Suppression impossible                               │
│                                                         │
│ La liste "Instructeurs actifs" est utilisée            │
│ comme sous-liste dans :                                 │
│                                                         │
│  • Bureau étendu (142 dest.)                           │
│  • Tous les responsables (87 dest.)                    │
│                                                         │
│ Options:                                                │
│  1. Retirez-la d'abord de ces listes                   │
│  2. Supprimez ces listes parentes                      │
│                                                         │
│ [Annuler]  [Retirer et supprimer]                      │
└─────────────────────────────────────────────────────────┘
```

**Bouton "Retirer et supprimer" :**
- Retire automatiquement la liste de toutes les listes parentes
- Puis la supprime
- Affiche un récapitulatif des listes modifiées

**Implémentation :** FK `ON DELETE RESTRICT`

#### 3.2.2 Suppression d'une liste contenant des sous-listes

**Scénario :** L'utilisateur supprime "Bureau étendu" qui contient 3 sous-listes.

**Comportement :** Confirmation standard de suppression

**Résultat :**
- La liste "Bureau étendu" est supprimée
- Les 3 sous-listes restent intactes
- Les lignes correspondantes dans `email_list_sublists` sont supprimées automatiquement

**Implémentation :** FK `ON DELETE CASCADE` sur `parent_list_id`

### 3.3 Résolution et dédoublonnage

#### 3.3.1 Algorithme de résolution

```
Liste "Bureau étendu"
├─ Source 1 (Critères): Président × Planeur
│  └─ jean.dupont@example.com
│
├─ Source 2 (Manuel): 2 membres sélectionnés
│  ├─ marie.martin@example.com
│  └─ paul.bernard@example.com
│
├─ Source 3 (Externes): 1 adresse
│  └─ externe@example.com
│
└─ Source 4 (Sous-listes):
   ├─ Sous-liste: Instructeurs actifs
   │  ├─ jean.dupont@example.com  ← DOUBLON (déjà dans Critères)
   │  ├─ sophie.legrand@example.com
   │  └─ luc.petit@example.com
   │
   └─ Sous-liste: Trésoriers
      └─ marie.martin@example.com  ← DOUBLON (déjà dans Manuel)

RÉSULTAT (dédoublonné):
├─ jean.dupont@example.com
├─ marie.martin@example.com
├─ paul.bernard@example.com
├─ externe@example.com
├─ sophie.legrand@example.com
└─ luc.petit@example.com

Total: 6 adresses uniques (au lieu de 9 brutes)
```

**Règles :**
1. Résoudre chaque source individuellement
2. Fusionner toutes les adresses (Critères + Manuel + Externes + Sous-listes)
3. Dédoublonnage case-insensitive sur l'email
4. Utiliser la fonction existante `deduplicate_emails()`

#### 3.3.2 Performance

- **Profondeur fixe** : 1 niveau uniquement (pas de récursion)
- **Complexité** : O(n) où n = nombre total d'adresses (somme des sous-listes)
- **Dédoublonnage** : Utilise `deduplicate_emails()` existant
- **Cache possible** : Optionnel pour les grosses listes (implémentation future)

---

## 4. Spécifications techniques

### 4.1 Schéma de base de données

#### Nouvelle table : `email_list_sublists`

```sql
CREATE TABLE `email_list_sublists` (
  `id` INT UNSIGNED NOT NULL AUTO_INCREMENT,
  `parent_list_id` INT UNSIGNED NOT NULL COMMENT 'La liste parente',
  `child_list_id` INT UNSIGNED NOT NULL COMMENT 'La liste simple incluse',
  `added_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,

  PRIMARY KEY (`id`),

  -- FK parent : CASCADE (supprimer liste parente = supprimer relations)
  FOREIGN KEY (`parent_list_id`)
    REFERENCES `email_lists`(`id`)
    ON DELETE CASCADE,

  -- FK child : RESTRICT (empêcher suppression si utilisée)
  FOREIGN KEY (`child_list_id`)
    REFERENCES `email_lists`(`id`)
    ON DELETE RESTRICT,

  -- Éviter doublons
  UNIQUE KEY `unique_parent_child` (`parent_list_id`, `child_list_id`),

  -- Index pour performance
  KEY `idx_parent` (`parent_list_id`),
  KEY `idx_child` (`child_list_id`)

) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Points clés :**
- **ON DELETE CASCADE** sur `parent_list_id` : Supprimer une liste parente supprime ses références
- **ON DELETE RESTRICT** sur `child_list_id` : Empêcher de supprimer une liste utilisée
- **UNIQUE** sur la paire (parent, child) : Éviter les doublons

#### Migration

**Fichier :** `application/migrations/053_create_email_list_sublists.php`

**Version :** Incrémenter `application/config/migration.php` à `53`

### 4.2 Modifications du modèle

#### Nouvelles méthodes dans `Email_lists_model`

```php
/**
 * Ajouter une sous-liste à une liste
 *
 * @param int $parent_list_id ID de la liste parente
 * @param int $child_list_id ID de la liste simple à inclure
 * @return array ['success' => bool, 'error' => string|null]
 */
public function add_sublist($parent_list_id, $child_list_id)

/**
 * Retirer une sous-liste d'une liste
 *
 * @param int $parent_list_id ID de la liste parente
 * @param int $child_list_id ID de la liste simple à retirer
 * @return bool TRUE si succès
 */
public function remove_sublist($parent_list_id, $child_list_id)

/**
 * Obtenir toutes les sous-listes d'une liste
 *
 * @param int $parent_list_id ID de la liste parente
 * @return array Tableau de listes avec métadonnées
 */
public function get_sublists($parent_list_id)

/**
 * Vérifier si une liste contient des sous-listes
 *
 * @param int $list_id ID de la liste
 * @return bool TRUE si la liste contient des sous-listes
 */
public function has_sublists($list_id)

/**
 * Obtenir les listes parentes qui contiennent une liste donnée comme sous-liste
 *
 * @param int $child_list_id ID de la liste
 * @return array Tableau des listes parentes
 */
public function get_parent_lists($child_list_id)

/**
 * Obtenir toutes les listes qui peuvent être utilisées comme sous-listes
 * (c'est-à-dire qui ne contiennent pas elles-mêmes de sous-listes)
 *
 * @param int $user_id ID de l'utilisateur
 * @param bool $is_admin Si l'utilisateur est admin
 * @param int $exclude_list_id Exclure cette liste (pour éviter l'auto-référence)
 * @return array Tableau de listes
 */
public function get_available_sublists($user_id, $is_admin = false, $exclude_list_id = null)
```

#### Modification de `textual_list()`

```php
/**
 * Résoudre les adresses d'une liste (avec toutes ses sources)
 *
 * @param int $list_id ID de la liste
 * @return array Tableau d'adresses email (strings) dédoublonnées
 */
public function textual_list($list_id) {
    $list = $this->get_list($list_id);
    if (!$list) {
        return array();
    }

    $emails = array();

    // Source 1: Résoudre via critères (rôles × sections)
    // ... code existant ...
    
    // Source 2: Résoudre via sélection manuelle
    // ... code existant ...
    
    // Source 3: Résoudre via adresses externes
    // ... code existant ...
    
    // Source 4: Résoudre via sous-listes (NOUVEAU)
    $sublists = $this->get_sublists($list_id);
    foreach ($sublists as $sublist) {
        $sublist_emails = $this->textual_list($sublist['child_list_id']);
        $emails = array_merge($emails, $sublist_emails);
    }

    // Dédoublonnage final de toutes les sources
    $this->load->helper('email');
    $emails = deduplicate_emails($emails);

    return $emails;
}
```

#### Modification de `detailed_list()`

Similaire à `textual_list()` mais retourne les métadonnées (nom, source, etc.)

### 4.3 Modifications du contrôleur

#### Nouvelles actions dans `Email_lists`

```php
/**
 * API AJAX: Ajouter une sous-liste
 * POST /email_lists/add_sublist_ajax
 * Params: parent_list_id, child_list_id
 */
public function add_sublist_ajax()

/**
 * API AJAX: Retirer une sous-liste
 * POST /email_lists/remove_sublist_ajax
 * Params: parent_list_id, child_list_id
 */
public function remove_sublist_ajax()

/**
 * API AJAX: Obtenir les listes disponibles comme sous-listes
 * GET /email_lists/get_available_sublists_ajax
 */
public function get_available_sublists_ajax()
```

### 4.4 Nouvelles vues

```
application/views/email_lists/
├─ _sublists_tab.php          # Onglet "Sous-listes" (NOUVEAU)
└─ ... (vues existantes)
```

### 4.5 JavaScript

#### Nouvelles fonctions dans `assets/js/email_lists.js`

```javascript
/**
 * Ajouter une sous-liste à la liste courante
 */
function addSublist(childListId)

/**
 * Retirer une sous-liste de la liste courante
 */
function removeSublist(childListId)

/**
 * Charger la liste des listes disponibles comme sous-listes
 */
function loadAvailableSublists()

/**
 * Mettre à jour l'affichage après ajout/retrait de sous-liste
 */
function updateSublistsDisplay()
```

---

## 5. Validation et règles métier

### 5.1 Règles de validation

#### Lors de l'ajout d'une sous-liste

1. ✅ **Existence** : `parent_list_id` et `child_list_id` doivent exister
2. ✅ **Auto-référence** : `parent_list_id` ≠ `child_list_id`
3. ✅ **Profondeur** : `child_list_id` ne doit **pas** contenir de sous-listes
4. ✅ **Doublon** : La paire (parent, child) doit être unique
5. ✅ **Cohérence de visibilité** :
   - Une liste **privée** peut contenir des sous-listes **privées** et publiques
   - Une liste **publique** ne peut contenir que des sous-listes **publiques**

**Messages d'erreur :**
- "Liste parente introuvable"
- "Liste enfant introuvable"
- "Une liste ne peut pas se contenir elle-même"
- "Cette liste contient déjà des sous-listes et ne peut pas être incluse"
- "Cette sous-liste est déjà incluse"
- "Impossible d'ajouter une sous-liste privée à une liste publique"

#### Lors du changement de visibilité d'une liste

**Règle :** Une liste ne peut être rendue publique si elle contient des sous-listes privées.

**Scénario :** L'utilisateur tente de rendre publique une liste "Bureau étendu" qui contient 2 sous-listes privées.

**Comportement :**
```
┌─────────────────────────────────────────────────────────┐
│ ⚠️ Changement de visibilité impossible                 │
│                                                         │
│ La liste "Bureau étendu" contient des sous-listes      │
│ privées :                                               │
│                                                         │
│  • Instructeurs actifs (privée)                        │
│  • Trésoriers (privée)                                 │
│                                                         │
│ Une liste publique ne peut contenir que des            │
│ sous-listes publiques.                                  │
│                                                         │
│ Options:                                                │
│  1. Garder la liste privée                             │
│  2. Rendre publiques la liste et toutes ses            │
│     sous-listes privées                                 │
│                                                         │
│ [Annuler]  [Rendre tout public]                        │
└─────────────────────────────────────────────────────────┘
```

**Bouton "Rendre tout public" :**
- Rend publique la liste parente
- Rend publiques toutes les sous-listes privées
- Affiche un récapitulatif des listes modifiées

#### Lors de la suppression d'une liste

1. ✅ **Vérifier dépendances** : Si la liste est sous-liste d'autres listes
2. ✅ **Bloquer ou proposer retrait** : ON DELETE RESTRICT + message explicatif

### 5.2 Comptage des destinataires

```php
// Pour n'importe quelle liste (avec ou sans sous-listes)
$count = count($this->email_lists_model->textual_list($list_id));

// Afficher également le détail si la liste a des sous-listes
if ($this->email_lists_model->has_sublists($list_id)) {
    $sublists = $this->email_lists_model->get_sublists($list_id);
    $total_raw = 0;
    foreach ($sublists as $sublist) {
        $total_raw += count($this->email_lists_model->textual_list($sublist['child_list_id']));
    }
    
    // Ajouter aussi les autres sources
    // ... critères, manuel, externes ...
    
    echo "Total brut: {$total_raw} | Après dédoublonnage: {$count}";
}
```

---

## 6. Scénarios d'usage

### 6.1 Scénario 1 : Créer une liste avec sous-listes "Bureau étendu"

**Étapes :**
1. Utilisateur clique sur "Nouvelle liste"
2. Saisit le nom "Bureau étendu"
3. Enregistre
4. Clique sur l'onglet "Sous-listes"
5. Coche "Membres CA", "Instructeurs actifs" et "Trésoriers"
6. Preview affiche : "142 destinataires (dont 6 doublons éliminés)"

**Résultat :**
- Liste "Bureau étendu" créée avec 3 sous-listes
- 142 adresses uniques disponibles pour export

### 6.2 Scénario 2 : Combiner sources

**Étapes :**
1. Créer une liste "Communication large"
2. Onglet Critères : Ajouter "Président" × "Toutes sections"
3. Onglet Manuel : Ajouter 5 membres spécifiques
4. Onglet Externes : Ajouter 2 adresses
5. Onglet Sous-listes : Ajouter "Instructeurs actifs"

**Résultat :**
- 4 sources actives dans la même liste
- Dédoublonnage automatique entre toutes les sources

### 6.3 Scénario 3 : Supprimer une liste utilisée

**Étapes :**
1. Utilisateur tente de supprimer "Instructeurs actifs"
2. ⚠️ Popup : "Cette liste est utilisée dans 2 listes : Bureau étendu, Communication large"
3. Options :
   - **Annuler** : Annule la suppression
   - **Retirer et supprimer** : Retire "Instructeurs" des 2 listes, puis la supprime
4. Si choix "Retirer et supprimer" :
   - Retrait automatique de "Instructeurs" des 2 listes
   - Suppression de "Instructeurs actifs"
   - Message : "Liste supprimée. 2 listes ont été modifiées."

**Résultat :**
- Suppression sécurisée avec traçabilité
- Les listes parentes restent cohérentes

### 6.4 Scénario 4 : Rendre publique une liste avec sous-listes privées

**Étapes :**
1. Utilisateur édite la liste "Bureau étendu" (actuellement privée)
2. Tente de cocher "Liste visible par tous" pour la rendre publique
3. La liste contient 2 sous-listes privées : "Instructeurs actifs", "Trésoriers"
4. ⚠️ Popup : "La liste contient des sous-listes privées. Une liste publique ne peut contenir que des sous-listes publiques."
5. Options :
   - **Annuler** : Garde la liste privée
   - **Rendre tout public** : Rend publiques la liste et ses 2 sous-listes privées
6. Si choix "Rendre tout public" :
   - "Bureau étendu" devient publique
   - "Instructeurs actifs" devient publique
   - "Trésoriers" devient publique
   - Message : "Liste rendue publique. 2 sous-listes ont également été rendues publiques."

**Résultat :**
- Cohérence de visibilité garantie
- Transparence sur les modifications effectuées

---

## 7. Limitations et contraintes

### 7.1 Limitations techniques

1. **Profondeur 1 uniquement** : Les sous-listes ne peuvent pas contenir de sous-listes

### 7.2 Limitations fonctionnelles

1. **Cohérence de visibilité** :
   - Une liste **privée** peut contenir des sous-listes **privées** et publiques
   - Une liste **publique** ne peut contenir que des sous-listes **publiques**
   - Rendre une liste publique alors qu'elle contient des sous-listes privées nécessite de rendre publiques toutes les sous-listes
2. **Permissions** : L'utilisateur ne peut inclure que les listes qu'il peut voir (selon `get_user_lists()`)
3. **Suppression protégée** : ON DELETE RESTRICT empêche la suppression silencieuse

### 7.3 Cas non supportés

❌ **Sous-liste contenant des sous-listes**
```
Liste A
├─ Sous-liste B  ← OK
│  ├─ Source: Critères
│  └─ Source: Manuel
└─ Sous-liste C  ← OK si C ne contient pas de sous-listes
   ├─ Sous-liste D  ← IMPOSSIBLE
   └─ Source: Externes
```
---

## 8. Tests d'acceptation

### 8.1 Tests fonctionnels

| Test | Description | Résultat attendu |
|------|-------------|------------------|
| **TF-1** | Créer une liste avec 3 sous-listes | Liste créée, 3 sous-listes ajoutées, comptage correct |
| **TF-2** | Combiner 4 sources (Critères + Manuel + Externes + Sous-listes) | Toutes sources actives, dédoublonnage correct |
| **TF-3** | Supprimer une liste utilisée comme sous-liste | Popup d'avertissement, suppression bloquée |
| **TF-4** | Supprimer une liste contenant des sous-listes | Liste supprimée, sous-listes conservées |
| **TF-5** | Exporter une liste avec sous-listes en TXT | Toutes les adresses dédoublonnées exportées |
| **TF-6** | Prévisualiser une liste avec sous-listes | Détail des 4 sources, comptage avant/après dédoublonnage |

### 8.2 Tests de validation

| Test | Description | Résultat attendu |
|------|-------------|------------------|
| **TV-1** | Ajouter une liste contenant des sous-listes comme sous-liste | Refusé avec message clair |
| **TV-2** | Ajouter une liste comme sous-liste d'elle-même | Refusé avec message "auto-référence" |
| **TV-3** | Ajouter deux fois la même sous-liste | Refusé avec message "déjà incluse" |
| **TV-4** | Supprimer une liste avec FK RESTRICT | Popup d'avertissement, liste des listes utilisatrices |
| **TV-5** | Ajouter une sous-liste privée à une liste publique | Refusé avec message "Impossible d'ajouter une sous-liste privée à une liste publique" |
| **TV-6** | Ajouter une sous-liste publique à une liste privée | Accepté |
| **TV-7** | Rendre publique une liste contenant des sous-listes privées | Popup d'avertissement avec option "Rendre tout public" |
| **TV-8** | Utiliser "Rendre tout public" | Liste parente et toutes sous-listes privées deviennent publiques |


---

## 9. Migration et déploiement

### 9.1 Plan de migration

1. **Phase 1 : Migration DB**
   - Créer la table `email_list_sublists`
   - Ajouter les FK avec ON DELETE CASCADE/RESTRICT
   - Ajouter les index

2. **Phase 2 : Modèle**
   - Ajouter les nouvelles méthodes au modèle
   - Modifier `textual_list()` et `detailed_list()`

3. **Phase 3 : Contrôleur**
   - Ajouter les actions AJAX pour sublists
   - Modifier `edit()` pour gérer les sous-listes

4. **Phase 4 : Vues**
   - Créer `_sublists_tab.php`
   - Modifier `edit.php` pour gérer les sous-listes
   - Modifier `index.php` pour afficher icône 📂

5. **Phase 5 : JavaScript**
   - Ajouter fonctions de gestion des sous-listes
   - Mise à jour dynamique de l'interface

6. **Phase 6 : Tests**
   - Tests unitaires (PHPUnit)
   - Tests d'intégration (MySQL)
   - Tests end-to-end (Playwright)

### 9.2 Compatibilité ascendante

✅ **100% compatible** : Les listes existantes fonctionnent exactement comme avant. Aucune donnée n'est modifiée. Les sous-listes sont simplement une nouvelle source optionnelle.

### 9.3 Rollback

En cas de problème, le rollback est simple :
```php
// Migration down
$this->dbforge->drop_table('email_list_sublists', TRUE);
```

Toutes les listes existantes restent intactes.

---

## 10. Documentation utilisateur

### 10.1 Message d'aide dans l'interface

```
┌─────────────────────────────────────────────────────────┐
│ ℹ️ Aide : Sous-listes                                   │
├─────────────────────────────────────────────────────────┤
│ Les sous-listes vous permettent d'inclure d'autres     │
│ listes comme source d'adresses, avec dédoublonnage     │
│ automatique.                                            │
│                                                         │
│ Exemples d'usage :                                      │
│  • Combiner "Bureau" + "Instructeurs" + "Trésoriers"   │
│  • Créer "Tous les responsables" à partir de plusieurs │
│    listes de rôles                                      │
│                                                         │
│ ⚠️ Important :                                          │
│  • Une liste qui contient des sous-listes ne peut pas  │
│    être incluse comme sous-liste dans une autre liste  │
│  • Toutes les autres sources (Critères, Manuel,        │
│    Externes) restent actives et combinables            │
│                                                         │
│ [Fermer]                                                │
└─────────────────────────────────────────────────────────┘
```

### 10.2 Documentation à ajouter dans README.md

Section à ajouter dans la documentation utilisateur :

```markdown
### Sous-listes

Les sous-listes permettent d'inclure d'autres listes d'emails
comme source d'adresses, avec dédoublonnage automatique.

**Utiliser des sous-listes :**
1. Éditez une liste
2. Allez dans l'onglet "Sous-listes"
3. Cochez les listes à inclure

**Limitations :**
- Une liste qui contient des sous-listes ne peut pas être 
  incluse dans une autre liste
- Toutes les autres sources restent actives et combinables
```

---

## 11. Évolutions futures possibles


### 11.1 Export avec métadonnées

Améliorer l'export pour indiquer les sources :
```
jean.dupont@example.com (Jean DUPONT) [CA, Instructeurs]
marie.martin@example.com (Marie MARTIN) [CA, Trésoriers]
```

---

## 12. Questions ouvertes

### 12.1 À décider avec l'utilisateur

1. **Icônes** : Confirmer les icônes 📋 (liste standard) et 📂 (liste avec sous-listes)


2. **Limite de sous-listes** : Faut-il limiter le nombre de sous-listes par liste ?
non.

---

## 13. Annexes

### 13.1 Exemples de requêtes SQL

#### Trouver toutes les listes contenant des sous-listes
```sql
SELECT DISTINCT el.*
FROM email_lists el
INNER JOIN email_list_sublists els ON el.id = els.parent_list_id;
```

#### Trouver toutes les listes sans sous-listes
```sql
SELECT el.*
FROM email_lists el
WHERE el.id NOT IN (
  SELECT DISTINCT parent_list_id
  FROM email_list_sublists
);
```

#### Compter les sous-listes d'une liste
```sql
SELECT COUNT(*) as sublist_count
FROM email_list_sublists
WHERE parent_list_id = ?;
```

#### Trouver les listes utilisant une liste donnée comme sous-liste
```sql
SELECT el.id, el.name, COUNT(els2.child_list_id) as total_sublists
FROM email_lists el
INNER JOIN email_list_sublists els ON el.id = els.parent_list_id
LEFT JOIN email_list_sublists els2 ON el.id = els2.parent_list_id
WHERE els.child_list_id = ?
GROUP BY el.id, el.name;
```

### 13.2 Diagramme de flux

```
┌─────────────────────────────────────────────────┐
│ Utilisateur édite une liste                    │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
         ┌───────────────────┐
         │ 4 sources         │
         │ disponibles :     │
         ├───────────────────┤
         │ 1. Critères       │
         │ 2. Manuel         │
         │ 3. Externes       │
         │ 4. Sous-listes    │
         └───────────────────┘
                 │
                 ▼
         ┌───────────────────┐
         │ Résolution avec   │
         │ dédoublonnage     │
         └───────────────────┘
                 │
                 ▼
         ┌───────────────────┐
         │ Liste finale      │
         │ d'adresses        │
         └───────────────────┘
```

**Note** : Contrairement au flux initial qui nécessitait des modes distincts, la nouvelle approche simplifie l'interface en traitant toutes les sources de manière égale. La seule contrainte est qu'une liste contenant des sous-listes ne peut pas elle-même être incluse comme sous-liste.

---

## 14. Approbation

| Rôle | Nom | Date | Signature |
|------|-----|------|-----------|
| **Demandeur** | Fred | 2025-11-25 | ✓ |
| **Développeur** | Claude Code | 2025-11-25 | ✓ |
| **Validation** | - | - | - |

---

**Version:** 1.0
**Statut:** Proposition - En attente d'approbation
**Prochaine étape:** Création du document de design technique
