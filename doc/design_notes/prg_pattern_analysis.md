# Analyse du Pattern Post-Redirect-Get (PRG) dans GVV

**Date:** 2025-12-02
**Auteur:** Analyse automatisée du codebase
**Statut:** Documentation architecturale

## Contexte

Le pattern Post-Redirect-Get (PRG) est une bonne pratique web standard qui consiste à :
1. Recevoir une soumission POST
2. Traiter les données côté serveur
3. **Rediriger (302/303) vers une page GET**

Ainsi, même si l'utilisateur recharge la page (F5), c'est un GET qui est réexécuté, pas le POST, évitant les doubles soumissions.

## Résumé Exécutif

Le pattern PRG est **partiellement appliqué** dans GVV avec une approche pragmatique :
- ✅ **Appliqué systématiquement après succès** pour éviter les doubles soumissions
- ❌ **Non appliqué après erreurs** pour préserver les données saisies et afficher les messages d'erreur
- ⚠️ **Exception problématique** : "Créer et continuer" ne redirige pas

**Impact global :** Bon - Une seule vulnérabilité mineure identifiée dans le workflow "Créer et continuer".

---

## 1. CAS OÙ LE PATTERN PRG EST APPLIQUÉ

### 1.1 Pattern Standard dans `Gvv_Controller`

**Fichier:** `application/libraries/Gvv_Controller.php`

#### Après modification réussie
```php
// Ligne 611 dans formValidation()
$this->pop_return_url();  // Fait un redirect (ligne 728 ou 735)
```

#### Après création réussie
```php
// Ligne 624 dans formValidation()
$this->validationOkPage($processed_data, $button);  // Redirect (ligne 380 ou 384)
```

#### Après suppression
```php
// Ligne 148 dans delete()
redirect($this->controller . "/page");
```

**Mécanisme `pop_return_url()` :**
```php
// Ligne 709-736
function pop_return_url($skip = 0) {
    // ... validation et stack management
    redirect($url);  // ← TOUJOURS un redirect
}
```

### 1.2 Exemples dans les Contrôleurs Spécifiques

#### `email_lists.php` (Bon exemple moderne)
```php
// Ligne 135 - Après création
redirect('email_lists/edit/' . $list_id);

// Ligne 375 - Après modification
redirect('email_lists/edit/' . $id);

// Ligne 428 - Après suppression
redirect('email_lists');
```

#### `procedures.php`
```php
// Ligne 206 - Après création
redirect('procedures');

// Ligne 260 - Après modification
redirect("procedures/view/$id");

// Ligne 289 - Après suppression
redirect('procedures');
```

#### `compta.php` (Cas complexe)
```php
// Ligne 346 - Après création avec bouton "Créer"
redirect("compta/journal_compte/" . $processed_data['compte1']);

// Ligne 358 - Après modification avec gel d'écriture
redirect("compta/journal_compte/" . $compte);
```

#### `authorization.php`
```php
// Lignes 459, 528, 578, 580
redirect('authorization/roles/' . $message);  // Après CRUD sur rôles
```

#### Autres contrôleurs appliquant PRG
- `achats.php` : redirect après create/edit/delete
- `avion.php`, `planeur.php` : redirect après modifications
- `membre.php` : redirect après modifications membres
- `tarifs.php`, `reports.php` : redirect après clonage
- `backend.php` : redirect après gestion utilisateurs
- `comptes.php` : redirect après suppressions

### 1.3 Filtres et Recherches

**Pattern systématique :**
```php
// Validation du filtre → redirect vers page filtrée
public function filterValidation() {
    $this->active_filter($this->filter_variables);
    redirect($this->controller . '/page');  // ← PRG appliqué
}
```

**Exemples :**
- `tarifs.php:79`
- `vols_planeur.php:813`
- `membre.php:246`
- `pompes.php:221`
- `event.php:198`

---

## 2. CAS OÙ LE PATTERN PRG N'EST PAS APPLIQUÉ

