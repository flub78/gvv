# PRD : Système de Réservation d'Aéronefs

## 1. Introduction

Ce document décrit les exigences pour un nouveau système de réservation d'aéronefs au sein de l'application GVV. Le système permettra aux membres du club de réserver des aéronefs, gérer leurs réservations et vérifier la disponibilité des instructeurs. Il est conçu pour prévenir les conflits de planification et fournir une vue centralisée et claire de l'allocation des ressources.

## 2. Objectifs

*   **Rationaliser les Réservations :** Simplifier le processus pour que les membres puissent réserver et gérer les réservations d'aéronefs.
*   **Prévenir les Conflits :** Éliminer les doubles réservations pour les aéronefs et les instructeurs.
*   **Améliorer la Visibilité :** Fournir des plannings clairs et à jour pour tous les aéronefs et instructeurs.
*   **Permissions Flexibles :** Accorder des niveaux d'accès appropriés pour les pilotes standard, les instructeurs et les administrateurs. Utiliser le mécanisme d'autorisation standard de GVV décrit dans...
*   **Gérer la Disponibilité de la Flotte :** Permettre au personnel autorisé de bloquer les aéronefs pour maintenance ou autres raisons.

## 3. Rôles et Personas Utilisateur

*   **Pilote :** Un membre standard du club qui peut réserver des vols pour lui-même.
*   **Pilote Élève :** Un pilote en formation qui doit réserver des vols avec un instructeur.
*   **Instructeur :** Un instructeur certifié qui peut enseigner aux élèves et aussi gérer le planning de vol plus large.
*   **Administrateur/Mécanicien :** Un utilisateur responsable de l'état opérationnel de la flotte d'aéronefs.

## 4. User Stories

| En tant que... | Je veux... | Pour que je puisse... |
| :--- | :--- | :--- |
| Pilote | Voir un planning de tous les aéronefs disponibles | Trouver et réserver un avion pour un vol personnel. |
| Pilote | Modifier la date ou l'heure de ma réservation | Ajuster mes plans sans avoir besoin d'annuler et de re-réserver. |
| Pilote | Annuler ma réservation | Libérer l'aéronef pour d'autres membres si je ne peux plus voler. |
| Pilote Élève | Voir la disponibilité de tous les instructeurs | Planifier une session de formation quand un instructeur est libre. |
| Pilote Élève | Réserver un aéronef et un instructeur ensemble | M'assurer d'avoir à la fois l'avion et l'instructeur pour ma leçon. |
| Instructeur | Voir et gérer toutes les réservations dans le système | Aider les membres, résoudre les conflits et gérer le planning quotidien. |
| Administrateur | Marquer un aéronef comme "indisponible" pour une période | Empêcher les réservations pendant la maintenance ou les inspections. |
| Utilisateur | Être empêché de réserver un aéronef réservé | Éviter de se présenter pour un vol que quelqu'un d'autre a réservé. |
| Utilisateur | Être empêché de réserver un instructeur occupé | M'assurer que l'instructeur est réellement disponible pour voler avec moi. |
| Utilisateur | Être empêché de réserver si la somme de mes réservations dépasse ma limite de crédit | |
| Utilisateur | Interagir avec le système de réservation en cliquant sur le calendrier ou les réservations existantes | Mettre à jour facilement les réservations |
| Utilisateur | Glisser-déposer les réservations dans le calendrier | Mettre à jour facilement les réservations |
| Utilisateur | Étendre les réservations en faisant glisser la fin | Mettre à jour facilement les réservations |

## 5. Exigences Fonctionnelles

### 5.1. Gestion de Réservation de Base

*   **Créer une Réservation :** Les utilisateurs authentifiés peuvent réserver un aéronef pour une date et un créneau horaire spécifiques.
*   **Modifier une Réservation :** Un utilisateur peut changer la date, l'heure ou l'aéronef de sa **propre** réservation.
*   **Supprimer une Réservation :** Un utilisateur peut annuler sa **propre** réservation.
*   **Voir les Réservations :** Les utilisateurs peuvent voir leurs propres réservations à venir. Une vue calendrier (quotidienne/hebdomadaire/mensuelle) devrait être disponible pour voir toutes les réservations d'aéronefs.

### 5.2. Permissions et Rôles

*   Les **Pilotes** peuvent seulement créer, modifier ou supprimer leurs propres réservations.
*   Les **Instructeurs** ont des privilèges élevés. Ils peuvent créer, modifier et supprimer la réservation de **n'importe quel** membre.
*   Un rôle **Administrateur** (ou similaire) est requis pour gérer la disponibilité des aéronefs.

### 5.3. Disponibilité des Aéronefs

*   Les administrateurs doivent avoir une fonction pour créer un "bloc d'indisponibilité" pour un aéronef.
*   Ce bloc nécessite une date/heure de début, une date/heure de fin et une raison obligatoire (ex. "visite 50 heures", "Événement privé").
*   Le système **doit interdire** la création ou le déplacement d'une réservation dans un créneau horaire qui chevauche un bloc d'indisponibilité.

### 5.4. Disponibilité des Instructeurs

*   Le système doit fournir une vue dédiée (ex. un calendrier consolidé) montrant quand les instructeurs sont réservés.
*   Un instructeur est considéré comme indisponible s'il est déjà assigné à une autre réservation dans ce créneau horaire.

### 5.5. Prévention des Conflits (Règles Métier)

*   **Conflit d'Aéronef :** Le système doit rejeter toute tentative de créer ou déplacer une réservation vers un horaire qui chevauche une réservation existante pour le **même aéronef**.
*   **Conflit d'Instructeur :** Le système doit rejeter toute tentative de réserver un vol avec un instructeur qui est déjà planifié pour un vol chevauchant.
*   Les réservations ne peuvent pas être créées ou déplacées dans le passé.

## 6. Exigences Non Fonctionnelles

*   **Utilisabilité :** L'interface devrait être intuitive, avec une forte préférence pour un calendrier visuel glisser-déposer pour faire et modifier les réservations.
*   **Sécurité :** Toutes les modifications de réservation doivent être authentifiées et autorisées en fonction des rôles utilisateur.
*   **Performance :** La vue du planning doit se charger rapidement, même avec un grand nombre de réservations.
*   **Réutilisation de code :** Le contrôleur de calendrier a déjà un mécanisme pour que les membres indiquent leur intention. Réutiliser la même bibliothèque javascript de calendrier. Ce calendrier utilise un Google calendar comme source de données, pour la réservation d'avion la source de données peut être la base de données locale.

## 7. Hors Périmètre

*   Facturation automatisée ou traitement des paiements pour les vols.
*   Une fonctionnalité de liste d'attente pour les aéronefs ou instructeurs complètement réservés.
*   Notifications automatisées (email, SMS) pour les confirmations de réservation, rappels ou annulations.
*   Fonctionnalités avancées de réservation récurrente (ex. "réserver chaque mardi pendant 6 semaines").
*

## 8. Maquettes et Idées

* mockups/gvv_presence_calendar.png - calendrier existant dans GVV
* mockups/gvv_presence_popup.png - popup de présence existant dans GVV
* mockups/openflyers_booking_calendar.png - un exemple de calendrier de réservation d'une autre application
* mockups/openflyers_booking_form.png - un exemple de formulaire de réservation d'une autre application


