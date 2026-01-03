# Plan d'Implémentation - Comptes Multi-Sections au Tableau de Bord

**Date:** 3 janvier 2026  
**Contexte:** GVV - Multi-section Accounts in Dashboard  
**Statut:** En Préparation  
**Design Document:** `doc/design_notes/multi_section_accounts_design.md`

---

## 1. Aperçu de la Fonctionnalité

Afficher plusieurs cartes "Mon compte" au dashboard, une par section où le pilote a un compte 411, avec la possibilité d'accéder au compte 411 de chaque section via un paramètre `$section` dédié.

---

## 2. Décomposition des Tâches

### Phase 1: Modèle - Récupération des Comptes Multi-Sections

**Tâche 1.1:** Ajouter méthode `get_pilote_comptes()` à `comptes_model.php`

- **Fichier:** `application/models/comptes_model.php`
- **Méthode:** Nouvelle méthode `public function get_pilote_comptes($pilote)`
- **Description:** 
  - Récupère tous les comptes 411 du pilote pour toutes ses sections
  - Joint avec la table `sections` pour obtenir le nom et acronyme
  - Retourne un array avec `id`, `nom`, `club`, `section_name`, `solde`
- **Dépendances:** `sections_model` (déjà chargé dans le modèle)
- **Tests:** 
  - Pilote avec 1 compte ✓
  - Pilote avec 2+ comptes ✓
  - Pilote sans compte ✓

**Tâche 1.2:** Ajouter méthode de vérification `has_compte_in_section()`

- **Fichier:** `application/models/comptes_model.php`
- **Méthode:** Nouvelle méthode `public function has_compte_in_section($pilote, $section_id)`
- **Description:**
  - Vérifie qu'un pilote a un compte 411 actif dans une section donnée
  - Utilisée pour la sécurité
- **Tests:**
  - Retourne true si compte existe ✓
  - Retourne false sinon ✓

---

### Phase 2: Contrôleur - Paramètre Section

**Tâche 2.1:** Modifier `mon_compte()` pour accepter le paramètre `$section_id`

- **Fichier:** `application/controllers/compta.php`
- **Méthode:** Modifier `function mon_compte($section_id = null)`
- **Description:**
  - Accepte un paramètre `$section_id` optionnel (URI routing)
  - Si `$section_id` est fourni:
    - Récupère l'objet section correspondant
    - Vérifie que l'utilisateur a un compte dans cette section
    - Utilise cette section pour filtrer les données
  - Si `$section_id` n'est pas fourni:
    - Utilise la section de session (comportement actuel)
  - Appelle `compte_pilote()` et `journal_compte()` en passant la section en argument
- **Sécurité:** Vérifier que l'utilisateur possède le compte avant d'afficher

**Tâche 2.2:** Modifier `compte_pilote()` pour accepter un paramètre `$section`

- **Fichier:** `application/controllers/compta.php`
- **Méthode:** Modifier `private function compte_pilote($pilote, $section = null)`
- **Description:**
  - Actuellement utilise `$this->sections_model->section()` pour la section active
  - Doit accepter un paramètre `$section` pour utiliser une section explicite
  - Fallback à `$this->sections_model->section()` si `$section` est null
- **Impact:** Propagate à `journal_data()`

**Tâche 2.3:** Modifier `journal_compte()` pour accepter un paramètre `$section`

- **Fichier:** `application/controllers/compta.php`
- **Méthode:** Modifier `private function journal_compte($compte_id, $section = null)`
- **Description:**
  - Accepte le paramètre `$section` 
  - Passe ce paramètre à `journal_data()`
- **Impact:** Propagation vers `journal_data()`

**Tâche 2.4:** Modifier `journal_data()` pour filtrer par section explicite

- **Fichier:** `application/controllers/compta.php`
- **Méthode:** Modifier `function journal_data(&$data, $compte_id, $section = null)`
- **Description:**
  - Le paramètre `$section` est la section spécifique à utiliser
  - Utiliser `$section['id']` plutôt que `$this->section['id']` pour le filtrage
  - Cela isole complètement le filtrage des données de la session
  - Les écritures doivent correspondre au compte dans cette section
