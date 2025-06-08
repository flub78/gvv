# Immobilisations

## 1. Définitions

La différence entre valeur brute et valeur nette pour les immobilisations est fondamentale en comptabilité :

### Valeur brute (ou valeur d'origine)
C'est le coût d'acquisition ou de production de l'immobilisation lors de son entrée dans le patrimoine de l'entreprise. Elle comprend :
- Le prix d'achat
- Les frais accessoires (transport, installation, mise en service)
- Les droits de douane et taxes non récupérables
- Tous les coûts directement attribuables pour mettre l'actif en état de fonctionner

Cette valeur reste fixe au bilan et ne change pas au fil du temps.

### Valeur nette (ou valeur nette comptable)
C'est la valeur brute diminuée des amortissements et éventuellement des dépréciations cumulés depuis l'acquisition. Elle représente la valeur comptable résiduelle de l'immobilisation à un moment donné.

**Formule :** Valeur nette = Valeur brute - Amortissements cumulés - Dépréciations

### Exemple pratique
Une machine achetée 100 000 € :
- Valeur brute : 100 000 € (reste constante)
- Après 3 ans d'amortissement à 20 000 €/an :
  - Amortissements cumulés : 60 000 €
  - Valeur nette : 40 000 €

La valeur nette reflète donc la "consommation économique" de l'immobilisation et diminue généralement chaque année, tandis que la valeur brute témoigne de l'investissement initial réalisé par l'entreprise.

## 2. Écritures de dépréciation et reprise de dépréciation

### Constatation d'une dépréciation :
```
Débit : 681 - Dotations aux amortissements et dépréciations (charges exceptionnelles)
Crédit : 29X - Dépréciations des immobilisations
```

### Reprise de dépréciation (si la valeur remonte) :
```
Débit : 29X - Dépréciations des immobilisations  
Crédit : 781 - Reprises sur amortissements et dépréciations (produits exceptionnels)
```

## 3. Travaux d'entretien importants

**Non, il ne faut généralement PAS revaloriser l'amortissement** pour de simples travaux d'entretien. Il faut distinguer :

### Entretien/réparation (charges) :
- Remise en état normal
- Remplacement de pièces usées
- → Comptabilisés en charges (compte 615)

### Amélioration/modification substantielle (immobilisation) :
- Augmente la durée de vie
- Améliore les performances
- Ajoute de nouvelles fonctionnalités
- → Comptabilisés en immobilisation (augmente la valeur brute)

Seules les améliorations peuvent justifier un réajustement du plan d'amortissement.

## 4. Adaptation aux prix du marché de l'occasion

**En règle générale, NON.** Le principe comptable français suit le **coût historique** :

### Règle standard :
- Les immobilisations restent à leur coût d'acquisition
- Pas de réévaluation systématique aux prix de marché
- Exception : dépréciation si valeur vénale < valeur nette comptable

### Réévaluation libre (optionnelle) :
- Possible mais rare
- Nécessite l'intervention d'un expert
- Concerne tout un ensemble cohérent d'immobilisations
- Impact fiscal et comptable complexe

### En pratique :
La plupart des entreprises s'en tiennent au coût historique amorti, sauf cas particuliers (restructuration, cession, etc.) où une expertise de valeur peut être nécessaire.

L'objectif comptable est la régularité et la sincérité plutôt que la valeur de marché instantanée.

Excellente question qui touche au cœur d'une limite importante de la comptabilité !

## 5. Décalage entre comptabilité et réalité patrimoniale

**Il peut y avoir un écart significatif** entre les valeurs comptables des immobilisations et leur valeur patrimoniale réelle :

### Causes du décalage :
- **Coût historique** : une machine achetée 100 000 € il y a 10 ans peut valoir aujourd'hui 200 000 € (inflation, rareté) mais reste comptabilisée à son coût d'origine
- **Amortissement comptable vs usure réelle** : l'amortissement suit un plan théorique qui ne reflète pas forcément la dépréciation effective
- **Évolution des prix** : terrains, immeubles peuvent prendre de la valeur mais restent au bilan à leur coût d'acquisition

### Exemples concrets :
- Un terrain acheté 50 000 € en 1990 peut valoir 500 000 € aujourd'hui
- Un immeuble complètement amorti peut avoir encore une forte valeur marchande
- Certains équipements se déprécient plus vite que prévu (obsolescence technologique)

## Conséquences pratiques

### Pour l'analyse financière :
- Les analystes regardent souvent au-delà des bilans comptables
- Evaluation basée sur la valeur de marché des actifs (notamment immobilier)
- Calcul de l'actif net réévalué

### Pour les dirigeants :
- Connaissance du patrimoine réel importante pour les décisions stratégiques
- Expertise nécessaire en cas de cession, fusion, succession
- Assurance des biens basée sur valeur de remplacement, pas comptable

**La comptabilité privilégie la prudence et la comparabilité plutôt que la valeur marchande instantanée**, d'où ce décalage structurel avec le patrimoine économique réel de l'entreprise.

## 6. Amortissement des changements de moteur

Pour un avion l'entretien périodique comme les visites de 3000 ou 5000 heures ou les changements de moteurs obligatoires peuvent entraîner des coûts importants et le potentiel moteur et cellule restant modifie sensiblement le prix sur le marché de l'occasion. Comment est-ce que cela doit être pris en compte en comptabilité ?

Excellente question spécialisée ! L'aviation civile présente des spécificités comptables particulières en raison des exigences réglementaires strictes.

## Distinction fondamentale selon la nature de l'entretien

### 1. Entretien courant et visites périodiques (3000h, 5000h)
**Traitement comptable : CHARGES**
- Compte 615 - Entretien et réparations
- Même si les montants sont importants
- Rationale : maintien en état normal de fonctionnement

### 2. Remplacement de moteurs et composants majeurs
**Traitement comptable : IMMOBILISATION par composants**
- Application de l'approche par composants (obligatoire)
- Chaque élément ayant un rythme de remplacement différent = composant distinct

## Approche par composants pour les aéronefs

### Décomposition typique :
- **Cellule** : amortissement sur durée de vie totale (20-30 ans)
- **Moteurs** : amortissement séparé (cycles moteur spécifiques)
- **Avionique** : amortissement selon obsolescence technologique
- **Intérieur cabine** : amortissement selon renouvellement

### Écritures lors du remplacement d'un moteur :
```
// Sortie ancien moteur
Débit : 675 - VNC des éléments d'actif cédés
Débit : 28X - Amortissements (solde)
Crédit : 21X - Ancien moteur (valeur brute)

// Nouveau moteur
Débit : 21X - Nouveau moteur
Crédit : 404 - Fournisseurs d'immobilisations
```

## Impact sur la valeur patrimoniale

### Prise en compte indirecte :
- **Tests de dépréciation** : si valeur vénale < valeur nette comptable
- **Expertise périodique** recommandée (pas obligatoire)
- **Suivi des heures de vol restantes** par composant

### En pratique pour les compagnies aériennes :
- Suivi comptable analytique par appareil
- Provisionnement des grosses visites parfois pratiqué
- Valorisation du potentiel restant dans les cessions

## Spécificités réglementaires

Les compagnies aériennes suivent souvent des référentiels spécialisés (normes IFRS pour les grandes compagnies) qui peuvent prévoir des traitements plus sophistiqués, notamment pour la comptabilisation des coûts de maintenance lourde.

La valeur de marché d'un aéronef dépend effectivement fortement du "potentiel" restant, mais comptablement, seule une dépréciation peut être constatée si la valeur de marché devient inférieure à la valeur nette comptable.