# Plan d'Implémentation : Pattern PRG pour "Créer et continuer"

**Date:** 2025-12-02
**Version:** 1.0
**Statut:** Prêt pour implémentation
**PRD:** `doc/prds/prg_creer_et_continuer.md`
**Design:** `doc/design_notes/prg_pattern_analysis.md`

---

## Vue d'ensemble

**Objectif:** Appliquer le pattern Post-Redirect-Get au workflow "Créer et continuer" pour éliminer le risque de double soumission F5, tout en préservant le pré-remplissage du formulaire pour la saisie rapide.

**Approche:** Option A - PRG avec préservation du pré-remplissage via session flash data

**Estimation totale:** 5 heures

---

## Phase 1 : Préparation et Analyse (30 min)

### ✅ TÂCHE 1.1 : Lire et comprendre le contexte
**Durée:** 10 min
**Statut:** ✅ Complété

**Objectif:** S'assurer de comprendre le problème, la solution proposée et l'architecture actuelle

**Actions:**
- [x] Lire le PRD v2.0
- [x] Lire la design note sur le pattern PRG
- [x] Comprendre le comportement actuel vs souhaité

### ⏳ TÂCHE 1.2 : Identifier tous les contrôleurs concernés
**Durée:** 20 min
**Statut:** En attente
**Dépendances:** Aucune

**Objectif:** Trouver tous les endroits où le comportement "Créer et continuer" est implémenté

**Actions:**
- [ ] Rechercher les occurrences de `load_last_view` après création réussie
- [ ] Identifier les contrôleurs héritant de `Gvv_Controller` utilisant "Créer et continuer"
- [ ] Vérifier les overrides dans les contrôleurs enfants
- [ ] Créer une liste complète des fichiers à modifier

**Commandes:**
```bash
source setenv.sh
# Rechercher load_last_view dans le contexte de création réussie
grep -r "load_last_view" application/controllers/ application/libraries/ --include="*.php"
# Rechercher les références au bouton "continuer"
grep -r "continuer\|continue" application/controllers/ --include="*.php" -i
```

**Livrables:**
- Liste des fichiers à modifier avec numéros de ligne

---

## Phase 2 : Modification du Contrôleur Parent (1h30)

### ⏳ TÂCHE 2.1 : Modifier Gvv_Controller::formValidation() - Cas succès
**Durée:** 45 min
**Statut:** En attente
**Dépendances:** TÂCHE 1.2
**Fichier:** `application/libraries/Gvv_Controller.php`
**Lignes:** 557-573

**Objectif:** Implémenter le redirect avec flash data pour "Créer et continuer"

**Modifications:**

**AVANT (lignes ~557-573):**
```php
if ($button == $this->lang->line("gvv_create_and_continue")) {
    $image = $this->gvv_model->image($id);
    $msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
    $this->data['message'] = '<div class="text-success">' . $msg . '</div>';
    $this->form_static_element($action);
    return load_last_view($this->form_view, $this->data, $this->unit_test);
}
```

**APRÈS:**
```php
if ($button == $this->lang->line("gvv_create_and_continue")) {
    // Préparer le message de succès
    $image = $this->gvv_model->image($id);
    $msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
    $this->session->set_flashdata('success', $msg);

    // Préparer les données pour pré-remplissage (exclure champs problématiques)
    $prefill_data = $processed_data;
    unset($prefill_data['id']);
    unset($prefill_data['date_creation']);
    // Ajouter d'autres exclusions spécifiques si nécessaire

    $this->session->set_flashdata('prefill_data', $prefill_data);

    // Rediriger vers la page de création (PRG appliqué)
    redirect($this->controller . "/create");
}
```

**Actions:**
- [ ] Localiser le bloc exact dans `Gvv_Controller.php`
- [ ] Remplacer `load_last_view` par `redirect` avec flash data
- [ ] Ajouter nettoyage des champs id et date_creation
- [ ] Ajouter commentaires expliquant le pattern PRG
- [ ] Valider syntaxe PHP avec `php -l`

