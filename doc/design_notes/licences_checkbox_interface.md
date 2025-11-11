# Interface Licences avec Checkboxes

## Objectif

Remplacer l'interface de gestion des licences par année (`http://gvv.net/licences/per_year`) pour utiliser des checkboxes interactives au lieu de liens textuels.

## Problème Initial

L'interface précédente utilisait des liens pour créer/supprimer des licences :
- Un lien "-" pour créer une licence (appel à `licences/set`)
- Un lien avec l'année pour supprimer une licence (appel à `licences/switch_it`)

Cette interface n'était pas intuitive pour l'utilisateur.

## Solution Implémentée

### 1. Modification du Modèle (`application/models/licences_model.php`)

La méthode `per_year()` a été modifiée pour générer des checkboxes HTML au lieu de liens :

```php
// Checkbox non cochée par défaut
$checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $mlogin . '" data-year="' . $year . '" data-type="' . $type . '">';

// Checkbox cochée pour les licences existantes
$checkbox = '<input type="checkbox" class="licence-checkbox" data-pilote="' . $pilote . '" data-year="' . $year . '" data-type="' . $type . '" checked>';
```

Chaque checkbox contient les attributs `data-*` nécessaires pour identifier :
- `data-pilote` : login du pilote
- `data-year` : année de la licence
- `data-type` : type de licence

### 2. Modification de la Vue (`application/views/licences/bs_TablePerYear.php`)

Ajout d'un gestionnaire JavaScript jQuery pour réagir aux changements de checkboxes :

```javascript
$('.licence-checkbox').on('change', function() {
    var checkbox = $(this);
    var pilote = checkbox.data('pilote');
    var year = checkbox.data('year');
    var type = checkbox.data('type');
    var isChecked = checkbox.is(':checked');

    // Désactiver la checkbox pendant le traitement
    checkbox.prop('disabled', true);

    // Déterminer l'URL en fonction de l'état
    var url;
    if (isChecked) {
        // Cocher = créer la licence
        url = '<?php echo base_url(); ?>licences/set/' + pilote + '/' + year + '/' + type;
    } else {
        // Décocher = supprimer la licence
        url = '<?php echo base_url(); ?>licences/switch_it/' + pilote + '/' + year + '/' + type;
    }

    // Envoyer la requête AJAX
    $.ajax({
        url: url,
        type: 'GET',
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        },
        success: function(response) {
            checkbox.prop('disabled', false);
        },
        error: function(xhr, status, error) {
            checkbox.prop('checked', !isChecked);
            checkbox.prop('disabled', false);
            alert('Erreur lors de la mise à jour de la licence: ' + error);
        }
    });
});
```

### 3. Modification du Contrôleur (`application/controllers/licences.php`)

Les méthodes `set()` et `switch_it()` ont été modifiées pour supporter les appels AJAX :

```php
// Dans set()
if ($this->input->is_ajax_request()) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => true));
} else {
    $this->per_year();
}

// Dans switch_it()
if ($this->input->is_ajax_request()) {
    header('Content-Type: application/json');
    echo json_encode(array('success' => true));
} else {
    $this->per_year();
}
```

Cette approche maintient la compatibilité avec les anciens appels directs tout en supportant les nouveaux appels AJAX.

## Comportement

1. **Cocher une checkbox vide** :
   - Envoie une requête AJAX à `licences/set/{pilote}/{year}/{type}`
   - Crée une nouvelle licence en base de données
   - La checkbox reste cochée

2. **Décocher une checkbox cochée** :
   - Envoie une requête AJAX à `licences/switch_it/{pilote}/{year}/{type}`
   - Supprime la licence de la base de données
   - La checkbox reste décochée

3. **Gestion des erreurs** :
   - En cas d'erreur AJAX, la checkbox revient à son état précédent
   - Un message d'erreur est affiché à l'utilisateur

4. **Feedback utilisateur** :
   - La checkbox est désactivée pendant le traitement de la requête
   - Elle est réactivée une fois la requête terminée

