# PRD - Contrôle des carnets de route

## 1. Objectif

Permettre aux administrateurs club de contrôler la continuité des carnets de route des avions sur une période donnée, afin de détecter rapidement les écarts et recouvrements d’horamètre.

## 2. Contexte

Le contrôle des carnets de route est une tâche sensible pour la fiabilité des données d’exploitation et de maintenance. Une lecture manuelle des vols rend difficile :
- la vérification de la continuité des horamètres,
- l’identification des incohérences entre vols successifs,
- l’analyse des périodes longues,
- l’exploitation des résultats pour archivage et communication.

Ce PRD définit un écran de contrôle dédié, accessible depuis les entrées d’administration club.

## 3. Périmètre

### 3.1 Inclus

- Ajout d’une entrée dans le menu Avion pour les administrateurs club.
- Ajout d’une carte sur le dashboard Administration club pour les administrateurs club.
- Les deux entrées doivent ouvrir la même page de contrôle des carnets de route.
- Filtrage par machine (avion) avec prise en compte de la section active.
- Filtrage par date de début et date de fin.
- Valeurs par défaut des dates :
  - date de début = 1er janvier de l’année courante,
  - date de fin = date courante.
- Affichage des vols de la machine sélectionnée sur la période.
- Contrôle visuel de la continuité horamètre entre vols successifs.
- Affichage explicite des écarts et recouvrements d’horamètre.
- Affichage en datatable avec recherche et pagination.
- Export CSV et PDF.

### 3.2 Hors périmètre

- Correction automatique des incohérences d’horamètre.
- Modification des vols depuis l’écran de contrôle.
- Contrôle des carnets d’autres types d’aéronefs si non couverts par le périmètre avion.
- Mécanisme d’alerte automatique par email.

## 4. Parties prenantes

- Administrateur club
- Responsable flotte
- Trésorier ou gestionnaire de section (consultation éventuelle)

## 5. User Stories

| En tant que... | Je veux... | Afin de... |
| :--- | :--- | :--- |
| Administrateur club | accéder rapidement à un contrôle des carnets de route | vérifier la qualité des enregistrements de vols |
| Administrateur club | filtrer par avion et période | concentrer l’analyse sur une machine donnée |
| Administrateur club | visualiser les vols successifs avec continuité colorée | identifier immédiatement les anomalies |
| Administrateur club | voir les écarts ou recouvrements sous forme de lignes dédiées | comprendre la nature de l’incohérence |
| Administrateur club | rechercher et paginer dans la liste | naviguer efficacement dans de longues périodes |
| Administrateur club | exporter en CSV et PDF | partager et archiver les résultats du contrôle |

## 6. Exigences Fonctionnelles

### 6.1 Accès et navigation

- EF-001 : Une entrée de menu doit être ajoutée dans le menu Avion pour les administrateurs club.
- EF-002 : Une carte doit être ajoutée sur le dashboard Administration club pour les administrateurs club.
- EF-003 : Les deux entrées doivent rediriger vers la même page de contrôle des carnets de route.
- EF-004 : L’accès à la page doit respecter les règles d’autorisation existantes de GVV.

### 6.2 Filtres

- EF-010 : La page doit proposer un filtre de sélection d’avion.
- EF-011 : La page doit proposer un filtre de date de début et un filtre de date de fin.
- EF-012 : Par défaut, la date de début doit être initialisée au 1er janvier de l’année courante.
- EF-013 : Par défaut, la date de fin doit être initialisée à la date courante.
- EF-014 : Si une section active est sélectionnée (hors « Toutes »), le filtre avion ne doit proposer que les avions de cette section.
- EF-015 : Si la section active est « Toutes », le filtre avion doit proposer les avions de toutes les sections.

### 6.3 Affichage des vols

- EF-020 : Une fois la machine sélectionnée, la page doit afficher tous les vols de la période.
- EF-021 : L’affichage doit inclure les colonnes suivantes : date, pilote, immatriculation, horamètre de début, horamètre de fin, durée, lieu, observation.
- EF-022 : Les vols doivent être ordonnés chronologiquement pour permettre le contrôle des enchaînements.

### 6.4 Contrôle de continuité horamètre

- EF-030 : Pour chaque paire de vols successifs, le système doit comparer l’horamètre de fin du vol précédent à l’horamètre de début du vol suivant.
- EF-031 : Si les deux valeurs sont égales, les deux vols concernés doivent être affichés en vert.
- EF-032 : Si les deux valeurs sont différentes, les deux vols concernés doivent être affichés en rouge.
- EF-033 : En cas d’écart positif entre les deux valeurs, une ligne intermédiaire doit être affichée entre les deux vols avec la durée de l’écart.
- EF-034 : La durée de l’écart doit être affichée dans l’unité de l’horamètre.
- EF-035 : En cas de recouvrement entre deux vols successifs, une ligne intermédiaire rouge doit être affichée entre les deux vols avec la durée du recouvrement.
- EF-036 : La durée du recouvrement doit être affichée dans l’unité de l’horamètre.

