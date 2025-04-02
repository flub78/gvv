# Configuration

## Fichier de configurations

Traditionnellement, il existe un plusieurs fichiers de configuration:
* club.php
* config.php
* database.php
* program.php

Ces fichiers définissent un ensemble de paramètres souvent relatifs au déploiement, donc au serveur sur lequel tourne GVV.

D'autres paramètres sont plutôt des choix de l'association, comme le nom du club, le nom du contact, etc. On peut les distinguer en se demandant s'il pourrait rester les mêmes en cas de changement d'hébergeur. Typiquement les paramètres club devrait rentrer dans cette catégorie. Les paramètres de cette seconde catégorie peuvent être modifiés par l'administrateur du site.

Il est relativement logique de garder les premiers dans des fichiers de configuration. De toute façon s'ils ne sont pas bien configurés, l'application ne tourne pas. Pour les seconds par contre, il est plus logique de les garder en base de données, puisqu'on pourrait envisager de déménager l'application et de restaurer une sauvegarde pour continuer l'exploitation sur un nouveau serveur. 

De plus la modification par l'application d'un fichier de configuration est potentiellement une opération plus risquée que la modification d'une base de données (en cas de coupure de courants, par exemple, on pourrait avoir une corruption du fichier).

## Mécanisme de clé - valeur

On va gérer en base de données un ensemble de clé/valeur pour gérer la configuration.

Les clés pourront être traduites, en fonction de la langue utilisée. La liste des clés valides devra être contrôlée mais pourra être codée en dur puisque ces paramètres sont connus du code.

Pour l'instant pas de besoin identifié d'avoir autre chose que des valeurs chaînes de caractère.

## Structure de la table

* cle varchar(128)
* lang varchar(6), eng, fr, etc.
* valeur varchar(255)
* categorie varchar(64) Les catégories sont définies dans le code. et utilisées pour présenter à l'utilisateur des panneaux de configuration sur un sujet, par exemple la configuration club, la configuration des vi, etc.

## Utilisation

Je vais utiliser initialement cette resource pour configurer la gestion des vols de découverte. Il serait logique qu'à un certain point la configuration club soit migré vers ce genre de mécanisme