**Commandes:**
```bash
source setenv.sh
php -l application/libraries/Gvv_Controller.php
```

**Validation:**
- Le fichier doit compiler sans erreur
- Le code doit suivre les conventions GVV existantes

### ⏳ TÂCHE 2.2 : Modifier Gvv_Controller::create() - Réinjecter prefill
**Durée:** 45 min
**Statut:** En attente
**Dépendances:** TÂCHE 2.1
**Fichier:** `application/libraries/Gvv_Controller.php`
**Lignes:** 118-134

**Objectif:** Réinjecter les données de pré-remplissage dans le formulaire de création

**Modifications:**

Trouver dans `create()` où `$this->data` est initialisé avec les defaults, puis ajouter:

```php
// Après initialisation des defaults
$table = $this->gvv_model->table();
$this->data = $this->gvvmetadata->defaults_list($table);

// Réinjecter les données de pré-remplissage si disponibles (après redirect)
$prefill = $this->session->flashdata('prefill_data');
if ($prefill) {
    $this->data = array_merge($this->data, $prefill);
}

// Le reste du code reste inchangé
$this->form_static_element(CREATION);
return load_last_view($this->form_view, $this->data, $this->unit_test);
```

**Actions:**
- [ ] Localiser l'initialisation de `$this->data` dans `create()`
- [ ] Ajouter la récupération de `prefill_data` depuis flash
- [ ] Merger avec les defaults existants
- [ ] Ajouter commentaires
- [ ] Valider syntaxe PHP

**Commandes:**
```bash
source setenv.sh
php -l application/libraries/Gvv_Controller.php
```

**Validation:**
- Les defaults restent présents pour les champs non pré-remplis
- Les données flash sont correctement mergées

---

## Phase 3 : Modification des Overrides Spécifiques (1h)

### ⏳ TÂCHE 3.1 : Modifier compta.php::formValidation()
**Durée:** 45 min
**Statut:** En attente
**Dépendances:** TÂCHE 2.1, TÂCHE 2.2
**Fichier:** `application/controllers/compta.php`
**Lignes:** 334-342

**Objectif:** Appliquer le même pattern PRG dans l'override comptabilité

**Contexte:**
Le contrôleur `compta.php` a un override spécifique pour "Créer et continuer". Il faut appliquer la même logique que dans le parent.

**Modifications:**

**AVANT (lignes ~334-342):**
```php
if ($button == $this->lang->line("gvv_create_and_continue")) {
    $image = $this->gvv_model->image($id);
    $msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
    $this->data['message'] = '<div class="text-success">' . $msg . '</div>';
    $this->form_static_element($action);
    return load_last_view($this->form_view, $this->data, $this->unit_test);
}
```

**APRÈS:**
```php
if ($button == $this->lang->line("gvv_create_and_continue")) {
    // Préparer le message de succès
    $image = $this->gvv_model->image($id);
    $msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
    $this->session->set_flashdata('success', $msg);

    // Préparer les données pour pré-remplissage
    $prefill_data = $processed_data;
    unset($prefill_data['id']);
    unset($prefill_data['date_creation']);
    // Exclure autres champs spécifiques comptabilité si nécessaire

    $this->session->set_flashdata('prefill_data', $prefill_data);

    // Rediriger vers la page de création (PRG appliqué)
    redirect($this->controller . "/create");
}
```

**Actions:**
- [ ] Localiser le bloc exact dans `compta.php`
- [ ] Appliquer les mêmes modifications que dans `Gvv_Controller`
- [ ] Identifier et exclure les champs spécifiques comptabilité si nécessaire
- [ ] Ajouter commentaires
- [ ] Valider syntaxe PHP

**Commandes:**
```bash
source setenv.sh
php -l application/controllers/compta.php
```

