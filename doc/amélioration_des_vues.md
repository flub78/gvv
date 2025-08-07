# Organizing Views in CodeIgniter 2 - Conversation

## Question initiale
**Utilisateur :** Avec CodeIgniter, dans une application WEB legacy, j'ai l'habitude de générer des tableaux dans la vue avec des fonctions PHP. C'est flexible, mais il manque un endroit qui contiendrait visuellement le HTML et les classes associées, un peu comme les templates Laravel. Y a-t-il un moyen dans CodeIgniter d'organiser les vues pour que tout le HTML et les classes restent dans un fichier et que les données soient générées par le contrôleur ? Votre réponse doit convenir aux structures de données semi-complexes où les éléments ne sont pas de simples tableaux de lignes mais eux-mêmes une combinaison de tableaux.

## Réponse - Solutions pour organiser les vues

Dans CodeIgniter 2, vous pouvez utiliser plusieurs approches pour mieux organiser vos vues et séparer la logique de présentation :

### 1. Template Parser Library
CodeIgniter 2 inclut une classe Template Parser qui permet d'utiliser des pseudo-variables :

```php
// Controller
$data = [
    'users' => $complex_user_data,
    'table_rows' => $this->build_table_rows($complex_user_data)
];
$this->parser->parse('user_table', $data);
```

```html
<!-- Vue user_table.php -->
<table class="table table-striped custom-table">
    <thead>
        <tr><th>Nom</th><th>Actions</th></tr>
    </thead>
    <tbody>
        {table_rows}
    </tbody>
</table>
```

### 2. Nested Views avec préparation des données
Préparez vos données complexes dans le controller et utilisez des vues imbriquées :

```php
// Controller
$processed_data = $this->format_complex_data($raw_data);
$data['table_content'] = $this->load->view('partials/table_rows', ['items' => $processed_data], TRUE);
$this->load->view('main_table', $data);
```

### 3. Helper personnalisé pour la logique de rendu
Créez un helper qui transforme vos structures complexes en HTML :

```php
// application/helpers/table_helper.php
function render_complex_row($item_data, $css_classes = []) {
    // Logique de transformation des données complexes
    return $formatted_html;
}
```

**Note:** La Template Parser reste la plus proche de l'approche Laravel Blade pour garder le HTML propre.

---

## Question de suivi
**Utilisateur :** Dans votre première suggestion, comment puis-je contrôler des classes CSS spécifiques pour les éléments ? Par exemple, quand les éléments sont eux-mêmes des tableaux et que je veux un style spécifique pour leur en-tête ?

## Réponse - Contrôle des classes CSS

Avec le Template Parser de CodeIgniter 2, vous pouvez contrôler les classes CSS en préparant les données avec les classes incluses dans le controller :

### Préparation dans le controller
```php
$data = [];
foreach($complex_data as $item) {
    $header_class = $item['is_critical'] ? 'table-danger' : 'table-primary';
    $row_class = $item['status'] == 'active' ? 'active-row' : 'inactive-row';
    
    $data['table_rows'] .= $this->parser->parse('partials/nested_table', [
        'header_class' => $header_class,
        'row_class' => $row_class,
        'nested_data' => $this->format_nested_data($item['sub_items'])
    ], TRUE);
}
```

### Vue principale
```html
<div class="main-container">
    {table_rows}
</div>
```

### Vue partielle (partials/nested_table.php)
```html
<table class="nested-table {row_class}">
    <thead class="table-header {header_class}">
        <tr><th>Sous-élément</th><th>Valeur</th></tr>
    </thead>
    <tbody>
        {nested_data}
    </tbody>
</table>
```

### Alternative avec conditions
Si vous avez des conditions plus complexes, vous pouvez aussi inclure des booléens :

```php
'show_warning' => $item['level'] > 5,
'is_premium' => $item['type'] == 'premium'
```

```html
{if show_warning}<div class="alert alert-warning">{/if}
<table class="{is_premium}premium-table{/is_premium}">
```

## Conclusion

L'approche Template Parser de CodeIgniter 2 permet de :
- Séparer clairement la logique métier du HTML
- Contrôler finement les classes CSS selon les données
- Maintenir une structure de vue lisible
- Gérer des structures de données complexes avec des vues imbriquées

Cette solution s'adapte bien aux applications legacy en PHP 7 avec CodeIgniter 2.