# Plan d'implémentation - Sous-listes d'emails

**Projet:** GVV - Gestion Vol à voile
**Fonctionnalité:** Support des sous-listes dans les listes d'emails
**Basé sur:** `doc/prds/email_sublists.md` v1.0
**Date:** 2025-11-26
**Statut:** 🟡 En cours - Phase 1/6 terminée

---

## Vue d'ensemble

### Objectif
Implémenter le support des sous-listes dans les listes d'emails, permettant aux utilisateurs de combiner plusieurs listes existantes avec dédoublonnage automatique des destinataires.

### Approche
- **Profondeur fixe** : 1 niveau uniquement (pas de récursion)
- **Intégration progressive** : 6 phases indépendantes
- **Compatibilité** : 100% rétrocompatible avec les listes existantes
- **Tests** : PHPUnit + Playwright à chaque phase

---

## Architecture technique

### Schéma de base de données

```
┌─────────────────────────────────────────────────────────┐
│ email_lists (existant)                                  │
├─────────────────────────────────────────────────────────┤
│ id (PK)                                                 │
│ name                                                    │
│ visible (public/privé)                                  │
│ user_id                                                 │
│ ...                                                     │
└─────────────────────────────────────────────────────────┘
                    ▲                    ▲
                    │                    │
        parent_list_id (FK, CASCADE)    child_list_id (FK, RESTRICT)
                    │                    │
┌─────────────────────────────────────────────────────────┐
│ email_list_sublists (NOUVELLE)                          │
├─────────────────────────────────────────────────────────┤
│ id (PK)                                                 │
│ parent_list_id → email_lists.id (ON DELETE CASCADE)    │
│ child_list_id → email_lists.id (ON DELETE RESTRICT)    │
│ added_at (timestamp)                                    │
│                                                         │
│ UNIQUE (parent_list_id, child_list_id)                 │
└─────────────────────────────────────────────────────────┘
```

**Points clés :**
- `ON DELETE CASCADE` sur parent : supprimer une liste parente supprime ses références
- `ON DELETE RESTRICT` sur child : empêcher la suppression d'une liste utilisée comme sous-liste
- Index sur `parent_list_id` et `child_list_id` pour performance

### Flux de résolution des adresses

```
textual_list(list_id)
├─ Source 1: Critères (rôles × sections)
│  └─ [adresses...]
├─ Source 2: Manuel (membres sélectionnés)
│  └─ [adresses...]
├─ Source 3: Externes (adresses saisies)
│  └─ [adresses...]
└─ Source 4: Sous-listes (NOUVEAU)
   ├─ Sous-liste A → textual_list(A) ← Appel NON récursif
   ├─ Sous-liste B → textual_list(B)
   └─ Sous-liste C → textual_list(C)

→ Fusion toutes sources
→ Dédoublonnage (deduplicate_emails)
→ Retour liste finale
```

**Contrainte** : Les sous-listes ne peuvent pas contenir de sous-listes (profondeur 1 uniquement)

### Règles de cohérence de visibilité

```
┌─────────────────────────────────────────────────────────┐
│ RÈGLE : Cohérence de visibilité                        │
├─────────────────────────────────────────────────────────┤
│ • Liste PRIVÉE → peut contenir sous-listes privées     │
│                  ET publiques                           │
│                                                         │
│ • Liste PUBLIQUE → peut contenir UNIQUEMENT            │
│                    sous-listes publiques                │
│                                                         │
│ • Rendre PUBLIQUE une liste avec sous-listes privées   │
│   → Proposer de rendre publiques toutes les            │
│      sous-listes privées                                │
└─────────────────────────────────────────────────────────┘
```

**Validation** :
- À l'ajout d'une sous-liste : vérifier la cohérence
- Au changement de visibilité : vérifier et proposer propagation

---

## Breakdown des tâches

### Phase 1 : Migration de base de données
**Statut:** ✅ **TERMINÉ** (2025-11-26)

