# Command pr-review

Revue de code d'une pull request GitHub. Numéro de PR : $ARGUMENTS

## Steps
1. Récupérer les informations de la PR avec `gh pr view $ARGUMENTS` et le diff avec `gh pr diff $ARGUMENTS`.
2. Analyser les fichiers modifiés avec les mêmes critères que `/code-review` : bugs, bugs potentiels, implémentation inefficace, code mort, mauvais style, complexité élevée, duplication de code.
3. Quand un même problème apparaît à plusieurs endroits, le regrouper en une seule remarque listant toutes les occurrences (fichier:ligne) plutôt que de répéter la remarque à chaque endroit.
4. Construire une synthèse des problèmes trouvés, ordonnés par criticité décroissante.
5. Poster la synthèse comme un unique commentaire de revue sur la PR avec `gh pr comment $ARGUMENTS --body "<synthèse>"`. Pas de commentaires ligne par ligne.
6. Ne pas positionner d'état formel de revue (`approve` / `request changes`) — la synthèse informe l'utilisateur, qui reste seul décisionnaire de l'approbation et du merge via `/merge-pr`.
7. Afficher un résumé de la revue à l'utilisateur dans la conversation.
