# Plan d'implémentation - Saisie Simplifiée de Cotisations

## Vue d'ensemble

Implémentation d'une interface unique pour enregistrer le paiement d'une cotisation et générer automatiquement les écritures comptables associées (encaissement + facturation) en une seule opération.

**Référence**: `doc/prds/saisie_cotisations_prd.md`

---

## Architecture

### Flux de données

```
Formulaire de saisie
    ↓
Validation (pas de double cotisation)
    ↓
Transaction atomique:
    1. Écriture encaissement (512 → 411)
    2. Écriture facturation (411 → 700)
    3. Création licence
    ↓
Message succès/erreur + rechargement
```

### Composants impliqués

1. **Contrôleur**: `application/controllers/compta.php` (nouvelle méthode `saisie_cotisation`)
2. **Vue**: `application/views/compta/bs_saisie_cotisation_formView.php`
3. **Modèles existants**: `ecritures_model`, `comptes_model`, `licences_model`
4. **Métadonnées**: Ajout dans `Gvvmetadata.php` si nécessaire
5. **Langues**: Ajout de clés dans les fichiers de langue (FR/EN/NL)
6. **Tests**: `application/tests/controllers/ComptaCotisationTest.php`

---

## Tâches d'implémentation

### ✅ Phase 0: Préparation et analyse
- [x] Lecture du PRD
- [x] Analyse du code `oneshot/process_cotisation`
- [x] Analyse du contrôleur `compta/recettes`
- [x] Analyse de la vue `compta/bs_formView.php`
- [x] Analyse de la structure des tables (licences, ecritures, comptes)

### Phase 1: Métadonnées et configuration

**Tâche 1.1**: Ajouter les métadonnées pour le formulaire de saisie de cotisation
- **Fichier**: `application/libraries/Gvvmetadata.php`
- **Actions**:
  - Définir les sélecteurs pour:
    - Compte banque (512): `compte_banque_selector`
    - Compte recette (700): `compte_recette_cotisation_selector`
    - Compte pilote (411): `compte_pilote_selector`
    - Membre/pilote: utiliser le sélecteur existant
  - Définir le champ `annee_cotisation` (type: int, année en cours par défaut)

**Tâche 1.2**: Ajouter les clés de langue
- **Fichiers**:
  - `application/language/french/compta_lang.php`
  - `application/language/english/compta_lang.php`
  - `application/language/dutch/compta_lang.php`
- **Clés à ajouter**:
  ```php
  // Français
  'gvv_compta_title_saisie_cotisation' => 'Enregistrement Cotisation'
  'gvv_compta_label_compte_banque' => 'Compte banque (512)'
  'gvv_compta_label_compte_pilote' => 'Compte pilote (411)'
  'gvv_compta_label_compte_recette' => 'Compte recette cotisation (700)'
  'gvv_compta_label_annee_cotisation' => 'Année de cotisation'
  'gvv_compta_label_montant' => 'Montant de la cotisation'
  'gvv_compta_error_double_cotisation' => 'Ce membre a déjà une cotisation pour cette année'
  'gvv_compta_success_cotisation' => 'Cotisation enregistrée avec succès'
  'gvv_compta_error_cotisation' => 'Erreur lors de l\'enregistrement de la cotisation'
  'gvv_compta_label_pilote' => 'Membre'
  ```
  - Traduire en anglais et néerlandais

### Phase 2: Modèle de données

**Tâche 2.1**: Vérifier/créer le modèle Licences
- **Fichier**: `application/models/licences_model.php`
- **Actions**:
  - Vérifier que le modèle existe et contient les méthodes nécessaires
  - Si nécessaire, créer la méthode `check_cotisation_exists($pilote, $year)` pour vérifier l'existence d'une cotisation
  - Créer la méthode `create_cotisation($pilote, $type, $year, $date, $comment)` pour créer une licence

**Tâche 2.2**: Étendre le modèle Comptes
- **Fichier**: `application/models/comptes_model.php`
- **Actions**:
  - Créer `selector_comptes_411()` : retourne sélecteur des comptes 411 avec pilote
  - Créer `selector_comptes_512()` : retourne sélecteur des comptes banque (512)
  - Créer `selector_comptes_700()` : retourne sélecteur des comptes recette cotisation (700-708)

### Phase 3: Contrôleur