#### Tâche 1.1 : Créer la migration
- [x] Créer `application/migrations/054_create_email_list_sublists.php`
- [x] Définir la table `email_list_sublists`
- [x] Ajouter les FK avec CASCADE/RESTRICT
- [x] Ajouter l'index UNIQUE sur (parent_list_id, child_list_id)
- [x] Ajouter les index sur parent_list_id et child_list_id
- [x] Implémenter la méthode `down()` pour rollback

#### Tâche 1.2 : Mettre à jour la version de migration
- [x] Modifier `application/config/migration.php`
- [x] Passer la version à `54`

#### Tâche 1.3 : Tester la migration
- [x] Créer test PHPUnit de migration (application/tests/mysql/EmailListSublistsMigrationTest.php)
- [x] Vérifier la création de table (13 tests créés)
- [x] Vérifier les FK et contraintes
- [x] Tester CASCADE et RESTRICT (fonctionnel)
- [x] Tester idempotence

**Critères d'acceptation :**
- ✅ Migration s'exécute sans erreur
- ✅ Table créée avec toutes les contraintes
- ✅ Rollback fonctionne
- ✅ **Tests passent : 13/13 ✅ (19 assertions, 100% succès)**

---

### Phase 2 : Modèle - Opérations CRUD sur sous-listes
**Statut:** 🔴 Non démarré
**Dépend de:** Phase 1

#### Tâche 2.1 : Ajouter les méthodes de base
**Fichier:** `application/models/email_lists_model.php`

- [ ] `add_sublist($parent_list_id, $child_list_id)` : Ajouter une sous-liste
  - Valider existence parent/child
  - Valider auto-référence (parent ≠ child)
  - Valider profondeur (child ne contient pas de sous-listes)
  - Valider cohérence visibilité
  - Valider doublon
  - Insérer dans `email_list_sublists`
  - Retourner `['success' => bool, 'error' => string|null]`

- [ ] `remove_sublist($parent_list_id, $child_list_id)` : Retirer une sous-liste
  - Supprimer la ligne correspondante
  - Retourner TRUE/FALSE

- [ ] `get_sublists($parent_list_id)` : Obtenir les sous-listes
  - SELECT avec JOIN pour récupérer les infos de chaque sous-liste
  - Retourner tableau avec id, name, visible, recipient_count

- [ ] `has_sublists($list_id)` : Vérifier si une liste contient des sous-listes
  - COUNT(*) sur email_list_sublists WHERE parent_list_id = ?
  - Retourner bool

- [ ] `get_parent_lists($child_list_id)` : Obtenir les listes parentes
  - SELECT avec JOIN pour listes qui contiennent cette sous-liste
  - Retourner tableau avec id, name, recipient_count

- [ ] `get_available_sublists($user_id, $is_admin, $exclude_list_id)` : Listes disponibles comme sous-listes
  - Récupérer listes visibles par l'utilisateur
  - Exclure celles qui contiennent déjà des sous-listes
  - Exclure $exclude_list_id (éviter auto-référence)
  - Retourner tableau avec id, name, visible, recipient_count

#### Tâche 2.2 : Tests unitaires du modèle
**Fichier:** `application/tests/mysql/EmailListsSublistsModelTest.php`

- [ ] Test `add_sublist()` - cas nominal
- [ ] Test `add_sublist()` - auto-référence (doit échouer)
- [ ] Test `add_sublist()` - profondeur > 1 (doit échouer)
- [ ] Test `add_sublist()` - doublon (doit échouer)
- [ ] Test `add_sublist()` - visibilité incohérente (doit échouer)
- [ ] Test `remove_sublist()` - cas nominal
- [ ] Test `get_sublists()` - liste avec sous-listes
- [ ] Test `get_sublists()` - liste sans sous-listes
- [ ] Test `has_sublists()` - TRUE/FALSE
- [ ] Test `get_parent_lists()` - liste utilisée/non utilisée
- [ ] Test `get_available_sublists()` - filtrage correct

**Critères d'acceptation :**
- ✅ Toutes les validations fonctionnent
- ✅ Tests passent avec >75% coverage
- ✅ Messages d'erreur clairs