### ⏳ TÂCHE 3.2 : Vérifier et modifier autres contrôleurs si nécessaire
**Durée:** 15 min
**Statut:** En attente
**Dépendances:** TÂCHE 1.2, TÂCHE 2.1, TÂCHE 2.2

**Objectif:** S'assurer qu'aucun autre contrôleur n'a d'override non traité

**Actions:**
- [ ] Parcourir la liste des contrôleurs identifiés en TÂCHE 1.2
- [ ] Pour chaque contrôleur avec override du "Créer et continuer":
  - [ ] Appliquer les mêmes modifications
  - [ ] Identifier les champs à exclure spécifiques au domaine
  - [ ] Valider syntaxe PHP
- [ ] Documenter les modifications effectuées

**Validation:**
- Tous les overrides identifiés sont traités
- Tous les fichiers compilent sans erreur

---

## Phase 4 : Tests Manuels (1h)

### ⏳ TÂCHE 4.1 : Test comptabilité - Créer et continuer
**Durée:** 20 min
**Statut:** En attente
**Dépendances:** TÂCHE 2.1, TÂCHE 2.2, TÂCHE 3.1
**URL:** http://gvv.net/compta/create

**Objectif:** Valider le comportement principal sur le cas d'usage le plus critique

**Scénario de test:**

**Test 1: Création réussie avec pré-remplissage**
1. [ ] Se connecter avec rôle "tresorier"
2. [ ] Aller sur `/compta/create`
3. [ ] Remplir le formulaire:
   - Date: 01/12/2025
   - Compte1: 512
   - Compte2: 411
   - Montant: 100.00
   - Description: "Test PRG - Facture A"
4. [ ] Cliquer sur "Créer et continuer"
5. [ ] **Vérifier:**
   - [ ] ✅ URL = `/compta/create` (GET)
   - [ ] ✅ Message de succès affiché (alerte verte)
   - [ ] ✅ Formulaire **pré-rempli** avec:
     - Compte1 = 512
     - Compte2 = 411
     - Montant = 100.00
     - Description = "Test PRG - Facture A"
   - [ ] ✅ Champ `id` vide/absent
   - [ ] ✅ Date_creation vide/réinitialisée

**Test 2: F5 n'a aucun effet**
6. [ ] Appuyer sur F5 (rafraîchir)
7. [ ] **Vérifier:**
   - [ ] ✅ Page recharge (GET)
   - [ ] ✅ Message de succès disparu
   - [ ] ✅ Formulaire affiche valeurs par défaut (pré-remplissage disparu)
   - [ ] ✅ **AUCUN doublon créé en base de données**
8. [ ] Vérifier dans la base: `SELECT * FROM ecritures ORDER BY id DESC LIMIT 5`
   - [ ] ✅ Une seule écriture créée (pas de doublon)

**Test 3: Workflow saisie rapide**
9. [ ] Répéter création de 3 écritures similaires:
   - Écriture 1: Montant 100, Description "Facture A"
   - Écriture 2: Modifier seulement Montant 150, Description "Facture B"
   - Écriture 3: Modifier seulement Montant 200, Description "Facture C"
10. [ ] **Vérifier:**
    - [ ] ✅ Workflow rapide (pas de re-saisie des comptes)
    - [ ] ✅ 3 écritures distinctes créées en base
    - [ ] ✅ Aucun doublon

**Livrables:**
- Rapport de test avec captures d'écran si erreur
- Confirmation que les 3 tests passent

### ⏳ TÂCHE 4.2 : Test autres contrôleurs - Non régression
**Durée:** 30 min
**Statut:** En attente
**Dépendances:** TÂCHE 4.1

**Objectif:** Vérifier que la modification n'a pas cassé d'autres contrôleurs

**Contrôleurs à tester (minimum 5):**

