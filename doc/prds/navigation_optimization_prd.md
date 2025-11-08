# PRD - Optimisation de la Navigation GVV

**Version:** 1.0
**Date:** 2025-11-08
**Statut:** Draft

---

## 1. Contexte

L'application GVV comprend environ 50 contrôleurs correspondant à 100-200 routes. La navigation actuelle repose sur deux systèmes parallèles :

1. **Menu horizontal** : 7 sous-menus, 3 niveaux de profondeur
2. **Tableau de bord accordéon** : 6 volets, 2 niveaux maximum

L'application est utilisée à la fois sur smartphone et PC (responsive). Le tableau de bord est intuitivement utilisable sur smartphone, tandis que le menu horizontal fonctionne mieux sur PC.

### Problématique

- **Redondance** : Certaines fonctionnalités sont accessibles via le menu ET le tableau de bord, créant confusion et maintenance double
- **Incomplétion du dashboard** : Certaines fonctionnalités ne sont accessibles que via le menu
- **Nombre de clics** : Jusqu'à 3 clics nécessaires pour atteindre certaines fonctions via le menu
- **Manque de cohérence** : Pas de workflow clair entre fonctionnalités liées
- **Navigation contextuelle limitée** : Peu de boutons d'accès direct entre fonctionnalités connexes

---

## 2. Objectifs

### Objectifs Principaux

1. **Réduire le nombre de clics** pour les workflows courants (objectif : ≤2 clics)
2. **Améliorer l'expérience mobile** en privilégiant le tableau de bord
3. **Maintenir l'expérience PC** avec un menu simplifié
4. **Créer des workflows cohérents** avec navigation contextuelle
5. **Éliminer la redondance** entre menu et dashboard

### Objectifs Secondaires

- Faciliter la découverte des fonctionnalités par les nouveaux utilisateurs
- Améliorer la maintenance en centralisant la navigation
- Réduire la charge cognitive avec une organisation par rôle/tâche

---

## 3. Analyse de l'Existant

### 3.1. Menu Horizontal (7 sous-menus)

**Structure actuelle :**

1. **Administration** (CA uniquement) - 3 niveaux
   - Vols découverte (2 items)
   - Rapports (6 items)
   - HEVA (6 items)
   - Admin club (9 items)
   - Admin comptable (6 items - trésorier)
   - Admin système (5 items - admin)

2. **Membres** (tous) - 2 niveaux
   - Liste membres
   - Licences (CA)
   - Ma fiche
   - Mot de passe
   - Ma facture
   - Calendrier

3. **Planeur** - 2 niveaux
   - Carnets de vol
   - Saisie vols (planchiste)
   - Planche auto (planchiste)
   - GESASSO (planchiste)
   - Machines
   - Statistiques (4 items)
   - Formation (4 items)

4. **Avion** - 2 niveaux
   - Carnets de vol
   - Saisie vols (planchiste)
   - Machines (planchiste)
   - Statistiques (2 items)
   - Pompes (admin)

5. **Comptabilité** (bureau) - 2 niveaux
   - Journal (bureau)
   - Balance (bureau)
   - Balance pilotes (CA)
   - Résultat (CA)
   - Bilan (CA)
   - Achats (CA)
   - Trésorerie (CA)
   - Pièces jointes (CA)
   - Dashboard (CA)
   - Synchro OpenFlyers (3 items - trésorier)
   - Rapprochements (trésorier)

6. **Écritures** (trésorier) - 1 niveau
   - 14 types d'écritures comptables

7. **Menu Club spécifique** (variable selon club)

**Total estimé : ~80 entrées de menu**

### 3.2. Tableau de Bord Accordéon (6 volets)

**Structure actuelle :**

1. **Mon espace personnel** (tous) - ouvert par défaut
   - Calendrier (si activé)
   - Ma facture
   - Mes vols avion
   - Mes vols planeur
   - Mes infos
   - Mot de passe
   - Mes tickets (si activé)