---

### Phase 3 : Modèle - Résolution des adresses avec sous-listes
**Statut:** 🔴 Non démarré
**Dépend de:** Phase 2

#### Tâche 3.1 : Modifier `textual_list()`
**Fichier:** `application/models/email_lists_model.php`

- [ ] Ajouter résolution Source 4 (Sous-listes)
- [ ] Boucle sur `$this->get_sublists($list_id)`
- [ ] Pour chaque sous-liste : appeler `textual_list($sublist_id)`
- [ ] Fusionner avec les autres sources
- [ ] Appliquer `deduplicate_emails()`
- [ ] S'assurer qu'il n'y a pas de récursion infinie (profondeur 1 uniquement)

#### Tâche 3.2 : Modifier `detailed_list()`
**Fichier:** `application/models/email_lists_model.php`

- [ ] Ajouter résolution Source 4 (Sous-listes) avec métadonnées
- [ ] Retourner infos : email, nom, source = "sublist:nom_liste"
- [ ] Fusionner avec les autres sources
- [ ] Dédoublonner

#### Tâche 3.3 : Tests de résolution
**Fichier:** `application/tests/mysql/EmailListsResolutionTest.php`

- [ ] Test `textual_list()` avec 1 sous-liste
- [ ] Test `textual_list()` avec 3 sous-listes
- [ ] Test `textual_list()` avec sous-listes + critères + manuel + externes
- [ ] Test dédoublonnage entre sources
- [ ] Test `detailed_list()` avec sous-listes
- [ ] Test comptage destinataires bruts vs dédoublonnés

**Critères d'acceptation :**
- ✅ Résolution fonctionne pour toutes les sources
- ✅ Dédoublonnage correct
- ✅ Performance acceptable (pas de récursion)
- ✅ Tests passent avec >75% coverage

---

### Phase 4 : Contrôleur - API AJAX
**Statut:** 🔴 Non démarré
**Dépend de:** Phase 3

#### Tâche 4.1 : Actions AJAX
**Fichier:** `application/controllers/email_lists.php`

- [ ] `add_sublist_ajax()` : POST avec parent_list_id, child_list_id
  - Vérifier permissions
  - Appeler `$this->email_lists_model->add_sublist()`
  - Retourner JSON : `{success: true|false, error: string|null, message: string}`

- [ ] `remove_sublist_ajax()` : POST avec parent_list_id, child_list_id
  - Vérifier permissions
  - Appeler `$this->email_lists_model->remove_sublist()`
  - Retourner JSON : `{success: true|false, message: string}`

- [ ] `get_available_sublists_ajax()` : GET
  - Récupérer user_id de la session
  - Appeler `$this->email_lists_model->get_available_sublists()`
  - Retourner JSON : `{sublists: [{id, name, visible, recipient_count}, ...]}`

- [ ] `check_visibility_consistency_ajax()` : POST avec list_id, new_visibility
  - Vérifier si liste contient sous-listes privées
  - Si oui et new_visibility = public : retourner liste des sous-listes à modifier
  - Retourner JSON : `{can_change: bool, private_sublists: [{id, name}, ...]}`

- [ ] `propagate_visibility_ajax()` : POST avec list_id, new_visibility
  - Rendre publique la liste
  - Rendre publiques toutes les sous-listes privées
  - Retourner JSON : `{success: bool, updated_lists: [{id, name}, ...]}`

#### Tâche 4.2 : Tests contrôleur
**Fichier:** `application/tests/controllers/EmailListsControllerSublistsTest.php`

- [ ] Test `add_sublist_ajax()` - cas nominal
- [ ] Test `add_sublist_ajax()` - validation échoue
- [ ] Test `add_sublist_ajax()` - permissions
- [ ] Test `remove_sublist_ajax()` - cas nominal
- [ ] Test `get_available_sublists_ajax()` - filtrage correct
- [ ] Test `check_visibility_consistency_ajax()` - détection sous-listes privées
- [ ] Test `propagate_visibility_ajax()` - propagation correcte

