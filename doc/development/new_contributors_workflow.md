# Guide de contribution (nouveaux contributeurs)

## Objectifs et priorités

Les priorités de développement, dans cet ordre, sont :

1) **Ne pas casser les données de production**
   - Toute modification doit préserver l’intégrité des données.
   - Les migrations doivent être réversibles et testées.

2) **Éviter les régressions de code**
   - Chaque changement doit être couvert par des tests.
   - Les tests existants doivent rester verts.

3) **Apporter de nouvelles fonctionnalités**
   - Les nouvelles fonctions viennent après la sécurité des données et la stabilité.
   - Privilégier la propreté du code à la vitesse d'implémentation

## Règle clé : commits fréquents

Les commits doivent être **petits, fréquents et ciblés**. Cela facilite :
- la revue,
- l’historique,
- les retours arrière.

## Pré-requis de démarrage (installation contributeur)

Les premières étapes impliquent :
- déployer un serveur **Apache** en local,
- installer les bonnes versions de **PHP** et **MySQL**,
- cloner le projet.

Il est **indispensable d’installer GVV** avant toute contribution.

Le processus d’installation d’un contributeur est **terminé** quand il est capable de **déboguer GVV pas à pas**.

## Workflow de contribution (Pull Requests)

### 1) Préparer son travail
- Se baser sur un besoin clair (bug identifié ou fonctionnalité demandée).
- Créer une branche dédiée à partir de la branche principale.
- Garder la branche courte et focalisée sur un seul objectif.

### 2) Travailler en mode TDD (Test-Driven Development)
Le développement est **test-driven** :

1. **Écrire un test** qui reproduit le bug ou décrit la fonctionnalité. Le test doit **échouer** au départ.
2. **Implémenter** le correctif ou la fonctionnalité **au minimum** pour faire passer le test.
3. **Implémenter** Modifier le code
4. **Relancer les tests** : tous les tests doivent passer à la fin.

### 3) Ouvrir une Pull Request
La PR doit contenir :
- un résumé du besoin,
- une description des changements,
- les tests ajoutés ou mis à jour.

#### Comment ouvrir une Pull Request (pas à pas)
1. Pousser la branche sur GitHub.
2. Ouvrir le dépôt dans GitHub et cliquer sur “Compare & pull request”.
3. Vérifier la branche source et la branche cible.
4. Renseigner le titre et la description (contexte, solution, tests).
5. Ajouter des reviewers (ou laisse*r la PR ouverte à t*oute l’équipe).
6. Créer la PR et vérifier que les checks CI se lancent.

#### Exemple de PR (structure recommandée)
- **Titre** : "Fix: correction du calcul de facturation"
- **Description** :
  - Contexte et problème constaté
  - Solution proposée
  - Tests ajoutés
  - Impacts éventuels

#### Captures d’écran GitHub (à ajouter)
- Écran de création de PR (zone titre/description)
- Écran des checks CI
- Écran des approvals

> Emplacement recommandé pour les captures : doc/images/

## Mécanisme de revue (ouvert à toute l’équipe)

- Toute PR est **ouverte à la revue** de tous les membres de l’équipe.
- Les reviewers peuvent :
  - commenter,
  - proposer des corrections,
  - demander des clarifications.
- Le contributeur met à jour la PR jusqu’à validation.

### Comment un reviewer teste une branche non mergée

1. Récupérer la branche de la PR localement (git fetch + checkout).
2. **Rebaser ou mettre à jour** si nécessaire pour être proche de **main**.
3. **Tester manuellement** la fonctionnalité dans l’environnement local.
4. **Lancer les tests** de la branche.

> Important : avant tout test PHP, **source setenv.sh** pour activer PHP 7.4.

Le reviewer valide ensuite la PR si :
- le test manuel est OK,
- les tests automatiques passent,
- le code est conforme aux standards du projet.

## Mécanisme de merge

- Une PR ne peut être mergée que si :
  - les tests passent,
  - la revue est terminée.
- Le merge est effectué via GitHub (interface web), après accord des reviewers.
- Le type de merge recommandé est **squash** pour garder un historique propre.

#### Exemple de merge (à illustrer)
- Sélection du bouton “Squash and merge”
- Confirmation du message final

## Bonnes pratiques

- **Un sujet par PR**
- **Commits fréquents**
- **Documentation mise à jour** si nécessaire
- **Tests obligatoires** avant merge
- **Règle du boy scout** : laisser le code plus propre que trouvé, sans sortir du périmètre de la PR
- **Revue par les pairs** : toute PR doit être revue par au moins un autre membre de l’équipe
- **Toute PR doit contenir** : de la documentation fonctionnelle, de design, du code et des tests

## Droits nécessaires pour les contributeurs

Objectif : permettre aux contributeurs de **pousser leur branche** sur GitHub **sans pouvoir merger sur main**.

- **Droit minimal recommandé** : *Write* (écriture) sur le dépôt.
  - Autorise : créer des branches, pousser des commits, ouvrir des PR.
  - N’autorise pas : modifier directement la branche **main** si la protection est active.

- **Protection de la branche main (obligatoire)** :
  - Interdire les pushes directs sur **main**.
  - Exiger une Pull Request pour tout changement.
  - Exiger au moins une approbation.
  - Exiger le passage des checks CI.
  - Exiger que la branche soit à jour avec **main** avant merge.
  - Interdire les force-pushes et la suppression de la branche.

Avec cette configuration, un contributeur peut travailler et pousser sa branche, mais **ne peut pas merger** sur **main**.

### La protection s’applique-t-elle au propriétaire du projet ?

Par défaut, sur GitHub, les **admins/owners** peuvent **contourner** certaines règles de protection.
Pour que les limitations s’appliquent aussi aux propriétaires, il faut **activer l’option** “Include administrators”.
Sans cette option, un owner peut toujours merger sur **main** malgré les protections.

## Identification des contributeurs sur GitHub

Les contributeurs sont identifiés par leur **compte GitHub** (utilisateur ou organisation) et par leur **adresse e‑mail** associée aux commits.

- Oui, un contributeur doit avoir **un compte GitHub** pour :
  - pousser des branches,
  - ouvrir des Pull Requests,
  - participer aux revues.

Sans compte GitHub, il est possible de proposer un patch hors plateforme, mais **pas** d’interagir avec le dépôt (push/PR/review).

## Résumé rapide

- Priorité : **données > stabilité > fonctionnalités**
- TDD : **test qui échoue → correction → refactor → tests OK**
- PR ouvertes à tous pour revue
- Merge via GitHub, de préférence en squash
