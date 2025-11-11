# Sliders de Plage d'Années - Interface Licences

## Objectif

Ajouter deux curseurs (sliders) pour permettre à l'utilisateur de contrôler la plage d'années affichée dans la table des licences.

## Spécifications

### Valeurs par Défaut
- **Année de fin** : Année courante
- **Année de début** : Année courante - 5

### Limites
- **Minimum** : Première année pour laquelle il y a des données dans la table `licences`
  - Si aucune donnée : année courante - 5
- **Maximum** : Année courante

### Comportement
- Les curseurs ne peuvent pas se croiser (année début ≤ année fin)
- Mise à jour automatique de la table après changement de plage
- Valeurs sauvegardées en session pour persistance entre les pages

## Implémentation

### 1. Modèle (`application/models/licences_model.php`)

#### Méthode `per_year()` Modifiée

Ajout de paramètres optionnels pour la plage d'années :

```php
public function per_year($type, $year_min = null, $year_max = null)
```

- Si `$year_min` et `$year_max` sont `null` : mode automatique (comme avant)
- Sinon : utilise les valeurs fournies

#### Nouvelle Méthode `get_min_year()`

```php
public function get_min_year()
```

Retourne l'année minimum présente dans la table `licences`, ou `année_courante - 5` si pas de données.

### 2. Contrôleur (`application/controllers/licences.php`)

#### Méthode `per_year()` Étendue

- Initialise les valeurs par défaut en session si non définies
- Récupère l'année minimum des données
- Passe les valeurs à la vue : `year_min`, `year_max`, `min_year_data`, `current_year`

#### Nouvelle Méthode `set_year_range()`

```php
public function set_year_range($year_min, $year_max)
```

- Valide et sauvegarde la plage en session
- Empêche le croisement des valeurs
- Retourne JSON en cas d'appel AJAX
- Redirige vers `per_year` sinon

### 3. Vue (`application/views/licences/bs_TablePerYear.php`)

#### Interface HTML

Ajout d'une carte Bootstrap contenant deux sliders :

```html
<div class="card">
    <div class="card-body">
        <h5 class="card-title">Plage d'années</h5>
        <div class="row">
            <div class="col-md-6">
                <label>Année de début: <span id="year_min_value">...</span></label>
                <input type="range" id="year_min_slider" ... >
            </div>
            <div class="col-md-6">
                <label>Année de fin: <span id="year_max_value">...</span></label>
                <input type="range" id="year_max_slider" ... >
            </div>
        </div>
    </div>
</div>
```

#### JavaScript

**Fonctionnalités** :

1. **Mise à jour de l'affichage** (`updateYearDisplay()`) :
   - Affiche les valeurs des sliders
   - Empêche le croisement (si min > max, ajuste min = max)

2. **Gestion des changements** (`handleSliderChange()`) :
   - Debounce de 500ms pour éviter trop de requêtes
   - Appel AJAX à `licences/set_year_range/min/max`
   - Rechargement de la page en cas de succès

3. **Événements** :
   - `input` : met à jour l'affichage en temps réel
   - `change` : déclenche la sauvegarde et le rechargement

## Flux d'Utilisation

1. **Chargement initial** :
   - Récupération des valeurs de session (ou initialisation par défaut)
   - Affichage des sliders avec les valeurs

2. **Déplacement d'un slider** :
   - Événement `input` → mise à jour de l'affichage
   - Événement `change` → debounce 500ms → AJAX

3. **Sauvegarde** :
   - Appel AJAX à `set_year_range()`
   - Sauvegarde en session
   - Retour JSON `{success: true}`

4. **Rechargement** :
   - `window.location.reload()`
   - La page se recharge avec la nouvelle plage
   - Le modèle génère la table avec les années sélectionnées

## Prévention du Croisement

### Côté Client (JavaScript)

```javascript
if (minVal > maxVal) {
    yearMinSlider.val(maxVal);
    minVal = maxVal;
}
```

### Côté Serveur (PHP)

