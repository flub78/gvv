# Sauvegarde et restaurations

Il est possible de sauvegarder et restaurer la base de données.

Attention une sauvegarde complète de l'environnement devrait également comprendre les fichiers de configurations ainsi que les fichiers qui ont été chargés dans l'application (photo des pilotes, pièces jointes, etc). Ces fichier peuvent être sauvegardés et restaurés en copiant les arborescences sur un support externe. Il n'y a pas de support dans GVV pour faire cela, cela doit être fait à la main.

## Sauvegarde automatique

En plus des sauvegardes manuelles, il est possible de configurer une sauvegarde automatique de la base de données.

Il y a un script tools/autobackup.py qui fait cela. Il doit être installé dans une tâche cron.

Pour lister les tâches cron :

    crontab -l 


## Sauvegarde des media

All files are under gvv/uploads

Il y a des références dans la base de données sur les fichiers chargés.