**Critères d'acceptation :**
- ✅ API retourne JSON valide
- ✅ Permissions respectées
- ✅ Tests passent avec >70% coverage

---

### Phase 5 : Vues et JavaScript
**Statut:** 🔴 Non démarré
**Dépend de:** Phase 4

#### Tâche 5.1 : Créer l'onglet "Sous-listes"
**Fichier:** `application/views/email_lists/_sublists_tab.php`

- [ ] Créer la vue partielle
- [ ] Liste des sous-listes actuellement incluses (checkboxes cochées)
- [ ] Liste des sous-listes disponibles (checkboxes non cochées)
- [ ] Badge avec nombre de destinataires pour chaque liste
- [ ] Filtre : ne pas afficher les listes qui contiennent des sous-listes
- [ ] Appliquer filtrage selon visibilité (cohérence)
- [ ] Utiliser Bootstrap 5

#### Tâche 5.2 : Modifier la vue d'édition
**Fichier:** `application/views/email_lists/edit.php`

- [ ] Ajouter 4ème onglet "Sous-listes"
- [ ] Inclure `_sublists_tab.php`
- [ ] Gérer l'activation de l'onglet
- [ ] Ajouter validation côté client pour visibilité

#### Tâche 5.3 : Modifier la vue index
**Fichier:** `application/views/email_lists/index.php`

- [ ] Ajouter colonne "Sources" avec icônes
- [ ] 📋 pour listes standard
- [ ] 📂 pour listes avec sous-listes
- [ ] Afficher "S (X listes)" quand sous-listes présentes

#### Tâche 5.4 : Modifier la vue de prévisualisation
**Fichier:** `application/views/email_lists/view.php`

- [ ] Afficher section "Sous-listes" si applicable
- [ ] Lister les sous-listes avec leur nombre de destinataires
- [ ] Afficher comptage brut vs dédoublonné

#### Tâche 5.5 : JavaScript
**Fichier:** `assets/js/email_lists.js`

- [ ] `addSublist(childListId)` : Appeler AJAX add_sublist
- [ ] `removeSublist(childListId)` : Appeler AJAX remove_sublist
- [ ] `loadAvailableSublists()` : Charger listes disponibles
- [ ] `updateSublistsDisplay()` : Rafraîchir affichage
- [ ] `handleVisibilityChange(newVisibility)` : Vérifier cohérence
- [ ] Si incohérence : afficher popup avec option "Rendre tout public"
- [ ] `propagateVisibility()` : Propager changement de visibilité

#### Tâche 5.6 : Tests Playwright
**Fichier:** `playwright/tests/email_lists_sublists.spec.js`

- [ ] Test : Créer liste avec 3 sous-listes
- [ ] Test : Retirer une sous-liste
- [ ] Test : Affichage icône 📂 dans index
- [ ] Test : Prévisualisation avec sous-listes
- [ ] Test : Validation visibilité (liste publique + sous-liste privée)
- [ ] Test : Propagation visibilité ("Rendre tout public")
- [ ] Test : Tenter d'ajouter liste avec sous-listes comme sous-liste (doit échouer)

**Critères d'acceptation :**
- ✅ Interface Bootstrap 5 cohérente
- ✅ UX fluide et intuitive
- ✅ Tests Playwright passent
- ✅ Validation client + serveur

---

### Phase 6 : Gestion de la suppression
**Statut:** 🔴 Non démarré
**Dépend de:** Phase 5

#### Tâche 6.1 : Modifier la suppression
**Fichier:** `application/controllers/email_lists.php`

- [ ] Modifier `delete()` pour vérifier `get_parent_lists()`
- [ ] Si liste utilisée : afficher popup avec listes parentes
- [ ] Proposer 2 options :
  - Annuler
  - Retirer et supprimer
- [ ] Implémenter "Retirer et supprimer" :
  - Retirer de toutes les listes parentes
  - Puis supprimer la liste

