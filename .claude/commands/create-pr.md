# Command create-pr

Pousse la branche courante et crée la pull request GitHub correspondante. Titre optionnel : $ARGUMENTS

## Steps
1. Vérifier qu'on n'est pas sur `main`. Si c'est le cas, arrêter et demander à l'utilisateur de préciser la branche ou de lancer `/branch` d'abord.
2. Vérifier l'état du dépôt avec `git status`. S'il reste des modifications non commitées, demander confirmation avant de les committer (ne jamais committer sans validation du message par l'utilisateur).
3. Vérifier qu'il y a des commits sur la branche absents de `origin/main` (`git log origin/main..HEAD`). S'il n'y en a aucun, avertir qu'il n'y a rien à publier et s'arrêter.
4. Pousser la branche : `git push -u origin <branche>`.
5. Générer le titre et le corps de la PR à partir des commits de la branche (`git log main..HEAD --oneline`) : résumé des changements, et référence aux documents `doc/design_notes`, `doc/prd` ou `doc/plans` correspondants s'ils existent pour cette fonctionnalité.
6. Créer la PR avec `gh pr create --title "<titre>" --body "<corps>"`. Utiliser $ARGUMENTS comme titre si fourni, sinon le déduire des commits.
7. Afficher l'URL de la PR retournée à l'utilisateur.