2. **Gestion des vols** (planchiste)
   - **Planeur :**
     - Carnets de vol
     - Saisie des vols
     - Planche auto
   - **Avion :**
     - Carnets de vol
     - Saisie des vols

3. **Trésorerie** (bureau)
   - **Comptabilité :**
     - Comptes bancaires
     - Journal (bureau)
     - Balance (bureau)
     - Comptes pilotes (CA)
     - Résultat (CA)
     - Bilan (CA)
     - Achats (CA)
     - Trésorerie (CA)
     - Pièces jointes (CA)
     - Dashboard comptable (CA)
     - Import opérations (trésorier)
     - Import soldes (trésorier)
     - Associations comptes (trésorier)
     - Rapprochements (trésorier)
   - **Écritures :**
     - 14 types d'écritures + écriture générique (trésorier)
   - **Configuration comptable :**
     - Tarifs (trésorier)
     - Plan comptable (trésorier)

4. **Administration du club** (CA)
   - Configuration
   - Paramètres
   - Autorisations
   - Terrains
   - Planeurs
   - Avions
   - Membres
   - Liste de diffusion
   - Envoi email
   - Formation
   - Rapports
   - Historique
   - Vols découverte
   - Procédures

5. **Administration système** (admin)
   - Sauvegarde
   - Restauration
   - Migrations
   - Utilisateurs
   - Rôles
   - Permissions
   - Sections
   - Rôles sections
   - Admin

6. **Développement & Tests** (dev - fpeignot)
   - Anonymisation
   - Extraction test
   - Cohérence BDD

**Total : ~55 cartes dans le dashboard**

### 3.3. Boutons d'Accès Direct Identifiés

**Dans les formulaires :**

- **Formulaire membre :**
  - → Journal de compte
  - → Certificats
  - → Vols avion du pilote
  - → Vols planeur du pilote
  - → Tickets du pilote (si activé)

- **Journal de compte :**
  - → Création achat directement depuis le journal

**Dans le menu utilisateur (icône profil) :**
- Ma facture
- Mot de passe
- Tickets
- Validités (alarmes)
- Déconnexion

### 3.4. Workflows Principaux Identifiés

#### Workflow 1 : Planchiste - Saisie de vols quotidienne
**Parcours actuel :**
1. Connexion → Dashboard
2. Gestion des vols (clic accordéon)
3. Planche auto (clic carte) = **2 clics**

**Ou via menu :**
1. Menu Planeur → Planche auto = **2 clics**

#### Workflow 2 : Trésorier - Saisie comptable quotidienne
**Parcours actuel :**
1. Connexion → Dashboard
2. Trésorerie (clic accordéon)
3. Section Écritures (scroll)
4. Type d'écriture (ex: Recettes) = **3 clics + scroll**

**Ou via menu :**
1. Menu Écritures → Type d'écriture = **2 clics**

#### Workflow 3 : Bureau - Consultation balance
**Parcours actuel :**
1. Connexion → Dashboard
2. Trésorerie (clic accordéon)
3. Balance = **2 clics**

**Ou via menu :**
1. Menu Comptabilité → Balance = **2 clics**

#### Workflow 4 : CA - Gestion membre
**Parcours actuel via dashboard :**
1. Dashboard → Admin club → Membres
2. Sélection membre → Formulaire
3. Bouton "Facture" pour voir le compte = **3 clics total**

**Parcours actuel via menu :**
1. Menu Membres → Liste membres
2. Sélection → Formulaire
3. Bouton "Facture" = **3 clics total**

#### Workflow 5 : Utilisateur - Consultation personnelle
**Parcours actuel :**
1. Connexion → Dashboard
2. Mon espace personnel (ouvert par défaut)
3. Ma facture = **1 clic** ✓

**Ou via menu utilisateur (icône profil) :**
1. Clic icône → Ma facture = **2 clics**

#### Workflow 6 : Admin - Gestion utilisateurs
**Parcours actuel via dashboard :**
1. Dashboard → Admin système → Utilisateurs = **2 clics**

**Via menu :**
1. Menu Admin → Admin système → Utilisateurs = **3 clics**

### 3.5. Analyse de Couverture