#### Tâche 6.2 : Vue de confirmation de suppression
**Fichier:** `application/views/email_lists/_delete_confirmation.php`

- [ ] Créer modal Bootstrap pour confirmation
- [ ] Afficher liste des listes parentes si applicable
- [ ] Boutons "Annuler" / "Retirer et supprimer"

#### Tâche 6.3 : Tests de suppression
**Tests MySQL :**
- [ ] Test FK ON DELETE CASCADE : supprimer liste parente
- [ ] Test FK ON DELETE RESTRICT : tenter de supprimer liste utilisée (doit échouer)

**Tests Playwright :**
- [ ] Test : Supprimer liste avec sous-listes (doit réussir)
- [ ] Test : Supprimer liste utilisée comme sous-liste (popup)
- [ ] Test : "Retirer et supprimer" (doit retirer puis supprimer)

**Critères d'acceptation :**
- ✅ FK fonctionnent correctement
- ✅ Popup affichée si liste utilisée
- ✅ "Retirer et supprimer" fonctionne
- ✅ Tests passent

---

## Traductions

### Fichiers à modifier

- [ ] `application/language/french/email_lists_lang.php`
  - "Sous-listes"
  - "Listes disponibles comme sous-listes"
  - Messages d'erreur de validation
  - Messages de confirmation

- [ ] `application/language/english/email_lists_lang.php`
  - "Sublists"
  - "Available lists as sublists"
  - Validation error messages
  - Confirmation messages

- [ ] `application/language/dutch/email_lists_lang.php`
  - "Sublijsten"
  - "Beschikbare lijsten als sublijsten"
  - Validatiefoutmeldingen
  - Bevestigingsberichten

---

## Métadonnées (Gvvmetadata.php)

### Modifications nécessaires

**Fichier:** `application/libraries/Gvvmetadata.php`

- [ ] Ajouter métadonnées pour `email_list_sublists.parent_list_id`
- [ ] Ajouter métadonnées pour `email_list_sublists.child_list_id`
- [ ] Configurer selectors pour afficher les listes disponibles

---

## Tests de régression

### Suite complète de tests

#### Tests unitaires (PHPUnit)
- [ ] Tous les helpers existants (non-régression)
- [ ] Modèle email_lists (fonctions existantes + nouvelles)
- [ ] Validation et règles métier

#### Tests d'intégration (MySQL)
- [ ] Migration up/down
- [ ] FK CASCADE/RESTRICT
- [ ] Résolution complète des adresses

#### Tests contrôleur
- [ ] API AJAX
- [ ] Permissions
- [ ] JSON responses

#### Tests end-to-end (Playwright)
- [ ] Création/modification/suppression avec sous-listes
- [ ] Validation visibilité
- [ ] Propagation visibilité
- [ ] Export (TXT, Markdown)

**Objectif coverage :** >75% global

---

## Déploiement

### Checklist pré-déploiement

- [ ] Tous les tests passent (PHPUnit + Playwright)
- [ ] Coverage >75%
- [ ] Validation manuelle sur environnement de test
- [ ] Documentation utilisateur mise à jour
- [ ] Traductions complètes (FR/EN/NL)
- [ ] Backup base de données

### Procédure de déploiement

1. **Backup :**
   ```bash
   mysqldump -u gvv_user -p gvv2 > backup_pre_sublists.sql
   ```

2. **Déployer le code :**
   ```bash
   git pull origin main
   ```

3. **Exécuter la migration :**
   - Via interface GVV : Admin > Migrations
   - Ou via CLI : `php index.php migrate`

4. **Vérifier :**
   - [ ] Table `email_list_sublists` créée
   - [ ] FK en place
   - [ ] Interface accessible
   - [ ] Aucune erreur dans logs

5. **Tests smoke :**
   - [ ] Créer une liste avec 2 sous-listes
   - [ ] Vérifier export TXT
   - [ ] Tester suppression protégée
   - [ ] Tester changement de visibilité

### Rollback (si nécessaire)

