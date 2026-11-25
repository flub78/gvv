---
description: Revue des méthodes de développement. La façon de travailler est-elle efficace et sûre ? Produit des fiches pour la section Méthodes de développement de doc/suggestions.md, enregistrées après accord.
argument-hint: ""
disable-model-invocation: true
---

# Command: review-dev-methods

Revue des méthodes de développement. Arguments : $ARGUMENTS

## 1. Sources
- Historique git : `git log --oneline -100`, `git log --stat -30` (taille des commits),
  branches, PR récentes (`gh pr list --state all --limit 30`).
- CI : workflows éventuels dans `.github/` (aucun à ce jour), vérifications des PR récentes
  (`gh pr checks`).
- Scripts : `run-all-tests.sh`, `setenv-php8.sh`, `bin/`, `playwright/` (configuration).
- Consignes : `CLAUDE.md`, `.claude/commands/`, `README.md`,
  `doc/development/`.
- Tests : structure et nombre par suite, couverture (`build/coverage/` si présent),
  `doc/testing/test_docs.html` ; pas le contenu des tests.
- Demandes répétées à l'assistant : les demandes de l'utilisateur dans les transcriptions
  du projet (`~/.claude/projects/-home-frederic-git-gvv/*.jsonl`, messages user seulement).
- `doc/suggestions.md` (section Méthodes) pour ne pas reproposer.

## 2. Vérifier
- Taille et rythme des commits, stratégie de branches et de PR.
- Tests automatiques : exécution en CI, durée, tests instables, état laissé dans la base.
- Étapes manuelles répétées (déploiement, migrations, génération de documentation).
- Documentation à jour ; consignes de `CLAUDE.md` encore valides, redondantes ou
  contradictoires.
- Usage de l'assistant : demandes répétées qui mériteraient un script ou une commande.

## 3. Se demander
- Quelle tâche fait-on à la main à chaque modification ?
- Quelle erreur est déjà arrivée deux fois ?

## 4. Produire
- Les irritants classés par fréquence × coût, des fiches complètes (constat,
  proposition, bénéfices, coûts, rapport, premier pas).
- Demander lesquelles enregistrer dans la section Méthodes de développement de
  `doc/suggestions.md`. N'écrire qu'après accord. Ne rien implémenter.