**Fonctionnalités UNIQUEMENT dans le menu :**
- GESASSO (synchro planeur)
- Statistiques planeur détaillées (âge, historique)
- Formation détaillée (FAI, par pilote)
- Pompes (avion)
- HEVA (fédération)
- Rapports administratifs (DGAC, FFVV)
- Vols découverte (recherche par ID)

**Fonctionnalités UNIQUEMENT dans le dashboard :**
- Dashboard comptable
- Import/vérification soldes OpenFlyers
- Associations comptes OpenFlyers
- Procédures

**Redondances (menu ET dashboard) :**
- Toutes les fonctionnalités de l'espace personnel
- Carnets de vol (planeur/avion)
- Saisie vols
- Planche auto
- Machines (planeur/avion)
- Journal comptable
- Balance
- Comptes bancaires
- Écritures comptables (tous types)
- Configuration club
- Membres
- Terrains
- Autorisations
- Licences
- Plan comptable
- Tarifs
- Pièces jointes
- Admin système (sauvegarde, migration, utilisateurs, rôles)

**Taux de redondance : ~60%**

---

## 4. Stratégie d'Optimisation de la Navigation

### 4.1. Principes Directeurs

1. **Dashboard First pour mobile** : Le tableau de bord devient la navigation principale, optimisé pour mobile
2. **Menu simplifié pour PC** : Menu réduit à 1-2 niveaux, accès rapide aux fonctions avancées
3. **Navigation contextuelle renforcée** : Boutons d'accès direct entre fonctionnalités liées
4. **Organisation par workflow** : Regroupement des fonctions selon les tâches métier
5. **Persistance de l'état** : Mémorisation des volets ouverts (déjà implémenté)

### 4.2. Architecture Proposée

#### A. Tableau de Bord Principal (navigation par défaut)

**Réorganisation en 5 volets basés sur les rôles :**

##### Volet 1 : Mon Espace (tous) - ouvert par défaut
*Inchangé, fonctionne bien*

- Calendrier
- Ma facture
- Mes vols avion
- Mes vols planeur
- Mes infos
- Mot de passe
- Mes tickets

##### Volet 2 : Vols (planchiste) - optimisé pour workflow quotidien
**Réorganisation :**

**Saisie Rapide** (priorité haute - en haut) :
- ⭐ **Planche auto planeur** (accès direct 1 clic)
- ⭐ **Saisie vol planeur** (accès direct 1 clic)
- ⭐ **Saisie vol avion** (accès direct 1 clic)

**Consultation** :
- Carnets vol planeur
- Carnets vol avion
- Statistiques planeur (nouveau : regroupe mensuel, annuel, cumuls)
- Statistiques avion (nouveau : regroupe mensuel, annuel)

**Gestion** (moins fréquent) :
- Planeurs (machines)
- Avions (machines)
- GESASSO (synchro - déplacé du menu)

##### Volet 3 : Trésorerie (bureau/trésorier) - optimisé pour workflow comptable
**Réorganisation par fréquence d'utilisation :**

**Saisie Quotidienne** (priorité haute - en haut) :
- ⭐ **Recettes** (accès direct 1 clic)
- ⭐ **Règlement pilote** (accès direct 1 clic)
- ⭐ **Dépenses** (accès direct 1 clic)
- ⭐ **Facturation pilote** (accès direct 1 clic)

**Écritures Spécifiques** (accordéon secondaire replié par défaut) :
- Crédit pilote
- Débit pilote
- Avoir fournisseur
- Utilisation avoir fournisseur
- Virement
- Dépôt espèces
- Retrait liquide
- Remboursement capital
- Encaissement section
- Reversement section
- ⚠️ Écriture générique

**Consultation** :
- Dashboard comptable (nouveau : en tête)
- Comptes bancaires (soldes)
- Balance générale
- Balance pilotes
- Journal comptable
- Résultat
- Bilan
- Trésorerie (flux)
- Achats par année

