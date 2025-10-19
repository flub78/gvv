# Prototypes GVV - Navigation Hybride

Prototypes HTML/CSS autonomes démontrant la nouvelle charte graphique et navigation hybride pour GVV.

## Fichiers

### CSS
- **`gvv_charter.css`** - Feuille de style complète de la charte graphique
  - Variables CSS pour toutes les couleurs, espacements, polices
  - Composants : boutons, formulaires, tableaux, cartes, badges, alertes
  - Navigation : topbar, primary nav, secondary nav, breadcrumbs
  - Modal de recherche globale
  - Utilitaires et classes helpers
  - Responsive design

### Prototypes HTML

1. **`prototype_dashboard.html`** - Tableau de bord utilisateur
   - Vue d'accueil personnalisée par rôle (exemple: pilote)
   - Widgets : vols récents, compte, validités, statistiques, actions rapides, météo
   - Statistiques rapides en haut de page
   - Navigation complète (topbar + primary + recherche)
   - Animations au chargement

2. **`prototype_vols.html`** - Liste des vols planeurs
   - Navigation contextuelle avec actions rapides
   - Fil d'Ariane (breadcrumbs)
   - Sous-navigation par onglets
   - Filtres multiples (date, machine, pilote, type)
   - Tableau avec actions par ligne
   - Pagination
   - Export CSV/PDF

## Fonctionnalités démontrées

### Navigation

#### Topbar (barre supérieure)
- Logo GVV cliquable (retour accueil)
- **Recherche globale** avec placeholder
- Raccourci clavier `Ctrl+K` affiché
- Menu utilisateur avec nom/rôle

#### Navigation primaire
- 5 onglets principaux : Tableau de bord, Vols, Membres, Comptabilité, Administration
- Badge de notification sur "Vols" (5)
- Indicateur visuel de l'onglet actif

#### Navigation secondaire (contextuelle)
- Fil d'Ariane (breadcrumbs) montrant le parcours
- **Actions rapides contextuelles** selon la page
  - Dashboard : accès direct aux fonctions fréquentes
  - Vols : Nouveau vol, Saisie auto, GESASSO, Stats
- Sous-navigation par onglets (Liste, Statistiques, Machines, Formation)

### Recherche globale

- Modal plein écran avec fond sombre
- Input focus automatique
- Résultats groupés par catégorie :
  - Actions rapides
  - Membres
  - Vols récents
  - Pages
- Navigation clavier affichée (↑↓, ↵, Esc)
- Fermeture par `Esc` ou clic sur fond
- **Raccourci `Ctrl+K`** depuis n'importe quelle page

### Composants UI

#### Boutons
- Primaire (vert) : actions principales
- Secondaire (bordure) : actions secondaires
- Danger (rouge) : suppressions
- Info (bleu) : informations
- Tailles : sm, md (défaut), lg
- États : hover avec élévation, active, disabled
- Icônes Font Awesome intégrées

#### Tableaux
- En-tête vert avec texte blanc (respect charte actuelle)
- Lignes alternées (bleu clair/blanc)
- Hover sur ligne avec effet
- Colonne actions avec icônes
- Footer avec totaux
- Responsive : colonnes masquées sur mobile

#### Cartes (Cards)
- En-tête avec titre et action
- Corps avec contenu flexible
- Ombre légère au repos, accentuée au survol
- Variantes : standard, statistique, alerte

#### Badges
- Success (vert), Warning (orange), Error (rouge), Info (bleu), Neutral (gris)
- Coins arrondis
- Texte petit et gras

#### Alertes
- Bordure gauche colorée épaisse
- Fond coloré léger
- Icônes appropriées
- 4 types : success, warning, error, info

### Interactions

#### Raccourcis clavier
- `Ctrl+K` : Ouvrir recherche globale (fonctionne partout)
- `Ctrl+N` : Nouveau vol (sur page vols)
- `Esc` : Fermer modal recherche

#### Animations
- Apparition progressive des cartes au chargement (dashboard)
- Transitions douces sur hover (0.2s)
- Élévation des boutons au survol
- Changement de couleur des liens

#### Feedback utilisateur
- Alertes JavaScript pour simuler actions (production: vraies actions)
- Confirmations pour suppressions
- Messages informatifs

### Responsive

- **Desktop** (>992px) : Affichage complet
- **Tablet** (768-991px) : Navigation adaptée, colonnes réduites
- **Mobile** (<768px) :
  - Barre de recherche en pleine largeur
  - Navigation horizontale scrollable
  - Actions contextuelles en colonne
  - Colonnes tableau masquées (.hide-mobile)

## Utilisation

### Ouvrir les prototypes

1. **Méthode simple** : Double-cliquer sur les fichiers HTML
   - `prototype_dashboard.html` → Tableau de bord
   - `prototype_vols.html` → Liste des vols

2. **Méthode serveur local** (recommandée pour développement)
   ```bash
   cd doc/prototypes
   python3 -m http.server 8000
   # Ouvrir http://localhost:8000/prototype_dashboard.html
   ```

### Navigation entre les prototypes

- **Dashboard → Vols** : Cliquer sur onglet "Vols" ou badge
- **Vols → Dashboard** : Cliquer sur logo GVV ou onglet "Tableau de bord"
- **Recherche globale** : `Ctrl+K` depuis n'importe où