```php
if ($year_min > $year_max) {
    $temp = $year_min;
    $year_min = $year_max;
    $year_max = $temp;
}
```

## Variables de Session

- `licence_year_min` : Année de début sélectionnée
- `licence_year_max` : Année de fin sélectionnée

Ces valeurs persistent pendant toute la session utilisateur.

## Améliorations UX

1. **Debounce** : Évite les requêtes multiples lors du déplacement rapide du slider
2. **Feedback visuel** : Les valeurs s'affichent en temps réel
3. **Validation** : Impossible de sélectionner une plage invalide
4. **Persistance** : Les valeurs sont conservées en session

## Fichiers Modifiés

- `application/models/licences_model.php` :
  - Méthode `per_year()` : ajout de paramètres `$year_min`, `$year_max`
  - Nouvelle méthode `get_min_year()`

- `application/controllers/licences.php` :
  - Méthode `per_year()` : gestion de la plage d'années
  - Nouvelle méthode `set_year_range()`

- `application/views/licences/bs_TablePerYear.php` :
  - Ajout de l'interface HTML (sliders)
  - Ajout du JavaScript pour la gestion interactive

## Test Manuel

1. Aller sur `http://gvv.net/licences/per_year`
2. Observer les deux sliders
3. Déplacer le slider "Année de début" → la valeur s'affiche
4. Relâcher → la page se recharge avec la nouvelle plage
5. Vérifier que les colonnes de la table correspondent aux années sélectionnées
6. Essayer de croiser les sliders → impossible

## Corrections Apportées

### 1. Problème de la Ligne "Total" - Nombre de Colonnes

**Symptôme** : La ligne de total cassait le DataTable quand le nombre de colonnes ne correspondait pas.

**Cause** :
- Les colonnes de total n'étaient pas toujours initialisées correctement
- Les licences hors de la plage sélectionnée étaient traitées, causant des index incorrects

**Solution** :
1. Initialiser `$total_annuel` avec toutes les colonnes (même logique que l'en-tête)
2. Filtrer les licences hors plage avec `if ($year < $min || $year > $max) continue;`
3. Remplir les colonnes manquantes avec 0 avant d'ajouter la ligne de total
4. S'assurer que `$num_columns` correspond exactement au nombre de colonnes de données

### 2. Problème de la Ligne "Total" - Tri Alphabétique

**Symptôme** : La ligne de total était incluse dans le DataTable et donc triée alphabétiquement, se retrouvant au milieu de la table si un membre avait un nom commençant par une lettre après "T".

**Cause** :
- La ligne de total était ajoutée au tableau `$results` avec `$results[] = $total_annuel`
- Le DataTable trie toutes les lignes, y compris le total

**Solution** :
1. **Modèle** (`licences_model.php`) : Retourner les données et le total séparément
   ```php
   return array(
       'data' => $results,      // Données sans le total
       'total' => $total_annuel // Ligne de total à part
   );
   ```

2. **Contrôleur** (`licences.php`) : Séparer les données
   ```php
   $result = $this->gvv_model->per_year($data['type'], $year_min, $year_max);
   $data['table'] = $result['data'];
   $data['total'] = $result['total'];
   ```

3. **Vue** (`bs_TablePerYear.php`) : Afficher le total en dehors du DataTable
   ```php
   $table->display();

   // Ligne de total dans une table séparée (non triable)
   echo '<table class="table table-bordered table-sm">';
   echo '<thead class="table-secondary">';
   echo '<tr>';
   foreach ($total as $value) {
       echo '<th class="text-center">' . $value . '</th>';
   }
   echo '</tr>';
   echo '</thead>';
   echo '</table>';
   ```

**Résultat** : La ligne de total est maintenant toujours affichée en bas, après le DataTable, et n'est pas affectée par le tri.

## Limitations

- Rechargement complet de la page à chaque changement
  - Alternative : recharger uniquement la table via AJAX (plus complexe)
- Debounce de 500ms : l'utilisateur doit attendre un peu après avoir relâché le slider
