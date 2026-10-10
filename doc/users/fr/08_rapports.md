# Rapports et Statistiques

Ce guide présente les fonctionnalités de reporting de GVV. Vous apprendrez à générer des rapports, analyser l'activité et exporter des données.

## Table des matières

1. [Vue d'ensemble](#vue-ensemble)
2. [Types de rapports](#types-rapports)
3. [Statistiques adhérents](#statistiques-adherents)
4. [Génération de rapports](#generation)
5. [Formats d'export](#formats)
6. [Analyses statistiques](#statistiques)
7. [Tableaux de bord](#tableaux-bord)

## Vue d'ensemble {#vue-ensemble}

Le module de rapports GVV propose :
- **Rapports prédéfinis** pour les besoins courants
- **Rapports personnalisés** selon vos critères
- **Exports** dans différents formats
- **Statistiques** d'activité détaillées
- **Tableaux de bord** temps réel

### Accès aux rapports

![Menu des rapports](../screenshots/08_reports/01_reports_menu.png)

Les rapports sont accessibles via :
- **Menu "Rapports"** dans la navigation principale
- **Liens directs** depuis les modules concernés
- **Tableaux de bord** de synthèse
- **Exports** automatisés

## Types de rapports {#types-rapports}

### Rapports d'activité

#### Activité de vol
- **Heures de vol** par période (jour, mois, année)
- **Nombre de vols** par type d'activité
- **Utilisation** des aéronefs
- **Activité** par pilote ou instructeur

#### Performance des aéronefs
- **Taux d'utilisation** de chaque aéronef
- **Maintenance** et immobilisations
- **Rentabilité** par appareil
- **Coûts d'exploitation**

#### Évolution temporelle
- **Comparaisons** inter-périodes
- **Tendances** d'activité
- **Saisonnalité** des vols
- **Projections** et prévisions

### Rapports financiers

#### Chiffre d'affaires
- **CA** par type de prestation
- **CA** par aéronef
- **CA** par période
- **Comparaisons** budgétaires

#### Comptes clients
- **Soldes** clients détaillés
- **Encours** et créances
- **Impayés** et retards
- **Analyse** des paiements

#### Rentabilité
- **Marge** par activité
- **Coûts** directs et indirects
- **Seuils** de rentabilité
- **Indicateurs** de performance

### Rapports réglementaires

#### Déclarations officielles
- **Heures** de vol pour autorités
- **Activité** des instructeurs
- **Statistiques** fédérales
- **Bilans** d'activité annuels

#### Sécurité et maintenance
- **Suivi** des maintenances
- **Incidents** et événements
- **Qualifications** des pilotes
- **Validité** des documents

### Rapports de gestion

#### Membres et formation
- **Liste** des membres par section
- **Progression** des élèves
- **Activité** des instructeurs
- **Qualifications** et brevets

#### Planification
- **Utilisation** des créneaux
- **Demandes** de vol
- **Optimisation** des plannings
- **Prévisions** d'activité

## Statistiques adhérents {#statistiques-adherents}

La page **Statistiques adhérents** décrit la population des adhérents d'une année : âges, répartition hommes / femmes, évolution sur plusieurs années, fidélisation et ancienneté, par section et pour le club.

### Accès

La page est réservée aux membres du CA. Elle couvre tout le club : un membre du CA d'une seule section voit aussi les chiffres et les listes de noms des autres sections. Elle est accessible :
- par le menu **Gestion → Rapports → Statistiques adhérents** ;
- par la carte **Statistiques adhérents** du tableau de bord **Administration du club**.

Le sélecteur en haut de la page choisit l'année affichée. L'année reste mémorisée pendant la session.

### Définitions

- **Adhérent de l'année** : membre ayant une cotisation enregistrée pour cette année. Les membres sans cotisation ne sont pas comptés, même s'ils sont actifs.
- **Adhérent d'une section** : adhérent ayant un compte pilote (compte 411) dans cette section. Un membre qui a un compte dans plusieurs sections est compté dans chacune d'elles, mais une seule fois dans le total club. La somme des colonnes de section peut donc dépasser le total club.
- **Âge** : âge en années révolues au **1er janvier** de l'année sélectionnée, comme pour les déclarations fédérales.

### Âge inconnu

Les adhérents sans date de naissance, ou avec une date invalide, sont comptés sur une ligne **Âge inconnu** et exclus des âges moyens et médians. Un bandeau en haut de la page indique leur nombre. Le bouton du bandeau déplie leur liste, avec un lien vers chaque fiche membre pour compléter la date de naissance.

### Indicateurs et répartition par âge

![Indicateurs](../screenshots/08_reports/stats_adherents_indicateurs.png)

Le bloc **Indicateurs** donne l'effectif, l'âge moyen et l'âge médian de chaque section et du club.

Deux tableaux répartissent ensuite les adhérents par âge :
- **Classes réglementaires** : moins de 25 ans, 25-59 ans, 60 ans et plus. Ce sont les classes demandées par la fédération.
- **Tranches de 10 ans** : de « moins de 20 ans » à « 80 ans et plus », avec un histogramme pour le club.

![Répartition par tranches de 10 ans](../screenshots/08_reports/stats_adherents_tranches_10_ans.png)

Chaque cellule indique le nombre d'adhérents et, entre parenthèses, le pourcentage de la colonne.

### Hommes / femmes et pyramide des âges

![Répartition hommes / femmes](../screenshots/08_reports/stats_adherents_sexes.png)

Le tableau donne la répartition hommes / femmes par section. La pyramide des âges croise le sexe et les tranches de 10 ans pour le club : les hommes à gauche, les femmes à droite.

### Évolution

![Évolution du club](../screenshots/08_reports/stats_adherents_evolution.png)

Le tableau et le graphique présentent, pour les 10 dernières années civiles, l'effectif du club, l'âge moyen et la répartition par classe réglementaire. Une année sans aucune cotisation enregistrée est signalée par « Aucune cotisation enregistrée » et laisse un trou dans la courbe.

> **Attention** : une baisse brutale de l'effectif traduit souvent des cotisations qui n'ont pas été saisies dans GVV cette année-là, et non une baisse réelle du nombre d'adhérents.

### Fidélisation

![Fidélisation](../screenshots/08_reports/stats_adherents_fidelisation.png)

Pour l'année N sélectionnée, chaque adhérent est classé dans une catégorie :
- **Nouveaux** : première cotisation en N ;
- **Retours** : adhérents en N, absents en N-1 mais adhérents auparavant ;
- **Renouvellements** : adhérents en N-1 et en N ;
- **Départs** : adhérents en N-1 qui n'ont pas cotisé en N.

On a toujours : nouveaux + retours + renouvellements = effectif de N, et renouvellements + départs = effectif de N-1.

La ligne **Rétention des nouveaux** donne la part des nouveaux de N-1 qui sont encore adhérents en N.

Cliquer sur un nombre affiche la liste des membres concernés, avec un lien vers leur fiche. Une seule liste est ouverte à la fois.

Les sections sont calculées à partir des comptes pilotes actuels : un membre passé d'une section à une autre n'est pas vu comme un départ de l'une et une arrivée dans l'autre.

> **Année en cours** : tant que toutes les cotisations ne sont pas enregistrées, les départs sont provisoires. Un bandeau le rappelle.

### Ancienneté

![Ancienneté](../screenshots/08_reports/stats_adherents_anciennete.png)

L'ancienneté est calculée à partir de la plus ancienne des deux dates : date d'inscription de la fiche membre ou première cotisation enregistrée. Les tranches sont : moins de 2 ans, 2 à 5 ans, 5 à 10 ans, 10 ans et plus. La ligne **Inconnue** regroupe les membres sans date d'inscription ni cotisation exploitable.

> **Limite** : pour les membres saisis à la mise en service de GVV, la date d'inscription est souvent celle de la création de la fiche et non celle de l'adhésion réelle. L'ancienneté est alors faussée ; corriger la date d'inscription dans la fiche membre si nécessaire.

## Génération de rapports {#generation}

### Interface de génération

#### Sélection du rapport
1. **Choisissez** le type de rapport souhaité
2. **Configurez** les paramètres et filtres
3. **Sélectionnez** la période d'analyse
4. **Générez** et visualisez le rapport

#### Paramètres courants

**Période :**
- **Dates** de début et fin
- **Périodes prédéfinies** (mois, trimestre, année)
- **Exercices** comptables
- **Comparaisons** multi-périodes

**Filtres :**
- **Section** d'activité
- **Aéronefs** spécifiques
- **Pilotes** ou instructeurs
- **Types** de vol

**Détail :**
- **Niveau** de synthèse ou détail
- **Regroupements** par critères
- **Tris** et classements
- **Calculs** et totalisations

### Rapports prédéfinis

#### Rapports mensuels
- **Activité** mensuelle du club
- **Facturation** et encaissements
- **Utilisation** des aéronefs
- **Performance** des instructeurs

#### Rapports annuels
- **Bilan** d'activité annuel
- **Évolution** sur plusieurs années
- **Statistiques** complètes
- **Analyses** de tendances

#### Rapports de suivi
- **Maintenance** des aéronefs
- **Formation** des pilotes
- **Gestion** financière
- **Qualité** et sécurité

### Rapports personnalisés

#### Création de rapports
1. **Définissez** les données sources
2. **Sélectionnez** les champs à afficher
3. **Configurez** les filtres et tris
4. **Personnalisez** la présentation
5. **Sauvegardez** le modèle

#### Réutilisation
- **Modèles** de rapports sauvegardés
- **Partage** avec d'autres utilisateurs
- **Modification** des paramètres
- **Automatisation** de la génération

## Formats d'export {#formats}

### Formats disponibles

#### PDF
- **Rapport** formaté pour impression
- **Mise en page** professionnelle
- **Graphiques** et tableaux intégrés
- **Archivage** long terme

#### Excel (CSV)
- **Données** tabulaires exploitables
- **Import** dans tableurs
- **Analyses** complémentaires
- **Graphiques** personnalisés

#### HTML
- **Consultation** en ligne
- **Partage** via email ou web
- **Interactivité** avec liens
- **Mise à jour** dynamique

### Options d'export

#### Paramètres de sortie
- **Format** de fichier
- **Qualité** d'impression
- **Orientation** (portrait/paysage)
- **Marges** et en-têtes

#### Contenu
- **Données** complètes ou résumées
- **Graphiques** inclus ou séparés
- **Annexes** et détails
- **Légendes** et notes

#### Distribution
- **Envoi** automatique par email
- **Stockage** sur serveur
- **Publication** sur site web
- **Archivage** automatique

## Analyses statistiques {#statistiques}

### Indicateurs de performance

#### Activité de vol
- **Heures** de vol totales et moyennes
- **Nombre** de vols par période
- **Durée** moyenne des vols
- **Taux** d'activité par jour

#### Utilisation des ressources
- **Taux d'occupation** des aéronefs
- **Disponibilité** effective
- **Temps** de maintenance
- **Optimisation** des plannings

#### Performance économique
- **Chiffre d'affaires** par heure de vol
- **Coût** horaire d'exploitation
- **Rentabilité** par activité
- **Retour** sur investissement

### Analyses comparatives

#### Évolutions temporelles
- **Croissance** d'activité
- **Variations** saisonnières
- **Tendances** long terme
- **Cycles** d'activité

#### Benchmarking
- **Comparaisons** inter-sections
- **Performance** relative des aéronefs
- **Efficacité** des instructeurs
- **Positionnement** tarifaire

### Prévisions et projections

#### Modèles prédictifs
- **Extrapolation** des tendances
- **Modèles** saisonniers
- **Prévisions** d'activité
- **Planification** des ressources

#### Scénarios
- **Analyse** de sensibilité
- **Impact** des variations
- **Optimisation** des paramètres
- **Aide** à la décision

## Tableaux de bord {#tableaux-bord}

### Dashboard temps réel

#### Indicateurs clés
- **Activité** du jour
- **Soldes** clients
- **Disponibilité** des aéronefs
- **Météo** et conditions de vol

#### Alertes
- **Maintenance** programmée
- **Impayés** clients
- **Qualifications** expirées
- **Anomalies** détectées

### Personnalisation

#### Configuration
- **Choix** des indicateurs affichés
- **Seuils** d'alerte personnalisés
- **Couleurs** et présentation
- **Mise à jour** automatique

#### Rôles utilisateurs
- **Tableaux** spécifiques par rôle
- **Accès** aux données sensibles
- **Droits** de modification
- **Partage** d'informations

## Utilisation avancée

### Automatisation

#### Rapports automatiques
- **Génération** programmée
- **Envoi** automatique par email
- **Archivage** systématique
- **Notifications** d'anomalies

#### Intégrations
- **Export** vers outils BI
- **APIs** pour applications tierces
- **Synchronisation** avec comptabilité
- **Interfaces** web

### Analyses avancées

#### Data mining
- **Découverte** de patterns
- **Corrélations** entre variables
- **Segmentation** des données
- **Modèles** prédictifs

#### Optimisation
- **Simulation** de scénarios
- **Optimisation** des ressources
- **Aide** à la décision
- **ROI** des investissements

## Bonnes pratiques

### Utilisation efficace

#### Planification
- **Définissez** vos besoins de reporting
- **Planifiez** la génération régulière
- **Automatisez** les rapports récurrents
- **Archivez** systématiquement

#### Analyse
- **Interprétez** les données dans le contexte
- **Comparez** avec les objectifs
- **Identifiez** les tendances significatives
- **Partagez** les insights utiles

### Qualité des données

#### Validation
- **Vérifiez** la cohérence des données sources
- **Contrôlez** les paramètres de génération
- **Validez** les résultats obtenus
- **Documentez** les méthodologies

#### Amélioration continue
- **Évaluez** la pertinence des rapports
- **Ajustez** selon les retours utilisateurs
- **Optimisez** les performances
- **Évoluez** avec les besoins

### Sécurité et confidentialité

#### Accès contrôlé
- **Limitez** l'accès aux données sensibles
- **Authentifiez** les utilisateurs
- **Tracez** les consultations
- **Protégez** les exports

#### Archivage
- **Conservez** les rapports historiques
- **Sécurisez** les données archivées
- **Respectez** les durées légales
- **Facilitez** les audits

---

**Guide GVV** - Gestion Vol à Voile  
*Rapports et Statistiques - Version française*  
*Mis à jour en décembre 2024*

[◀ Comptabilité](07_comptabilite.md) | [Retour à l'index](README.md)