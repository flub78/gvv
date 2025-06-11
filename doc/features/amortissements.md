# Amortissements

Il pourrait être utile d'extraire des tableaux d'amortissements de la comptabilité et peut-être de faciliter au automatiser le passage des écritures d'amortissements.

## Présentation SAGEVI

Par compte d'immobilisation:
- des valeurs brutes qui peuvent être réparties sur des sous systèmes (transpondeurs)
- sur chaque sous système on a plusieurs écritures d'amortissement

Immobilisation
- date d'achat
- description
- valeur brute  ... compte d'immo
- section
- compte
- durée en période
- taux en pourcentage
- durée en jour d'une période
- dépréciations 68 -> 29 
- amortissements 68 -> 281

## Solution GVV (une parmi d'autres)

- Un compte d'immobilisation par sous système
- Possibilité d'utiliser des codec détaillés
  - 281100  Avion
  - 281101  Transpondeur
  - 281101  Travaux de peinture

Inconvénient c'est un peu la structure rigide d'EBP

Ce n'est pas très orthogonale avec l'utilisation initiale des codec

On pourrait associer un amortissement à une écriture