```bash
# Restaurer le backup
mysql -u gvv_user -p gvv2 < backup_pre_sublists.sql

# Revenir au code précédent
git revert <commit_hash>
```

---

## Risques et mitigation

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| FK RESTRICT bloque suppressions légitimes | Moyen | Faible | UI claire + option "Retirer et supprimer" |
| Performance avec nombreuses sous-listes | Moyen | Faible | Index DB + limite profondeur=1 |
| Confusion UX (trop d'onglets) | Faible | Moyen | Design clair + aide contextuelle |
| Incohérence visibilité oubliée | Élevé | Moyen | Validation stricte + propagation assistée |
| Récursion infinie (bug) | Élevé | Très faible | Profondeur=1 + validation stricte |

---

## Métriques de succès

### Critères de validation

- [ ] **Fonctionnel :** Toutes les user stories du PRD implémentées
- [ ] **Qualité :** Coverage >75%
- [ ] **Performance :** Résolution <500ms pour liste avec 5 sous-listes
- [ ] **UX :** Interface intuitive (validation utilisateur)
- [ ] **Robustesse :** Pas de régression sur fonctionnalités existantes

### Indicateurs post-déploiement

- Nombre de listes avec sous-listes créées (objectif : >10 en 1 mois)
- Taux d'utilisation vs autres sources
- Feedback utilisateurs
- Bugs critiques : 0

---

## Prochaines étapes

1. **Immédiat :** Approbation du plan
2. **Phase 1 :** Migration DB (estimé : 1 jour)
3. **Phase 2 :** Modèle CRUD (estimé : 2 jours)
4. **Phase 3 :** Résolution adresses (estimé : 1 jour)
5. **Phase 4 :** API AJAX (estimé : 1 jour)
6. **Phase 5 :** UI (estimé : 2 jours)
7. **Phase 6 :** Suppression (estimé : 1 jour)
8. **Tests finaux :** 1 jour
9. **Déploiement :** 0.5 jour

**Estimation totale :** ~10 jours de développement

---

## Notes de conception

### Choix techniques justifiés

1. **Profondeur 1 uniquement :**
   - ✅ Simplicité conceptuelle
   - ✅ Pas de récursion = performance garantie
   - ✅ Évite cycles automatiquement
   - ✅ Suffisant pour 99% des cas d'usage

2. **ON DELETE RESTRICT sur child_list_id :**
   - ✅ Protection contre suppression accidentelle
   - ✅ Force l'utilisateur à décider explicitement
   - ✅ UI claire avec option "Retirer et supprimer"

3. **ON DELETE CASCADE sur parent_list_id :**
   - ✅ Cohérence : supprimer liste parente = nettoyer références
   - ✅ Pas d'orphelins dans `email_list_sublists`

4. **Cohérence de visibilité :**
   - ✅ Liste publique ne peut contenir que sous-listes publiques (évite fuite d'infos)
   - ✅ Liste privée peut contenir sous-listes publiques ET privées (flexibilité)
   - ✅ Propagation assistée pour changement de visibilité

5. **Pas de type/mode distinct :**
   - ✅ Toutes les listes sont égales
   - ✅ Détection automatique via `has_sublists()`
   - ✅ Rétrocompatibilité totale

### Alternatives considérées et rejetées

❌ **Profondeur N (récursif) :**
- Trop complexe pour l'utilisateur
- Risque de cycles
- Performance imprévisible

❌ **Type "liste composée" séparé :**
- Fragmente les listes
- Perd la rétrocompatibilité
- Conversion manuelle nécessaire

❌ **ON DELETE CASCADE partout :**
- Suppression silencieuse dangereuse
- Perte de traçabilité

---

## Approbation

| Rôle | Nom | Date | Statut |
|------|-----|------|--------|
| **Développeur** | Claude Code | 2025-11-26 | ✓ Proposé |
| **Validation** | Fred | - | ⏳ En attente |

---

**Version:** 1.0
**Statut:** 🔴 Non démarré - En attente d'approbation
**Prochaine étape:** Validation du plan puis démarrage Phase 1