**Gestion & Synchro** (trésorier) :
- Import opérations OpenFlyers
- Import/vérif soldes OpenFlyers
- Associations comptes OF
- Rapprochements bancaires
- Pièces jointes

**Configuration** (trésorier) :
- Plan comptable
- Tarifs
- Types de tickets

##### Volet 4 : Administration Club (CA)
*Structure actuelle conservée, bon équilibre*

**Membres & Communication :**
- Membres
- Licences
- Listes de diffusion
- Envoi email

**Ressources :**
- Planeurs
- Avions
- Terrains

**Gestion & Sécurité :**
- Configuration club
- Paramètres
- Autorisations
- Formation (événements/certificats)

**Suivi :**
- Vols découverte
- Rapports club
- Historique
- Procédures

##### Volet 5 : Administration Système (admin)
*Structure actuelle conservée*

- Sauvegarde
- Restauration
- Migrations
- Utilisateurs
- Rôles
- Permissions URI
- Sections
- Rôles par section
- Admin (outils)

**Volet 6 : Dev & Tests** (dev uniquement) - inchangé

#### B. Menu Horizontal Simplifié (2 niveaux max)

**Objectif :** Accès rapide aux fonctions avancées non dans le dashboard + contexte de travail

**Structure proposée (5 menus) :**

##### Menu 1 : Vols
- **Planeur :**
  - Carnets de vol
  - Statistiques avancées (âge, histo, par pilote)
  - Formation (stats annuelles, club, FAI)
- **Avion :**
  - Carnets de vol
  - Pompes (si activé)
- **Découverte :**
  - Liste des bons
  - Recherche par ID

##### Menu 2 : Membres
- Liste membres
- Licences par année
- Ma fiche
- Mon compte
- Calendrier

##### Menu 3 : Comptabilité (bureau)
- Dashboard comptable
- Journal
- Balance / Balance pilotes
- Résultat / Bilan
- Trésorerie / Achats
- Rapprochements

##### Menu 4 : Administration (CA)
**Sous-menu 1 : Club**
- Configuration
- Membres / Licences
- Flotte (planeurs, avions, terrains)
- Communication (listes, envoi)
- Autorisations

**Sous-menu 2 : Fédération (HEVA)**
- Association
- Licenciés
- Facturation club
- Vente licences
- Types qualif
- Facturation

**Sous-menu 3 : Rapports**
- Rapports club
- Rapports financiers
- Validités (alarmes)
- Tickets usage
- Rapport FFVV
- Rapport DGAC (admin)

##### Menu 5 : Système (admin)
- Sauvegarde / Restauration
- Migrations
- Utilisateurs / Rôles / Permissions
- Sections

**Menu Utilisateur (icône profil - inchangé) :**
- Ma facture
- Mot de passe
- Tickets
- Validités
- Déconnexion

**Total menu : ~40 entrées (réduction de 50%)**

#### C. Navigation Contextuelle Renforcée

**Principe :** Barre de boutons contextuels en haut des formulaires/listes

##### Contexte Membre (formulaire ou liste)
**Barre de navigation contexte :**
- [Fiche] [Facture] [Vols Planeur] [Vols Avion] [Certificats] [Tickets]

**Depuis n'importe quelle vue membre, 1 clic pour :**
- Voir sa facture
- Consulter ses vols
- Vérifier ses certificats
- Consulter ses tickets

##### Contexte Comptabilité
**Depuis Balance pilotes :**
- [Balance] [Journal pilote] [Nouvelle écriture]

**Depuis Journal compte pilote :**
- [Balance pilotes] [Fiche membre] [Nouvelle écriture]
- Bouton "Achat" intégré (déjà présent)

**Depuis Journal comptable :**
- [Balance] [Résultat] [Bilan] [Nouvelle écriture]

##### Contexte Vols
**Depuis Carnet de vol planeur :**
- [Saisie vol] [Planche auto] [Statistiques] [Machines]

**Depuis Formulaire machine :**
- [Carnets vol] [Statistiques machine] [Alarmes machine]

##### Contexte Configuration
**Depuis Planeurs/Avions :**
- [Terrains] [Tarifs] [Alarmes]