1. **Vols avion** (`vols_avion/create`):
   - [ ] Créer vol avec "Créer"
   - [ ] Vérifier redirect vers liste/détail
   - [ ] F5 ne recrée pas le vol

2. **Membres** (`membre/create`):
   - [ ] Créer membre avec "Créer"
   - [ ] Vérifier redirect
   - [ ] F5 ne recrée pas

3. **Tarifs** (`tarifs/create`):
   - [ ] Créer tarif avec "Créer"
   - [ ] Vérifier redirect
   - [ ] F5 ne recrée pas

4. **Avions** (`avion/create`):
   - [ ] Créer avion avec "Créer"
   - [ ] Vérifier redirect
   - [ ] F5 ne recrée pas

5. **Procedures** (`procedures/create`):
   - [ ] Créer procédure avec "Créer"
   - [ ] Vérifier redirect
   - [ ] F5 ne recrée pas

**Pour chaque contrôleur:**
- [ ] Création réussie → redirect (AC-006)
- [ ] Échec validation → pas de redirect, erreurs affichées (AC-007)
- [ ] F5 après succès → pas de doublon

**Livrables:**
- Liste des contrôleurs testés avec résultats (✅/❌)

### ⏳ TÂCHE 4.3 : Test cas d'erreur - Validation et DB
**Durée:** 10 min
**Statut:** En attente
**Dépendances:** TÂCHE 4.1

**Objectif:** S'assurer que les cas d'erreur ne sont pas affectés (REQ-006)

**Test validation échouée:**
1. [ ] Aller sur `/compta/create`
2. [ ] Remplir avec des données invalides (ex: compte1 = compte2)
3. [ ] Cliquer "Créer et continuer"
4. [ ] **Vérifier:**
   - [ ] ✅ Pas de redirect (URL reste POST)
   - [ ] ✅ Erreurs de validation affichées
   - [ ] ✅ Données saisies préservées
   - [ ] ✅ Peut corriger et re-soumettre

**Test erreur base de données:**
(Si possible - dépend du schéma)
1. [ ] Forcer une erreur DB (ex: FK constraint)
2. [ ] **Vérifier:**
   - [ ] ✅ Pas de redirect
   - [ ] ✅ Message d'erreur DB affiché
   - [ ] ✅ Données préservées

**Livrables:**
- Confirmation que les workflows d'erreur sont inchangés

---

## Phase 5 : Tests Automatisés (30 min)

### ⏳ TÂCHE 5.1 : Exécuter suite PHPUnit complète
**Durée:** 15 min
**Statut:** En attente
**Dépendances:** TÂCHE 2.1, TÂCHE 2.2, TÂCHE 3.1

**Objectif:** S'assurer qu'aucun test existant n'est cassé

**Actions:**
- [ ] Exécuter la suite de tests complète avec couverture
- [ ] Analyser les résultats
- [ ] Corriger les tests cassés si nécessaire
- [ ] Documenter les modifications de tests

**Commandes:**
```bash
source setenv.sh
./run-all-tests.sh
```

**Critère de succès:**
- [ ] ✅ 100% des tests passent (AC-REG-002)
- [ ] ✅ Pas de nouvelles erreurs PHP
- [ ] ✅ Couverture de code maintenue ou améliorée

**Si échec:**
- Identifier les tests cassés
- Comprendre pourquoi (changement de comportement légitime ou bug)
- Corriger tests ou code selon le cas

### ⏳ TÂCHE 5.2 : Vérifier logs d'erreur
**Durée:** 10 min
**Statut:** En attente
**Dépendances:** TÂCHE 4.1, TÂCHE 4.2, TÂCHE 5.1

**Objectif:** S'assurer qu'aucune erreur PHP n'a été introduite

**Actions:**
- [ ] Consulter `/var/log/apache2/error.log`
- [ ] Vérifier absence d'erreurs PHP liées aux modifications
- [ ] Vérifier absence de warnings