**Tâche 3.1**: Créer la méthode de formulaire `saisie_cotisation()`
- **Fichier**: `application/controllers/compta.php`
- **Actions**:
  - Créer méthode `saisie_cotisation()` inspirée de `recettes()`
  - Initialiser les données du formulaire:
    ```php
    - date_op = date du jour
    - annee_cotisation = année en cours
    - montant = 0
    ```
  - Préparer les sélecteurs:
    - `pilote_selector` (membres actifs)
    - `compte_banque_selector` (comptes 512)
    - `compte_pilote_selector` (comptes 411 avec pilote)
    - `compte_recette_selector` (comptes 700)
  - Charger la vue `compta/bs_saisie_cotisation_formView`

**Tâche 3.2**: Créer la méthode de validation `formValidation_saisie_cotisation()`
- **Fichier**: `application/controllers/compta.php`
- **Actions**:
  - Récupérer les données POST:
    ```php
    - pilote
    - date_op
    - annee_cotisation
    - compte_banque (512)
    - compte_pilote (411)
    - compte_recette (700)
    - montant
    - description (libellé)
    - num_cheque (numéro de pièce)
    - type (mode de paiement)
    ```
  - Validation des règles:
    - Tous les champs obligatoires présents
    - Montant > 0
    - Date valide
    - Comptes existent et sont du bon type (512, 411, 700)
  - Vérifier absence de double cotisation:
    ```php
    $this->licences_model->check_cotisation_exists($pilote, $annee_cotisation)
    ```
  - Si validation OK → appeler `process_saisie_cotisation()`
  - Si validation KO → réafficher formulaire avec erreurs

**Tâche 3.3**: Créer la méthode de traitement `process_saisie_cotisation()`
- **Fichier**: `application/controllers/compta.php`
- **Actions**:
  - Démarrer transaction DB: `$this->db->trans_start()`
  - **Étape 1**: Créer écriture encaissement (512 → 411)
    ```php
    $ecriture_encaissement = [
        'annee_exercise' => date('Y'),
        'date_creation' => date('Y-m-d'),
        'date_op' => $date_op,
        'compte1' => $compte_banque (512),
        'compte2' => $compte_pilote (411),
        'montant' => $montant,
        'description' => $description,
        'type' => $type,
        'num_cheque' => $num_cheque,
        'saisie_par' => $this->dx_auth->get_username(),
        'gel' => 0,
        'club' => club_id,
        'categorie' => 0
    ];
    $this->ecritures_model->insert($ecriture_encaissement);
    ```
  - **Étape 2**: Créer écriture facturation (411 → 700)
    ```php
    $ecriture_facturation = [
        'annee_exercise' => date('Y'),
        'date_creation' => date('Y-m-d'),
        'date_op' => $date_op,
        'compte1' => $compte_pilote (411),
        'compte2' => $compte_recette (700),
        'montant' => $montant,
        'description' => $description,
        'type' => $type,
        'num_cheque' => $num_cheque,
        'saisie_par' => $this->dx_auth->get_username(),
        'gel' => 0,
        'club' => club_id,
        'categorie' => 0
    ];
    $this->ecritures_model->insert($ecriture_facturation);
    ```
  - **Étape 3**: Créer licence
    ```php
    $licence = [
        'pilote' => $pilote,
        'type' => 0, // Type par défaut (cotisation simple)
        'year' => $annee_cotisation,
        'date' => $date_op,
        'comment' => 'Cotisation enregistrée via saisie simplifiée'
    ];
    $this->licences_model->insert($licence);
    ```
  - **Étape 4**: Gérer les attachements (si fournis)
    - Récupérer les attachements temporaires de la session
    - Lier aux deux écritures créées
  - Compléter transaction: `$this->db->trans_complete()`
  - Vérifier statut: `$this->db->trans_status()`
  - Si succès:
    - Flashdata: `'success' => 'Cotisation enregistrée avec succès'`
    - Redirect vers `compta/saisie_cotisation`
  - Si erreur:
    - Rollback automatique
    - Flashdata: `'error' => 'Erreur lors de l\'enregistrement'`
    - Redirect vers `compta/saisie_cotisation`

### Phase 4: Vue

