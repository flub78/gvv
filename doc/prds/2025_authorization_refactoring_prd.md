# PRD : Refonte du Système d'Autorisation

**Version du Document :** 1.0
**Date :** 2025-01-08
**Statut :** Proposé
**Auteur :** Extrait du Plan de Refonte par Gemini

---

## 1. Résumé Exécutif

Ce document décrit les exigences pour refondre le système d'autorisation de GVV. Le modèle actuel de contrôle d'accès basé sur les rôles hiérarchique (RBAC) sera remplacé par un système d'autorisation plat, basé sur les domaines et conscient des sections. Le nouveau système supportera à la fois des rôles globaux et spécifiques aux sections, fournira une interface utilisateur améliorée pour la gestion des permissions, et implémentera des contrôles d'accès aux données au niveau des lignes.

---

## 2. Analyse de l'État Actuel

### 2.1. Système Existant (Architecture 2011)

- **Schéma :** Le système utilise un ensemble de rôles hiérarchiques (`membre` → `planchiste` → `ca` → `bureau` → `tresorier` → `admin`). Les permissions sont stockées sous forme d'URIs sérialisés assignés à chaque rôle.
- **Hiérarchie :** `admin` (2) → `tresorier` (9) → `bureau` (3) → `ca` (8) → `planchiste` (7) → `membre` (1).
- **Contrôleurs Affectés :** `backend.php`, `migration.php`, `presences.php`, `rapports.php`, `config.php`.

### 2.2. Problèmes Principaux du Système Actuel

1.  **Problème d'Héritage Hiérarchique :** Les rôles de niveau supérieur (ex. Trésoriers) héritent incorrectement des permissions des rôles de niveau inférieur (ex. accès aux données de vol du Planchiste).
2.  **Pas de Granularité par Section :** Les permissions sont globales. Un utilisateur ne peut pas avoir différents rôles dans différentes sections (ex. Planchiste pour 'Planeur' et simple 'Utilisateur' pour 'ULM').
3.  **Gestion Complexe des URI :** L'édition manuelle des chaînes URI sérialisées dans une zone de texte est difficile et source d'erreurs.
4.  **Pas de Sécurité au Niveau des Lignes :** Le système ne peut pas distinguer entre voir ses "propres données" versus "toutes les données" dans une table (ex. un utilisateur voyant seulement ses propres vols vs. un gestionnaire voyant tous les vols).
5.  **UX Médiocre :** Il n'y a pas d'interface intuitive pour que les administrateurs puissent voir ou gérer les permissions utilisateur d'un coup d'œil.

---

## 3. Objectifs et Exigences

### 3.1. Exigences Fonctionnelles

1.  **EF1 : Modèle de Rôle Plat**
    -   Éliminer la hiérarchie rigide parent-enfant des rôles.
    -   Les rôles doivent être des domaines d'autorité indépendants (ex. "Gestionnaire de Vol", "Gestionnaire Financier").
    -   Les permissions ne doivent pas être héritées automatiquement.

2.  **EF2 : Rôles Conscients des Sections**
    -   Support des **Rôles Globaux** qui s'appliquent à l'ensemble de l'application (ex. `admin`, `bureau`).
    -   Support des **Rôles de Section** qui sont spécifiques à une seule section (ex. `tresorier`, `planchiste`).
    -   Un utilisateur doit pouvoir détenir différents rôles dans différentes sections.

3.  **EF3 : Interface de Gestion des Rôles Utilisateur Améliorée**
    -   Fournir une interface mono-page pour gérer les rôles utilisateur.
    -   L'interface doit présenter une liste d'utilisateurs avec des cases à cocher pour les attributions de rôles.
    -   Les administrateurs doivent pouvoir filtrer la liste d'utilisateurs par section et par statut actif/inactif.
    -   L'interface doit utiliser DataTables pour la recherche et le tri.
    -   Il doit être visuellement clair quels rôles sont globaux versus spécifiques à une section.

4.  **EF4 : Interface de Gestion des Permissions Améliorée**
    -   Créer une interface pour gérer les permissions assignées à chaque rôle.
    -   Les permissions (URIs) doivent être organisées par fonctionnalité/domaine d'application (ex. "Membres", "Vols").
    -   Remplacer la zone de texte par une liste de vérification visuelle pour assigner les permissions.
    -   L'interface doit clairement montrer quels rôles ont quelles permissions.

5.  **EF5 : Accès aux Données au Niveau des Lignes**
    -   Implémenter un mécanisme pour contrôler l'accès aux données au niveau des lignes, distinguant entre données "propres" et "toutes" les données.
    -   **Exemples :**
        -   Un `utilisateur` ne doit voir que ses propres factures.
        -   Un `tresorier` doit voir toutes les factures de sa section assignée.
        -   Un `auto_planchiste` peut éditer ses propres vols.
        -   Un `planchiste` peut éditer tous les vols de sa section.

