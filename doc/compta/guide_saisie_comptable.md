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

https://chatgpt.com/share/68090b23-6ccc-800e-a865-b9209db8b94d


## Comptes pour initialiser le comptabilité

https://chatgpt.com/share/67cef055-1f0c-800a-bacc-c6d0c6f9239d

## Gestion des amortissements





