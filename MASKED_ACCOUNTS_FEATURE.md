# Fonctionnalité Comptes Masqués - Documentation

## Vue d'ensemble

Cette fonctionnalité permet de masquer des comptes de la base de données pour qu'ils n'apparaissent plus dans:
- Les sélecteurs de comptes (dropdowns)
- Les listes de comptes
- La balance détaillée
- Les rapports de résultats

**Contrainte importante**: Un compte ne peut être masqué que si son solde est à 0 €.

## Étape 1: Migration de base de données ✅

### Fichiers créés/modifiés:

1. **Migration 047** - `application/migrations/047_add_masked_to_comptes.php`
   - Ajoute le champ `masked` (TINYINT(1), default 0) à la table `comptes`
   - Méthode `up()`: Ajoute la colonne
   - Méthode `down()`: Supprime la colonne (rollback)

2. **Configuration** - `application/config/migration.php`
   - Version mise à jour de 46 à 47

### Structure du champ:
```sql
ALTER TABLE comptes ADD COLUMN masked TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'Account is hidden from selectors and reports when set to 1';
```

## Étape 2: Modification du formulaire et du modèle ✅

### Contrôleur - `application/controllers/comptes.php`

#### Règles de validation
Ajout de la règle de validation personnalisée:
```php
protected $rules = [
    'club' => "callback_section_selected",
    'masked' => "callback_check_masked_with_balance"
];
```

#### Méthode `form_static_element()`
- Calcule le solde actuel du compte lors de l'édition
- Passe `compte_solde` et `can_mask` à la vue
- Permet d'afficher un message informatif à l'utilisateur

#### Callback de validation `check_masked_with_balance()`
```php
public function check_masked_with_balance($masked_value) {
    // Autorise si on ne masque pas (masked = 0)
    if (!$masked_value) return TRUE;
    
    // Si on essaie de masquer, vérifie le solde
    $compte_id = $this->input->post('id');
    $solde = $this->comptes_model->solde($compte_id);
    
    if ($solde != 0) {
        // Message d'erreur avec le solde formaté
        $msg = sprintf(
            $this->lang->line('gvv_comptes_error_cannot_mask_non_zero_balance'),
            number_format($solde, 2, ',', ' ')
        );
        $this->form_validation->set_message('check_masked_with_balance', $msg);
        return FALSE;
    }
    
    return TRUE;
}
```

### Modèle - `application/models/comptes_model.php`

#### Nouvelle méthode `solde()`
```php
public function solde($compte_id) {
    $solde = $this->ecritures_model->solde_compte($compte_id);
    return $solde;
}
```

Permet de récupérer le solde d'un compte pour vérifier s'il peut être masqué.

### Métadonnées - `application/libraries/Gvvmetadata.php`

Définition du champ `masked`:
```php
$this->field['comptes']['masked']['Name'] = 'Masqué';
$this->field['comptes']['masked']['Subtype'] = 'checkbox';
$this->field['comptes']['masked']['Type'] = 'tinyint';
$this->field['comptes']['masked']['Comment'] = $CI->lang->line("gvv_comptes_comment_masked");
```

### Fichiers de langue

#### Français - `application/language/french/comptes_lang.php`
```php
$lang['gvv_comptes_comment_masked'] = "Cocher pour masquer le compte des sélecteurs et rapports (uniquement possible si le solde est à 0)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Impossible de masquer un compte dont le solde n'est pas nul. Solde actuel : %s €";
$lang['gvv_comptes_field_masked'] = "Masqué";
$lang['gvv_comptes_masked_warning'] = "Attention : un compte avec un solde de %s € ne peut pas être masqué.";
$lang['gvv_comptes_can_mask'] = "Le solde est à 0, le compte peut être masqué.";
```

#### Anglais - `application/language/english/comptes_lang.php`
```php
$lang['gvv_comptes_comment_masked'] = "Check to hide the account from selectors and reports (only possible if balance is 0)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Cannot mask an account with a non-zero balance. Current balance: %s €";
$lang['gvv_comptes_field_masked'] = "Masked";
$lang['gvv_comptes_masked_warning'] = "Warning: an account with a balance of %s € cannot be masked.";
$lang['gvv_comptes_can_mask'] = "Balance is 0, account can be masked.";
```

