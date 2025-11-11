# Debug de l'interface Licences Checkbox

## Symptôme

Lorsqu'on coche une checkbox dans l'interface `licences/per_year`, rien n'est créé en base de données.

## Étapes de Debug

### 1. Vérifier la Console JavaScript

1. Ouvrir la page `http://localhost/licences/per_year` dans le navigateur
2. Ouvrir les DevTools (F12)
3. Aller dans l'onglet Console
4. Cocher une checkbox
5. Vérifier les messages :

**Messages attendus :**
```
Licence checkbox handler ready
Checkbox changed: {pilote: "xxx", year: 2024, type: 0, checked: true}
Calling URL: http://localhost/licences/set/xxx/2024/0
AJAX success: {success: true, data: {...}}
Licence mise à jour avec succès
```

**Si erreur :**
```
AJAX error: {status: "...", error: "...", response: "..."}
```

### 2. Vérifier l'onglet Network

1. Dans DevTools, aller dans l'onglet Network (Réseau)
2. Cocher une checkbox
3. Trouver la requête vers `licences/set/...`
4. Vérifier :
   - **Status Code** : devrait être 200
   - **Request Headers** : devrait contenir `X-Requested-With: XMLHttpRequest`
   - **Response** : devrait contenir `{"success":true,"data":{...}}`

### 3. Vérifier les Logs PHP

Les logs de debug sont dans `application/logs/log-YYYY-MM-DD.php`

**Messages attendus :**
```
DEBUG - Licences::set called with pilote=xxx, year=2024, type=0
DEBUG - Is AJAX request: YES
DEBUG - Create result: ...
```

**Si erreur :**
```
ERROR - Error creating licence: ...
```

### 4. Vérifier la Base de Données

```sql
-- Voir toutes les licences
SELECT * FROM licences ORDER BY year DESC, pilote;

-- Vérifier si une licence existe
SELECT * FROM licences WHERE pilote='xxx' AND year=2024 AND type=0;

-- Voir les licences créées récemment (si la table a un champ timestamp)
SELECT * FROM licences ORDER BY id DESC LIMIT 10;
```

## Problèmes Courants

### La requête AJAX n'est pas envoyée

**Symptômes :**
- Pas de message "Checkbox changed" dans la console
- Pas de requête dans l'onglet Network

**Solutions :**
1. Vérifier que jQuery est bien chargé : `console.log($)`
2. Vérifier que le script est bien après le chargement du DOM
3. Vérifier que les checkboxes ont bien la classe `licence-checkbox`

### La requête est envoyée mais retourne une erreur 403/401

**Symptômes :**
- Requête visible dans Network avec status 403 ou 401
- Message d'erreur dans la console

**Solutions :**
1. Vérifier que l'utilisateur est bien connecté
2. Vérifier les autorisations (rôle 'ca' requis)
3. Vérifier les sessions PHP

### La requête réussit mais rien n'est créé en base

**Symptômes :**
- AJAX success dans la console
- Pas de ligne dans la table `licences`

**Solutions :**
1. Vérifier les logs PHP pour voir si `create()` est appelé
2. Vérifier que le modèle `licences_model` fonctionne correctement
3. Tester directement :
   ```php
   $this->db->insert('licences', array(
       'pilote' => 'test',
       'year' => 2024,
       'type' => 0,
       'date' => '2024-01-01'
   ));
   ```

### Le header AJAX n'est pas détecté

**Symptômes :**
- La page se recharge complètement après le clic
- `Is AJAX request: NO` dans les logs

**Solutions :**
1. Vérifier que le header `X-Requested-With: XMLHttpRequest` est bien envoyé
2. Dans le code AJAX, vérifier :
   ```javascript
   headers: {
       'X-Requested-With': 'XMLHttpRequest'
   }
   ```

## Test Manuel Simple

Pour tester sans l'interface, ouvrir directement dans le navigateur :

```
http://localhost/licences/set/admin/2024/0
```

Cela devrait créer une licence et rediriger vers la page `per_year`. Si cela fonctionne, le problème est uniquement dans l'AJAX.

## Désactiver le Mode Développement

Une fois le debug terminé, ne pas oublier de remettre en mode production dans `index.php` :

```php
// define('ENVIRONMENT', 'development');
define('ENVIRONMENT', 'production');
```
