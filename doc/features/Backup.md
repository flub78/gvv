# Sauvegarde et restaurations

Il est possible de sauvegarder et restaurer la base de données.

Attention une sauvegarde complète de l'environnement devrait également comprendre les fichiers de configurations, qui doivent être sauvegardés et restaurés à la main en copiant les arborescences sur un support externe. Les fichiers qui ont été chargés dans l'application (photo des pilotes, pièces jointes, etc) peuvent en revanche être sauvegardés automatiquement, voir "Sauvegarde des media" ci-dessous.

## Sauvegarde automatique

En plus des sauvegardes manuelles, il est possible de configurer une sauvegarde automatique de la base de données.

Il y a un script tools/autobackup.py qui fait cela. Il doit être installé dans une tâche cron.

Pour lister les tâches cron :

    crontab -l 


## Sauvegarde des media

All files are under gvv/uploads

Il y a des références dans la base de données sur les fichiers chargés.

Il y a un script tools/autobackup_media.py qui sauvegarde automatiquement le
répertoire uploads/ (hors sous-répertoire restore/) sous forme d'archive
tar.gz. Il fonctionne sur le même principe que tools/autobackup.py : il doit
être installé dans une tâche cron, et applique la même politique de
rétention (sauvegardes quotidiennes gardées 1 semaine, hebdomadaires 1 mois,
mensuelles 1 an). Il partage le répertoire backups/ et le journal
logfile.txt avec les sauvegardes de base de données.

Contrairement à la sauvegarde manuelle depuis l'interface d'administration
(bouton "Sauvegarder les médias"), ce script ne propose pas de chiffrement.


## Problèmes liés à la sauvegarde

Les sauvegardes et restaurations peuvent être des opérations lourdes (relativement aux requêtes habituelles sur GVV) à la fois en mémoire, en taille de fichiers chargés et en CPU. Avec une base de données qui grossit on dépasse facilement les valeurs par défaut.