### 2.1 Pattern Standard dans `Gvv_Controller`

#### Après échec de validation
```php
// Ligne 648 dans formValidation()
load_last_view($this->form_view, $this->data);  // ← PAS de redirect
```

**Justification :**
- Affiche les erreurs de validation à l'utilisateur
- Préserve toutes les données saisies
- Permet correction immédiate sans re-saisie

#### Après erreur base de données (CREATE)
```php
// Ligne 536 dans formValidation()
$this->data['message'] = '<div class="text-danger">' . $msg . '</div>';
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);  // ← PAS de redirect
```

#### Après erreur base de données (UPDATE)
```php
// Ligne 604 dans formValidation()
$this->data['message'] = '<div class="text-danger">' . $msg . '</div>';
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);  // ← PAS de redirect
```

**Justification :**
- Affiche le message d'erreur technique (duplicate key, FK constraint, etc.)
- Préserve les données pour que l'utilisateur puisse corriger
- Erreur empêche de toute façon la soumission

#### ⚠️ Après création réussie avec "Créer et continuer"
```php
// Ligne 571 dans formValidation()
$image = $this->gvv_model->image($id);
$msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
$this->data['message'] = '<div class="text-success">' . $msg . '</div>';
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);  // ← PAS de redirect ⚠️
```

**Problème identifié :**
- **État après création** : Formulaire PRÉ-REMPLI avec les valeurs créées (car `$this->data` contient les valeurs POST)
- **RISQUE** : F5 re-soumet le POST original → création d'un doublon identique
- **Mécanisme** : Le navigateur détecte une page POST et propose "Confirmer nouvelle soumission"
- **Impact** : Moyen - L'utilisateur peut ne pas réaliser qu'un F5 recrée un élément
- **Fréquence** : Rare (workflow de création multiple)
- **Aucune protection** : Pas de contrainte UNIQUE, pas de jeton CSRF
- **Note importante** : Le pré-remplissage EST utile pour la saisie rapide d'éléments similaires (comptabilité, vols)
- **Voir recommandation section 6.1 - Option A retenue**

### 2.2 Exemples dans les Contrôleurs Spécifiques

#### `procedures.php`
```php
// Ligne 175 - Après échec validation
if ($this->form_validation->run() === FALSE) {
    $this->create();  // ← PAS de redirect, réaffiche form avec erreurs
    return;
}

// Ligne 232 - Après échec validation UPDATE
if ($this->form_validation->run() === FALSE) {
    $this->edit($id);  // ← PAS de redirect
    return;
}

// Lignes 200, 212, 263 - Après erreurs métier
$this->create();  // ou $this->edit($id) ← PAS de redirect
```

#### `email_lists.php`
```php
// Ligne 113 - Échec validation
if ($this->form_validation->run() === FALSE) {
    return $this->create();  // ← PAS de redirect
}

// Ligne 130 - Erreur création
if (!$list_id) {
    $this->session->set_flashdata('error', ...);
    return $this->create();  // ← PAS de redirect
}
```

#### `compta.php`
```php
// Ligne 341 - "Créer et continuer" ⚠️
$this->data['message'] = '<div class="text-success">' . $msg . '</div>';
$this->form_static_element($action);
load_last_view($this->form_view, $this->data);  // ← RISQUE double soumission

// Ligne 367 - Échec validation
load_last_view($this->form_view, $this->data);  // ← PAS de redirect (normal)
```

---

## 3. CAS PARTICULIERS

### 3.1 Requêtes AJAX

**Ne suivent PAS le pattern PRG** - C'est normal et attendu.

Les requêtes AJAX retournent du JSON au lieu de faire des redirects :

```php
// Exemple : compta.php ligne 46
if (!$this->input->is_ajax_request()) {
    redirect("auth/login");
}

// Exemple : licences.php, vols_decouverte.php
if ($this->input->is_ajax_request()) {
    echo json_encode(array('success' => true, ...));
    exit();
} else {
    redirect(controller_url("..."));
}
```

