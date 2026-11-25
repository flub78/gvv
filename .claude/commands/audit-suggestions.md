---
description: Audit de recul. Relit objectifs, PRD, notes de conception et plans, les confronte à l'état du projet, et propose de réorganiser doc/suggestions.md. Ne descend pas au niveau du code.
argument-hint: "[sources:chemin,…] [type:…] [revue]"
disable-model-invocation: true
---

# Command: audit-suggestions

Audit de suggestions. Arguments : $ARGUMENTS

## 1. Sources
Par défaut, lire :
- `CLAUDE.md`, `README.md` ;
- objectifs et état du projet : `doc/gvv_prd.md`, `doc/features.md`, `doc/drawbacks.md`,
  `doc/todo.md`, `doc/done.md`, `doc/release_notes.md` ;
- conception : `doc/prds/`, `doc/design_notes/`, `doc/plans/` (titres et sections
  principales ; lire en entier seulement les documents concernés par une question) ;
- `doc/reviews/` : seulement les titres et conclusions, pour connaître les problèmes déjà
  identifiés ;
- toujours `doc/suggestions.md`.

Avec `sources:`, se limiter aux chemins donnés (plus `doc/suggestions.md`).

Pour l'état réel du projet : arborescence (2 niveaux), liste de `application/controllers/`
et `application/migrations/` (noms seulement), `git log --oneline -50`, liste des branches.
**Ne pas lire le code source** : la qualité du code relève de `/code-review`, `/pr-review`
et `/security-audit`.

## 2. Questions à poser
**Objectifs**
- Les objectifs écrits sont-ils toujours ceux que les derniers commits poursuivent ?
- Un objectif est-il atteint, abandonné ou contredit sans que la documentation le dise ?
- Un PRD ou un plan est-il terminé mais présenté comme en cours, ou l'inverse ?

**Système et organisation**
- Quels artefacts existent (documents, exports, outils, scripts) ? Lesquels sont
  maintenus en double ? Lesquels n'ont plus d'utilisateur ?
- La chaîne de fabrication (tests, documentation générée, déploiement) a-t-elle des
  étapes manuelles qu'on pourrait supprimer ?

**Conception et architecture**
- Les choix documentés tiennent-ils face à ce qui a été construit ?
- Une décision prise pour une raison qui a disparu mérite-t-elle d'être revue ?

**Fonctionnalités**
- Quelles extensions à faible coût découlent naturellement de l'existant ?
- Quelles fonctionnalités promises manquent, et lesquelles ne valent plus la peine ?

**Méthodes**
- Le workflow (branches, tests, revues, documentation) est-il adapté à la taille du projet ?

## 3. Produire
- Des fiches complètes (constat, proposition, bénéfices, coûts, rapport, premier pas),
  destinées aux sections de `doc/suggestions.md`.
- Avec `revue` : relire toutes les fiches ; repérer les doublons à fusionner, les
  suggestions réalisées à passer en « faite » (citer le commit), les suggestions
  reportées dont la condition est maintenant remplie, les fiches à archiver ; proposer un
  nouveau classement de l'index.
- Dans la réponse : les 5 suggestions au meilleur rapport, les 2 plus ambitieuses, et
  les écarts entre objectifs et réalité.
- Demander quelles fiches et quelles modifications appliquer à `doc/suggestions.md`.
  N'écrire qu'après accord. Ne rien implémenter.