**Commandes:**
```bash
# Vérifier les erreurs récentes
sudo tail -n 100 /var/log/apache2/error.log | grep -i "php\|error\|warning"
```

**Critère de succès:**
- [ ] ✅ Aucun message d'erreur PHP (AC-REG-003)
- [ ] ✅ Aucun warning lié aux modifications

### ⏳ TÂCHE 5.3 : Créer test Playwright de fumée (optionnel)
**Durée:** 5 min
**Statut:** En attente (optionnel)
**Dépendances:** TÂCHE 4.1

**Objectif:** Automatiser le test principal pour détection future de régression

**Actions:**
- [ ] Créer un test Playwright simple:
  - Login
  - Créer écriture avec "Créer et continuer"
  - Vérifier redirect
  - Vérifier pas de doublon avec F5
- [ ] Ajouter au test suite Playwright

**Note:** Optionnel - Peut être fait plus tard si temps insuffisant

---

## Phase 6 : Documentation et Finalisation (30 min)

### ⏳ TÂCHE 6.1 : Ajouter commentaires dans le code
**Durée:** 15 min
**Statut:** En attente
**Dépendances:** TÂCHE 2.1, TÂCHE 2.2, TÂCHE 3.1

**Objectif:** Documenter le pattern PRG pour les développeurs futurs (NFR-003)

**Actions:**
- [ ] Ajouter commentaire de classe dans `Gvv_Controller.php`:

```php
/**
 * Contrôleur GVV parent - Pattern de gestion des formulaires
 *
 * STRATÉGIE POST-REDIRECT-GET (PRG) :
 *
 * ✅ AVEC REDIRECT (PRG appliqué) :
 *    - Après création/modification/suppression réussie (y compris "Créer et continuer")
 *    - Après validation de filtres
 *    - Utiliser : redirect(), pop_return_url(), validationOkPage()
 *    - Messages via set_flashdata('success', $msg)
 *
 * ❌ SANS REDIRECT (affichage direct) :
 *    - Après échec de validation (préserver données + afficher erreurs)
 *    - Après erreur DB (afficher erreur technique + préserver données)
 *    - Utiliser : load_last_view() avec $this->data
 *
 * WORKFLOW "CRÉER ET CONTINUER" :
 *    - Applique PRG pour éviter doubles soumissions avec F5
 *    - Préserve pré-remplissage via flash data pour saisie rapide
 *    - Exclut champs problématiques (id, date_creation) du pré-remplissage
 */
```

- [ ] Ajouter commentaires inline dans les sections modifiées
- [ ] Documenter les champs exclus du prefill

**Validation:**
- Les commentaires sont clairs et utiles
- La stratégie PRG est explicitée

### ⏳ TÂCHE 6.2 : Mettre à jour la design note
**Durée:** 10 min
**Statut:** En attente
**Dépendances:** TÂCHE 5.1, TÂCHE 5.2

**Objectif:** Documenter que le problème a été corrigé

**Actions:**
- [ ] Ouvrir `doc/design_notes/prg_pattern_analysis.md`
- [ ] Marquer la section 6.1 comme résolue:

```markdown
### 6.1 Risque Critique : "Créer et continuer" ✅ CORRIGÉ

**Statut:** ✅ Corrigé le 2025-12-02

**Solution implémentée:** Option A - PRG avec préservation du pré-remplissage

**Modifications:**
- `application/libraries/Gvv_Controller.php:557-573` - Redirect avec flash data
- `application/libraries/Gvv_Controller.php:118-134` - Réinjection prefill
- `application/controllers/compta.php:334-342` - Idem pour override comptabilité
```

- [ ] Mettre à jour le score global et la conclusion

**Validation:**
- La documentation reflète l'état actuel du code

### ⏳ TÂCHE 6.3 : Mettre à jour le PRD
**Durée:** 5 min
**Statut:** En attente
**Dépendances:** TÂCHE 6.2