**Contrôleurs avec logique AJAX :**
- `compta.php`
- `licences.php`
- `vols_decouverte.php`
- `user_roles_per_section.php`

### 3.2 Workflows Spéciaux

#### Redirections conditionnelles (`achats.php`)
```php
// Lignes 124, 131, 138 - Si achat généré par vol/pompe
if ($vol != 0) {
    redirect("vols_avion/edit/" . $vol);  // ← Redirect vers l'entité parente
    return;
}
```

#### Redirections avec paramètres (`authorization.php`)
```php
// Message passé dans URL (évite les flash messages)
redirect('authorization/roles/' . $this->lang->line('authorization_role_created'));
```

---

## 4. STATISTIQUES DE COUVERTURE

### Analyse quantitative du codebase

**✅ PRG appliqué systématiquement :**
| Cas | Couverture | Contrôleurs |
|-----|-----------|-------------|
| Créations réussies | ~95% | Tous sauf "Créer et continuer" |
| Modifications réussies | ~100% | Tous |
| Suppressions | ~100% | Tous |
| Filtres/Recherches | ~100% | Tous |
| Redirections inter-contrôleurs | ~100% | Tous |

**❌ PRG non appliqué (volontairement) :**
| Cas | Couverture | Justification |
|-----|-----------|---------------|
| Échecs de validation | ~100% | Préserver données + afficher erreurs |
| Erreurs base de données | ~100% | Afficher erreur technique + préserver données |
| "Créer et continuer" | ~100% | **⚠️ PROBLÉMATIQUE - voir recommandations** |
| Requêtes AJAX | 0% | Normal - retour JSON |

### Contrôleurs analysés (50+)

**Appliquant strictement PRG après succès :**
- `email_lists`, `procedures`, `authorization`, `reports`, `tarifs`
- `avion`, `planeur`, `membre`, `backend`, `comptes`
- `pompes`, `event`, `vols_planeur`, `vols_avion`, `tickets`

**Avec logique complexe mais conforme :**
- `compta` (redirections conditionnelles selon bouton/gel)
- `achats` (redirections vers entités parentes)
- `licences` (mix AJAX/redirect)

**Anciens contrôleurs (legacy) :**
- `tests`, `import`, `coverage`, `dbchecks` (mode développement/maintenance)

---

## 5. AVANTAGES DE L'APPROCHE ACTUELLE

### ✅ Avec PRG (après succès)

| Avantage | Impact | Bénéfice Utilisateur |
|----------|--------|---------------------|
| **Évite doubles soumissions** | Critique | Pas de doublons avec F5 |
| **URL propre** | Moyen | URL reflète l'état réel |
| **Bookmarkable** | Faible | Peut sauvegarder l'URL de résultat |
| **SEO-friendly** | Faible | Pas applicable (app privée) |
| **Messages flash** | Élevé | Feedback clair et non intrusif |

### ✅ Sans PRG (après erreur)

| Avantage | Impact | Bénéfice Utilisateur |
|----------|--------|---------------------|
| **Préserve données saisies** | Critique | Pas de re-saisie complète |
| **Erreurs précises** | Élevé | Sait exactement quoi corriger |
| **Contexte intact** | Élevé | Voit données + erreurs ensemble |
| **Pas de perte info** | Critique | Correction immédiate possible |

---

## 6. RISQUES ET RECOMMANDATIONS

### 6.1 Risque Critique : "Créer et continuer" ⚠️

**Localisation :**
- `application/libraries/Gvv_Controller.php:571`
- `application/controllers/compta.php:341`

**Problème :**
```php
// Après création réussie avec "Créer et continuer"
$this->data['message'] = '<div class="text-success">Création réussie</div>';
load_last_view($this->form_view, $this->data);  // ← F5 = doublon!
```

