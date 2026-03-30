# PRD - Optimisation de la Navigation GVV

**Version:** 2.0 (Révision hiérarchie 2 niveaux)
**Date:** 2026-03-30
**Statut:** En cours (étapes 1-2 prévues)

---

## 1. Problématique et Objectifs

### État Actuel
- **100+ cartes** réparties en **8 sections accordéons** (Mon Espace, Vols, Trésorerie, Admin Club, Admin Système, Dev & Tests, etc.)
- Surcharge visuelle et fatigue cognitive : trop de cartes par volet
- Workflows peu guidés : utilisateur doit chercher sa fonction
- Mobile : scroll excessif dans les accordéons denses

### Objectifs Principaux

1. **Réduire surcharge cognitive** passant de 100+ cartes en 1 seule vue à 8 sections → **5-6 dashboards spécialisés**
2. **Implémenter hiérarchie 2 niveaux** :
   - Niveau 1 : Dashboard principal avec 5-6 grandes tuiles (domaines métier)
   - Niveau 2 : Dashboard spécialisé par domaine (contient cartes du volet correspondant)
3. **Systématiser breadcrumbs** : `Accueil > Domaine > (optionnel : sous-contexte)`
4. **Améliorer l'expérience mobile** avec navigation plus claire
5. **Maintenir l'efficacité workflows** : workflows quotidiens accessibles en ≤2 clics

---

## 2. Principes Directeurs

1. **Dashboard-First** : Dashboard est la navigation primaire (70% du trafic), menu reste accès fonctions avancées (30%)
2. **Hiérarchie 2 niveaux avec breadcrumbs** : Utilisateur sait toujours où il est et peut revenir au niveau précédent
3. **Mobile-friendly** : Accordéons simples, pas de scroll excessif, grandes zones cliquables
4. **Organization par domaine métier** : Regroupement naturel des fonctions : Vols, Trésorerie, Administration, Système
5. **Workflows guidés** : Fonctions quotidiennes en haut, accessibles en max 2 clics depuis l'accueil

---

## 3. Architecture Proposée : Hiérarchie 2 Niveaux

### Niveau 1 : Dashboard Principal
Grande page d'accueil avec **5-6 grandes tuiles** (domaines métier) :

```
┌─────────────────────────────────────┐
│  Dashboard GVV - Bienvenue          │
├─────────────────────────────────────┤
│ [👤 Mon Espace]  [✈️ Vols]         │
│ [💰 Trésorerie]  [🏢 Administration] │
│ [🔧 Système]     [📋 Procédures]   │
└─────────────────────────────────────┘
```

Chaque tuile : icône + titre + description brève. Clic → dashboard spécialisé.

### Niveau 2 : Dashboards Spécialisés

**Dashboard Mon Espace** (inchangé essentiellement)
- Calendrier, Ma facture, Mes vols, Mes infos, Mot de passe, Tickets

**Dashboard Vols** (optimisé planchistes)
- Saisie rapide : Planche auto, Saisie vol planeur, Saisie vol avion
- Consultation : Carnets vols, Statistiques
- Gestion : Machines, GESASSO

**Dashboard Trésorerie** (optimisé comptables)
- Saisie quotidienne : Recettes, Dépenses, Règlement pilote, Facturation
- Écritures spéciales (accordéon replié) : 14 types + générique
- Consultation : Dashboard compta, Balance, Journal, Résultat, Bilan
- Gestion : Import OF, Rapprochements, Pièces jointes
- Configuration : Plan comptable, Tarifs

**Dashboard Administration** (CA + trésorier)
- Gestion membres : Membres, Licences
- Gestion ressources : Planeurs, Avions, Terrains
- Configuration : Paramètres, Autorisations
- Suivi : Vols découverte, Rapports, Historique

**Dashboard Système** (admin uniquement)
- Sauvegarde/Restauration, Migrations, Utilisateurs, Rôles, Permissions, Sections

### Breadcrumbs Systématiques
Affichés en haut de chaque page de niveau 2 :

```
🏠 Accueil > 💰 Trésorerie
```

- **Accueil** : lien retour vers dashboard principal
- **Domaine actif** : contexte utilisateur
- Optionnel : sous-contexte si applicable

---

## 4. Navigation Contextuelle

Barres de boutons contextuels en haut des formulaires/listes pour naviguer entre fonctions liées :

| Contexte | Boutons |
|----------|---------|
| **Membre** | [Fiche] [Facture] [Vols Planeur] [Vols Avion] [Certificats] |
| **Comptabilité** | [Balance] [Journal] [Résultat] [Nouvelle écriture] |
| **Vols** | [Saisie] [Planche auto] [Statistiques] [Machines] |
| **Machine** | [Carnets] [Statistiques] [Alarmes] [Configuration] |

---

## 5. Bénéfices Attendus