- **Tests:**
  - Vérifier les écritures pour chaque section ✓
  - Vérifier qu'aucune écriture d'une autre section n'apparaît ✓

---

### Phase 3: Vue - Cartes Dynamiques

**Tâche 3.1:** Modifier `bs_dashboard.php` pour générer cartes multi-sections

- **Fichier:** `application/views/bs_dashboard.php`
- **Section:** "Mon espace personnel" (voir les lignes 117-200 actuellement)
- **Description:**
  - Charger le modèle `comptes_model`
  - Récupérer les comptes du pilote via `get_pilote_comptes($username)`
  - Boucle sur chaque compte:
    - Récupère la section correspondante
    - Génère une carte avec titre "Mon compte - [Section]"
    - Lien: `controller_url('compta/mon_compte/' . $compte['club'])`
  - Fallback: si pas de comptes, afficher une seule carte "Ma facture" (comportement actuel)
- **Design:** Cohérent avec Bootstrap 5 et style existant
- **Tests:**
  - Pilote mono-section: 1 carte ✓
  - Pilote 2 sections: 2 cartes ✓
  - Pilote 3 sections: 3 cartes ✓
  - Ordre des sections: logique (par club ID) ✓

---

### Phase 4: Traductions

**Tâche 4.1:** Ajouter clés de traduction

- **Fichiers:**
  - `application/language/french/dashboard_lang.php` (ou créer si nécessaire)
  - `application/language/english/dashboard_lang.php`
  - `application/language/dutch/dashboard_lang.php`
- **Clés suggérées:**
  ```php
  $lang['dashboard_my_account'] = "Mon compte";  // FR
  $lang['dashboard_consult'] = "Consulter";
  ```
- **Tests:** Vérifier que les traductions s'affichent correctement ✓

---

### Phase 5: Tests et Validation

**Tâche 5.1:** Créer tests unitaires

- **Fichier:** `application/tests/unit/models/comptes_model_multi_section_test.php`
- **Tests:**
  - `test_get_pilote_comptes_single_section()`
  - `test_get_pilote_comptes_multi_section()`
  - `test_get_pilote_comptes_no_account()`
  - `test_has_compte_in_section_exists()`
  - `test_has_compte_in_section_not_exists()`
- **Couverture:** 70%+ des nouvelles méthodes

**Tâche 5.2:** Créer tests d'intégration

- **Fichier:** `application/tests/integration/compta_multi_section_integration_test.php`
- **Tests:**
  - `test_mon_compte_with_section_id()`
  - `test_mon_compte_without_section_id_uses_session()`
  - `test_mon_compte_security_check_unauthorized_section()`
  - `test_dashboard_displays_multiple_account_cards()`

**Tâche 5.3:** Tests manuels (Smoke tests)

- **Scénario 1:** Utilisateur mono-section (ex: planchiste Planeur uniquement)
  - Dashboard affiche 1 carte "Mon compte"
  - Clic sur carte: affiche le compte
  - ✓ Pas de régression

- **Scénario 2:** Utilisateur multi-section (ex: fpeignot Planeur + ULM)
  - Dashboard affiche 2 cartes: "Mon compte - Planeur", "Mon compte - ULM"
  - Clic sur première: affiche compte Planeur, écritures filtrées
  - Clic sur deuxième: affiche compte ULM, écritures filtrées
  - ✓ Comportement nouveau

- **Scénario 3:** Utilisateur sans compte
  - Dashboard: une seule carte "Ma facture" (fallback)
  - ✓ Pas de crash

- **Scénario 4:** Sécurité - Tentative d'accès non autorisé
  - Créer URL manuelle: `/compta/mon_compte/3` (Avion)
  - Utilisateur n'a pas de compte en Avion
  - Résultat: Redirection + message d'erreur
  - ✓ Protégé

---

## 3. Dépendances et Prérequis

### Code Existant à Réutiliser
- ✓ `membres_model->registered_in_sections()` - Déjà existe
- ✓ `sections_model->section()`, `get_by_id()` - Déjà existe
- ✓ `comptes_model->compte_pilote()` - À modifier
- ✓ Bootstrap 5 grid layout - Déjà utilisé

