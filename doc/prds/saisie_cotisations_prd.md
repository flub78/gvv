# PRD - Saisie Simplifiée de Cotisations

## Objectif

Permettre aux trésoriers d'enregistrer le paiement d'une cotisation et de générer automatiquement les écritures comptables associées (crédit compte pilote + débit compte pilote vers compte recette) en une seule opération.

## Contexte

Actuellement, l'enregistrement d'une cotisation nécessite deux opérations distinctes:
1. Enregistrement du paiement → crédit du compte pilote (411)
2. Facturation de la cotisation → débit du compte pilote vers compte recette

Les trésoriers souhaitent une interface unique pour réaliser ces deux opérations simultanément.

## Périmètre Fonctionnel

### Fonctionnalité Principale

**Écran de saisie unique "Enregistrement Cotisation"** permettant de:
- Sélectionner le membre concerné
- Sélectionner l'année/période de cotisation
- Sélectionner le type de cotisation (produit tarifé)
- Indiquer le nombre de cotisations (1 par défaut)
- Saisir le libellé de l'opération
- Saisir le numéro de pièce comptable
- Ajouter des justificatifs (fichiers attachés)
- Valider en une seule action

### Comportement Système

Lors de la validation, le système doit automatiquement:
1. Créditer le compte pilote (411) du montant payé
2. Débiter le compte pilote du montant de la cotisation
3. Créditer le compte de recette approprié
4. Marquer le membre comme "inscrit" pour la période

### Contraintes

- Le montant payé doit correspondre au tarif de la cotisation en vigueur
- L'opération doit être atomique (tout ou rien)
- Les écritures comptables doivent être tracées avec référence au paiement
- Accessible uniquement aux trésoriers et administrateurs
- **Champs obligatoires d'une écriture comptable**:
  - Date de l'opération (par défaut: date du jour)
  - Libellé (description de l'opération)
  - Numéro de pièce comptable (référence du chèque, virement, etc.)
  - Mode de paiement (type de paiement)
- **Justificatifs**:
  - Possibilité d'attacher un ou plusieurs fichiers (scan chèque, relevé bancaire, etc.)
  - Stockage via le système d'attachments existant (table `attachments`)
  - Les justificatifs sont liés aux écritures générées

## Cas Limites

- **Adaptation aux réductions familiales**: pour éviter de complexifier le système il sera on créera un produit "unité de cotisation" à 1 € et le trésorier pourra ainsi appliquer les réductions en ajustant le nombre de cotisations saisies.

Ce champ ne doit pas servir pour vendre deux cotisations à un membre. Le système vérifiera que le membre n'est pas déjà inscrit pour la période. Si un membre paye une cotisation pour deux personnes, le trésorier devra faire deux saisies distinctes et demander deux paiements distincts au membre (ou reverser lui même d'un compte pilote sur un autre).

- **Membre déjà inscrit pour la période**: Afficher avertissement, permettre la validation (renouvellement anticipé)

Ce mécanisme permettra gérer le nombre d'adhérents pour la période. Il ne permettra pas de savoir si les membres sont à jour de leur assurance pour les différentes activités.

## Interfaces Utilisateur

### Écran de Saisie

Un nouvel écran accessible depuis le menu "Comptes" ou "Membres" comprenant:

**Section "Membre et Cotisation"**:
- Sélecteur de membre
- Sélecteur de type de cotisation (avec affichage automatique du tarif en vigueur)
- Sélecteur de période/année de cotisation
- Affichage du montant à payer

**Section "Paiement"** (champs standards d'écriture comptable):
- Date de l'opération (par défaut: date du jour, modifiable)
- Nombre de cotisations (champ numérique, par défaut 1)
- Numéro de pièce comptable (champ texte)
- Libellé (champ texte, pré-rempli avec suggestion, modifiable)

**Section "Justificatifs"** (optionnelle):
- Bouton "Ajouter un justificatif"
- Liste des fichiers attachés avec possibilité de suppression
- Upload de fichiers (formats acceptés: PDF, images)

**Actions**:
- Bouton "Enregistrer" (validation avec contrôles)
- Bouton "Annuler"
- Confirmation visuelle claire de l'opération réalisée

## Hors Périmètre

- Gestion des cotisations multi-périodes (annuelle, semestrielle, etc.) - à traiter ultérieurement si nécessaire
- Annulation/correction de cotisations - utiliser les mécanismes existants d'annulation de factures
- Relances automatiques de cotisations impayées

## Bénéfices Attendus

- Réduction du temps de saisie pour les trésoriers
- Diminution des erreurs de saisie
- Simplification du processus d'inscription des membres
