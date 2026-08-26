# Command merge-pr

Merge une pull request GitHub après vérification. Numéro de PR : $ARGUMENTS

## Steps
1. Vérifier l'état des checks CI avec `gh pr checks $ARGUMENTS`. S'il n'y a pas de CI configurée ou si des checks échouent, avertir l'utilisateur et proposer de lancer `./run-all-tests.sh` localement (après `source setenv-php7.sh` ou `setenv-php8.sh`) avant de continuer.
2. Vérifier que la PR est mergeable sans conflit avec `gh pr view $ARGUMENTS --json mergeable`. En cas de conflit, arrêter et signaler qu'il faut les résoudre localement (`git merge main` sur la branche, résolution manuelle, push).
3. Demander confirmation explicite à l'utilisateur avant de merger (action visible et irréversible sur le dépôt distant).
4. Merger avec un merge simple, sans squash ni rebase : `gh pr merge $ARGUMENTS --merge`.
5. Revenir sur main et se mettre à jour : `git checkout main && git pull`.
6. Demander à l'utilisateur s'il souhaite supprimer la branche (locale et distante) maintenant qu'elle est mergée. Ne la supprimer que sur confirmation explicite, avec `git push origin --delete <branche>` et `git branch -d <branche>`.
7. Confirmer à l'utilisateur que la PR est mergée et que main est à jour.