### Pas de Dépendances Externes Nouvelles
- Pas de migrations BD
- Pas de nouvelles tables
- Pas de nouvelles dépendances PHP

---

## 4. Estimations

| Tâche | Complexité | Temps Estimé |
|-------|-----------|------------|
| 1.1 - get_pilote_comptes() | Basse | 30 min |
| 1.2 - has_compte_in_section() | Basse | 15 min |
| 2.1 - mon_compte() | Moyenne | 45 min |
| 2.2 - compte_pilote() | Moyenne | 30 min |
| 2.3 - journal_compte() | Basse | 20 min |
| 2.4 - journal_data() | Moyenne | 45 min |
| 3.1 - bs_dashboard.php | Basse | 30 min |
| 4.1 - Traductions | Très basse | 15 min |
| 5.1 - Tests unitaires | Moyenne | 60 min |
| 5.2 - Tests intégration | Moyenne | 60 min |
| 5.3 - Smoke tests | Basse | 30 min |
| **TOTAL** | | **~6h30** |

---

## 5. Risques et Mitigations

| Risque | Probabilité | Impact | Mitigation |
|--------|------------|--------|-----------|
| Filtrage des écritures incomplet | Moyen | Haut | Tests exhaustifs de filtrage |
| Fuite de données (voir compte d'un autre) | Basse | Critique | Vérification sécurité stricte |
| Régression utilisateurs mono-section | Basse | Moyen | Tests de régression ciblés |
| Performance (multi-requêtes) | Basse | Faible | Optimisation des joins BD |

---

## 6. Checklist d'Implémentation

### Phase 1: Modèle
- [ ] Ajouter `get_pilote_comptes()`
- [ ] Ajouter `has_compte_in_section()`
- [ ] Tester localement

### Phase 2: Contrôleur
- [ ] Modifier signature `mon_compte($section_id = null)`
- [ ] Modifier `compte_pilote()` pour accepter section
- [ ] Modifier `journal_compte()` pour accepter section
- [ ] Modifier `journal_data()` pour utiliser section explicite
- [ ] Tester appels sans paramètre (fallback session) ✓
- [ ] Tester appels avec paramètre (section explicite) ✓

### Phase 3: Vue
- [ ] Charger modèle comptes
- [ ] Générer boucle sur comptes
- [ ] Créer cartes dynamiques
- [ ] Valider CSS/Bootstrap
- [ ] Tester avec 0, 1, 2, 3 comptes ✓

### Phase 4: Traductions
- [ ] Ajouter clés français
- [ ] Ajouter clés anglais
- [ ] Ajouter clés néerlandais

### Phase 5: Tests
- [ ] Tests unitaires ✓
- [ ] Tests intégration ✓
- [ ] Smoke tests manuels ✓
- [ ] Vérifier aucune régression ✓

---

## 7. Critères d'Acceptance

1. ✓ Pilote mono-section: voir 1 carte "Mon compte"
2. ✓ Pilote 2+ sections: voir N cartes "Mon compte - [Section]"
3. ✓ Clic sur une carte: affiche le compte de la section concernée
4. ✓ Écritures filtrées correctement par section
5. ✓ Sécurité: impossible d'accéder à un compte d'une section où on n'a pas de compte
6. ✓ Rétro-compatibilité: `/compta/mon_compte` sans paramètre fonctionne encore (section session)
7. ✓ Tests: couverture > 70%
8. ✓ Pas de régression détectée sur utilisateurs mono-section

---

## 8. Timeline

- **Day 1:** Phases 1-3 (Modèle, Contrôleur, Vue)
- **Day 2:** Phase 4-5 (Traductions, Tests)
- **Day 3:** Review, Bug fixes, Documentation

---

## 9. Documentation à Mettre à Jour

- [ ] Ce plan d'implémentation (progression)
- [ ] Design document `doc/design_notes/multi_section_accounts_design.md` ✓ Créé
- [ ] README ou wikis internes si nécessaire
- [ ] Commentaires de code pour les nouvelles méthodes