| Bénéfice | Mesure | Impact |
|----------|--------|--------|
| **Surcharge réduite** | 100+ cartes → 5-6 tuiles au principal | Satisfaction +30% |
| **Mobile friendly** | Accordéons simples, pas de scroll excessif | Temps accès <3s |
| **Navigation claire** | Breadcrumbs systématiques | Users savent où ils sont |
| **Workflows rapides** | Fonctions quotidiennes en ≤2 clics depuis accueil | Efficacité +50% |
| **Maintenance** | Modèle centralisé (breadcrumb helper) | Effort -30% |

---

## 6. Plan d'Implémentation

### Phase 1 : Dashboard Principal + Routing (1-2 semaines) ⭐⭐⭐

- [ ] Créer ou refactoriser vers `/dashboard` (route primaire)
- [ ] Créer contrôleur `dashboard::index()` affichant 5-6 grandes tuiles
- [ ] Routes `/dashboard/{domaine}` vers dashboards spécialisés
- [ ] Tests routage et permissions par rôle

### Phase 2 : Dashboards Spécialisés (2-3 semaines) ⭐⭐⭐

- [ ] Créer 5-6 dashboards spécialisés
- [ ] Migrer cartes des volets actuels
- [ ] Réorganiser par fréquence (quotidien en haut)
- [ ] Tests responsive mobile

### Phase 3 : Breadcrumbs + Contextes (1-2 semaines) ⭐⭐⭐

- [ ] Créer helper `breadcrumb_helper.php` 
- [ ] Intégrer breadcrumbs dans toutes les vues de niveau 2
- [ ] Implémenter barres de navigation contextuelle (helper `contextual_nav()`)
- [ ] Tests workflows complets

### Phase 4 : Menu Simplifié (1-2 semaines) ⭐⭐

- [ ] Réduire menu à 2 niveaux maximum
- [ ] Regrouper par domaine (Vols, Membres, Compta, Admin, Système)
- [ ] Éliminer doublons avec dashboard
- [ ] Feature flag pour migration progressive

### Phase 5 : Optimisations Avancées (optionnel) ⭐

- Recherche globale, favoris utilisateur, analytics navigation

---

## 7. Roadmap

| Sprints | Durée | Priorité | Livrable |
|---------|-------|----------|----------|
| 1-2 | 2-3 sem | HAUTE | Dashboard principal + routing |
| 3-4 | 2-3 sem | HAUTE | Dashboards spécialisés |
| 5-6 | 1-2 sem | HAUTE | Breadcrumbs + contextes |
| 7-8 | 1-2 sem | MOYENNE | Menu simplifié |
| 9+ | Variable | BASSE | Optimisations avancées |

### Quick Wins (À faire en premier)

1. **Volet Trésorerie - Écritures prioritaires** (2-3 jours)
   - Déplacer Recettes, Dépenses, Règlement pilote en haut
   - Impact : -50% clics trésoriers

2. **Volet Vols - Planche auto en premier** (1 jour)
   - Reposition carte prioritaire
   - Impact : -50% clics planchistes

3. **Barre contextuelle Membre** (3-4 jours)
   - Boutons Fiche → Facture → Vols
   - Impact : -33% clics CA

---

## 8. Risques et Mitigation

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Résistance changement | Haut | Moyenne | Communication en amont, migration progressive, feature flags |
| Régression fonctionnelle | Haut | Faible | Tests exhaustifs, rollback possible |
| Problèmes mobile | Moyen | Moyenne | Tests vrais devices, beta testeurs |
| Dashboard surchargé | Moyen | Faible | Accordéons secondaires, pagination si besoin |

---

## 9. Métriques de Succès

**Quantitatives :**
- Réduction clics moyen par workflow : -30% minimum
- Temps d'accès fonctions fréquentes : <3 secondes
- Taux utilisation dashboard vs menu : 70/30
- Satisfaction mobile : >80%

**Qualitatives :**
- Feedback utilisateurs post-déploiement
- Tickets support navigation : -50%
- Temps apprentissage nouveaux users : -20%

---

## 10. Décisions à Valider

1. **5 ou 6 domaines au principal ?**
   - Recommandé : 5 (ou 6 si Procédures séparé)

2. **Breadcrumb à tous les niveaux ?**
   - Recommandé : niveau 2 minimum

3. **Conserver menu horizontal ?**
   - Recommandé : OUI mais simplifié (phase 4)

4. **Accordéon secondaire pour écritures ?**
   - Recommandé : OUI (replié par défaut)

---

## 11. Glossaire

- **Dashboard principal** : page d'accueil avec 5-6 tuiles (niveau 1)
- **Dashboard spécialisé** : page contenant cartes d'un domaine (niveau 2)
- **Breadcrumb** : fil d'Ariane : `Accueil > Domaine > Optionnel`
- **Navigation contextuelle** : boutons accès direct entre fonctions liées
- **Quick win** : amélioration rapide à fort impact

---

**Prochaines étapes :**
1. Revue et validation PRD par équipe projet
2. Ajustements selon feedback utilisateurs
3. Lancement Phase 1