### Tester les fonctionnalités

#### Dashboard
1. Observer les widgets personnalisés (pilote)
2. Cliquer sur les cartes pour explorer
3. Tester les boutons d'actions rapides
4. Vérifier l'affichage des alertes (validités)

#### Vols
1. Utiliser les filtres (date, machine, pilote, type)
2. Cliquer sur une ligne du tableau (affiche détails)
3. Tester les actions par ligne (voir, modifier, dupliquer, supprimer)
4. Tester le bouton "Nouveau vol planeur" (Ctrl+N)
5. Paginer (précédent/suivant)
6. Exporter (CSV, PDF, Imprimer)

#### Recherche
1. Appuyer sur `Ctrl+K` n'importe où
2. Commencer à taper (simulation de résultats)
3. Naviguer avec flèches (pas encore implémenté)
4. Fermer avec `Esc` ou clic sur fond

## Adaptation pour GVV

### Intégration dans CodeIgniter

#### 1. Copier le CSS
```bash
cp gvv_charter.css ../../../assets/css/
```

#### 2. Inclure dans header
```php
// application/views/bs_header.php (ou créer bs_header_v2.php)
echo html_link(array(
    'rel' => "stylesheet",
    'type' => "text/css",
    'href' => base_url() . 'assets/css/gvv_charter.css'
));
```

#### 3. Créer les vues de navigation
```php
// application/views/navigation/topbar.php
// application/views/navigation/primary_nav.php
// application/views/navigation/secondary_nav.php
// application/views/navigation/search_modal.php
```

#### 4. Copier le JavaScript
```javascript
// Extraire le code JS des prototypes dans:
// assets/javascript/gvv_navigation.js
```

#### 5. Adapter les URLs
Remplacer les `#` et alertes par de vraies URLs CodeIgniter :
```php
// Au lieu de:
<a href="#">

// Utiliser:
<a href="<?= controller_url('vols_planeur/page') ?>">
```

### Actions contextuelles dynamiques

Créer un helper pour générer les actions selon le contexte :

```php
// application/helpers/navigation_helper.php
function get_context_actions($context, $role) {
    $actions = array();

    if ($context === 'vols_planeur' && has_role('planchiste')) {
        $actions[] = array(
            'label' => 'Nouveau vol',
            'url' => controller_url('vols_planeur/create'),
            'icon' => 'fas fa-plus',
            'class' => 'btn-gvv-primary',
            'shortcut' => 'Ctrl+N'
        );
        // ...
    }

    return $actions;
}
```

### Widgets dashboard dynamiques

```php
// application/libraries/Dashboard_widgets.php
class Dashboard_widgets {
    public function get_widgets_for_role($role) {
        $widgets = array();

        if ($role === 'pilote') {
            $widgets[] = array(
                'type' => 'recent_flights',
                'title' => 'Mes vols récents',
                'data' => $this->CI->vols_model->get_user_recent_flights(5)
            );
            // ...
        }

        return $widgets;
    }
}
```

## Personnalisation

### Couleurs

Modifier les variables CSS dans `gvv_charter.css` :

```css
:root {
  --gvv-primary: #2E7D32;        /* Changer la couleur principale */
  --gvv-secondary: #1976D2;      /* Changer la couleur secondaire */
  /* ... */
}
```

### Composants

Ajouter de nouveaux styles en suivant la convention :

```css
/* Nouveau composant */
.mon-composant-gvv {
    /* Utiliser les variables CSS */
    background-color: var(--gvv-primary);
    padding: var(--gvv-space-4);
    /* ... */
}
```

### Workflows

Ajouter des actions contextuelles dans le HTML :

```html
<div class="gvv-context-actions">
    <button class="btn-gvv btn-gvv-primary">
        <i class="fas fa-plus"></i>
        Nouvelle action
    </button>
</div>
```

## Compatibilité

### Navigateurs testés
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Safari 14+
- ✅ Edge 90+

### Dépendances externes
- Font Awesome 6.4.0 (CDN)
- Polices système (pas de dépendance)

### Pas de dépendances
- ❌ jQuery (pas nécessaire pour ces prototypes)
- ❌ Bootstrap JS (CSS uniquement)
- ❌ Frameworks JS lourds

## Points d'amélioration futurs

### Recherche globale
- [ ] Implémentation navigation clavier (flèches)
- [ ] Recherche en temps réel avec AJAX
- [ ] Historique de recherche
- [ ] Résultats récents

### Dashboard
- [ ] Widgets drag & drop
- [ ] Configuration personnalisée
- [ ] Actualisation en temps réel
- [ ] Graphiques interactifs

### Vols
- [ ] Tri des colonnes
- [ ] Filtres avancés (date range picker)
- [ ] Sélection multiple pour actions par lot
- [ ] Vue calendrier

### Général
- [ ] Mode sombre
- [ ] Favoris persistants (localStorage)
- [ ] Notifications push
- [ ] Indicateur de chargement global

## Support

Pour toute question sur les prototypes :
1. Lire la documentation complète : `../CHARTE_GRAPHIQUE_ET_NAVIGATION.md`
2. Vérifier les exemples de code dans les prototypes HTML
3. Consulter les commentaires CSS dans `gvv_charter.css`

---

**Créé le** : Octobre 2025
**Version** : 1.0
**Statut** : Prototype de démonstration
