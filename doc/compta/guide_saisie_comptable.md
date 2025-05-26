# Guide de saisie comptable

Ce document liste les bonnes pratiques et les conventions utiliées en comptablilité par l'aéroclub d'Abbeville.

Par défaut nous utilisons le plan comptable des associations.
L'association ne gère pas la TVA.

## Ecriture générale dans GVV

Les écritures générales permettent de passer une écriture qui référence un compte en emploi et un compte en resource. Il n'y a pas de contrôle de la logique comptable.

## Ecritures guidées dans GVV

Les écritures guidées sont des écritures entre des comptes selectionnés. Par exemple l'enregistrement du versement d'une somme d'argent d'un pilote sur son compte ne peut concerner qu'un compte de banque (512) en emploi et un compte client (411). La saisie de ce type d'acriture ne présentera que les comptes 512 et 411 dans le formulaire de saisie.

Les écritures guidées sont les suivantes:

| Ecritures | Resource | Emploi |
|---------|-------|----------|
| Recettes | Produit 700 | Banque 500 |
| Réglement par pilote | Client 411 | Banque 500  |
| Facturation manuelle d'un pilote | Produit 700 | Client 411 |
| Enregistrement d'un avoir fournisseur | Charges 600 | Fournisseur 481 |
|_______________________________________|_____________|_________________|
| Dépenses | Banque 500  | Charges 600 |
| Dépenses payés par un pilote | Client 411 | Charges 600 |
| Remboursement d'avance pilote | Banque 500  |  Client 411 |
| Paiement par avoir fournisseur | Fournisseur 481 | Charges 600 |
|_______________________________________|_____________|_________________|
| Virements | Banque 500 | Banque 500 |


## Utilisation de la caisse (compte 58)

En comptabilité, le compte 58 est généralement destiné à la gestion des virements internes et des mouvements de fonds. Il sert à enregistrer les transferts de fonds entre différents comptes de l'entreprise.

Lors d'un paiement en liquide, le compte à utiliser est le compte 53 "Caisse", qui enregistre les mouvements d'espèces (entrées et sorties). Plus précisément :

- Pour enregistrer une dépense en espèces : on crédite le compte 53 "Caisse"
- Pour enregistrer une recette en espèces : on débite le compte 53 "Caisse"

Le compte 53 permet ainsi de suivre tous les mouvements d'argent liquide dans l'entreprise.

## Remboursements d'emprunts

En comptabilité comment prendre en compte le versement de la somme emprunté sur le compte bancaire, les remboursements de capital et le paiement des intérêts ?

En comptabilité, ces trois opérations liées à un emprunt se traitent différemment :

### 1. Versement de la somme empruntée

Lors de la réception des fonds sur le compte bancaire :
- **Débit** : Compte 512 "Banque" (montant total emprunté)
- **Crédit** : Compte 164 "Emprunts auprès des établissements de crédit" (montant total emprunté)

Cette écriture augmente à la fois votre trésorerie et votre dette au passif.

### 2. Remboursement du capital

À chaque échéance, la partie capital remboursée :
- **Débit** : Compte 164 "Emprunts auprès des établissements de crédit" (montant du capital)
- **Crédit** : Compte 512 "Banque" (montant du capital)

Cette écriture diminue votre dette et votre trésorerie.

En cas de mise en place d'un système comptable en cours d'emprunt comment initialiser le compte 164

### 3. Paiement des intérêts

Pour la partie intérêts de chaque échéance :
- **Débit** : Compte 661 "Charges d'intérêts" (montant des intérêts)
- **Crédit** : Compte 512 "Banque" (montant des intérêts)

Les intérêts constituent une charge financière qui impacte directement le résultat.

### Écriture globale d'une échéance

Si vous payez une mensualité de 1 000 € comprenant 800 € de capital et 200 € d'intérêts :
- Débit 164 : 800 €
- Débit 661 : 200 €
- Crédit 512 : 1 000 €

Cette méthode permet de suivre précisément l'évolution de votre endettement et l'impact des charges financières sur votre résultat.


## Comptes pour initialiser la comptabilité

https://chatgpt.com/share/67cef055-1f0c-800a-bacc-c6d0c6f9239d

https://claude.ai/share/8c3c0c3b-540e-4d7d-88b3-a0a13fab6d21


## Gestion des amortissements

## Gestion des salaires

En comptabilité, la passation des salaires suit un processus structuré avec plusieurs écritures comptables spécifiques. Voici les étapes principales :

## Calcul et préparation de la paie

D'abord, vous calculez pour chaque salarié le salaire brut, les cotisations sociales (salariales et patronales), les retenues diverses et le salaire net à payer.

## Écritures comptables principales

**1. Constatation de la charge salariale**
- Débit 641 "Rémunérations du personnel" (salaire brut)
- Débit 645 "Charges de sécurité sociale et de prévoyance" (cotisations patronales)
- Crédit 421 "Personnel - Rémunérations dues" (net à payer)
- Crédit 43 "Sécurité sociale et autres organismes sociaux" (cotisations totales)
- Crédit 447 "Autres impôts, taxes et versements assimilés" (si retenue à la source)

**2. Règlement des salaires**
- Débit 421 "Personnel - Rémunérations dues"
- Crédit 512 "Banque" (virement) ou 530 "Caisse" (espèces)

**3. Règlement des cotisations sociales**
- Débit 43 "Sécurité sociale et autres organismes sociaux"
- Crédit 512 "Banque"

## Cas particuliers à considérer

Les provisions pour congés payés, les avantages en nature, les frais professionnels remboursés et les primes exceptionnelles nécessitent des écritures spécifiques adaptées à leur nature.

La périodicité habituelle est mensuelle, avec un décalage entre la constatation de la charge (fin de mois) et les règlements (début du mois suivant). Il est essentiel de respecter les délais légaux de paiement et de déclaration aux organismes sociaux.





