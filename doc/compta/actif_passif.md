# Comptabilité en partie double — Principes fondamentaux

## Actif et Passif

**Actif** : ensemble des ressources économiques contrôlées par l'entité (ce qu'elle *possède* ou ce qu'on lui *doit*) — immobilisations, stocks, créances, trésorerie.

**Passif** : ensemble des obligations de l'entité envers des tiers (ce qu'elle *doit*) — capitaux propres, dettes fournisseurs, emprunts. Dans le PCG, les capitaux propres font partie du passif car ils représentent la dette de l'entité envers ses membres/associés.

| | Comptes d'ACTIF | Comptes de PASSIF |
|---|---|---|
| **Débit** | Augmente le solde | Diminue le solde |
| **Crédit** | Diminue le solde | Augmente le solde |
| **Solde normal** | Débiteur | Créditeur |

## Débit et Crédit dans les écritures

**Débit** et **Crédit** sont les deux colonnes d'un compte — ce ne sont pas des jugements de valeur (ni "positif" ni "négatif") :
- Le **débit** est toujours la colonne de *gauche*
- Le **crédit** est toujours la colonne de *droite*

L'effet sur le solde dépend du type de compte. Toute écriture comporte un **débit** et un **crédit** de montant égal :
- **Débiter** un compte d'actif l'augmente ; débiter un compte de passif le diminue.
- **Créditer** un compte d'actif le diminue ; créditer un compte de passif l'augmente.

**Exemple :** le club reçoit une cotisation de 100 € en banque :

| Compte | Intitulé | Débit | Crédit |
|---|---|---|---|
| 512 | Banque (actif) | 100 € | |
| 756 | Cotisations (produit) | | 100 € |

Le débit du compte 512 augmente le solde bancaire ; le crédit du compte 756 constate le produit. Les deux montants sont égaux — c'est le principe de la *partie double*.

## Comptes bilatéraux (4xx)

Les comptes de tiers basculent selon leur solde :

| Solde | Présentation au bilan |
|---|---|
| **Débiteur** | Actif (créance) |
| **Créditeur** | Passif (dette) |

Pour les mouvements, ils se comportent toujours comme des comptes d'actif : débiter augmente, créditer diminue. C'est uniquement au bilan que le solde final détermine leur classement.

**Exemple 411 — Vol d'un pilote sans provision suffisante (solde normal débiteur) :**

| Compte | Intitulé | Débit | Crédit |
|---|---|---|---|
| 411 | Pilote (tiers) | 100 € | |
| 706 | Prestation de vol (produit) | | 100 € |

Le 411 est débité → solde débiteur → présenté à l'**actif** (le pilote doit 100 € au club).

**Exemple 467 — Paiement encaissé par la section Général au profit de la section Planeur :**

| Compte | Intitulé | Débit | Crédit |
|---|---|---|---|
| 512 | Banque (Général) | 100 € | |
| 467 | Section Planeur (tiers) | | 100 € |

Le 467 est crédité → solde créditeur → présenté au **passif** (la section Général doit 100 € à la section Planeur, qui lui a confié l'encaissement).

**Cas particulier GVV — comptes 411 pilotes :**

Dans GVV, les pilotes sont censés prépayer leurs vols. Le compte 411 pilote a donc un solde normalement **créditeur** (le club doit des vols au pilote). La distinction entre 411 et 467 est une convention sémantique :
- **411** = personnes physiques (pilotes)
- **467** = entités internes (inter-sections)

En cas de retard de paiement, le 411 pilote peut basculer en solde **débiteur** → il repasse alors à l'actif, comme dans l'exemple ci-dessus.

## Analogie hydraulique

La comptabilité en partie double peut se visualiser comme un système de **réservoirs** (comptes) reliés par des **tuyaux** (flux financiers) :

- Chaque **réservoir** a un niveau = le solde du compte
- Chaque **écriture** fait circuler du liquide entre deux réservoirs — ce qui sort de l'un entre dans l'autre, la quantité totale est conservée

**Différence actif / passif :**

Les réservoirs d'actif et de passif sont **orientés en sens opposé**, avec un niveau de référence (zéro) au milieu :

- Réservoir d'**actif** : se remplit par le haut (débit), le liquide s'accumule **au-dessus** du zéro → solde normal débiteur
- Réservoir de **passif** : se remplit par le bas (crédit), le liquide s'accumule **en-dessous** du zéro → solde normal créditeur

Ce qui correspond exactement à la structure du bilan :

```
ACTIF          |  PASSIF
(soldes        |  (soldes
débiteurs)     |  créditeurs)
```

**Comptes bilatéraux (4xx) :** le réservoir peut se remplir des deux côtés du zéro — au-dessus (créance, actif) ou en-dessous (dette, passif).

**Charges et produits :**

Les réservoirs de charges (6xx) et de produits (7xx) se remplissent pendant l'exercice, puis sont **vidés en fin d'année** vers un réservoir intermédiaire, le résultat (12x), qui est lui-même versé dans le grand réservoir permanent du passif : les **capitaux propres** (106) :

```
Charges (6xx) ──┐
                ├──→ Résultat (12x) ──→ Capitaux propres (106)
Produits (7xx) ─┘
```

Les capitaux propres accumulent les résultats année après année et représentent ce que le club "vaut" au sens comptable.

## Conseils pour la tenue de la comptabilité

### La cohérence avant tout

La qualité d'une comptabilité ne se mesure pas au logiciel utilisé, ni à la vitesse de saisie, mais à la **cohérence des écritures**. Une comptabilité tenue rigoureusement dans un cahier est plus utile qu'une comptabilité saisie en désordre dans un logiciel.

La saisie informatique n'est que la **partie émergée de l'iceberg** : le vrai travail, c'est de comprendre et qualifier chaque opération avant de la saisir. Re-saisir dans un logiciel une comptabilité exacte tenue dans un livre de comptes est un travail mécanique, rapide et sans surprise. Localiser et corriger des erreurs éparpillées dans un logiciel est un travail long et frustrant.

### La dette technique comptable

En développement logiciel, on parle de *dette technique* : des raccourcis pris aujourd'hui qui coûtent beaucoup plus cher à corriger demain. Le même concept s'applique à la comptabilité. Quand on prend un emprunt, il faudra rembourser le capital, mais aussi les intérêts. Et attention au surendettement : plus la dette est importante, plus le coût de correction est élevé, et on peut en arriver à un point où toutes les ressources sont consacrées à rembourser la dette.

**Une écriture non saisie** : le travail reste à faire. C'est localisé et quantifiable. Il suffira de la saisir. C'est le capital.

**Une écriture fausse, en double ou manquante** : le travail reste aussi à faire — en plus, il faudra d'abord localiser et comprendre l'erreur. Le coût de correction est supérieur au coût du travail nécessaire, on rembourse capital et intérêts.

> **Règle pratique :** mieux vaut suspendre une saisie douteuse et y revenir que de saisir quelque chose d'approximatif "pour ne pas perdre le fil".