### 6.5 Datatable et exports

- EF-040 : Le résultat doit être présenté dans un datatable.
- EF-041 : Le datatable doit proposer une recherche textuelle.
- EF-042 : Le datatable doit proposer une pagination.
- EF-043 : La page doit proposer un export CSV.
- EF-044 : La page doit proposer un export PDF.

## 7. Exigences d’Interface Utilisateur

### 7.1 Entrées d’accès

- Une entrée visible dans le menu Avion pour les profils autorisés.
- Une carte visible dans le dashboard Administration club pour les profils autorisés.

### 7.2 Écran de contrôle

- Zone de filtres en haut de page : avion, date de début, date de fin.
- Tableau principal listant les vols de la machine sélectionnée.
- Mise en couleur des lignes de vols selon la continuité (vert) ou l’incohérence (rouge).
- Lignes intermédiaires dédiées pour écarts et recouvrements.
- Contrôles de recherche et pagination.
- Boutons d’export CSV et PDF.

## 8. Comportement Système

1. L’administrateur ouvre la page via le menu Avion ou la carte du dashboard Administration club.
2. Le système affiche les filtres avec dates par défaut (1er janvier de l’année courante à date courante).
3. L’administrateur choisit un avion.
4. Le système charge les vols de la machine sur la période.
5. Le système compare chaque transition entre deux vols successifs.
6. Le système applique les couleurs sur les vols et insère les lignes d’écart ou recouvrement si nécessaire.
7. L’administrateur peut rechercher, paginer et exporter le résultat en CSV ou PDF.

## 9. Cas Limites

- CL-001 : Aucun vol sur la période sélectionnée.
- CL-002 : Un seul vol sur la période (pas de comparaison possible).
- CL-003 : Horamètre manquant sur un vol ; l’anomalie doit rester visible et explicite.
- CL-004 : Plusieurs vols le même jour avec enchaînements complexes.
- CL-005 : Changement d’unité d’horamètre non prévu pour une même machine ; l’affichage doit rester cohérent avec l’unité configurée.
- CL-006 : Date de début postérieure à la date de fin ; la page doit empêcher ou signaler la saisie invalide.

## 10. Critères d’Acceptation

- CA-001 : Un administrateur club voit l’entrée dans le menu Avion et la carte sur le dashboard Administration club.
- CA-002 : Les deux accès ouvrent la page de contrôle des carnets de route.
- CA-003 : Les dates par défaut sont correctement initialisées (1er janvier de l’année courante, date courante).
- CA-004 : Le filtre avion respecte la section active et le mode « Toutes ».
- CA-005 : La page affiche les vols de la machine sur la période avec les colonnes demandées.
- CA-006 : Si continuité horamètre exacte, les deux vols successifs sont affichés en vert.
- CA-007 : Si continuité non exacte, les deux vols successifs sont affichés en rouge.
- CA-008 : En cas d’écart, une ligne intermédiaire affiche la durée de l’écart dans l’unité de l’horamètre.
- CA-009 : En cas de recouvrement, une ligne intermédiaire rouge affiche la durée du recouvrement dans l’unité de l’horamètre.
- CA-010 : Le datatable propose recherche et pagination.
- CA-011 : Les exports CSV et PDF sont disponibles depuis la page.

## 11. Dépendances Produit

- Système d’authentification et d’autorisations existant.
- Données vols et horamètres disponibles dans GVV.
- Composant datatable utilisé dans les pages GVV.
- Mécanismes d’export CSV et PDF déjà présents dans l’application.

## 12. Questions Ouvertes

- QO-001 : Faut-il inclure un export qui conserve la mise en évidence couleur (notamment en PDF) ? oui, l’export PDF doit conserver la mise en évidence couleur pour faciliter la lecture des anomalies.
- QO-002 : Faut-il afficher un résumé des anomalies (nombre d’écarts et recouvrements) en tête de page ? oui, un résumé en tête de page permettrait à l’administrateur d’avoir une vue d’ensemble rapide des anomalies détectées.
- QO-003 : En cas d’horamètre manquant, faut-il créer une ligne intermédiaire spécifique ou uniquement marquer les vols en anomalie ? oui, une ligne intermédiaire spécifique doit être créée pour indiquer clairement l’absence d’horamètre.