**Objectif:** Marquer le PRD comme implémenté

**Actions:**
- [ ] Ouvrir `doc/prds/prg_creer_et_continuer.md`
- [ ] Mettre à jour le statut: `**Statut:** Implémenté`
- [ ] Remplir la section 13.2 "Processus de Validation":
  - ✅ Revue PRD
  - ✅ Implémentation
  - ✅ Tests
  - ✅ Revue de code
  - En attente: Merge
- [ ] Compléter la section 13.3 "Document approuvé par"

---

## Phase 7 : Revue et Validation Finale (Mainteneur)

### ⏳ TÂCHE 7.1 : Revue de code par le mainteneur
**Durée:** 30 min (mainteneur)
**Statut:** En attente
**Dépendances:** Toutes les tâches précédentes

**Objectif:** Validation finale avant merge

**Points de revue:**
- [ ] Code suit les conventions GVV
- [ ] Pattern PRG correctement implémenté
- [ ] Commentaires clairs et utiles
- [ ] Tests passent
- [ ] Documentation à jour
- [ ] Pas de régression introduite

**Actions mainteneur:**
- [ ] Revue du code modifié
- [ ] Exécution des tests manuels
- [ ] Validation des modifications
- [ ] Approbation ou demande de corrections

### ⏳ TÂCHE 7.2 : Merge dans main
**Durée:** 5 min (mainteneur)
**Statut:** En attente
**Dépendances:** TÂCHE 7.1

**Actions mainteneur:**
- [ ] Créer un commit avec message descriptif:
```
Appliquer pattern PRG au workflow "Créer et continuer"

- Modifié Gvv_Controller::formValidation() pour redirect avec flash
- Modifié Gvv_Controller::create() pour réinjection prefill
- Modifié compta.php::formValidation() pour même comportement
- Exclusion champs id et date_creation du prefill
- Préserve workflow de saisie rapide (comptabilité, vols)
- Élimine risque de doublon avec F5

Closes: PRD prg_creer_et_continuer v2.0

🤖 Generated with Claude Code
Co-Authored-By: Claude <noreply@anthropic.com>
```

- [ ] Merge dans main
- [ ] Pousser vers le dépôt

---

## Métriques de Succès

### Critères d'Acceptation (du PRD)

| ID | Critère | Status |
|----|---------|--------|
| AC-001 | Après "Créer et continuer", l'URL est GET | ⏳ |
| AC-002 | Message de succès s'affiche | ⏳ |
| AC-003 | Formulaire pré-rempli avec valeurs utiles | ⏳ |
| AC-004 | Champs id et date_creation exclus | ⏳ |
| AC-005 | F5 ne crée pas de doublon | ⏳ |
| AC-006 | Bouton "Créer" standard fonctionne | ⏳ |
| AC-007 | Erreurs de validation sans redirect | ⏳ |
| AC-008 | Erreurs DB sans redirect | ⏳ |
| AC-009 | Workflow saisie rapide préservé | ⏳ |
| AC-REG-001 | Autres contrôleurs fonctionnent | ⏳ |
| AC-REG-002 | Tests PHPUnit passent | ⏳ |
| AC-REG-003 | Pas d'erreur PHP | ⏳ |
| AC-PERF-001 | Performance maintenue | ⏳ |

### Jalons

| Jalon | Statut | Date |
|-------|--------|------|
| M1: Modification code | ⏳ En attente | - |
| M2: Tests manuels | ⏳ En attente | - |
| M3: Tests automatisés | ⏳ En attente | - |
| M4: Documentation | ⏳ En attente | - |
| M5: Revue de code | ⏳ En attente | - |

---

## Risques Identifiés et Mitigations

