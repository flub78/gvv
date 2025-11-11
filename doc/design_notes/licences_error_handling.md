# Gestion des Erreurs - Interface Licences Checkbox

## Objectif

Capturer les erreurs de base de données lors de la création/suppression de licences et les afficher à l'utilisateur via une popup JavaScript.

## Problème Initial

Lorsqu'une erreur SQL se produisait (par exemple : `Field 'comment' doesn't have a default value`), l'opération échouait silencieusement côté serveur mais le client recevait `success: true`. L'utilisateur ne voyait aucune erreur.

## Solution Implémentée

### 1. Côté Serveur (PHP)

#### Désactivation du Debug DB
```php
$this->db->db_debug = FALSE;
```
Empêche CodeIgniter d'afficher directement les erreurs SQL et permet de les gérer manuellement.

#### Vérification des Erreurs (CodeIgniter 2.x)
```php
// CodeIgniter 2.x utilise _error_message() et _error_number()
$db_error_msg = $this->db->_error_message();
$db_error_num = $this->db->_error_number();

if (!empty($db_error_msg) || !empty($db_error_num)) {
    $error_text = "Error #$db_error_num: $db_error_msg";
    log_message('error', "Database error creating licence: " . $error_text);

    if ($is_ajax) {
        while (ob_get_level()) {
            ob_end_clean();
        }
        header('Content-Type: application/json');
        echo json_encode(array(
            'success' => false,
            'error' => 'Erreur de base de données: ' . $db_error['message']
        ));
        exit();
    }
}
```

#### Réponse JSON en Cas d'Erreur
- `success: false` : indique l'échec
- `error: "message"` : contient le message d'erreur

### 2. Côté Client (JavaScript)

Le code JavaScript vérifie déjà `response.success` et affiche une popup d'erreur :

```javascript
success: function(response) {
    console.log('AJAX success:', response);
    checkbox.prop('disabled', false);

    if (response.success) {
        console.log('Licence mise à jour avec succès');
    } else {
        console.error('Operation failed:', response.error);
        alert('Erreur: ' + response.error);
        checkbox.prop('checked', !isChecked);
    }
}
```

## Flux Complet

### Cas d'Erreur

1. **Utilisateur coche une checkbox**
2. **Requête AJAX** envoyée vers `licences/set/...`
3. **Serveur** tente de créer la licence
4. **Erreur SQL** détectée (ex: champ manquant)
5. **Serveur** retourne `{"success": false, "error": "Erreur de base de données: ..."}`
6. **JavaScript** reçoit la réponse dans le callback `success`
7. **Vérification** de `response.success` → `false`
8. **Popup JavaScript** affiche le message d'erreur
9. **Checkbox** est décochée (retour à l'état initial)

### Cas de Succès

1. **Utilisateur coche une checkbox**
2. **Requête AJAX** envoyée
3. **Serveur** crée la licence avec succès
4. **Aucune erreur** détectée
5. **Serveur** retourne `{"success": true, "data": {...}}`
6. **JavaScript** reçoit la réponse
7. **Vérification** de `response.success` → `true`
8. **Message console** "Licence mise à jour avec succès"
9. **Checkbox** reste cochée

## Test de la Gestion d'Erreur

Pour tester que la gestion d'erreur fonctionne, le champ `comment` a été temporairement omis de l'array `$row` :

```php
$row = array (
    'pilote' => $pilote,
    'year' => $year,
    'type' => $type,
    'date' => "$year-01-01"
    // Note: le champ 'comment' sera ajouté après avoir testé la gestion d'erreur
);
```

### Test Attendu

1. Ouvrir la console du navigateur
2. Cocher une checkbox
3. **Observer** :
   - Console : `AJAX success: {success: false, error: "..."}`
   - Console : `Operation failed: Erreur de base de données: Field 'comment' doesn't have a default value`
   - **Popup** : "Erreur: Erreur de base de données: Field 'comment' doesn't have a default value"
   - La checkbox revient à l'état décoché

## Correction du Problème 'comment'

Une fois la gestion d'erreur validée, ajouter le champ `comment` avec une valeur par défaut :

```php
$row = array (
    'pilote' => $pilote,
    'year' => $year,
    'type' => $type,
    'date' => "$year-01-01",
    'comment' => ''  // Valeur par défaut pour le champ comment
);
```

## Corrections Apportées

### 1. Propriété Manquante `$use_new_auth`
**Erreur** : `Undefined property: Licences::$use_new_auth`

**Correction** : Ajout de la propriété dans la classe :
```php
protected $use_new_auth = FALSE; // Use legacy authorization system
```

### 2. Méthode `error()` Inexistante
**Erreur** : `Call to undefined method CI_DB_mysqli_driver::error()`

**Correction** : Utilisation des méthodes CodeIgniter 2.x :
- `$this->db->_error_message()` au lieu de `$this->db->error()['message']`
- `$this->db->_error_number()` pour le numéro d'erreur

## Fichiers Modifiés

- `application/controllers/licences.php` :
  - Ajout de la propriété `$use_new_auth = FALSE`
  - Méthode `set()` : ajout de la gestion d'erreur DB avec méthodes CI 2.x
  - Méthode `switch_it()` : ajout de la gestion d'erreur DB avec méthodes CI 2.x

- `application/views/licences/bs_TablePerYear.php` :
  - Déjà configuré pour gérer les erreurs (aucune modification nécessaire)

## Logs

Les erreurs sont loggées dans `application/logs/log-*.php` :

```
ERROR - Database error creating licence: Field 'comment' doesn't have a default value
```

## Bénéfices

1. **Transparence** : L'utilisateur voit immédiatement les erreurs
2. **Rollback visuel** : La checkbox revient à son état initial en cas d'erreur
3. **Debugging** : Les erreurs sont loggées côté serveur
4. **UX** : Message d'erreur clair et compréhensible
