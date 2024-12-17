# Mises à jour

Les mises à jour se font depuis une connexion ssh sur le serveur.

## Depuis Subversion

Il existait un script update_gvv.sh qui faisait les mises à jour depuis subversion sans écraser les fichier de configuration. Il inscrivait dernière mise à jour installée dans un fichier nommé installed.txt

Il supportait un paramètre pour revenir en arrière sue une version particulière.

### Attention le dépôt Subversion chez developpez.com n'est plus mis à jour.

## Depuis Github

Une fois la migration réalisée, il est possible de mettre à jour depuis github

    git pull
    cat "" >> installed.txt
    git log --stat -n 1 >> installed.txt

Si vous ne pouvez pas faire le pull à cause des fichiers de configuration locaux:

    git stash --include-untracked
    git pull
    git stash pop

Pour activer un commit spécifique:

    git checkout e517a13c7c242fdd0c93f2dca7f1a6ef32c52190

And retourner à la branche principale pour pouvoir faire des pull:

    git checkout main
    git pull