**Tâche 4.1**: Créer le fichier de vue
- **Fichier**: `application/views/compta/bs_saisie_cotisation_formView.php`
- **Actions**:
  - S'inspirer de `compta/bs_formView.php`
  - Structure HTML Bootstrap 5:
    ```html
    <div class="container-fluid">
      <h3>Enregistrement Cotisation</h3>

      <!-- Messages flash -->
      <div>checkalert()</div>

      <form name="saisie_cotisation" method="post">

        <!-- Section Membre et Cotisation -->
        <fieldset class="border p-3 mb-3">
          <legend>Membre et Cotisation</legend>

          <!-- Sélecteur de membre (pilote) -->
          <?= $this->gvvmetadata->input_field(...) ?>

          <!-- Année de cotisation -->
          <input type="number" name="annee_cotisation" value="<?= $annee_cotisation ?>" />
        </fieldset>

        <!-- Section Comptes -->
        <fieldset class="border p-3 mb-3">
          <legend>Comptes</legend>

          <!-- Compte banque (512) -->
          <?= form_dropdown('compte_banque', $compte_banque_selector, ...) ?>

          <!-- Compte pilote (411) -->
          <?= form_dropdown('compte_pilote', $compte_pilote_selector, ...) ?>

          <!-- Compte recette (700) -->
          <?= form_dropdown('compte_recette', $compte_recette_selector, ...) ?>
        </fieldset>

        <!-- Section Paiement -->
        <fieldset class="border p-3 mb-3">
          <legend>Paiement</legend>

          <!-- Date opération -->
          <?= $this->gvvmetadata->input_field('ecritures', 'date_op', ...) ?>

          <!-- Montant de la cotisation -->
          <input type="number" name="montant" step="0.01" />

          <!-- Libellé -->
          <input type="text" name="description" />

          <!-- Numéro de pièce -->
          <input type="text" name="num_cheque" />

          <!-- Type de paiement -->
          <?= form_dropdown('type', $type_paiement_selector, ...) ?>
        </fieldset>

        <!-- Section Justificatifs (optionnelle) -->
        <fieldset class="border p-3 mb-3">
          <legend>Justificatifs (optionnel)</legend>
          <!-- Réutiliser le code d'upload de compta/bs_formView.php -->
        </fieldset>

        <!-- Bouton validation -->
        <button type="submit" id="btnValidate" class="btn btn-primary">
          Enregistrer
        </button>
        <button type="button" class="btn btn-secondary" onclick="history.back()">
          Annuler
        </button>
      </form>
    </div>
    ```

**Tâche 4.2**: Ajouter JavaScript pour désactivation du bouton
- **Fichier**: Dans la vue `bs_saisie_cotisation_formView.php`
- **Actions**:
  - Après soumission réussie, désactiver le bouton "Enregistrer"
  - Réactiver le bouton si un champ est modifié
  - JavaScript:
    ```javascript
    $(document).ready(function() {
      var formChanged = false;
      var submitSuccess = <?= $this->session->flashdata('success') ? 'true' : 'false' ?>;

      // Désactiver bouton si succès précédent
      if (submitSuccess) {
        $('#btnValidate').prop('disabled', true);
      }

      // Réactiver bouton si changement
      $('form[name="saisie_cotisation"] input, form[name="saisie_cotisation"] select').on('change', function() {
        $('#btnValidate').prop('disabled', false);
      });
    });
    ```

### Phase 5: Intégration au menu

**Tâche 5.1**: Ajouter l'entrée au menu trésorerie
- **Fichier**: `application/views/bs_menu.php` ou fichier de configuration du menu
- **Actions**:
  - Ajouter lien "Saisie cotisation" dans le menu "Comptes" ou sous-menu trésorerie
  - URL: `compta/saisie_cotisation`
  - Icône suggérée: `bi-cash-coin` ou similaire

**Tâche 5.2**: Ajouter au dashboard trésorerie (si applicable)
- **Fichier**: `application/views/comptes/bs_tresorerie.php`
- **Actions**:
  - Ajouter un bouton/lien vers la saisie de cotisation
  - Exemple:
    ```html
    <div class="d-flex mb-3">
      <a href="<?= site_url('compta/saisie_cotisation') ?>" class="btn btn-primary">
        <i class="bi bi-cash-coin"></i> Enregistrer une cotisation
      </a>
    </div>
    ```

### Phase 6: Tests

**Tâche 6.1**: Créer tests unitaires du modèle
- **Fichier**: `application/tests/unit/models/LicencesModelTest.php`
- **Tests**:
  - `test_check_cotisation_exists_returns_true_when_exists()`
  - `test_check_cotisation_exists_returns_false_when_not_exists()`
  - `test_create_cotisation_inserts_record()`