**Scénario d'erreur (comportement actuel) :**
1. Utilisateur crée un enregistrement (ex: date=01/12, montant=100€) avec "Créer et continuer"
2. Page affiche formulaire **pré-rempli** avec ces données + message succès
3. L'URL reste POST (pas de redirect)
4. Utilisateur appuie sur F5 (rafraîchir)
5. Navigateur demande : "Confirmer nouvelle soumission du formulaire ?"
6. **→ POST original re-soumis → Crée un doublon identique (date=01/12, montant=100€)**

**Solution recommandée : Option A (PRG + préservation pré-remplissage) ✅**

Cette option a été retenue car :
- ✅ Élimine le risque F5
- ✅ Préserve la fonctionnalité utile de pré-remplissage
- ✅ Maintient le workflow de saisie rapide pour comptabilité et vols

```php
// AVANT (vulnérable)
$this->data['message'] = '<div class="text-success">' . $msg . '</div>';
load_last_view($this->form_view, $this->data);

// APRÈS (sécurisé + fonctionnel)
$image = $this->gvv_model->image($id);
$msg = $image . ' ' . $this->lang->line("gvv_succesful_creation");
$this->session->set_flashdata('success', $msg);

// Stocker données pour pré-remplissage (sauf champs à exclure)
$prefill_data = $processed_data;
unset($prefill_data['id']);
unset($prefill_data['date_creation']);
$this->session->set_flashdata('prefill_data', $prefill_data);

redirect($this->controller . "/create");  // ← PRG appliqué
```

```php
// Dans create() - réinjecter les données
$table = $this->gvv_model->table();
$this->data = $this->gvvmetadata->defaults_list($table);

// Réinjecter données pré-remplissage si disponibles
$prefill = $this->session->flashdata('prefill_data');
if ($prefill) {
    $this->data = array_merge($this->data, $prefill);
}

$this->form_static_element(CREATION);
return load_last_view($this->form_view, $this->data, $this->unit_test);
```

**Implémentation :**
1. Modifier `Gvv_Controller::formValidation()` ligne 557-573
2. Modifier `Gvv_Controller::create()` ligne 118-134
3. Modifier `Compta::formValidation()` ligne 334-342
4. Vérifier autres contrôleurs utilisant ce pattern

**Impact de la modification :**
- ✅ Aucune perte de fonctionnalité
- ✅ Formulaire reste **pré-rempli** pour saisie rapide (comptabilité, vols)
- ✅ Message de succès s'affiche via flash
- ✅ F5 n'a plus d'effet → pas de doublon
- ✅ Meilleure UX : sécurité + productivité

**Alternative rejetée : Option B (PRG simple, formulaire vide)**
- ✅ Élimine le risque F5
- ❌ Perte du workflow de saisie rapide
- ❌ Impact négatif sur productivité (comptabilité, vols)

### 6.2 Amélioration : Standardisation

**Recommandation :** Documenter explicitement la stratégie PRG

**Fichier à créer :** `application/libraries/Gvv_Controller.php` (commentaire de classe)

```php
/**
 * Contrôleur GVV parent - Pattern de gestion des formulaires
 *
 * STRATÉGIE POST-REDIRECT-GET (PRG) :
 *
 * ✅ AVEC REDIRECT (PRG appliqué) :
 *    - Après création/modification/suppression réussie
 *    - Après validation de filtres
 *    - Utiliser : redirect(), pop_return_url(), validationOkPage()
 *
 * ❌ SANS REDIRECT (affichage direct) :
 *    - Après échec de validation (préserver données + afficher erreurs)
 *    - Après erreur DB (afficher erreur technique + préserver données)
 *    - Utiliser : load_last_view() avec $this->data
 *
 * ⚠️ EXCEPTION À ÉVITER :
 *    - "Créer et continuer" DOIT rediriger pour éviter doubles soumissions
 */
```

### 6.3 Amélioration Future : Migration AJAX

**Priorité :** Basse (amélioration UX, pas sécurité)

**Bénéfices potentiels :**
- Validation en temps réel
- Soumission sans rechargement complet
- Meilleure expérience mobile
- Feedback plus rapide