6.  **EF6 : Dérogation Admin**
    -   Le rôle `admin` doit être maintenu comme super-utilisateur.
    -   Les utilisateurs admin doivent contourner toutes les vérifications de permissions et avoir un accès illimité.

7.  **EF7 : Framework de Tests**
    -   Des tests unitaires complets doivent être développés pour la nouvelle logique d'autorisation.
    -   Les tests doivent couvrir l'accès autorisé, le refus d'accès non autorisé, et les règles de données au niveau des lignes pour chaque rôle.

### 3.2. Exigences Non Fonctionnelles

1.  **ENF1 : Compatibilité Ascendante & Migration**
    -   Le système doit supporter une migration graduelle.
    -   Un flag de fonctionnalité doit être implémenté pour basculer entre l'ancien et le nouveau système d'autorisation.
    -   Un plan de retour en arrière clair doit être en place pour chaque phase de la migration.

2.  **ENF2 : Performance**
    -   Les vérifications individuelles de permissions doivent s'exécuter en moins de 10ms.
    -   Le système doit utiliser la mise en cache basée sur les sessions pour les permissions utilisateur afin de minimiser les requêtes de base de données.

3.  **ENF3 : Sécurité**
    -   Une piste d'audit doit être créée pour enregistrer tous les changements de rôles et permissions.
    -   Le système doit être sécurisé contre l'escalade de privilèges.
    -   La posture de sécurité par défaut doit être "refuser par défaut".

4.  **ENF4 : Maintenabilité**
    -   Le nouveau code doit être bien documenté et auto-explicatif.
    -   L'architecture doit faciliter l'ajout de nouveaux rôles et permissions à l'avenir.

---

## 4. Nouveaux Composants d'Interface (Maquettes)

### 4.1. Page de Gestion des Rôles Utilisateur

**Concept :** Une grille basée sur DataTables où les lignes sont des utilisateurs et les colonnes sont des rôles, groupés par portée globale et spécifique à la section.

**Fonctionnalités :**
- Filtres pour Section et utilisateurs Actifs/Inactifs.
- Cases à cocher pour accorder/révoquer des rôles.
- Sauvegarde basée sur AJAX.

**Structure de la Maquette :**
```
[Filtre Section: Tous / Planeur / ULM / Avion] [Utilisateurs Actifs Seulement: ☑] [Recherche: ______]

+----------+---------+------------+------------------+------------------+
| Username | Email   | Rôles      | Section: Planeur | Section: ULM     |
|          |         | Globaux    | Rôles            | Rôles            |
+----------+---------+------------+------------------+------------------+
| fpeignot | f@...   | ☑ Admin    | ☑ CA             | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ Planchiste     | ☐ Planchiste     |
+----------+---------+------------+------------------+------------------+
| agnes    | a@...   | ☐ Admin    | ☑ Tresorier      | ☐ Tresorier      |
|          |         | ☐ Bureau   | ☑ Utilisateur    | ☐ Utilisateur    |
+----------+---------+------------+------------------+------------------+
```

### 4.2. Page de Gestion des Permissions de Rôle

**Concept :** Une interface pour configurer les permissions URI spécifiques pour chaque rôle.

**Fonctionnalités :**
- Menus déroulants pour sélectionner le Rôle et (si applicable) la Section.
- Permissions groupées par contrôleur/fonctionnalité.
- Cases à cocher pour les types de permission (Voir, Créer, Éditer, Supprimer).

**Structure de la Maquette :**
```
[Sélectionner Rôle: Planchiste ▼]  [Section: Planeur ▼]

Domaine: Vols Planeur
┌──────────────────┬──────┬────────┬──────┬────────────┐
│ Action           │ Voir │ Créer  │ Édit.│ Supprimer  │
├──────────────────┼──────┼────────┼──────┼────────────┤
│ vols_planeur/    │  ☑   │   ☑    │  ☑   │   ☑        │
│ vols_planeur/pdf │  ☑   │   ☐    │  ☐   │   ☐        │
└──────────────────┴──────┴────────┴──────┴────────────┘

Domaine: Membres
┌──────────────────┬──────┬────────┬──────┬────────────┐
│ Action           │ Voir │ Créer  │ Édit.│ Supprimer  │
├──────────────────┼──────┼────────┼──────┼────────────┤
│ membre/          │  ☑   │   ☐    │  ☐   │   ☐        │
│ membre/view      │  ☑   │   ☐    │  ☐   │   ☐        │
└──────────────────┴──────┴────────┴──────┴────────────┘

[Sauvegarder Permissions]  [Annuler]
```