**Depuis Plan comptable :**
- [Tarifs] [Journal] [Balance]

---

## 5. Bénéfices Attendus

### 5.1. Réduction du Nombre de Clics

| Workflow | Avant | Après | Gain |
|----------|-------|-------|------|
| Planchiste - Planche auto | 2 clics | 1 clic | -50% |
| Trésorier - Saisie recette | 3 clics + scroll | 1 clic | -66% |
| Trésorier - Dashboard comptable | 2 clics | 1 clic | -50% |
| CA - Gestion membre → facture | 3 clics | 2 clics (contexte) | -33% |
| Bureau - Balance | 2 clics | 1 clic ou 2 clics | 0 à -50% |

**Gain moyen : 30-50% de clics en moins pour les workflows quotidiens**

### 5.2. Amélioration Mobile

- **Dashboard optimisé** : Accordéon native mobile, pas de survol requis
- **Fonctions prioritaires en haut** : Moins de scroll nécessaire
- **Menu simplifié** : Moins de sous-menus profonds
- **Persistance état** : Retour direct au contexte de travail

### 5.3. Cohérence et Découvrabilité

- **Élimination redondance** : Une seule façon principale d'accéder à chaque fonction
- **Organisation par rôle** : Dashboard adapté aux permissions
- **Navigation contextuelle** : Découverte naturelle des fonctions liées
- **Workflow guidé** : Ordre logique dans les volets

### 5.4. Maintenance Simplifiée

- **Centralisation dashboard** : Modifications dans un seul fichier
- **Menu allégé** : Moins de code à maintenir
- **Code réutilisable** : Barres de navigation contextuelles communes

---

## 6. Plan d'Implémentation

### Phase 1 : Fondations et Dashboard (Priorité Haute)
**Durée estimée : 3-4 semaines**

#### 1.1. Audit de navigation (1 semaine)
- [ ] Documenter toutes les routes et leurs accès actuels
- [ ] Identifier les doublons menu/dashboard
- [ ] Créer matrice de couverture complète
- [ ] Valider avec utilisateurs les workflows prioritaires

#### 1.2. Réorganisation Dashboard (2 semaines)
- [ ] Réorganiser volet "Vols" (saisie rapide en haut)
- [ ] Réorganiser volet "Trésorerie" (écritures quotidiennes en haut)
  - [ ] Créer accordéon secondaire pour écritures spécifiques
- [ ] Ajouter fonctions manquantes au dashboard :
  - [ ] GESASSO (dans volet Vols)
  - [ ] Statistiques regroupées (nouvelle carte)
  - [ ] Pompes si activé (dans volet Vols)
- [ ] Tests responsive mobile (smartphone/tablette)

#### 1.3. Tests et ajustements (1 semaine)
- [ ] Tests utilisateurs sur workflows principaux
- [ ] Ajustements visuels (icônes, couleurs)
- [ ] Validation accessibilité mobile
- [ ] Documentation mise à jour

### Phase 2 : Navigation Contextuelle (Priorité Haute)
**Durée estimée : 2-3 semaines**

#### 2.1. Composant barre contextuelle (1 semaine)
- [ ] Créer helper `contextual_nav_bar($context, $id)`
- [ ] Définir contextes standards :
  - [ ] `membre` : Fiche, Facture, Vols, Certificats, Tickets
  - [ ] `comptabilite` : Balance, Journal, Nouvelle écriture
  - [ ] `vols_planeur` : Saisie, Planche auto, Statistiques, Machines
  - [ ] `vols_avion` : Saisie, Carnets, Statistiques, Machines
  - [ ] `machine` : Carnets, Stats, Alarmes, Terrains
- [ ] Style CSS Bootstrap cohérent
- [ ] Tests responsive

#### 2.2. Intégration contextes (1-2 semaines)
- [ ] Intégrer dans formulaires membres
- [ ] Intégrer dans vues comptabilité
- [ ] Intégrer dans vues vols
- [ ] Intégrer dans vues machines
- [ ] Tests navigation complète

