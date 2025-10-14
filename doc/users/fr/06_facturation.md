# Facturation

Ce guide présente le système de facturation et de gestion des comptes clients dans GVV. Vous apprendrez à gérer les tarifs, émettre des factures et suivre les paiements.

## Table des matières

1. [Vue d'ensemble](#vue-ensemble)
2. [Comptes clients](#comptes-clients)
3. [Tarification](#tarification)
4. [Génération de factures](#factures)
5. [Suivi des paiements](#paiements)
6. [Gestion des avoirs](#avoirs)

## Vue d'ensemble {#vue-ensemble}

Le système de facturation GVV gère :
- **Comptes clients** individuels pour chaque membre
- **Tarification** flexible par activité et aéronef
- **Facturation** automatique des vols
- **Suivi** des paiements et impayés
- **Comptabilité** intégrée

### Principe de fonctionnement

1. **Vols** saisis et validés
2. **Facturation** automatique selon tarifs
3. **Émission** de factures périodiques
4. **Encaissement** et suivi des paiements
5. **Intégration** comptable automatique

## Comptes clients {#comptes-clients}

### Vue des comptes

![Liste des factures](../screenshots/06_billing/01_invoice_list.png)

### Structure des comptes

Chaque membre dispose d'un compte client comprenant :

#### Informations de facturation
- **Nom** et adresse de facturation
- **Email** pour l'envoi des factures
- **Conditions** de paiement spécifiques
- **Mode** de facturation (mensuel, trimestriel)

#### Solde et mouvements
- **Solde actuel** (débiteur/créditeur)
- **Historique** des factures
- **Détail** des vols facturés
- **Paiements** enregistrés

#### Paramètres spécifiques
- **Tarifs** particuliers (si négociés)
- **Remises** accordées
- **Blocage** en cas d'impayé
- **Limite** de crédit autorisée

### Gestion des soldes

#### Solde débiteur
- **Factures** en attente de paiement
- **Vols** non encore facturés
- **Frais** divers (adhésion, assurance)

#### Solde créditeur
- **Avoirs** constitués
- **Trop-perçu** à rembourser
- **Acomptes** versés d'avance

## Tarification {#tarification}

### Types de tarifs

#### Tarifs horaires
- **Vol planeur** : Tarif par heure de vol
- **Remorquage** : Tarif par remorquage ou hauteur
- **Instruction** : Supplément instructeur
- **Location** : Mise à disposition d'aéronef

#### Tarifs forfaitaires
- **Baptêmes** : Prix fixe par vol découverte
- **Stages** : Forfait formation complète
- **Adhésion** : Cotisation annuelle
- **Assurance** : Prime d'assurance

### Paramétrage des tarifs

#### Par aéronef
- **Tarif spécifique** pour chaque planeur/avion
- **Variations** selon la période (saison)
- **Remises** membres vs externes
- **Majorations** spéciales (compétition)

#### Par type de membre
- **Membres** : Tarif préférentiel
- **Stagiaires** : Tarif formation
- **Occasionnels** : Tarif standard
- **Invités** : Tarif externe

### Application automatique

Les tarifs sont appliqués automatiquement :
- **Lors de la saisie** des vols
- **Selon le pilote** et l'aéronef utilisé
- **En fonction** de la durée et du type de vol
- **Avec calcul** des taxes applicables

## Génération de factures {#factures}

### Facturation périodique

#### Processus automatique
1. **Sélection** de la période (mois, trimestre)
2. **Calcul** des montants dus par membre
3. **Génération** des factures PDF
4. **Envoi** automatique par email
5. **Mise à jour** des comptes clients

#### Facturation manuelle
- **Facture** individuelle pour un membre
- **Correction** d'erreurs de facturation
- **Ajout** de prestations ponctuelles
- **Avoir** ou remboursement

### Contenu des factures

#### En-tête
- **Informations** du club émetteur
- **Coordonnées** du client
- **Numéro** de facture unique
- **Dates** d'émission et d'échéance

#### Détail des prestations
- **Liste** des vols effectués
- **Détail** : date, aéronef, durée, tarif
- **Calculs** : sous-totaux, taxes, remises
- **Total** à payer

#### Conditions
- **Mode** de paiement accepté
- **Délai** de paiement
- **Pénalités** de retard
- **Coordonnées** pour questions

### Formats et envoi

#### Formats disponibles
- **PDF** : Format standard pour l'envoi
- **Email** : Envoi automatique avec accusé
- **Papier** : Impression pour archives
- **Export** : Intégration comptable

#### Personnalisation
- **Logo** du club
- **Mentions** légales spécifiques
- **Conditions** particulières
- **Design** adapté à l'image du club

## Suivi des paiements {#paiements}

### Enregistrement des paiements

#### Modes de paiement
- **Espèces** : Encaissement direct
- **Chèque** : Suivi des remises en banque
- **Virement** : Rapprochement bancaire
- **Carte bancaire** : Paiement en ligne
- **Prélèvement** : Automatisation

#### Saisie des règlements
1. **Sélection** de la facture payée
2. **Montant** et mode de paiement
3. **Date** d'encaissement
4. **Référence** (numéro de chèque, virement)
5. **Validation** et mise à jour du solde

### Relances et impayés

#### Relances automatiques
- **1ère relance** : J+10 après échéance
- **2ème relance** : J+30 avec majoration
- **Mise en demeure** : J+60 procédure
- **Blocage** du compte si nécessaire

#### Gestion des impayés
- **Suspension** des droits de vol
- **Négociation** d'échéanciers
- **Procédures** de recouvrement
- **Provisions** pour créances douteuses

### Tableaux de bord

#### Suivi financier
- **Chiffre d'affaires** par période
- **En-cours** client total
- **Taux** de recouvrement
- **Délai** moyen de paiement

#### Analyses
- **CA** par type d'activité
- **Rentabilité** par aéronef
- **Évolution** des tarifs
- **Comparaisons** inter-périodes

## Gestion des avoirs {#avoirs}

### Création d'avoirs

#### Cas d'usage
- **Annulation** de vol pour météo
- **Erreur** de facturation
- **Geste commercial** du club
- **Remboursement** de prestations

#### Processus
1. **Identification** du motif d'avoir
2. **Calcul** du montant à créditer
3. **Génération** de l'avoir
4. **Notification** au client
5. **Imputation** sur le compte

### Utilisation des avoirs

#### Compensation
- **Imputation** automatique sur factures suivantes
- **Déduction** lors de nouveaux achats
- **Report** sur la période suivante

#### Remboursement
- **Demande** explicite du client
- **Virement** ou chèque de remboursement
- **Clôture** de l'avoir

## Intégration comptable

### Écritures automatiques

GVV génère automatiquement :
- **Ventes** : Comptabilisation des factures
- **Encaissements** : Mouvements de trésorerie
- **TVA** : Déclarations fiscales
- **Créances** : Suivi des impayés

### Export comptable

#### Formats standards
- **FEC** : Fichier des écritures comptables
- **CSV** : Import dans logiciels comptables
- **Balance** : États de synthèse
- **Grand livre** : Détail par compte

## Bonnes pratiques

### Facturation régulière

- **Facturez** mensuellement pour le suivi
- **Validez** les vols rapidement
- **Vérifiez** les tarifs appliqués
- **Contrôlez** les calculs automatiques

### Communication client

- **Informez** des changements de tarifs
- **Expliquez** les factures détaillées
- **Répondez** rapidement aux questions
- **Facilitez** les modes de paiement

### Suivi financier

- **Surveillez** les impayés de près
- **Relancez** rapidement les retards
- **Négociez** les difficultés de paiement
- **Provisionnez** les créances douteuses

## Cas d'usage spécifiques

### Nouveau membre

1. **Création** du compte client
2. **Configuration** des tarifs applicables
3. **Information** sur les conditions
4. **Première** facturation

### Membre occasionnel

1. **Facturation** immédiate après vol
2. **Paiement** comptant ou carte
3. **Pas** de compte en cours
4. **Tarif** externe appliqué

### Stage de formation

1. **Facturation** forfaitaire du stage
2. **Acompte** à l'inscription
3. **Solde** en fin de formation
4. **Détail** des vols inclus

## Dépannage

### Problèmes fréquents

#### "Tarif incorrect appliqué"
- Vérifiez la configuration des tarifs
- Contrôlez les dates de validité
- Recalculez si nécessaire

#### "Facture non envoyée"
- Vérifiez l'adresse email du client
- Contrôlez les paramètres SMTP
- Consultez les logs d'envoi

#### "Solde client incorrect"
- Vérifiez les paiements saisis
- Contrôlez les avoir émis
- Recalculez le solde

---

**Guide GVV** - Gestion Vol à Voile  
*Facturation - Version française*  
*Mis à jour en décembre 2024*

[◀ Calendrier des vols](05_calendrier.md) | [Retour à l'index](README.md) | [Comptabilité ▶](07_comptabilite.md)