**Contrôleurs candidats pour migration :**
- `compta.php` (formulaire d'écriture complexe)
- `email_lists.php` (déjà partiellement en AJAX)
- `vols_planeur.php`, `vols_avion.php` (formulaires longs)

---

## 7. GUIDES D'IMPLÉMENTATION

### 7.1 Pour Nouveau Contrôleur

**Pattern recommandé :**

```php
class Mon_nouveau_controller extends Gvv_Controller {

    public function formValidation($action, $return_on_success = false) {
        $button = $this->input->post('button');

        // Boutons spéciaux
        if ($button == "Abandonner") {
            redirect("welcome");  // ← PRG appliqué
        }

        // Validation
        $this->form_validation->set_rules(...);

        if ($this->form_validation->run()) {
            // ✅ SUCCÈS → REDIRECT (PRG)

            if ($action == CREATION) {
                $id = $this->gvv_model->create($processed_data);

                if (!$id) {
                    // Erreur création → afficher erreur
                    $this->data['message'] = '<div class="text-danger">Erreur</div>';
                    load_last_view($this->form_view, $this->data);  // ← Pas de redirect (OK)
                    return;
                }

                // Succès → REDIRECT
                $this->session->set_flashdata('success', 'Création réussie');
                redirect($this->controller . "/page");  // ← PRG appliqué ✅

            } elseif ($action == MODIFICATION) {
                $this->gvv_model->update($this->kid, $processed_data, $id);

                // Succès → REDIRECT
                $this->pop_return_url();  // ← PRG appliqué ✅
            }
        } else {
            // ❌ ÉCHEC VALIDATION → PAS DE REDIRECT (OK)
            // Les erreurs et données sont préservées automatiquement
        }

        // Réaffichage form avec erreurs (pas de redirect)
        $this->form_static_element($action);
        load_last_view($this->form_view, $this->data);
    }
}
```

### 7.2 Checklist de Validation PRG

Avant de valider un nouveau contrôleur, vérifier :

- [ ] ✅ Création réussie → `redirect()` ou `pop_return_url()`
- [ ] ✅ Modification réussie → `redirect()` ou `pop_return_url()`
- [ ] ✅ Suppression → `redirect()` (toujours)
- [ ] ✅ Filtre validé → `redirect()`
- [ ] ❌ Échec validation → `load_last_view()` (pas de redirect)
- [ ] ❌ Erreur DB → `load_last_view()` avec message (pas de redirect)
- [ ] ⚠️ **PAS de "Créer et continuer" sans redirect**
- [ ] 🔵 AJAX → Retour JSON (pas de redirect)

### 7.3 Test Manuel PRG

**Scénario de test après modification de code :**

1. **Test création réussie :**
   - Remplir formulaire et soumettre
   - Vérifier redirect vers page GET
   - Appuyer sur F5 → doit recharger page GET (pas recréer)

2. **Test "Créer et continuer" (si applicable) :**
   - Créer avec bouton "Créer et continuer"
   - Vérifier redirect vers `/create`
   - Appuyer sur F5 → doit afficher form vide (pas recréer)

3. **Test échec validation :**
   - Soumettre form invalide
   - Vérifier affichage erreurs + données préservées
   - Vérifier URL = POST (pas de redirect)
   - Appuyer sur F5 → navigateur demande confirmation re-soumission (OK)

4. **Test modification réussie :**
   - Modifier un enregistrement
   - Vérifier redirect vers page liste ou détail
   - Appuyer sur F5 → doit recharger page GET

---

## 8. RÉFÉRENCES TECHNIQUES

### 8.1 Fichiers Clés

| Fichier | Lignes clés | Description |
|---------|-------------|-------------|
| `application/libraries/Gvv_Controller.php` | 463-650 | `formValidation()` - logique principale |
| | 709-736 | `pop_return_url()` - redirect après succès |
| | 377-385 | `validationOkPage()` - redirect conditionnel |
| `application/controllers/compta.php` | 274-368 | Override complexe avec cas spéciaux |
| `application/controllers/email_lists.php` | 100-136, 216-376 | Bon exemple moderne |
| `application/controllers/procedures.php` | 170-265 | Pattern mixte bien implémenté |

### 8.2 Fonctions CodeIgniter Utilisées

```php
redirect($uri, $method = 'auto', $code = NULL)
// → Fait une redirection HTTP 302 (ou 303 si $method='location')
// → Termine l'exécution du script

load_last_view($view, $data, $return = FALSE)
// → Charge une vue sans redirect
// → Préserve $data pour affichage
// → Utilisé pour afficher erreurs/formulaires

$this->session->set_flashdata($key, $value)
// → Stocke un message pour le prochain request (redirect)
// → Utilisé avec PRG pour afficher succès après redirect

current_url()
// → Retourne l'URL actuelle
// → Utilisé pour valider redirections et éviter boucles
```

### 8.3 Codes HTTP

| Code | Nom | Usage dans GVV |
|------|-----|----------------|
| 302 | Found (Temporary Redirect) | Default de `redirect()` |
| 303 | See Other | `redirect($url, 'location')` |
| 200 | OK | `load_last_view()` - affichage direct |

---

## 9. HISTORIQUE ET ÉVOLUTION

### État Actuel (2025)

- **Pattern PRG** largement adopté depuis les débuts du projet (2011)
- **Approche pragmatique** : PRG après succès, affichage direct après erreur
- **Cohérence** : ~95% des contrôleurs suivent le pattern standard
- **Vulnérabilité mineure** : "Créer et continuer" identifiée

### Migration PHPUnit (en cours)

Le projet migre actuellement de CI Unit_test vers PHPUnit. Cette migration n'impacte PAS la logique PRG, mais les tests doivent vérifier :
- Redirections après succès
- Affichage direct après échec
- Messages flash après redirect

### Recommandations Futures

1. **Court terme** : Corriger "Créer et continuer"
2. **Moyen terme** : Ajouter tests automatisés PRG
3. **Long terme** : Évaluer migration progressive vers AJAX

---

## 10. CONCLUSION

### Résumé de l'Analyse

Le codebase GVV implémente le pattern PRG de manière **mature et réfléchie** :

✅ **Points forts :**
- Application systématique après succès (évite doublons)
- Préservation données après erreur (bonne UX)
- Cohérence à travers ~50 contrôleurs
- Utilisation appropriée des flash messages

⚠️ **Point d'attention :**
- Cas "Créer et continuer" ne redirige pas (risque doublon avec F5)
- **Impact faible** : Workflow rare, utilisateurs avancés
- **Solution simple** : Appliquer redirect + flash message

### Score Global

| Critère | Score | Commentaire |
|---------|-------|-------------|
| **Sécurité** | 9/10 | Une vulnérabilité mineure ("Créer et continuer") |
| **Cohérence** | 9.5/10 | Pattern uniforme dans tout le codebase |
| **Maintenabilité** | 9/10 | Logique centralisée dans `Gvv_Controller` |
| **UX** | 9/10 | Bon équilibre entre sécurité et ergonomie |
| **Documentation** | 7/10 | Pattern implicite, manque documentation |

**Score total : 8.7/10** - Très bonne implémentation

### Actions Recommandées

| Priorité | Action | Effort | Impact |
|----------|--------|--------|--------|
| 🔴 Haute | Corriger "Créer et continuer" | Faible (2h) | Moyen |
| 🟡 Moyenne | Ajouter documentation inline | Faible (1h) | Faible |
| 🟢 Basse | Tests automatisés PRG | Moyen (1j) | Moyen |
| 🔵 Future | Évaluation AJAX | Élevé | Élevé |

---

**Dernière mise à jour :** 2025-12-02
**Prochaine revue suggérée :** Après correction "Créer et continuer"
