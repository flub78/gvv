# PRD - Facturation Périodique Automatisée

## Objectif

Permettre la facturation automatique et récurrente de prestations à périodicité fixe (mensuelle, trimestrielle, annuelle) telles que les locations de places de hangar, ou autres services récurrents.

## Contexte

Actuellement, GVV permet de facturer ponctuellement des produits tarifés via la table `achats`. Pour les prestations récurrentes (locations de hangar, abonnements), les trésoriers doivent :
- Se souvenir de qui doit être facturé et à quelle échéance
- Risquer des oublis ou des erreurs de calendrier

Ce PRD propose un système de **contrats/abonnements** avec facturation automatisée.

## Périmètre Fonctionnel

### Fonctionnalité Principale

**Système de contrats récurrents** permettant :
- Définir des contrats de prestations périodiques pour un membre
- Générer automatiquement les factures selon le calendrier défini
- Gérer le cycle de vie des contrats (création, suspension, résiliation)

### Cas d'Usage Typiques

1. **Location de place de hangar** : 50€/mois, facturé le 1er de chaque mois

### Gestion des Contrats

**Création d'un contrat** :
- Membre concerné
- Produit tarifé (référence dans table `tarifs`)
- Périodicité : mensuelle, trimestrielle, annuelle
- Date de début du contrat
- Date de fin (optionnelle, pour contrats à durée limitée)
- Mois de facturation (pour périodicité trimestrielle/annuelle)
- Montant (par défaut = tarif en vigueur, modifiable)
- Statut : actif, suspendu, résilié

**Actions sur contrats** :
- Suspendre temporairement (pause facturation)
- Réactiver
- Résilier (arrêt définitif)
- Modifier le montant (changement de tarif)

### Génération Automatique des Factures

**Mécanisme de batch** :
- Tâche programmée (cron) exécutée quotidiennement
- Identifie les contrats dont la date de facturation est atteinte
- Génère automatiquement les écritures comptables :
  - Débit compte pilote (411)
  - Crédit compte produit
- Enregistre dans `achats` avec référence au contrat
- Trace la période facturée pour éviter les doublons

**Règles de génération** :
- Un contrat ne peut générer qu'une seule facture par période
- Si le contrat est suspendu à la date de facturation : aucune facture générée
- Si le contrat est résilié : aucune facture après la date de résiliation
- Génération automatique du libellé : "Location hangar - Janvier 2025"

### Contraintes

- Un membre peut avoir plusieurs contrats actifs simultanément
- Les contrats suspendus ou résiliés ne génèrent plus de factures
- Impossibilité de supprimer un contrat ayant généré des factures (historique)
- Les factures générées sont des écritures normales (modifiables/annulables via mécanismes existants)
- Accessible uniquement aux trésoriers et administrateurs

## Interfaces Utilisateur

### Écran "Liste des Contrats"

Vue d'ensemble des contrats avec :
- Filtres : membre, statut (actif/suspendu/résilié), périodicité
- Colonnes : membre, produit, périodicité, montant, prochaine facturation, statut
- Actions : créer nouveau contrat, voir détail, modifier, suspendre, résilier

### Écran "Création/Édition de Contrat"

Formulaire comprenant :

**Section "Contrat"** :
- Sélecteur de membre
- Sélecteur de produit tarifé (filtré : uniquement produits récurrents)
- Périodicité (mensuel/trimestriel/annuel)
- Date de début
- Date de fin (optionnelle)
- Pour périodicité trimestrielle : mois de début (ex: janvier → facturation janvier/avril/juillet/octobre)
- Pour périodicité annuelle : mois de facturation (ex: janvier)

**Section "Tarification"** :
- Montant (pré-rempli avec tarif en vigueur, modifiable)
- Compte de recette (pré-rempli selon produit)

**Section "Statut"** :
- Statut (actif/suspendu/résilié)
- Notes (champ texte libre)

**Actions** :
- Enregistrer
- Annuler

### Écran "Historique Facturation Contrat"

Pour un contrat donné :
- Liste de toutes les factures générées
- Colonnes : date facture, période, montant, statut (payée/impayée)
- Lien vers l'écriture comptable correspondante

## Comportement Système

### Processus de Facturation Automatique

1. **Tâche cron quotidienne** (ex: 6h du matin) :
   - Sélectionne tous les contrats actifs
   - Pour chaque contrat, vérifie si date du jour = date de prochaine facturation
   - Génère l'écriture comptable si condition remplie
   - Met à jour la date de prochaine facturation du contrat

2. **Calcul date prochaine facturation** :
   - Mensuel : même jour du mois suivant (ex: 1er de chaque mois)
   - Trimestriel : même jour dans 3 mois
   - Annuel : même jour de l'année suivante

3. **Gestion des cas particuliers** :
   - Jour inexistant (ex: 31 février) → dernier jour du mois
   - Contrat créé en cours de période → première facturation à date de début, puis périodicité normale

### Notifications

- Email au trésorier en cas d'échec de génération (membre introuvable, compte inexistant, etc.)
- Rapport hebdomadaire optionnel : liste des factures générées dans la semaine

## Cas Limites

- **Contrat avec date de fin proche** : Avertissement si date de fin < prochaine facturation
- **Tarif modifié** : Le contrat conserve son montant sauf modification manuelle
- **Membre désactivé** : Le contrat continue sauf suspension/résiliation manuelle
- **Modification de périodicité** : Recalcul de la prochaine date de facturation
- **Double facturation** : Mécanisme de protection basé sur période déjà facturée
- **Indexation automatique des tarifs** : Les tarifs facturés le seront en fonction des tarifs en vigueur au moment de la facturation, les trésoriers ont la possibilité de modifier les tarifs.


## Hors Périmètre

- **Paiement automatique** : Le système génère les factures, le paiement reste manuel
- **Relances automatiques** : non prévu
- **Modification en masse** : Chaque contrat est géré individuellement

## Bénéfices Attendus

- **Gain de temps** : Élimination de la saisie manuelle mensuelle/trimestrielle
- **Fiabilité** : Aucun oubli de facturation
- **Traçabilité** : Historique complet des facturations par membre
- **Prévisibilité** : Vue claire des prochaines facturations à venir
- **Flexibilité** : Gestion fine du cycle de vie des contrats

## Dépendances

- Nécessite la migration de base de données pour créer les nouvelles tables
- Nécessite configuration d'une tâche cron sur le serveur
- Compatible avec le système de facturation existant (table `achats`)

