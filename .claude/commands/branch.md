# Command branch

Crée une nouvelle branche de développement à partir de main pour isoler un travail en cours. Sujet de la branche : $ARGUMENTS

## Steps
1. Vérifier l'état du dépôt avec `git status`. S'il y a des modifications non commitées, les signaler à l'utilisateur et demander s'il faut les committer, les stasher, ou les inclure dans la nouvelle branche avant de continuer.
2. `git fetch origin` puis vérifier que la branche locale `main` est à jour avec `origin/main`. Si elle est en retard, proposer `git pull` avant de créer la branche.
3. Générer un nom de branche à partir de $ARGUMENTS en respectant les conventions déjà utilisées dans le dépôt : préfixe `feature/` pour une nouvelle fonctionnalité, `fix/` ou `bugfix/` pour une correction, `docs/` pour de la documentation, `refactoring/` pour une réorganisation de code, suivi d'un slug court cohérent avec $ARGUMENTS.
4. Créer et basculer sur la nouvelle branche depuis main à jour : `git checkout -b <branche> main`.
5. Confirmer à l'utilisateur le nom de la branche créée. Rappeler que gvv.net sert désormais le code de cette branche (checkout direct dans le même répertoire, pas de worktree) tant qu'aucun retour sur main n'a été fait.
