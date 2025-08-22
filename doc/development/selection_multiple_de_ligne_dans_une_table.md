# Sélection multiple de lignes dans une table et application d'opérations globales  
### Analyse basée sur :  
- `application/controllers/openflyers.php`  
- `application/views/openflyers/bs_tableOperations.php`  
- `application/libraries/GrandLivreParser.php`  

---

## 1. Présentation générale

Le mécanisme pour sélectionner plusieurs lignes dans une table et appliquer des opérations globales repose sur l'utilisation de cases à cocher (`checkbox`) associées à chaque ligne. Une opération globale est ensuite appliquée aux lignes sélectionnées lors de la soumission du formulaire.

Ce modèle est utilisé pour appliquer le même traitement à plusieurs écritures comptables, mais il est adaptable à toute table HTML où des actions groupées sont souhaitées.

---

## 2. Affichage de la table avec cases à cocher

### a. Génération des lignes et cases à cocher

- Lors de la génération du tableau HTML (ex : via `GrandLivreParser::OperationsTable`), chaque ligne d'opération inclut :
  - Une case à cocher `<input type="checkbox" name="cb_ID" onchange="toggleRowSelection(this)">`
  - Les autres données de la ligne.

Extrait de génération côté contrôleur :
```php
$checkbox = '<input type="checkbox" name="cb_' . $elt['id'] . '" onchange="toggleRowSelection(this)">';
...
$table[] = $lst;
```
Lien : [openflyers.php#L470-L487](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/controllers/openflyers.php#L470-L487)

### b. Boutons d'action globale

Au-dessus ou en-dessous de la table, des boutons permettent de :
- Sélectionner toutes les lignes (`selectAll()`)
- Désélectionner toutes les lignes (`deselectAll()`)

Exemple dans la vue :
```html
<button class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
<button class="btn btn-primary" onclick="deselectAll()">Désélectionnez tout</button>
```
Lien : [bs_tableOperations.php#L27-L56](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/views/openflyers/bs_tableOperations.php#L27-L56)

---

## 3. Logique JavaScript pour la sélection

### a. Sélection visuelle

Un script JS ajoute/enlève une classe CSS à la ligne sélectionnée pour un retour visuel :
```js
function toggleRowSelection(checkbox) {
    const row = checkbox.closest('tr');
    if (checkbox.checked) {
        row.classList.add('selected-row');
    } else {
        row.classList.remove('selected-row');
    }
}
```
Lien : [bs_tableOperations.php#L85-L122](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/views/openflyers/bs_tableOperations.php#L85-L122)

### b. Sélection/désélection globale

```js
function selectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
}
function deselectAll() {
    const checkboxes = document.querySelectorAll('input[type="checkbox"]');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}
```

---

## 4. Soumission du formulaire & traitement serveur

Lors de la soumission du formulaire, seules les lignes dont la case à cocher est activée sont envoyées au serveur.  
Le traitement côté serveur consiste à :
- Parcourir tous les paramètres du POST
- Pour chaque clé commençant par `cb_`, traiter la ligne correspondante

Extrait typique côté contrôleur :
```php
$posts = $this->input->post();
foreach ($posts as $key => $value) {
    if (strpos($key, 'cb_') === 0) {
        // Récupérer les données liées à la ligne sélectionnée
        $line = str_replace("cb_", "", $key);
        ...
        // Appliquer l'opération globale sur la ligne
    }
}
```
Lien : [openflyers.php#L395-L417](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/controllers/openflyers.php#L395-L417)

---

## 5. Adaptation à une autre table

Pour appliquer ce mécanisme à une autre table :
1. **Ajouter une case à cocher** sur chaque ligne, avec un nom unique (ex : `cb_ID`).
2. **Ajouter des boutons d'action globale** pour sélectionner/désélectionner toutes les cases.
3. **Ajouter la logique JS** pour la sélection visuelle et la gestion des boutons.
4. **Dans le formulaire de soumission**, traiter côté serveur uniquement les lignes dont la case est cochée (clé `cb_...` présente dans le POST).

---

## 6. Lien vers le code source

Pour explorer plus en détail, consultez :  
- [application/controllers/openflyers.php](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/controllers/openflyers.php)
- [application/views/openflyers/bs_tableOperations.php](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/views/openflyers/bs_tableOperations.php)
- [application/libraries/GrandLivreParser.php](https://github.com/flub78/gvv/blob/f55695ef0a741febf32c743941aa6d050b6647fc/application/libraries/GrandLivreParser.php)

_Note AI : Les extraits sont partiels (max 10 résultats). Pour une analyse complète, consultez le code sur GitHub._

---

## 7. Synthèse

Le schéma "sélection multiple + opération globale" est simple et robuste :
- Interface utilisateur intuitive (cases à cocher, sélecteurs globaux)
- Traitement serveur simple (filtrage par clé `cb_`)
- Facilement réutilisable sur tout type de table HTML

---

### Auteur : Copilot pour flub78/gvv