**Tâche 6.2**: Créer tests d'intégration du contrôleur
- **Fichier**: `application/tests/controllers/ComptaSaisieCotisationTest.php`
- **Tests**:
  - `test_saisie_cotisation_form_loads_successfully()`
  - `test_validation_rejects_empty_fields()`
  - `test_validation_rejects_invalid_montant()`
  - `test_validation_rejects_double_cotisation()`
  - `test_process_saisie_cotisation_creates_two_ecritures()`
  - `test_process_saisie_cotisation_creates_licence()`
  - `test_transaction_rollback_on_error()`
  - `test_attachments_linked_to_ecritures()`

**Tâche 6.3**: Créer test Playwright end-to-end
- **Fichier**: `playwright/tests/saisie_cotisation.spec.js`
- **Scénario**:
  1. Naviguer vers `compta/saisie_cotisation`
  2. Remplir tous les champs du formulaire
  3. Soumettre
  4. Vérifier message de succès
  5. Vérifier bouton désactivé
  6. Modifier un champ
  7. Vérifier bouton réactivé
  8. Vérifier en DB: 2 écritures + 1 licence créées

**Tâche 6.4**: Test de régression sur double cotisation
- **Fichier**: Dans `ComptaSaisieCotisationTest.php`
- **Scénario**:
  1. Créer une première cotisation pour un membre
  2. Tenter de créer une deuxième cotisation pour le même membre et année
  3. Vérifier que l'erreur est affichée
  4. Vérifier qu'aucune écriture ni licence supplémentaire n'est créée

### Phase 7: Documentation

**Tâche 7.1**: Documenter la nouvelle fonctionnalité
- **Fichier**: `doc/features/saisie_cotisations.md`
- **Contenu**:
  - Description de la fonctionnalité
  - URL d'accès
  - Captures d'écran
  - Règles de validation
  - Comportement en cas d'erreur

**Tâche 7.2**: Mettre à jour le README si nécessaire
- **Fichier**: `README.md`
- **Actions**:
  - Ajouter mention de la nouvelle fonctionnalité dans la liste des features

---

## Checklist de validation

Avant de considérer l'implémentation comme terminée:

- [ ] Tous les tests PHPUnit passent (`./run-all-tests.sh`)
- [ ] Test Playwright end-to-end réussit
- [ ] Validation PHP sans erreur sur tous les fichiers créés/modifiés
- [ ] Test manuel de bout en bout dans l'environnement de dev
- [ ] Vérification en base de données: écritures et licences créées correctement
- [ ] Test de double cotisation: erreur affichée et pas de création
- [ ] Test des justificatifs: upload et liaison aux écritures
- [ ] Test de désactivation/réactivation du bouton
- [ ] Messages flash affichés correctement (succès/erreur)
- [ ] Code conforme aux conventions du projet (CodeIgniter 2.x, Bootstrap 5)
- [ ] Traductions complètes (FR/EN/NL)
- [ ] Documentation à jour

---

## Notes techniques

### Gestion des comptes

Les comptes sont identifiés par leur `codec`:
- **512**: Comptes banque
- **411**: Comptes pilotes (tiers clients)
- **700-708**: Comptes de recettes (cotisations)

Le champ `pilote` dans la table `comptes` (411) contient la référence au membre.

### Transaction atomique

Il est **critique** que la création des 2 écritures + 1 licence soit atomique. Utiliser:
```php
$this->db->trans_start();
// ... opérations ...
$this->db->trans_complete();
if ($this->db->trans_status() === FALSE) {
    // Erreur: rollback automatique
}
```

### Calcul du montant

Le trésorier saisit directement le montant effectif de la cotisation. Les réductions familiales éventuelles sont appliquées manuellement lors de la saisie du montant.

### Attachements

Réutiliser le système d'attachements existant de `compta/bs_formView.php`:
- Upload temporaire via AJAX
- Stockage en session pendant la saisie
- Liaison définitive aux écritures après validation

---

## Estimation

- **Phase 1**: 2h (métadonnées + langues)
- **Phase 2**: 2h (modèles)
- **Phase 3**: 4h (contrôleur avec validation et traitement)
- **Phase 4**: 4h (vue avec JavaScript)
- **Phase 5**: 1h (intégration menu)
- **Phase 6**: 4h (tests)
- **Phase 7**: 1h (documentation)

**Total estimé**: ~18 heures

---

## Statut

- [ ] Phase 0: Préparation ✅ (complétée)
- [ ] Phase 1: Métadonnées et configuration
- [ ] Phase 2: Modèle de données
- [ ] Phase 3: Contrôleur
- [ ] Phase 4: Vue
- [ ] Phase 5: Intégration au menu
- [ ] Phase 6: Tests
- [ ] Phase 7: Documentation

**Dernière mise à jour**: 2025-11-18
