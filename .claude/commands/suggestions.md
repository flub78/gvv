---
description: Propose des améliorations à partir du contexte récent (session, dernières demandes, derniers commits), classées par rapport bénéfice/coût. Met à jour doc/suggestions.md après accord.
argument-hint: "[portée] [type:ui,feature,perf,qualite,securite,archi,methode,systeme,doc] [max:N] [sans-fichier]"
disable-model-invocation: true
---

# Command: suggestions

Suggestions d'amélioration. Arguments : $ARGUMENTS

## 1. Lire les arguments
- **Portée** : un chemin, un dossier ou un nom de fonctionnalité (voir le tableau des
  fonctionnalités de `CLAUDE.md`). Rien : le contexte récent.
- **type:** : domaines retenus, séparés par des virgules. Rien : tous les domaines.
  ui = interface et parcours ; feature = fonctionnalités ; perf ; qualite = qualité et
  tests ; securite ; archi = architecture ; methode = méthodes de développement ;
  systeme = organisation du projet ; doc = documentation.
- **max:N** : nombre maximal de suggestions (défaut 5, plafond 7).
- **sans-fichier** : ne pas proposer de mettre à jour `doc/suggestions.md`.

## 2. Rassembler le contexte (budget borné)
Sans portée :
1. La conversation en cours : demandes de l'utilisateur, décisions prises, difficultés.
2. Si la session est récente ou vide, les 10 dernières demandes de l'utilisateur, lues
   dans les transcriptions du projet (`~/.claude/projects/-home-frederic-git-gvv/*.jsonl`,
   messages de type user, les plus récents d'abord). Seulement les demandes, pas les
   réponses ni les sorties d'outils.
3. `git log --oneline -20` et `git status`.
4. `CLAUDE.md`, `README.md`, `doc/todo.md`, `doc/suggestions.md`.

Avec une portée : les mêmes sources, plus les fichiers de la portée et leurs voisins
directs (contrôleur, modèle, vues, fichiers de langue, PRD, note de conception et plan
de la fonctionnalité). Pas d'exploration au-delà.

## 3. Analyser à trois niveaux
1. **Détail** : ce qui a été touché récemment.
2. **Fonctionnalité** : le parcours de l'utilisateur dont ce détail fait partie.
3. **Système** : l'organisation du projet sert-elle son objectif ?
   - Quelque chose est-il maintenu en double, à la main ?
   - Un artefact ou une étape a-t-il perdu sa raison d'être ?
   - Les demandes récentes renforcent-elles une organisation à remettre en cause ?
   - L'utilisateur répète-t-il une tâche qu'un outil ferait ?

Ne pas faire de revue ligne à ligne : signaler un problème de code seulement s'il est
déjà visible dans le contexte, et renvoyer vers `/code-review`.

## 4. Filtrer et classer
- Écarter les idées déjà présentes dans `doc/suggestions.md` ou `doc/todo.md`, sauf fait
  nouveau (le dire, et proposer de compléter la fiche existante).
- Pour chaque idée : constat, proposition, bénéfices, coûts (S/M/L, risques, ce qu'on
  perd), rapport bénéfice/coût, premier pas.
- Classer par rapport bénéfice/coût. Garder au moins une idée système si elle existe,
  même coûteuse.

## 5. Restituer
- Dans la réponse : un tableau court (domaine, idée, bénéfice, coût, rapport), puis les
  fiches des 3 premières.
- Sauf `sans-fichier` : demander lesquelles enregistrer dans `doc/suggestions.md`.
  N'écrire qu'après accord : nouvelles fiches dans leur section, identifiants suivant le
  dernier utilisé, index et date mis à jour.
- Ne rien implémenter.
