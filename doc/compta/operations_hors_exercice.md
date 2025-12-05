# Opérations hors exercice en comptabilité associative

Ceci est la réponse d'une IA sur le sujet. Sachant qu'on attend pas les seuils au delà desquels c'est obligatoire, ce document reste indicatif.

## Principe de rattachement des charges et produits

En comptabilité d'engagement (obligatoire pour les associations dépassant certains seuils), les opérations doivent être rattachées à l'exercice auquel elles se rapportent, **indépendamment de leur date de paiement ou d'encaissement**.

## Factures de l'exercice N-1 payées en N

### Situation
Vous payez en janvier N une facture datée de décembre N-1 concernant une prestation de N-1.

### Traitement comptable

**En N-1 (clôture)** :
- La charge doit être enregistrée en N-1
- Utilisation du compte **408 - Fournisseurs - Factures non parvenues (FNP)**

```
Débit 6xxx (Compte de charge approprié)
Crédit 408 (Fournisseurs - FNP)
```

**En N (ouverture)** :
- Extourne de la FNP au 1er janvier

```
Débit 408 (Fournisseurs - FNP)
Crédit 6xxx (Compte de charge approprié)
```

**En N (au paiement)** :
- Enregistrement normal de la facture

```
Débit 6xxx (Compte de charge)
Crédit 401 (Fournisseurs)

puis

Débit 401 (Fournisseurs)
Crédit 512 (Banque)
```

## Cotisations encaissées d'avance

### Situation
Vous encaissez en décembre N des cotisations pour l'année N+1.

### Traitement comptable

**En N (à l'encaissement)** :
- Utilisation du compte **487 - Produits constatés d'avance (PCA)**

```
Débit 512 (Banque)
Crédit 487 (Produits constatés d'avance)
```

⚠️ **Ne pas créditer** le compte de produit 756 (Cotisations) car cela gonflerait artificiellement les produits de N.

**En N+1 (ouverture)** :
- Extourne du PCA au 1er janvier

```
Débit 487 (Produits constatés d'avance)
Crédit 756 (Cotisations) ou 758 (Autres produits)
```

## Comptes de régularisation - Récapitulatif

| Type d'opération | Compte à utiliser | Sens |
|------------------|-------------------|------|
| Charges de N payées en N+1 | 486 - Charges constatées d'avance | Actif |
| Charges de N non facturées | 408 - Fournisseurs FNP | Passif |
| Produits de N encaissés en N+1 | 418 - Clients - Produits non encore facturés | Actif |
| Produits de N+1 encaissés en N | 487 - Produits constatés d'avance | Passif |

## Principe de l'extourne

L'extourne consiste à passer l'écriture inverse au 1er jour de l'exercice suivant. Cela permet :
- De "nettoyer" les comptes de régularisation
- D'enregistrer ensuite l'opération réelle normalement
- D'éviter les doublons

## Exemple pratique complet

### Cotisation 2025 encaissée le 15/12/2024 : 100 €

**15/12/2024** :
```
512 Banque              100
    487 PCA                  100
```

**01/01/2025** (extourne) :
```
487 PCA                 100
    756 Cotisations          100
```

Résultat : La cotisation apparaît bien dans les produits de 2025.

## Points d'attention

1. **Documentation** : Conservez un tableau de suivi des régularisations pour faciliter la clôture
2. **Cohérence** : Vérifiez que les montants extournés correspondent aux montants réels facturés/payés
3. **Récurrence** : Ces opérations sont à renouveler chaque année lors de la clôture
4. **Seuils** : Les petites associations en comptabilité de trésorerie peuvent enregistrer simplement selon les flux bancaires

## Avantage de cette méthode

Cette approche garantit une **image fidèle** de la situation financière de l'association à chaque clôture, en respectant le principe de **séparation des exercices**.