# Charte Graphique et Navigation Hybride - GVV

**Version**: 1.0
**Date**: Octobre 2025
**Projet**: GVV (Gestion Vol à Voile)

---

## Table des matières

1. [Introduction](#introduction)
2. [Charte Graphique](#charte-graphique)
   - [Palette de couleurs](#palette-de-couleurs)
   - [Typographie](#typographie)
   - [Composants UI](#composants-ui)
   - [Espacements et grilles](#espacements-et-grilles)
   - [Accessibilité](#accessibilité)
   - [États interactifs](#états-interactifs)
3. [Navigation Hybride](#navigation-hybride)
   - [Principes directeurs](#principes-directeurs)
   - [Architecture proposée](#architecture-proposée)
   - [Workflows utilisateurs](#workflows-utilisateurs)
   - [Implémentation](#implémentation)
4. [Plan de migration](#plan-de-migration)

---

## Introduction

Ce document définit la charte graphique moderne et la stratégie de navigation hybride pour l'application GVV. L'objectif est de moderniser l'interface tout en préservant la familiarité pour les utilisateurs existants et en optimisant les workflows quotidiens.

### Contexte technique
- **Framework**: CodeIgniter 2.x, PHP 7.4
- **UI actuelle**: Bootstrap 5
- **Navigation actuelle**: Menu horizontal à 3 niveaux
- **Utilisateurs**: 5-6 associations de vol à voile
- **Contrainte**: Intégration progressive sans refonte majeure

---

## Charte Graphique

### Palette de couleurs

#### Couleurs primaires

```css
/* Palette principale - Tons aviation/ciel */
--gvv-primary: #2E7D32;        /* Vert principal (actuel) */
--gvv-primary-light: #4CAF50;  /* Vert clair */
--gvv-primary-dark: #1B5E20;   /* Vert foncé */

--gvv-secondary: #1976D2;      /* Bleu ciel */
--gvv-secondary-light: #42A5F5; /* Bleu clair */
--gvv-secondary-dark: #0D47A1;  /* Bleu foncé */

--gvv-accent: #FF6F00;         /* Orange accent (actions importantes) */
--gvv-accent-light: #FF8F00;
--gvv-accent-dark: #E65100;
```

#### Couleurs sémantiques

```css
/* États et feedback */
--gvv-success: #4CAF50;        /* Validation, succès */
--gvv-warning: #FF9800;        /* Avertissements */
--gvv-error: #F44336;          /* Erreurs */
--gvv-info: #2196F3;           /* Information */

/* Comptabilité spécifique */
--gvv-debit: #F44336;          /* Rouge (dépenses) */
--gvv-credit: #4CAF50;         /* Vert (recettes) */
--gvv-compte: #e9a45a;         /* Orange (compte actuel) */
```

#### Couleurs neutres

```css
/* Textes et arrière-plans */
--gvv-text-primary: #212121;   /* Texte principal */
--gvv-text-secondary: #666666;  /* Texte secondaire */
--gvv-text-disabled: #9E9E9E;   /* Texte désactivé */

--gvv-bg-primary: #FFFFFF;      /* Fond principal */
--gvv-bg-secondary: #F5F5F5;    /* Fond secondaire */
--gvv-bg-tertiary: #e1e4e7;     /* Fond formulaires (actuel) */

--gvv-border: #E0E0E0;          /* Bordures */
--gvv-border-dark: #7e9bae;     /* Bordures accentuées */
--gvv-divider: #BDBDBD;         /* Séparateurs */
```

#### Couleurs de navigation

```css
/* Menu et navigation */
--gvv-nav-bg: #343A40;          /* Fond navbar (Bootstrap dark) */
--gvv-nav-text: #FFFFFF;        /* Texte navbar */
--gvv-nav-hover: #495057;       /* Hover navbar */
--gvv-nav-active: #1976D2;      /* Item actif */
```

### Typographie

#### Polices

```css
/* Polices système performantes */
--gvv-font-primary: -apple-system, BlinkMacSystemFont, "Segoe UI",
                     Roboto, "Helvetica Neue", Arial, sans-serif;
--gvv-font-monospace: "Courier New", Courier, monospace;

/* Fallback actuel conservé */
--gvv-font-legacy: "Trebuchet MS", Arial, Helvetica, sans-serif;
```

#### Tailles de texte

```css
/* Hiérarchie typographique */
--gvv-text-xs: 0.75rem;    /* 12px - Petites notes */
--gvv-text-sm: 0.85rem;    /* 13.6px - Tableaux, détails */
--gvv-text-base: 1rem;     /* 16px - Texte standard */
--gvv-text-lg: 1.125rem;   /* 18px - Titres secondaires */
--gvv-text-xl: 1.25rem;    /* 20px - Titres principaux */
--gvv-text-2xl: 1.5rem;    /* 24px - Titres de page */
--gvv-text-3xl: 2rem;      /* 32px - Titres majeurs */
```

#### Poids de police

```css
--gvv-font-normal: 400;
--gvv-font-medium: 500;
--gvv-font-semibold: 600;
--gvv-font-bold: 700;
```

#### Hauteur de ligne

```css
--gvv-leading-tight: 1.25;
--gvv-leading-normal: 1.5;
--gvv-leading-relaxed: 1.75;
```

### Composants UI

#### Boutons

```css
/* Bouton primaire */
.btn-gvv-primary {
    background-color: var(--gvv-primary);
    border-color: var(--gvv-primary);
    color: white;
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-gvv-primary:hover {
    background-color: var(--gvv-primary-dark);
    border-color: var(--gvv-primary-dark);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
}

.btn-gvv-primary:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
}

.btn-gvv-primary:disabled {
    background-color: var(--gvv-text-disabled);
    border-color: var(--gvv-text-disabled);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Bouton secondaire */
.btn-gvv-secondary {
    background-color: transparent;
    border: 2px solid var(--gvv-primary);
    color: var(--gvv-primary);
    padding: 0.5rem 1rem;
    border-radius: 4px;
    font-weight: 500;
    transition: all 0.2s ease;
}

.btn-gvv-secondary:hover {
    background-color: var(--gvv-primary);
    color: white;
}

/* Bouton danger */
.btn-gvv-danger {
    background-color: var(--gvv-error);
    border-color: var(--gvv-error);
    color: white;
}

.btn-gvv-danger:hover {
    background-color: #D32F2F;
    border-color: #D32F2F;
}

/* Tailles de boutons */
.btn-gvv-sm { padding: 0.25rem 0.5rem; font-size: 0.85rem; }
.btn-gvv-md { padding: 0.5rem 1rem; font-size: 1rem; }
.btn-gvv-lg { padding: 0.75rem 1.5rem; font-size: 1.125rem; }
```

#### Formulaires

```css
/* Champs de formulaire */
.form-gvv-input {
    background-color: white;
    border: 1px solid var(--gvv-border);
    border-radius: 4px;
    padding: 0.5rem 0.75rem;
    font-size: 1rem;
    transition: border-color 0.2s ease, box-shadow 0.2s ease;
}

.form-gvv-input:focus {
    outline: none;
    border-color: var(--gvv-primary);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
}

.form-gvv-input:disabled {
    background-color: var(--gvv-bg-secondary);
    cursor: not-allowed;
    opacity: 0.6;
}

.form-gvv-input.is-invalid {
    border-color: var(--gvv-error);
}

.form-gvv-input.is-invalid:focus {
    box-shadow: 0 0 0 3px rgba(244, 67, 54, 0.1);
}

/* Labels */
.form-gvv-label {
    display: block;
    margin-bottom: 0.5rem;
    font-weight: 500;
    color: var(--gvv-text-primary);
}

/* Groupe de formulaire */
.form-gvv-group {
    margin-bottom: 1.5rem;
}

/* Container formulaire avec fond */
.form-gvv-container {
    background-color: var(--gvv-bg-tertiary);
    border: 1px solid var(--gvv-border-dark);
    border-radius: 4px;
    padding: 1.5rem;
}
```

#### Tableaux

```css
/* Tableau GVV standard */
.table-gvv {
    width: 100%;
    border-collapse: collapse;
    background-color: white;
}

.table-gvv thead {
    background-color: var(--gvv-primary);
    color: white;
}

.table-gvv thead th {
    padding: 0.75rem;
    font-weight: 600;
    font-size: 1.1em;
    text-align: left;
    border: 1px solid var(--gvv-primary-dark);
}

.table-gvv tbody tr:nth-child(odd) {
    background-color: #E2E4FF;
}

.table-gvv tbody tr:nth-child(even) {
    background-color: white;
}

.table-gvv tbody tr:hover {
    background-color: #D3D6FF;
    cursor: pointer;
}

.table-gvv tbody td {
    padding: 0.5rem 0.75rem;
    border: 1px solid var(--gvv-border);
}

/* Ligne de titre dans tableau */
.table-gvv .row_title td {
    font-weight: bold;
    background-color: var(--gvv-bg-secondary);
}

/* Cellules comptabilité */
.table-gvv td.debit { color: var(--gvv-debit); font-weight: 600; }
.table-gvv td.credit { color: var(--gvv-credit); font-weight: 600; }
.table-gvv tr.compte { background-color: var(--gvv-compte); }
```

#### Cartes (Cards)

```css
/* Carte standard */
.card-gvv {
    background-color: white;
    border: 1px solid var(--gvv-border);
    border-radius: 8px;
    padding: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.08);
    transition: box-shadow 0.2s ease;
}

.card-gvv:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.12);
}

.card-gvv-header {
    border-bottom: 2px solid var(--gvv-border);
    padding-bottom: 1rem;
    margin-bottom: 1rem;
}

.card-gvv-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: var(--gvv-text-primary);
    margin: 0;
}

.card-gvv-body {
    color: var(--gvv-text-secondary);
}

/* Carte statistique */
.card-gvv-stat {
    text-align: center;
    padding: 2rem 1rem;
}

.card-gvv-stat-value {
    font-size: 2.5rem;
    font-weight: 700;
    color: var(--gvv-primary);
    margin-bottom: 0.5rem;
}

.card-gvv-stat-label {
    font-size: 0.9rem;
    color: var(--gvv-text-secondary);
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
```

#### Badges et étiquettes

```css
/* Badge standard */
.badge-gvv {
    display: inline-block;
    padding: 0.25rem 0.6rem;
    font-size: 0.75rem;
    font-weight: 600;
    line-height: 1;
    border-radius: 12px;
    text-align: center;
}

.badge-gvv-success { background-color: var(--gvv-success); color: white; }
.badge-gvv-warning { background-color: var(--gvv-warning); color: white; }
.badge-gvv-error { background-color: var(--gvv-error); color: white; }
.badge-gvv-info { background-color: var(--gvv-info); color: white; }
.badge-gvv-neutral { background-color: var(--gvv-bg-secondary); color: var(--gvv-text-primary); }
```

#### Alertes et messages

```css
/* Alerte standard */
.alert-gvv {
    padding: 1rem 1.5rem;
    border-radius: 4px;
    border-left: 4px solid;
    margin-bottom: 1rem;
}

.alert-gvv-success {
    background-color: #E8F5E9;
    border-color: var(--gvv-success);
    color: #1B5E20;
}

.alert-gvv-warning {
    background-color: #FFF3E0;
    border-color: var(--gvv-warning);
    color: #E65100;
}

.alert-gvv-error {
    background-color: #FFEBEE;
    border-color: var(--gvv-error);
    color: #B71C1C;
}

.alert-gvv-info {
    background-color: #E3F2FD;
    border-color: var(--gvv-info);
    color: #0D47A1;
}

.alert-gvv h2, .alert-gvv h3 {
    margin-top: 0;
    font-weight: 600;
}
```

### Espacements et grilles

#### Système d'espacement

```css
/* Espacement basé sur une échelle de 4px */
--gvv-space-1: 0.25rem;  /* 4px */
--gvv-space-2: 0.5rem;   /* 8px */
--gvv-space-3: 0.75rem;  /* 12px */
--gvv-space-4: 1rem;     /* 16px */
--gvv-space-5: 1.25rem;  /* 20px */
--gvv-space-6: 1.5rem;   /* 24px */
--gvv-space-8: 2rem;     /* 32px */
--gvv-space-10: 2.5rem;  /* 40px */
--gvv-space-12: 3rem;    /* 48px */
--gvv-space-16: 4rem;    /* 64px */

/* Classes utilitaires */
.gvv-m-1 { margin: var(--gvv-space-1); }
.gvv-m-2 { margin: var(--gvv-space-2); }
.gvv-m-4 { margin: var(--gvv-space-4); }
.gvv-p-1 { padding: var(--gvv-space-1); }
.gvv-p-2 { padding: var(--gvv-space-2); }
.gvv-p-4 { padding: var(--gvv-space-4); }
```

#### Grille responsive

```css
/* Container principal */
.gvv-container {
    width: 100%;
    padding-right: var(--gvv-space-4);
    padding-left: var(--gvv-space-4);
    margin-right: auto;
    margin-left: auto;
}

@media (min-width: 576px) {
    .gvv-container { max-width: 540px; }
}

@media (min-width: 768px) {
    .gvv-container { max-width: 720px; }
}

@media (min-width: 992px) {
    .gvv-container { max-width: 960px; }
}

@media (min-width: 1200px) {
    .gvv-container { max-width: 1140px; }
}

@media (min-width: 1400px) {
    .gvv-container { max-width: 1320px; }
}
```

### Accessibilité

#### Principes WCAG 2.1 AA

1. **Contraste des couleurs**
   - Ratio minimum 4.5:1 pour texte normal
   - Ratio minimum 3:1 pour texte large (≥18pt)
   - Tous les couples couleur/fond respectent ces ratios

2. **Navigation au clavier**
   ```css
   /* Focus visible pour navigation clavier */
   *:focus {
       outline: 2px solid var(--gvv-primary);
       outline-offset: 2px;
   }

   /* Focus amélioré pour éléments interactifs */
   .btn-gvv:focus,
   .form-gvv-input:focus,
   a:focus {
       box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.3);
   }
   ```

3. **Textes alternatifs**
   - Toutes les icônes fonctionnelles ont un aria-label
   - Les images décoratives ont alt=""
   - Les boutons icon-only ont un texte accessible

4. **Structure sémantique**
   - Utilisation correcte des balises HTML5 (header, nav, main, footer)
   - Hiérarchie des titres respectée (h1 → h2 → h3)
   - Landmarks ARIA pour navigation

5. **Tailles minimales**
   - Cibles tactiles ≥ 44x44px
   - Texte minimum 14px (0.875rem)
   - Espacement minimum 8px entre éléments interactifs

### États interactifs

#### Hover (survol souris)

```css
/* Liens */
a {
    color: var(--gvv-secondary);
    text-decoration: none;
    transition: color 0.2s ease;
}

a:hover {
    color: var(--gvv-secondary-dark);
    text-decoration: underline;
}

/* Lignes de tableau */
.table-gvv tbody tr:hover {
    background-color: #D3D6FF;
    cursor: pointer;
    transition: background-color 0.15s ease;
}

/* Boutons */
.btn-gvv:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    transition: all 0.2s ease;
}

/* Menu dropdown */
.dropdown-menu > li:hover {
    background-color: #f1f1f1;
    transition: background-color 0.15s ease;
}
```

#### Focus (navigation clavier)

```css
/* Focus global avec anneau */
*:focus-visible {
    outline: 2px solid var(--gvv-primary);
    outline-offset: 2px;
    border-radius: 2px;
}

/* Focus formulaires */
.form-gvv-input:focus {
    border-color: var(--gvv-primary);
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.1);
    outline: none;
}

/* Focus boutons */
.btn-gvv:focus-visible {
    box-shadow: 0 0 0 3px rgba(46, 125, 50, 0.3);
}
```

#### Active (clic)

```css
/* Boutons */
.btn-gvv:active {
    transform: translateY(0);
    box-shadow: 0 1px 2px rgba(0,0,0,0.15);
}

/* Liens */
a:active {
    color: var(--gvv-primary-dark);
}

/* Éléments de navigation */
.nav-link.active {
    background-color: var(--gvv-nav-active);
    color: white;
    font-weight: 600;
}
```

#### Disabled (désactivé)

```css
/* Boutons désactivés */
.btn-gvv:disabled,
.btn-gvv.disabled {
    background-color: var(--gvv-text-disabled);
    border-color: var(--gvv-text-disabled);
    color: white;
    cursor: not-allowed;
    opacity: 0.6;
    pointer-events: none;
}

/* Champs désactivés */
.form-gvv-input:disabled {
    background-color: var(--gvv-bg-secondary);
    color: var(--gvv-text-disabled);
    cursor: not-allowed;
    opacity: 0.6;
}

/* Liens désactivés */
a.disabled {
    color: var(--gvv-text-disabled);
    pointer-events: none;
    cursor: not-allowed;
}
```

#### Loading (chargement)

```css
/* Spinner de chargement */
.gvv-spinner {
    border: 3px solid var(--gvv-bg-secondary);
    border-top: 3px solid var(--gvv-primary);
    border-radius: 50%;
    width: 40px;
    height: 40px;
    animation: gvv-spin 0.8s linear infinite;
}

@keyframes gvv-spin {
    0% { transform: rotate(0deg); }
    100% { transform: rotate(360deg); }
}

/* Bouton en chargement */
.btn-gvv.loading {
    position: relative;
    color: transparent;
    pointer-events: none;
}

.btn-gvv.loading::after {
    content: "";
    position: absolute;
    width: 16px;
    height: 16px;
    top: 50%;
    left: 50%;
    margin-left: -8px;
    margin-top: -8px;
    border: 2px solid white;
    border-top-color: transparent;
    border-radius: 50%;
    animation: gvv-spin 0.6s linear infinite;
}
```

---

## Navigation Hybride

### Principes directeurs

1. **Orientation tâche d'abord, structure ensuite**
   - Accès rapide aux workflows quotidiens (saisie vols, facturation)
   - Navigation contextuelle basée sur le rôle utilisateur
   - Réduction du nombre de clics pour actions fréquentes

2. **Maintien de l'accès direct**
   - Recherche globale pour accès instantané
   - Favoris personnalisables
   - Historique de navigation
   - Menu complet toujours accessible

3. **Progressive disclosure**
   - Afficher d'abord ce qui est pertinent
   - Masquer la complexité pour débutants
   - Permettre accès expert pour utilisateurs avancés

4. **Compatibilité et migration**
   - Coexistence ancien/nouveau système
   - Migration progressive module par module
   - Formation minimale requise

### Architecture proposée

#### Structure de navigation à 3 niveaux

```
┌─────────────────────────────────────────────────────────────┐
│  [Logo GVV] [Recherche]  [Actions rapides]  [User][Section] │
├─────────────────────────────────────────────────────────────┤
│  🏠 Accueil │ ✈️ Vols │ 👥 Membres │ 💰 Compta │ ⚙️ Admin │
└─────────────────────────────────────────────────────────────┘
┌─────────────────────────────────────────────────────────────┐
│  CONTEXTE: Vols > Planeur                                    │
│  ┌─────────────────────────────────────────────────────────┐│
│  │ Actions rapides:                                        ││
│  │ [➕ Nouveau vol] [📋 Saisie auto] [📊 Stats du jour]   ││
│  └─────────────────────────────────────────────────────────┘│
│  Navigation secondaire: Liste | Statistiques | Machines     │
└─────────────────────────────────────────────────────────────┘
```

#### Composants de navigation

##### 1. Barre principale (Top bar)

```html
<nav class="gvv-topbar">
  <!-- Logo et retour accueil -->
  <a href="/calendar" class="gvv-topbar-brand">
    <img src="logo.png" alt="GVV" />
    <span>GVV</span>
  </a>

  <!-- Recherche globale -->
  <div class="gvv-search-global">
    <input type="search"
           placeholder="Rechercher membre, vol, facture..."
           aria-label="Recherche globale"
           class="gvv-search-input" />
    <kbd class="gvv-kbd">Ctrl+K</kbd>
  </div>

  <!-- Actions rapides contextuelles -->
  <div class="gvv-quick-actions">
    <!-- Adapté selon contexte et rôle -->
  </div>

  <!-- User menu -->
  <div class="gvv-user-menu">
    <div class="gvv-user-info">
      <span class="gvv-username">Jean Dupont</span>
      <span class="gvv-role">Administrateur</span>
    </div>
    <div class="gvv-section-selector" data-count="3">
      <select>...</select>
    </div>
  </div>
</nav>
```

##### 2. Navigation principale (Primary nav)

```html
<nav class="gvv-primary-nav" aria-label="Navigation principale">
  <ul class="gvv-nav-tabs">
    <li>
      <a href="/dashboard" class="gvv-nav-tab active">
        <i class="fas fa-home"></i>
        <span>Tableau de bord</span>
      </a>
    </li>
    <li>
      <a href="/vols" class="gvv-nav-tab">
        <i class="fas fa-plane"></i>
        <span>Vols</span>
        <span class="badge-gvv badge-gvv-info">5</span>
      </a>
    </li>
    <li>
      <a href="/membres" class="gvv-nav-tab">
        <i class="fas fa-users"></i>
        <span>Membres</span>
      </a>
    </li>
    <li>
      <a href="/comptabilite" class="gvv-nav-tab" data-role="bureau">
        <i class="fas fa-calculator"></i>
        <span>Comptabilité</span>
      </a>
    </li>
    <li>
      <a href="/admin" class="gvv-nav-tab" data-role="admin">
        <i class="fas fa-cog"></i>
        <span>Administration</span>
      </a>
    </li>
  </ul>
</nav>
```

##### 3. Navigation contextuelle (Secondary nav)

```html
<nav class="gvv-secondary-nav" aria-label="Navigation secondaire">
  <!-- Fil d'Ariane -->
  <ol class="gvv-breadcrumb">
    <li><a href="/vols">Vols</a></li>
    <li><a href="/vols/planeur">Planeur</a></li>
    <li aria-current="page">Liste</li>
  </ol>

  <!-- Actions rapides contextuelles -->
  <div class="gvv-context-actions">
    <button class="btn-gvv-primary">
      <i class="fas fa-plus"></i>
      Nouveau vol planeur
    </button>
    <button class="btn-gvv-secondary">
      <i class="fas fa-magic"></i>
      Saisie automatique
    </button>
    <button class="btn-gvv-secondary">
      <i class="fas fa-sync"></i>
      GESASSO
    </button>
  </div>

  <!-- Sous-navigation -->
  <ul class="gvv-subnav-tabs">
    <li><a href="/vols/planeur/liste" class="active">Liste</a></li>
    <li><a href="/vols/planeur/statistiques">Statistiques</a></li>
    <li><a href="/vols/planeur/machines">Machines</a></li>
    <li><a href="/vols/planeur/formation">Formation</a></li>
  </ul>
</nav>
```

##### 4. Recherche globale avancée

```html
<div class="gvv-search-modal" hidden>
  <div class="gvv-search-dialog">
    <input type="search"
           placeholder="Rechercher dans GVV..."
           class="gvv-search-modal-input"
           autofocus />

    <!-- Résultats groupés -->
    <div class="gvv-search-results">
      <section class="gvv-search-group">
        <h3>Actions rapides</h3>
        <ul>
          <li><a href="#"><i class="fas fa-plus"></i> Nouveau vol planeur</a></li>
          <li><a href="#"><i class="fas fa-file-invoice"></i> Nouvelle facture</a></li>
        </ul>
      </section>

      <section class="gvv-search-group">
        <h3>Membres</h3>
        <ul>
          <li><a href="#">Jean Dupont - Pilote</a></li>
          <li><a href="#">Marie Martin - Instructeur</a></li>
        </ul>
      </section>

      <section class="gvv-search-group">
        <h3>Vols récents</h3>
        <ul>
          <li><a href="#">F-CXXX - 15/10/2025</a></li>
        </ul>
      </section>

      <section class="gvv-search-group">
        <h3>Pages</h3>
        <ul>
          <li><a href="#">Statistiques mensuelles planeurs</a></li>
          <li><a href="#">Journal comptable</a></li>
        </ul>
      </section>
    </div>

    <!-- Aide -->
    <footer class="gvv-search-footer">
      <kbd>↑</kbd><kbd>↓</kbd> naviguer
      <kbd>↵</kbd> sélectionner
      <kbd>Esc</kbd> fermer
    </footer>
  </div>
</div>
```

##### 5. Favoris et historique

```html
<aside class="gvv-sidebar-panel" id="favorites-panel">
  <!-- Favoris personnalisés -->
  <section>
    <h3>Mes favoris</h3>
    <ul class="gvv-favorites-list">
      <li>
        <a href="/vols/planeur/create">
          <i class="fas fa-plus"></i>
          Nouveau vol planeur
        </a>
        <button class="gvv-btn-icon" aria-label="Retirer des favoris">
          <i class="fas fa-star"></i>
        </button>
      </li>
      <li>
        <a href="/compta/mon_compte">
          <i class="fas fa-file-invoice-dollar"></i>
          Mon compte
        </a>
        <button class="gvv-btn-icon" aria-label="Retirer des favoris">
          <i class="fas fa-star"></i>
        </button>
      </li>
    </ul>
    <button class="btn-gvv-secondary btn-gvv-sm">
      <i class="fas fa-edit"></i>
      Gérer les favoris
    </button>
  </section>

  <!-- Historique de navigation -->
  <section>
    <h3>Récent</h3>
    <ul class="gvv-history-list">
      <li><a href="/vols/planeur/page">Liste vols planeur</a></li>
      <li><a href="/membre/edit/123">Fiche membre #123</a></li>
      <li><a href="/compta/page">Journal comptable</a></li>
    </ul>
  </section>
</aside>
```

### Workflows utilisateurs

#### Workflow 1: Pilote saisissant un vol planeur

**Parcours actuel** (5 clics):
1. Menu "Planeurs" → 2. "Saisie vol" → 3. Formulaire → 4. Validation → 5. Retour liste

**Parcours optimisé** (2-3 clics):
1. Bouton "➕ Nouveau vol" (action rapide) → 2. Formulaire → 3. Validation

**Implémentation**:
```html
<!-- Action rapide toujours visible en contexte "Vols" -->
<div class="gvv-context-actions" data-context="vols">
  <button class="btn-gvv-primary"
          onclick="window.location='/vols_planeur/create'">
    <i class="fas fa-plus"></i>
    Nouveau vol planeur
  </button>
</div>

<!-- Alternative: Raccourci clavier -->
<script>
document.addEventListener('keydown', (e) => {
  if (e.ctrlKey && e.key === 'n' && currentContext === 'vols') {
    window.location = '/vols_planeur/create';
  }
});
</script>
```

#### Workflow 2: Trésorier consultant soldes pilotes

**Parcours actuel** (4 clics):
1. Menu "Comptabilité" → 2. "Comptes pilotes" → 3. Filtrer → 4. Afficher

**Parcours optimisé** (1-2 clics):
1. Raccourci "Comptes pilotes" sur dashboard → 2. Affichage direct

**Implémentation**:
```html
<!-- Widget dashboard pour trésorier -->
<div class="card-gvv" data-role="tresorier,bureau">
  <div class="card-gvv-header">
    <h3>Comptes pilotes</h3>
    <a href="/comptes/page/411" class="btn-gvv-sm">Voir tout</a>
  </div>
  <div class="card-gvv-body">
    <!-- Top 5 comptes négatifs -->
    <ul class="gvv-quick-list">
      <li>
        <a href="/comptes/detail/123">
          Jean Dupont
          <span class="text-debit">-245,50 €</span>
        </a>
      </li>
      <!-- ... -->
    </ul>
  </div>
</div>
```

#### Workflow 3: CA consultant validités et alertes

**Parcours actuel** (3 clics):
1. Menu "Rapports" → 2. "Validités" → 3. Affichage

**Parcours optimisé** (0-1 clic):
1. Notifications proactives sur dashboard + badge menu

**Implémentation**:
```html
<!-- Badge de notification sur menu -->
<a href="/alarmes" class="gvv-nav-tab" data-role="ca">
  <i class="fas fa-exclamation-triangle"></i>
  <span>Alertes</span>
  <span class="badge-gvv badge-gvv-error">12</span>
</a>

<!-- Widget dashboard alertes -->
<div class="card-gvv alert-gvv-warning">
  <div class="card-gvv-header">
    <h3>
      <i class="fas fa-exclamation-triangle"></i>
      Validités expirées ou expireront bientôt
    </h3>
  </div>
  <div class="card-gvv-body">
    <ul>
      <li>3 licences expirent ce mois-ci</li>
      <li>2 certificats médicaux à renouveler</li>
      <li>1 visite annuelle machine en retard</li>
    </ul>
    <a href="/alarmes" class="btn-gvv-primary">
      Voir toutes les alertes
    </a>
  </div>
</div>
```

#### Workflow 4: Membre consultant son compte

**Parcours actuel** (3 clics):
1. Menu "Membres" → 2. "Mon compte" → 3. Affichage

**Parcours optimisé** (1 clic):
1. Icône user → Mon compte (dans dropdown)

**Implémentation**:
```html
<!-- Menu utilisateur avec raccourci -->
<div class="dropdown">
  <button class="gvv-user-avatar" data-bs-toggle="dropdown">
    <i class="fas fa-user"></i>
    <span>Jean Dupont</span>
  </button>
  <ul class="dropdown-menu dropdown-menu-end">
    <li class="gvv-dropdown-header">
      <strong>Jean Dupont</strong>
      <span class="text-muted">Pilote</span>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item" href="/compta/mon_compte">
        <i class="fas fa-file-invoice-dollar"></i>
        Mon compte
        <span class="badge-gvv badge-gvv-success float-end">
          142,50 €
        </span>
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="/membre/edit">
        <i class="fas fa-user-edit"></i>
        Ma fiche membre
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="/alarmes">
        <i class="fas fa-exclamation-triangle"></i>
        Mes validités
        <span class="badge-gvv badge-gvv-warning float-end">2</span>
      </a>
    </li>
    <li><hr class="dropdown-divider"></li>
    <li>
      <a class="dropdown-item" href="/auth/change_password">
        <i class="fas fa-key"></i>
        Changer mot de passe
      </a>
    </li>
    <li>
      <a class="dropdown-item" href="/auth/logout">
        <i class="fas fa-sign-out-alt"></i>
        Déconnexion
      </a>
    </li>
  </ul>
</div>
```

#### Workflow 5: Recherche d'un membre spécifique

**Parcours actuel** (4 clics):
1. Menu "Membres" → 2. "Liste" → 3. Rechercher → 4. Cliquer

**Parcours optimisé** (2 touches + 1 clic):
1. Ctrl+K → 2. Taper nom → 3. Sélectionner

**Implémentation**:
```javascript
// Recherche globale avec auto-complétion
class GvvGlobalSearch {
  constructor() {
    this.modal = document.getElementById('gvv-search-modal');
    this.input = document.querySelector('.gvv-search-modal-input');
    this.results = document.querySelector('.gvv-search-results');

    // Raccourci Ctrl+K
    document.addEventListener('keydown', (e) => {
      if (e.ctrlKey && e.key === 'k') {
        e.preventDefault();
        this.open();
      }
    });

    // Recherche en temps réel
    this.input.addEventListener('input', this.debounce(() => {
      this.search(this.input.value);
    }, 300));
  }

  async search(query) {
    if (query.length < 2) return;

    const response = await fetch(`/api/search?q=${encodeURIComponent(query)}`);
    const data = await response.json();

    this.renderResults(data);
  }

  renderResults(data) {
    // Grouper par catégorie: membres, vols, pages, etc.
    let html = '';

    if (data.members?.length) {
      html += '<section class="gvv-search-group">';
      html += '<h3>Membres</h3><ul>';
      data.members.forEach(m => {
        html += `<li><a href="/membre/edit/${m.id}">
          <i class="fas fa-user"></i>
          ${m.nom} ${m.prenom} - ${m.qualif}
        </a></li>`;
      });
      html += '</ul></section>';
    }

    // Similar pour vols, factures, etc.

    this.results.innerHTML = html;
  }

  debounce(func, wait) {
    let timeout;
    return function(...args) {
      clearTimeout(timeout);
      timeout = setTimeout(() => func.apply(this, args), wait);
    };
  }
}

// Initialisation
new GvvGlobalSearch();
```

### Implémentation

#### Phase 1: Fondations (2-3 semaines)

**Objectif**: Mettre en place l'infrastructure de navigation hybride

1. **Créer le fichier CSS de charte graphique**
   ```bash
   # Nouveau fichier
   assets/css/gvv_charter.css
   ```

2. **Implémenter les composants de base**
   - Variables CSS (couleurs, espacements)
   - Classes utilitaires
   - Composants boutons, formulaires, tableaux
   - États interactifs

3. **Créer la nouvelle structure de navigation**
   ```php
   // application/views/bs_header_v2.php
   // application/views/bs_nav_primary.php
   // application/views/bs_nav_secondary.php
   ```

4. **Système de feature flag**
   ```php
   // application/config/config.php
   $config['enable_new_navigation'] = false; // À activer progressivement

   // application/helpers/navigation_helper.php
   function use_new_nav() {
     $CI =& get_instance();
     // Vérifier config + préférence utilisateur
     return $CI->config->item('enable_new_navigation')
         && $CI->session->userdata('beta_navigation');
   }
   ```

#### Phase 2: Navigation principale (3-4 semaines)

**Objectif**: Remplacer menu horizontal par navigation hybride

1. **Migrer la barre principale**
   - Logo + recherche globale
   - Menu utilisateur amélioré
   - Sélecteur de section

2. **Implémenter navigation primaire**
   - Onglets principaux basés sur rôles
   - Indicateurs de notifications
   - Navigation contextuelle

3. **Créer le système de recherche**
   ```php
   // application/controllers/Api.php
   public function search() {
     $query = $this->input->get('q');

     $results = array(
       'members' => $this->membre_model->search($query),
       'flights' => $this->vols_model->search($query),
       'pages' => $this->search_pages($query)
     );

     echo json_encode($results);
   }
   ```

4. **JavaScript de navigation**
   ```javascript
   // assets/javascript/gvv_navigation.js
   // Gestion recherche, raccourcis clavier, etc.
   ```

#### Phase 3: Actions contextuelles (2-3 semaines)

**Objectif**: Réduire nombre de clics pour actions fréquentes

1. **Identifier actions fréquentes par contexte**
   - Contexte "Vols": Nouveau vol, saisie auto, stats
   - Contexte "Compta": Nouvelle écriture, journal, soldes
   - Contexte "Membres": Nouveau membre, licences, emails

2. **Créer boutons d'actions rapides**
   ```php
   // application/libraries/Context_actions.php
   class Context_actions {
     public function get_actions($context, $role) {
       $actions = array();

       if ($context === 'vols_planeur' && has_role('planchiste')) {
         $actions[] = array(
           'label' => 'Nouveau vol',
           'url' => 'vols_planeur/create',
           'icon' => 'fas fa-plus',
           'class' => 'btn-gvv-primary'
         );
       }

       return $actions;
     }
   }
   ```

3. **Intégrer dans vues existantes**
   ```php
   // Dans controllers
   $data['context_actions'] = $this->context_actions
     ->get_actions('vols_planeur', $this->dx_auth->get_role_name());

   // Dans vues
   $this->load->view('bs_context_actions', $data);
   ```

#### Phase 4: Dashboard et widgets (3-4 semaines)

**Objectif**: Page d'accueil orientée workflows

1. **Créer page dashboard**
   ```php
   // application/controllers/Dashboard.php
   class Dashboard extends Gvv_Controller {
     public function index() {
       $role = $this->dx_auth->get_role_name();
       $data['widgets'] = $this->get_widgets_for_role($role);
       $this->load->view('dashboard/index', $data);
     }

     private function get_widgets_for_role($role) {
       // Widgets adaptés au rôle
     }
   }
   ```

2. **Créer widgets par rôle**
   - Pilote: Mes vols récents, Mon compte, Mes validités
   - Trésorier: Comptes débiteurs, Opérations récentes, Stats
   - CA: Alertes, Licences à renouveler, Rapports

3. **Système de widgets configurables**
   ```php
   // Permettre personnalisation dashboard
   // Drag & drop, afficher/masquer widgets
   ```

#### Phase 5: Favoris et historique (2 semaines)

**Objectif**: Navigation personnalisée

1. **Base de données favoris**
   ```sql
   CREATE TABLE user_favorites (
     id INT AUTO_INCREMENT PRIMARY KEY,
     user_id INT NOT NULL,
     url VARCHAR(255) NOT NULL,
     label VARCHAR(100) NOT NULL,
     icon VARCHAR(50),
     position INT DEFAULT 0,
     created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
   );
   ```

2. **Tracking historique navigation**
   ```php
   // application/libraries/Navigation_tracker.php
   // Enregistrer pages visitées (session ou DB)
   ```

3. **Interface gestion favoris**
   - Bouton "⭐" sur chaque page
   - Panel latéral favoris
   - Réorganisation drag & drop

#### Phase 6: Tests et optimisation (2-3 semaines)

**Objectif**: Stabilisation et performance

1. **Tests utilisateurs**
   - Pilotes: Saisie vols
   - Trésoriers: Opérations compta
   - CA: Rapports et admin

2. **Optimisations**
   - Lazy loading des menus
   - Cache recherche
   - Minification CSS/JS

3. **Documentation**
   - Guide utilisateur nouvelle navigation
   - Vidéos tutoriels
   - FAQ

4. **Formation**
   - Sessions formation pour utilisateurs
   - Documentation admin

---

## Plan de migration

### Stratégie de déploiement

#### Option A: Big Bang (déconseillé)
- Remplacement complet en une fois
- Risque élevé, formation intensive nécessaire
- Retour arrière difficile

#### Option B: Progressive (recommandé)
- Coexistence ancien/nouveau système
- Activation opt-in puis opt-out
- Migration module par module

```
Semaine 1-3:   Phase 1 - Fondations
Semaine 4-7:   Phase 2 - Navigation principale (beta opt-in)
Semaine 8-10:  Phase 3 - Actions contextuelles
Semaine 11-14: Phase 4 - Dashboard
Semaine 15-16: Phase 5 - Favoris
Semaine 17-19: Phase 6 - Tests et optimisation
Semaine 20:    Activation par défaut (opt-out possible)
Semaine 24:    Retrait ancien système
```

### Critères de succès

1. **Métriques quantitatives**
   - Réduction 30% du nombre de clics pour workflows principaux
   - Temps saisie vol < 2 minutes (vs 3 actuellement)
   - 80% adoption nouvelle navigation après 1 mois

2. **Métriques qualitatives**
   - Satisfaction utilisateurs ≥ 4/5
   - Taux erreur navigation < 5%
   - Formation < 30 minutes par utilisateur

3. **Métriques techniques**
   - Temps chargement page < 2s
   - Compatibilité 100% navigateurs modernes
   - Accessibilité WCAG 2.1 AA

### Gestion du changement

1. **Communication**
   - Annonce 1 mois avant activation
   - Newsletter hebdomadaire pendant migration
   - Canal support dédié

2. **Formation**
   - Vidéos courtes (< 3 min) par workflow
   - Sessions live Q&A
   - Documentation interactive

3. **Support**
   - Bouton "Aide" contextuel
   - Tooltips première utilisation
   - Chat/forum pour questions

---

## Mockups et exemples visuels

### Mockup 1: Dashboard pilote

```
┌─────────────────────────────────────────────────────────────┐
│ [GVV] [🔍 Rechercher...] [➕ Actions]    Jean Dupont [👤▼] │
├─────────────────────────────────────────────────────────────┤
│ 🏠 Tableau de bord │ ✈️ Vols │ 👥 Membres │ 💰 Compta │ ⚙️  │
├─────────────────────────────────────────────────────────────┤
│                                                              │
│  Bonjour Jean,                                               │
│                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐         │
│  │ Mes vols récents     │  │ Mon compte           │         │
│  ├──────────────────────┤  ├──────────────────────┤         │
│  │ 15/10 F-CXXX  2h30  │  │ Solde: 142,50 €      │         │
│  │ 12/10 F-CYYY  1h45  │  │ ✅ À jour            │         │
│  │ 08/10 F-CXXX  3h15  │  │                       │         │
│  │                      │  │ [Voir détails]       │         │
│  │ [Tous mes vols]      │  └──────────────────────┘         │
│  └──────────────────────┘                                    │
│                                                              │
│  ┌──────────────────────┐  ┌──────────────────────┐         │
│  │ ⚠️ Mes validités      │  │ 📊 Mes statistiques │         │
│  ├──────────────────────┤  ├──────────────────────┤         │
│  │ ⚠️ Visite médicale   │  │ Année: 45h30         │         │
│  │    Expire: 30/11     │  │ Mois: 8h15           │         │
│  │                      │  │ Semaine: 2h30        │         │
│  │ [Voir toutes]        │  │ [Détails]            │         │
│  └──────────────────────┘  └──────────────────────┘         │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Mockup 2: Page vols planeurs avec actions contextuelles

```
┌─────────────────────────────────────────────────────────────┐
│ [GVV] [🔍 F-CXXX...] [➕ Nouveau vol planeur]  Jean [👤▼]  │
├─────────────────────────────────────────────────────────────┤
│ 🏠 │ ✈️ Vols │ 👥 Membres │ 💰 Comptabilité │ ⚙️ Admin     │
├─────────────────────────────────────────────────────────────┤
│ Vols > Planeurs > Liste                                     │
│                                                              │
│ [➕ Nouveau vol] [📋 Saisie auto] [🔄 GESASSO] [📊 Stats]  │
│                                                              │
│ Liste │ Statistiques │ Machines │ Formation                 │
│ ─────                                                        │
│                                                              │
│  Filtres: [Date: Aujourd'hui ▼] [Machine: Toutes ▼]        │
│                                                              │
│  ┌────────────────────────────────────────────────────────┐ │
│  │ Date  │Machine│Pilote      │Durée│Type  │Actions      │ │
│  ├────────────────────────────────────────────────────────┤ │
│  │15/10  │F-CXXX │Dupont J.   │2h30 │Local │[👁][✏][🗑]│ │
│  │15/10  │F-CYYY │Martin M.   │1h15 │Local │[👁][✏][🗑]│ │
│  │15/10  │F-CZZZ │Durand P.   │3h45 │Cross │[👁][✏][🗑]│ │
│  │...                                                      │ │
│  └────────────────────────────────────────────────────────┘ │
│                                                              │
│  [◀ Précédent]  Page 1 sur 5  [Suivant ▶]                  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Mockup 3: Recherche globale

```
┌─────────────────────────────────────────────────────────────┐
│                                                              │
│     ┌────────────────────────────────────────────────────┐  │
│     │ 🔍  dupon                                          │  │
│     ├────────────────────────────────────────────────────┤  │
│     │                                                     │  │
│     │  Actions rapides                                   │  │
│     │  ➕ Nouveau vol planeur                            │  │
│     │  📧 Envoyer email à tous les membres               │  │
│     │                                                     │  │
│     │  Membres (3)                                       │  │
│     │  👤 Dupont Jean - Pilote                           │  │
│     │  👤 Dupont Marie - Instructeur                     │  │
│     │  👤 Dupont-Durand Paul - Élève                     │  │
│     │                                                     │  │
│     │  Vols récents (2)                                  │  │
│     │  ✈️ F-CXXX - Dupont J. - 15/10/2025                │  │
│     │  ✈️ F-CYYY - Dupont M. - 14/10/2025                │  │
│     │                                                     │  │
│     │  Factures (1)                                      │  │
│     │  🧾 Facture #2025-042 - Dupont Jean - 142,50 €     │  │
│     │                                                     │  │
│     ├────────────────────────────────────────────────────┤  │
│     │ ↑↓ naviguer  ↵ sélectionner  Esc fermer          │  │
│     └────────────────────────────────────────────────────┘  │
│                                                              │
└─────────────────────────────────────────────────────────────┘
```

### Mockup 4: Menu utilisateur

```
                                        ┌──────────────────────┐
                                        │ Jean Dupont          │
                                        │ Pilote               │
                                        ├──────────────────────┤
                                        │ 💰 Mon compte        │
                                        │    142,50 € ✅       │
                                        ├──────────────────────┤
                                        │ 👤 Ma fiche membre   │
                                        │ ⚠️  Mes validités (2)│
                                        │ 🔑 Changer MDP       │
                                        ├──────────────────────┤
                                        │ ⭐ Mes favoris       │
                                        │ 📚 Aide              │
                                        ├──────────────────────┤
                                        │ 🚪 Déconnexion       │
                                        └──────────────────────┘
```

---

## Annexes

### Annexe A: Classes CSS principales

```css
/* Navigation */
.gvv-topbar { }
.gvv-primary-nav { }
.gvv-secondary-nav { }
.gvv-breadcrumb { }
.gvv-subnav-tabs { }

/* Composants */
.btn-gvv-primary { }
.btn-gvv-secondary { }
.form-gvv-input { }
.form-gvv-label { }
.table-gvv { }
.card-gvv { }
.badge-gvv { }
.alert-gvv { }

/* Layout */
.gvv-container { }
.gvv-grid { }

/* Utilitaires */
.gvv-m-{1-16} { }
.gvv-p-{1-16} { }
.gvv-text-{xs,sm,base,lg,xl,2xl,3xl} { }
```

### Annexe B: Variables CSS complètes

Voir section "Charte Graphique" pour liste exhaustive.

### Annexe C: Raccourcis clavier

| Raccourci | Action |
|-----------|--------|
| Ctrl+K | Ouvrir recherche globale |
| Ctrl+N | Nouveau (contextuel) |
| Ctrl+S | Sauvegarder formulaire |
| Ctrl+F | Rechercher dans page |
| Ctrl+H | Afficher historique |
| Ctrl+B | Afficher favoris |
| Esc | Fermer modal/panneau |
| ? | Afficher aide raccourcis |

### Annexe D: Points d'extension

Pour faciliter les développements futurs:

1. **Hooks JavaScript**
   ```javascript
   // Événements personnalisés
   GVV.on('navigation.changed', callback);
   GVV.on('search.performed', callback);
   GVV.on('favorite.added', callback);
   ```

2. **Filtres PHP**
   ```php
   // Permettre modification actions contextuelles
   $actions = apply_filters('gvv_context_actions', $actions, $context);

   // Permettre ajout widgets dashboard
   $widgets = apply_filters('gvv_dashboard_widgets', $widgets, $role);
   ```

3. **Templates personnalisables**
   ```php
   // application/views/templates/
   // dashboard_widget.php
   // context_action.php
   // search_result.php
   ```

### Annexe E: Compatibilité navigateurs

| Navigateur | Version minimale | Support |
|------------|------------------|---------|
| Chrome | 90+ | ✅ Complet |
| Firefox | 88+ | ✅ Complet |
| Safari | 14+ | ✅ Complet |
| Edge | 90+ | ✅ Complet |
| Opera | 76+ | ✅ Complet |
| IE 11 | - | ❌ Non supporté |

**Note**: Pour IE 11, afficher message invitant à utiliser navigateur moderne.

---

## Conclusion

Cette charte graphique et ce système de navigation hybride permettront de moderniser GVV tout en préservant sa stabilité et sa familiarité. L'approche progressive garantit une transition en douceur avec formation minimale.

Les workflows orientés tâches réduiront significativement le nombre de clics pour les actions quotidiennes, tout en maintenant l'accès direct aux fonctions via la recherche globale et les favoris.

L'implémentation sur 20-24 semaines permet une validation continue avec les utilisateurs réels et des ajustements au fil de l'eau.

**Prochaines étapes**:
1. Validation de cette charte avec les utilisateurs clés (1 semaine)
2. Démarrage Phase 1 - Fondations (2-3 semaines)
3. Tests beta avec groupe pilote (Phase 2)
4. Déploiement progressif selon plan

---

**Document créé le**: Octobre 2025
**Auteur**: Claude Code
**Version**: 1.0
**Statut**: Proposition initiale