#### 2.3. Tests et validation (1 semaine)
- [ ] Tests workflows utilisateurs
- [ ] Validation ergonomie
- [ ] Documentation

### Phase 3 : Simplification Menu (Priorité Moyenne)
**Durée estimée : 2 semaines**

#### 3.1. Restructuration menu (1 semaine)
- [ ] Créer nouveau `bs_menu_v2.php`
- [ ] Réduire à 2 niveaux maximum
- [ ] Regrouper par domaine (Vols, Membres, Compta, Admin, Système)
- [ ] Éliminer doublons avec dashboard
- [ ] Garder accès fonctions avancées

#### 3.2. Migration progressive (1 semaine)
- [ ] Feature flag `use_simplified_menu`
- [ ] Tests A/B si possible
- [ ] Validation utilisateurs
- [ ] Migration définitive
- [ ] Suppression ancien menu

### Phase 4 : Optimisations Avancées (Priorité Basse)
**Durée estimée : 2-3 semaines**

#### 4.1. Recherche globale (optionnel)
- [ ] Barre de recherche dans header
- [ ] Recherche membres par nom
- [ ] Recherche vols par date/machine
- [ ] Recherche écritures comptables
- [ ] Raccourcis clavier

#### 4.2. Favoris utilisateur (optionnel)
- [ ] Permettre épingler fonctions fréquentes
- [ ] Section "Mes favoris" dans dashboard
- [ ] Personnalisation par utilisateur
- [ ] Stockage en base ou session

#### 4.3. Analytics navigation (optionnel)
- [ ] Logger utilisation menu vs dashboard
- [ ] Tracker chemins utilisateurs
- [ ] Identifier points de friction
- [ ] Optimisation continue

---

## 7. Priorisation et Roadmap

### Critères de Priorisation

1. **Impact utilisateur** : Fréquence d'utilisation quotidienne
2. **Gain efficacité** : Réduction nombre de clics
3. **Complexité technique** : Effort développement
4. **Risque** : Impact sur fonctionnalités existantes

### Roadmap Recommandée

#### Sprint 1-2 (Semaines 1-4) : Dashboard Optimisé ⭐⭐⭐
**Priorité : HAUTE**
- Réorganisation volets Vols et Trésorerie
- Ajout fonctions manquantes au dashboard
- Tests mobile
- **Livrable :** Dashboard complet et optimisé

#### Sprint 3-4 (Semaines 5-8) : Navigation Contextuelle ⭐⭐⭐
**Priorité : HAUTE**
- Création composant barres contextuelles
- Intégration dans vues principales
- Tests workflows
- **Livrable :** Navigation contextuelle opérationnelle

#### Sprint 5-6 (Semaines 9-12) : Menu Simplifié ⭐⭐
**Priorité : MOYENNE**
- Restructuration menu
- Migration progressive
- Validation utilisateurs
- **Livrable :** Menu simplifié en production

#### Sprint 7+ (Semaines 13+) : Optimisations Avancées ⭐
**Priorité : BASSE (optionnel)**
- Recherche globale
- Favoris utilisateur
- Analytics
- **Livrable :** Fonctionnalités avancées selon feedback

### Quick Wins (À faire en premier)

1. **Volet Trésorerie - Écritures prioritaires** (2-3 jours)
   - Déplacer Recettes, Dépenses, Règlement pilote en haut
   - Impact : -50% clics pour trésoriers (utilisation quotidienne)

2. **Volet Vols - Planche auto en premier** (1 jour)
   - Carte Planche auto en position 1
   - Impact : -50% clics pour planchistes (utilisation quotidienne)

3. **Barre contextuelle Membre** (3-4 jours)
   - Boutons Fiche → Facture → Vols → Certificats
   - Impact : -33% clics pour gestion membres

---

## 8. Métriques de Succès

### Métriques Quantitatives

1. **Réduction clics moyen** par workflow : -30% minimum
2. **Temps d'accès fonctions fréquentes** : <3 secondes
3. **Taux d'utilisation dashboard vs menu** : 70/30 (objectif)
4. **Satisfaction mobile** : >80% (enquête)