| Risque | Probabilité | Impact | Mitigation | Status |
|--------|-------------|--------|------------|--------|
| Tests PHPUnit cassés | Faible | Moyen | Exécuter tests dès modification faite | ⏳ |
| Contrôleur non identifié | Faible | Moyen | Recherche exhaustive en TÂCHE 1.2 | ⏳ |
| Champs à exclure non identifiés | Moyen | Faible | Tests manuels complets, ajuster si besoin | ⏳ |
| Performance dégradée | Très faible | Faible | Flash data est très performant | ⏳ |
| Comportement inattendu navigateur | Très faible | Faible | Tester sur Chrome/Firefox | ⏳ |

---

## Notes d'Implémentation

### Champs à exclure du prefill par défaut
- `id` - Clé primaire auto-incrémentée
- `date_creation` - Timestamp de création
- Potentiellement d'autres selon domaine (à identifier par contrôleur)

### Champs à CONSERVER pour prefill
- Comptes comptables (compte1, compte2)
- Pilotes, avions, instructeurs
- Dates d'opération (modifiables)
- Montants (modifiables)
- Descriptions (modifiables)

### Points d'attention
1. **Sessions PHP:** S'assurer que les sessions sont configurées (déjà le cas dans GVV)
2. **Flash data lifetime:** Les flash data disparaissent après 1 request (comportement souhaité)
3. **Array merge order:** `array_merge($defaults, $prefill)` pour que prefill override defaults
4. **Empty vs unset:** Utiliser `unset()` pas `= null` pour exclusion propre

---

## Changements par Rapport au Comportement Actuel

### Comportement Actuel
```
POST /compta/create [données]
  → Création en DB
  → Affichage direct formulaire pré-rempli (pas de redirect)
  → URL = POST
  → F5 = Confirmation "Renvoyer formulaire ?" → DOUBLON si confirmé ⚠️
```

### Nouveau Comportement
```
POST /compta/create [données]
  → Création en DB
  → Stockage message + données en flash
  → REDIRECT 302 vers GET /compta/create
GET /compta/create
  → Récupération flash data
  → Affichage formulaire pré-rempli avec données flash
  → Message de succès affiché
  → F5 = Rechargement GET → Formulaire vide → PAS DE DOUBLON ✅
```

### Impact Utilisateur
- **Visible:** Aucun changement visible pour l'utilisateur normal
- **Technique:** URL devient GET après création
- **Sécurité:** F5 ne crée plus de doublon
- **Performance:** ~50ms de overhead (redirect) - négligeable

---

## Commandes Utiles

```bash
# Environnement
source setenv.sh

# Validation syntaxe
php -l application/libraries/Gvv_Controller.php
php -l application/controllers/compta.php

# Tests
./run-all-tests.sh
./run-all-tests.sh --coverage

# Recherche
grep -r "load_last_view" application/libraries/ application/controllers/ --include="*.php"
grep -r "continuer\|continue" application/controllers/ --include="*.php" -i

# Logs
sudo tail -f /var/log/apache2/error.log
sudo tail -n 100 /var/log/apache2/error.log | grep -i "php\|error"

# Base de données (vérifier doublons)
mysql -u gvv_user -p gvv2 -e "SELECT id, date, compte1, compte2, montant, description FROM ecritures ORDER BY id DESC LIMIT 10"
```

---

## Prochaines Étapes (Après Implémentation)

1. **Court terme (cette implémentation):**
   - [ ] Implémenter les modifications (Phases 2-3)
   - [ ] Tester exhaustivement (Phase 4-5)
   - [ ] Documenter (Phase 6)
   - [ ] Merger (Phase 7)

2. **Moyen terme (futures améliorations):**
   - Ajouter tests automatisés Playwright pour ce workflow
   - Considérer l'application du même pattern à d'autres workflows similaires

3. **Long terme (hors scope):**
   - Migration progressive vers AJAX (PRD séparé)
   - Validation temps réel côté client (PRD séparé)

---

**Plan créé le:** 2025-12-02
**Dernière mise à jour:** 2025-12-02
**Prêt pour implémentation:** ✅ Oui
