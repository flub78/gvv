# Correction du bilan 2024 — Section planeur

## Contexte

La section planeur utilise GVV comme outil de comptabilité depuis 2011. Depuis cette époque, les chiffres sont extraits annuellement de GVV pour être intégrés dans le logiciel comptable EBP, cela sert de support pour la présentation du bilan annuel à l'Assemblée Générale.

En 2025, nous avons décidé d'utiliser GVV également pour gérer les section Avion, ULM et compte général, les comptes de ces sections ont été initialisé en reprenant les chiffres de 2024. Donc leurs fonds associatifs sont corrects.

Lors de la clôture annuelle 2024, une erreur de transmission a été commise. Le bilan a été présenté et validé par l'Assemblée Générale avec des chiffres erronés pour la section planeur.

L'expert comptable nous as indiqué qu'il faut absolument repartir des chiffres précédant, même erronés.

Pour synchroniser GVV avec les chiffres officiels, il faudrait passer des écritures de correction en sur des comptes de charges et produits en 2024 et re-clôturer l'exercice 2024. 

Cependant, **les exercices 2024 et 2025 ont tous deux été clôturés dans GVV**, ce qui rend toute modification des écritures impossible via l'interface normale (la date de gel bloquant toute saisie antérieure à la clôture).

Et il ne suffit de lever l'interdiction d'écrire dans le passé, il faut propager les corrections de 2024 dans le report à nouveau de 2025, ce qui nécessite de décloturer également 2025, puis de re-clôturer les deux exercices dans le bon ordre.

L'opération est complexe pour ne pas oublier d'impacts.
---

## Mécanisme de clôture dans GVV

La clôture d'un exercice dans GVV (`comptes.php::cloture()`) effectue deux opérations :

### 1. Écritures de clôture dans `ecritures`

Trois séries d'écritures sont générées avec :
- `num_cheque = 'Clôture exercice YYYY'`
- `date_op = 'YYYY-12-31'`
- `club = <id_section>`

Ces écritures soldent les comptes de charges (6xx) et produits (7xx), calculent le résultat, et imputent celui-ci en report à nouveau (comptes 110–129).

### 2. Enregistrement dans la table `clotures`

Un enregistrement est inséré dans la table `clotures` avec la date de clôture. C'est ce record qui active le gel : `freeze_date()` dans `clotures_model.php` renvoie cette date, et GVV refuse toute modification d'écriture antérieure.

---

## Stratégie de décloture

La décloture consiste à **supprimer les écritures de clôture** et **supprimer l'enregistrement dans `clotures`**. Une fois cela fait, les exercices redeviennent modifiables dans GVV.

La procédure doit se faire **dans l'ordre inverse** : décloture 2025 en premier, puis décloture 2024.

> **Attention** : cette opération contourne les protections de GVV. Elle doit être réalisée par un administrateur avec un accès direct à la base de données, après sauvegarde complète.

---

## Procédure de correction

### Étape 0 — Sauvegarde

Avant toute manipulation, effectuer une sauvegarde complète de la base de données :

### Étape 0 - Suppression des dates de gel

phpmyadmin → table `clotures` → supprimer les dates de gel pour 2024 et 2025

« DELETE FROM clotures WHERE `clotures`.`id` = 5 » 

### Étape 1 — Décloture de l'exercice 2025

```sql
-- Identifier les écritures de clôture 2025 (vérification)
SELECT * FROM ecritures
WHERE num_cheque = 'Clôture exercice 2025'
  AND club = 1
  AND date_op = '2025-12-31';

-- Identifier l'enregistrement de clôture (vérification)
SELECT * FROM clotures WHERE section = 1 AND date = '2025-12-31';

-- Supprimer les écritures de clôture 2025
DELETE FROM ecritures
WHERE num_cheque = 'Clôture exercice 2025'
  AND club = 1
  AND date_op = '2025-12-31';

-- Supprimer l'enregistrement de clôture 2025
DELETE FROM clotures WHERE section = 1 AND date = '2025-12-31';
```

> Remplacer `club = 1` et `section = 1` par l'identifiant réel de la section planeur. 1 est l'identifiant réel.

### Étape 2 — Décloture de l'exercice 2024

```sql
-- Identifier les écritures de clôture 2024 (vérification)
SELECT * FROM ecritures
WHERE num_cheque = 'Clôture exercice 2024'
  AND club = 1
  AND date_op = '2024-12-31';

-- Supprimer les écritures de clôture 2024
DELETE FROM ecritures
WHERE num_cheque = 'Clôture exercice 2024'
  AND club = 1
  AND date_op = '2024-12-31';

-- Supprimer l'enregistrement de clôture 2024
DELETE FROM clotures WHERE section = 1 AND date = '2024-12-31';
```

### Étape 3 — Correction des écritures 2024

Avec l'exercice 2024 à nouveau ouvert dans GVV, corriger les écritures erronées via l'interface normale. Suivre les recommandations de l'expert-comptable pour les imputations à corriger.

### Étape 4 — Re-clôture de l'exercice 2024

Via l'interface GVV (`comptes` → clôture), relancer la clôture de l'exercice 2024. GVV recalcule le résultat et génère de nouvelles écritures de clôture correctes.

### Étape 5 — Re-clôture de l'exercice 2025

Via l'interface GVV, relancer la clôture de l'exercice 2025. Le report à nouveau de 2025 reprend le résultat corrigé de 2024.

---

## Identification de l'id section

Pour trouver l'identifiant de la section planeur dans la base :

```sql
SELECT id, nom FROM sections WHERE nom LIKE '%planeur%';
-- ou
SELECT id, nom FROM sections;
```

---

## Vérifications post-correction

Après re-clôture des deux exercices :

1. Vérifier que les totaux bilans correspondent aux attentes de l'expert-comptable
2. Vérifier la cohérence du report à nouveau (compte 110/120/129) entre 2024 et 2025
3. Vérifier que GVV affiche à nouveau les exercices comme clôturés (icône cadenas)
4. Conserver le fichier de sauvegarde jusqu'à validation comptable définitive

---

## Notes

- La stratégie retenue (correction 2024 + re-clôtures successives) est préférable à une correction directement en 2025, car elle maintient la cohérence des reports à nouveau dans GVV.

