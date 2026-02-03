# Plan d'implémentation - Autorisations de Vol Solo

## Vue d'ensemble

Cette fonctionnalité permet aux instructeurs de créer et gérer des autorisations de vol solo pour les élèves pilotes. Les élèves peuvent consulter leurs autorisations dans la fiche de progression de leur formation.

## Structure de données

### Table `formation_autorisations_solo`

| Champ | Type | Description |
|-------|------|-------------|
| id | INT AUTO_INCREMENT | Identifiant unique |
| inscription_id | INT | FK vers formation_inscriptions |
| eleve_id | VARCHAR(25) | FK vers membres (mlogin) |
| instructeur_id | VARCHAR(25) | FK vers membres (mlogin) |
| date_autorisation | DATE | Date de l'autorisation |
| section_id | INT | FK vers sections |
| machine_id | VARCHAR(10) | Immatriculation de l'aéronef |
| consignes | TEXT | Instructions (min 250 caractères) |
| date_creation | DATETIME | Horodatage création |
| date_modification | DATETIME | Horodatage modification |

---

## Étapes d'implémentation

### Phase 1 : Base de données

**Étape 1.1 : Migration de base de données**
- [x] Créer `application/migrations/066_formation_autorisations_solo.php`
- [x] Définir la table avec les contraintes FK
- [x] Mettre à jour `application/config/migration.php` à la version 66
- [x] Tester la migration up/down

### Phase 2 : Couche modèle

**Étape 2.1 : Modèle**
- [x] Créer `application/models/formation_autorisation_solo_model.php`
- [x] Étendre `Common_Model`
- [x] Méthodes requises :
  - `get($id)` - Obtenir par ID
  - `get_full($id)` - Avec détails joints
  - `get_by_inscription($inscription_id)` - Pour une formation
  - `get_by_eleve($eleve_id)` - Pour un élève
  - `get_by_instructeur($instructeur_id)` - Pour un instructeur
  - `select_page($filters, $limit, $offset)` - Liste paginée
  - `count_filtered($filters)` - Comptage
  - `create($data)` - Création
  - `update($id, $data)` - Mise à jour
  - `delete($id)` - Suppression
  - `image($id)` - Représentation textuelle

### Phase 3 : Métadonnées

**Étape 3.1 : Gvvmetadata.php**
- [x] Ajouter les définitions de champs pour `formation_autorisations_solo`
- [x] Définir les types, sous-types et sélecteurs
- [x] Configurer les validations (consignes min 250 caractères)

### Phase 4 : Contrôleur

**Étape 4.1 : Contrôleur CRUD**
- [x] Créer `application/controllers/formation_autorisations_solo.php`
- [x] Méthodes :
  - `index()` - Liste des autorisations
  - `create()` - Formulaire de création
  - `store()` - Enregistrement
  - `edit($id)` - Formulaire de modification
  - `update($id)` - Mise à jour
  - `delete($id)` - Suppression
  - `detail($id)` - Vue détaillée
- [x] Contrôle d'accès via `Formation_access->is_instructeur()`

### Phase 5 : Internationalisation

**Étape 5.1 : Fichiers de langue**
- [x] `application/language/french/formation_lang.php` - Ajouter les clés
- [x] `application/language/english/formation_lang.php` - Traductions
- [x] `application/language/dutch/formation_lang.php` - Traductions

Clés à ajouter :
```
formation_autorisation_solo_title
formation_autorisation_solo_list
formation_autorisation_solo_create
formation_autorisation_solo_edit
formation_autorisation_solo_delete
formation_autorisation_solo_detail
formation_autorisation_solo_consignes
formation_autorisation_solo_consignes_minlength
formation_autorisation_solo_date
formation_autorisation_solo_machine
formation_autorisation_solo_empty
formation_autorisation_solo_confirm_delete
formation_autorisation_solo_created
formation_autorisation_solo_updated
formation_autorisation_solo_deleted
```

### Phase 6 : Vues

**Étape 6.1 : Vues instructeur**
- [ ] `application/views/formation_autorisations_solo/index.php` - Liste
- [ ] `application/views/formation_autorisations_solo/form.php` - Création/Édition
- [ ] `application/views/formation_autorisations_solo/detail.php` - Détail

**Étape 6.2 : Dashboard formation**
- [ ] Modifier `application/views/bs_dashboard.php`
- [ ] Ajouter la carte "Autorisations solo" dans la section Formation

**Étape 6.3 : Vue élève**
- [ ] Modifier `application/views/formation_progressions/mes_formations.php`
- [ ] Ajouter la section autorisations solo dans chaque carte de formation
- [ ] Ou modifier `application/views/formation_inscriptions/detail.php`

### Phase 7 : Tests

**Étape 7.1 : Tests PHPUnit**
- [ ] `application/tests/mysql/FormationAutorisationSoloTest.php` - Tests CRUD modèle
- [ ] Test de la migration up/down

**Étape 7.2 : Tests Playwright**
- [x] Test smoke : accès à la fonctionnalité
- [ ] Test CRUD basique

---

## Détails d'implémentation

### Contrôle d'accès

```php
// Dans le contrôleur - vérification instructeur
$this->load->library('formation_access');
if (!$this->formation_access->is_instructeur()) {
    show_error('Accès réservé aux instructeurs', 403);
}
```

### Validation des consignes

La validation du minimum de 250 caractères sera effectuée :
1. Côté serveur dans le contrôleur
2. Côté client avec attribut HTML `minlength="250"`

### Intégration dashboard

Ajouter après la carte "Ré-entrainement" dans `bs_dashboard.php` :

```html
<div class="col-6 col-md-4 col-lg-3 col-xl-2">
    <div class="sub-card text-center">
        <i class="fas fa-clipboard-check text-danger"></i>
        <div class="card-title">Autorisations Solo</div>
        <div class="card-text text-muted">Gérer</div>
        <a href="<?= controller_url('formation_autorisations_solo') ?>"
           class="btn btn-danger btn-sm">Gérer</a>
    </div>
</div>
```

### Vue élève dans la progression

Dans la vue de détail de formation, ajouter une section listant les autorisations solo :

```html
<!-- Autorisations de vol solo -->
<div class="card mt-3">
    <div class="card-header bg-warning text-dark">
        <h5>Autorisations de vol solo</h5>
    </div>
    <div class="card-body">
        <!-- Liste des autorisations -->
    </div>
</div>
```

---

## Dépendances

- Migration 066 dépend de : migration 063 (tables formation)
- Contrôleur dépend de : modèle, Formation_access
- Vues dépendent de : fichiers de langue, métadonnées

## Risques et mitigations

| Risque | Mitigation |
|--------|------------|
| FK vers machines (planeur/avion) | Utiliser VARCHAR sans FK, vérifier à l'exécution |
| Incohérence section/inscription | Hériter section de l'inscription si disponible |
| Consignes < 250 caractères | Double validation client + serveur |

---

## Critères de complétion

1. [x] Migration exécutée sans erreur
2. [x] CRUD fonctionnel pour instructeurs
3. [x] Autorisations visibles dans fiche élève
4. [x] Carte visible dans dashboard formation
5. [x] Tests PHPUnit passent (855 tests, 0 failures)
6. [x] Test Playwright smoke passe
7. [x] Traductions FR/EN/NL complètes
