# Sections WIP

Description détaillée du travail de développement.

## Resource sections

Elle définit les sections de l'association. C'est un CRUD qui doit être accessible aux admins et super trésoriers.

Que faire si quelqu'un essaye de détruire une section qui a des données?

Qui à le droit de créer et modifier des membres? Ca devient une resource partagée entre les sections. Il est donc possible qu'un admin planeur crée un membre qui ne fasse rien en planeur. Que le membre soit actif en ULM et donc qu'il crée des données ULM puis que l'admin planeur décide de le supprimer sans avoir connaissance de son activité ULM.

D'ailleurs la création d'un membre entraînait la création de son compte planeur. Est-qu'on veut maintenant créer trois comptes ou laisser au trésorier de chaque section la charge de créer les compte clients pour chaque section ??? Est-ce qu'on doit créer automatiquement un compte client pour chaque section?

Si on fait cela on risque d'être bloqué au moment de la facturation si le compte client n'existe pas (ça ne pouvait pas arriver avant) et ça arrivera au moment de la saisie du vol sans la présence du trésorier.

## Ressource Section_roles

Elle définit les rôles des utilisateurs en fonction des sections

## Affectation des données existantes à la section planeur

La section planeur est la la section qui exploitait GVV avant que les autres ne lui soit rattachées. Toutes les données existantes doivent être transférées dans la section planeur.

## Affichage de la section pour les données par section

Toutes les données spécifiques à une section doivent indiquer à quelle section elles appartiennent. 

Note : Il existe une section courante qui est la section dans laquelle l'utilisateur est connecté. Il devra être possible de changer de section pour les utilisateurs multi sections. Auquel cas peut-être qu'il n'y a pas besoin d'afficher la section sauf pour les super trésoriers et les admins.