### Métriques Qualitatives

1. **Feedback utilisateurs** : Enquête post-déploiement
2. **Tickets support navigation** : -50% (objectif)
3. **Temps d'apprentissage nouveaux utilisateurs** : -20% (objectif)

### Indicateurs de Monitoring

- Logs d'utilisation menu vs dashboard (si implémenté)
- Chemins de navigation les plus fréquents
- Taux de rebond sur certaines pages
- Feedback qualitatif mensuel

---

## 9. Risques et Mitigation

### Risques Identifiés

| Risque | Impact | Probabilité | Mitigation |
|--------|--------|-------------|------------|
| Résistance au changement utilisateurs | Haut | Moyenne | Communication en amont, formation, migration progressive |
| Régression fonctionnelle | Haut | Faible | Tests exhaustifs, feature flags, rollback possible |
| Maintenance double temporaire | Moyen | Haute | Planning serré Phase 3, cleanup rapide |
| Problèmes mobile non détectés | Moyen | Moyenne | Tests sur vrais devices, beta testeurs mobile |
| Dashboard trop chargé | Moyen | Moyenne | Accordéons secondaires, pagination si besoin |

### Plan de Rollback

- Feature flags pour activer/désactiver nouveau dashboard
- Conservation ancien menu en parallèle Phase 1-2
- Sauvegarde BDD avant migration menu
- Documentation rollback procédure

---

## 10. Décisions à Valider

### Décisions Architecture

1. **Dashboard ou Menu comme navigation primaire ?**
   - **Recommandation :** Dashboard primaire (mobile-first), menu secondaire
   - **Justification :** Tendance mobile, accordéon plus intuitif

2. **Garder menu horizontal ou passer menu latéral ?**
   - **Recommandation :** Garder horizontal mais simplifié
   - **Justification :** Habitudes utilisateurs, moins de refonte

3. **Niveau de personnalisation ?**
   - **Recommandation :** Pas de personnalisation Phase 1-3, évaluer Phase 4
   - **Justification :** Éviter complexité, standardiser workflows

### Décisions Fonctionnelles

1. **Réorganiser écritures comptables en accordéon secondaire ?**
   - **Recommandation :** OUI, avec 4 écritures fréquentes en accès direct
   - **Justification :** Réduire scroll, prioriser usage quotidien

2. **Conserver menu utilisateur (icône profil) ?**
   - **Recommandation :** OUI, inchangé
   - **Justification :** Fonctionne bien, standard UX

3. **Ajouter recherche globale ?**
   - **Recommandation :** Phase 4 optionnel
   - **Justification :** Nice to have mais pas critique

---

## 11. Annexes

### A. Matrice de Couverture Complète

*(À compléter lors Phase 1.1 - Audit)*

| Fonctionnalité | Menu | Dashboard | Contexte | Priorité |
|----------------|------|-----------|----------|----------|
| Planche auto | ✓ | ✓ | Vols | Haute |
| Recettes | ✓ | ✓ | - | Haute |
| ... | | | | |

### B. Glossaire

- **Workflow :** Suite d'actions utilisateur pour accomplir une tâche métier
- **Navigation contextuelle :** Boutons d'accès direct entre fonctions liées
- **Dashboard :** Tableau de bord accordéon page d'accueil
- **Quick win :** Amélioration rapide à fort impact

### C. Références

- Bootstrap 5 Accordion : https://getbootstrap.com/docs/5.0/components/accordion/
- Mobile-first navigation patterns : https://www.nngroup.com/articles/mobile-navigation-patterns/
- UX navigation best practices : https://www.nngroup.com/articles/navigation-you-are-here/

---

## 12. Changelog

| Version | Date | Auteur | Modifications |
|---------|------|--------|---------------|
| 1.0 | 2025-11-08 | Claude Code | Création initiale du PRD |

---

**Prochaines étapes :**
1. Revue et validation PRD par l'équipe
2. Ajustements selon feedback
3. Lancement Phase 1 - Sprint 1