#### Néerlandais - `application/language/dutch/comptes_lang.php`
```php
$lang['gvv_comptes_comment_masked'] = "Aanvinken om de rekening te verbergen in selectoren en rapporten (alleen mogelijk als saldo 0 is)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Kan geen rekening verbergen met een saldo dat niet nul is. Huidig saldo: %s €";
$lang['gvv_comptes_field_masked'] = "Verborgen";
$lang['gvv_comptes_masked_warning'] = "Waarschuwing: een rekening met een saldo van %s € kan niet worden verborgen.";
$lang['gvv_comptes_can_mask'] = "Saldo is 0, rekening kan worden verborgen.";
```

## Utilisation

### Pour masquer un compte:

1. Aller dans la gestion des comptes
2. Éditer le compte souhaité
3. Vérifier que le solde est à 0 €
4. Cocher la case "Masqué"
5. Enregistrer

### Comportement:

- ✅ **Solde = 0**: La case peut être cochée, le compte sera masqué
- ❌ **Solde ≠ 0**: Message d'erreur affiché avec le solde actuel
- Le compte masqué apparaîtra avec un indicateur visuel dans les listes

## Tests

✅ Tous les tests passent (424 tests, 1833 assertions)
✅ Validation PHP OK
✅ Multi-langue supporté

## Étapes suivantes (TODO)

### Étape 3: Modifier les sélecteurs ✅
- ✅ Filtrer les comptes masqués dans `comptes_model::selector()`
- ✅ Filtrer dans `comptes_model::selector_with_null()`
- ✅ Filtrer dans `comptes_model::selector_with_all()`
- ✅ Filtrer dans `comptes_model::list_of_account()`
- Les comptes masqués n'apparaissent plus dans aucun sélecteur de compte

### Étape 4: Modifier les rapports
- Balance détaillée: ✅ ne pas afficher les comptes masqués (via filtre, défaut = non masqués)
- Balance générale: ✅ ne pas afficher les comptes masqués (via filtre, défaut = non masqués)
- Rapport de résultats: TODO - ne pas afficher les comptes masqués
- ✅ Filtre ajouté pour afficher/masquer les comptes masqués

### Étape 5: Interface de gestion
- ✅ Filtre dans la liste des comptes pour voir les comptes masqués
- TODO: Ajouter un indicateur visuel (icône 👁️❌) pour les comptes masqués dans les listes
- TODO: Permettre de démasquer facilement un compte

## Fichiers modifiés (Étapes 1 & 2)

1. `application/migrations/047_add_masked_to_comptes.php` - Nouveau
2. `application/config/migration.php` - Version mise à jour
3. `application/controllers/comptes.php` - Validation + form_static_element + attributs dynamiques
4. `application/models/comptes_model.php` - Méthode solde()
5. `application/libraries/Gvvmetadata.php` - Définition champ masked
6. `application/libraries/MetaData.php` - Nouvelle méthode set_field_attr()
7. `application/views/comptes/bs_formView.php` - Ajout champ masked + messages conditionnels
8. `application/language/french/comptes_lang.php` - Traductions FR
9. `application/language/english/comptes_lang.php` - Traductions EN
10. `application/language/dutch/comptes_lang.php` - Traductions NL

## Interface utilisateur

### Formulaire d'édition de compte

Lorsque vous éditez un compte (`/comptes/edit/{id}`), vous verrez:

1. **Champ "Masqué"**: Une checkbox pour masquer le compte

2. **Message informatif** (selon le solde):
   - **Solde = 0**: 
     ```
     ℹ️ Le solde est à 0, le compte peut être masqué.
     ```
     → Checkbox activée, peut être cochée
   
   - **Solde ≠ 0**:
     ```
     ⚠️ Attention : un compte avec un solde de X,XX € ne peut pas être masqué.
     ```
     → Checkbox désactivée (grisée), ne peut pas être cochée
     → Tooltip sur la checkbox indique la raison

### Comportement de la validation

Si l'utilisateur tente de soumettre le formulaire avec la checkbox cochée alors que le solde n'est pas 0 (en bidouillant le HTML), la validation côté serveur bloquera avec le message d'erreur:
```
Impossible de masquer un compte dont le solde n'est pas nul. Solde actuel : X,XX €
```
