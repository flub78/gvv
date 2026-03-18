# Github workflow

## Créer une branche
git checkout -b fix/horametre_select
    effectue les changements
    puis commit/push

## Creation PR
    Aller sur GitHub, créer la PR, assigner des reviewers

## Revue de code
    open a pull request
    reviewers
        comment
        approve
        request changes

    corrige les éventuels commentaires
    commit/push

### Revue de code par agent IA (Claude)

    /code-review
        corrige
        commit/push

## Merge la PR
    # Aller sur GitHub, merger, supprimer la branche

## Retour sur la branche principale
git checkout main
git pull origin main
