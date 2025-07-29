# Configuration

Les informations relatives  au déploiement sont gérées dans les fichiers de configuration. L'admin qui réalise l'installation peut les éditer.

Les informations qui peuvent varier dans une association doivent pouvoir être modifiées à travers GVV. Il est donc plus logique de les garder en base.

## Fichier de configurations

Il existe un plusieurs fichiers de configuration:
* club.php
* config.php
* database.php
* program.php

## Mécanisme de clé - valeur

On va gérer en base de données un ensemble de clé/valeur pour gérer la configuration.

Les valeurs pourront être traduites, en fonction de la langue utilisée. La liste des clés valides devra être contrôlée mais pourra être codée en dur puisque ces paramètres sont connus du code.

Pour l'instant pas de besoin identifié d'avoir autre chose que des valeurs chaînes de caractère.

## Structure de la table

* id int autoincremented
* cle varchar(128)
* lang varchar(6), eng, fr, etc.
* valeur varchar(255)
* categorie varchar(64) Les catégories sont définies dans le code. et utilisées pour présenter à l'utilisateur des panneaux de configuration sur un sujet, par exemple la configuration club, la configuration des vi, etc.

### Liste des valeurs acceptées

Comme il s'agit de paramètres de configuration du programme, leur liste est limitée et connue. Il ne servirait à rien de définir un paramètre de configuration qui ne soit pas utilisé par le programme.

Par convention les paramètres sont structurés avec des points séparateurs, ex: "vols_découverte.ulm.tel_contact".

L'idée est que la même clé soit utilisée plusieurs fois avec des valeur différentes pour club et lang. 

## Utilisation

Je vais utiliser initialement cette resource pour configurer la gestion des vols de découverte. Il serait logique qu'à un certain point la configuration club soit migré vers ce genre de mécanisme