## Tests

Un test Playwright a été créé dans `playwright/tests/licences-checkbox.spec.js` pour vérifier :
- L'affichage correct des checkboxes
- La création de licences (cocher une checkbox)
- La suppression de licences (décocher une checkbox)
- Les toggles multiples

## Problème Résolu : Détection AJAX

### Symptôme Initial
Les requêtes AJAX retournaient du HTML au lieu de JSON, causant l'erreur :
```
SyntaxError: Unexpected token '<', "<div style"... is not valid JSON
```

### Cause
CodeIgniter 2.x `$this->input->is_ajax_request()` ne détectait pas correctement les requêtes AJAX.

### Solution
1. **Côté serveur** :
   - Vérification directe du header `$_SERVER['HTTP_X_REQUESTED_WITH']` au lieu de `is_ajax_request()`
   - Nettoyage du buffer de sortie avec `ob_end_clean()` avant d'envoyer le JSON
   - Utilisation de `exit()` après le JSON pour éviter tout output supplémentaire
2. **Côté client** : Utilisation de `beforeSend` avec `setRequestHeader()` pour garantir l'envoi du header

Le HTML affiché avant le JSON était causé par des messages de debug PHP ou des warnings envoyés dans le buffer de sortie avant le JSON.

## Fichiers Modifiés

- `application/models/licences_model.php` : Méthode `per_year()` modifiée pour générer des checkboxes
- `application/views/licences/bs_TablePerYear.php` : Ajout du gestionnaire JavaScript avec détection AJAX améliorée
- `application/controllers/licences.php` : Méthodes `set()` et `switch_it()` modifiées pour supporter AJAX avec détection header directe
- `playwright/tests/licences-checkbox.spec.js` : Nouveau test Playwright (créé)
- `doc/troubleshooting/licences_checkbox_debug.md` : Guide de debugging (créé)

## Validation

- ✓ Syntaxe PHP validée pour tous les fichiers modifiés
- ✓ Test Playwright créé
- ✓ Gestion d'erreur testée et fonctionnelle
- ✓ Erreur de champ 'comment' corrigée
- ✓ Interface complètement fonctionnelle

## Résultat Final

L'interface fonctionne maintenant correctement :
1. **Cocher une checkbox** → crée une licence en base de données avec succès
2. **Décocher une checkbox** → supprime la licence de la base de données
3. **Erreur de base de données** → popup d'erreur affichée et checkbox décochée
4. **Feedback visuel** → checkbox désactivée pendant le traitement AJAX
5. **Mise à jour des totaux** → les totaux se mettent à jour automatiquement après chaque changement

### Mise à Jour Dynamique des Totaux

Lorsqu'une checkbox est cochée ou décochée, le JavaScript met automatiquement à jour la ligne de totaux :

**Fonctionnement** :
1. Chaque cellule de la ligne de total possède un attribut `data-year` correspondant à l'année
2. La fonction `updateTotals()` compte toutes les checkboxes cochées pour chaque année
3. Les totaux sont recalculés et affichés immédiatement après succès de l'AJAX

**Code JavaScript** :
```javascript
function updateTotals() {
    $('#total-row th[data-col-index]').each(function() {
        var year = $(this).data('year');
        var count = 0;

        $('.licence-checkbox').each(function() {
            if (parseInt($(this).data('year')) === year && $(this).is(':checked')) {
                count++;
            }
        });

        $(this).text(count);
    });
}
```

Appelée dans le callback `success` de l'AJAX après modification d'une licence.

## Test Manuel Recommandé

1. Se connecter à l'application
2. Naviguer vers `licences/per_year`
3. Vérifier que la table affiche des checkboxes
4. Cocher une checkbox vide et vérifier qu'une licence est créée
5. Décocher une checkbox cochée et vérifier que la licence est supprimée
6. Tester plusieurs toggles sur la même checkbox
