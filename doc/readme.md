# GVV

Le 23/03/2023

GVV est un projet de gestion de club de vol en planeur. Le projet a été commencé en 2011 est il est utilisé par 6 ou 7 clubs.

Il est géré chez developez.com, attention certains points de la documentation sont obsolètes et les sites de démonstration ont été désactivés.

	http://projets.developpez.com/projects/gvv/wiki/Documentation_utilisateur
	
## Les fonctionnalités

* Gestion des membres, des machines, des vols planeur, ULM et avion.
* Gestion de la facturation
* Gestion de la comptabilité

et bien plus encore.

## Les points forts

* sa facilité d'utilisation
* sa robustesse, en place depuis longtemps beaucoup de bugs ont été corrigés.

## Les points faibles

* Il est resté en PHP 7.4 et le passage en 8.x s'avère difficile voir impossible
* La couverture de tests automatisés n'a jamais été à 100 % et beaucoup des technologies utilisés sont maintenant obsolètes ou compliquée à ré-installee.

# Evolutions

Le logiciel a subi une refonte récente (2022) pour le rendre responsive (utilisable sur téléphone portable).

Il y a quelques bugs à corrigé et il faudrait lui ajouter l'anonimisation des membres qui ont quitté l'association pour le rendre compatible avec le RGPD.

Une réécriture complète basée sur Laravel et PHP 8.x est en cours, mais un gros travail qui n'aboutira pas avant plusieurs année.

D'un autre coté, le logiciel fonctionne encore très bien et certaines évolutions serait pratiques.  

J'envisage donc à minima de redéployer un environement de test et des jobs jenkins sur AWS et de remettre en place des test automatiques.

* L'integration de phpunit avec les vielles versions de CodeIgniter < 3.0 n'a jamais été supporté officielement par CodeIgniter. On ne trouvait que quelques modules de qualité diverse fournis par la communauté. Donc plutôt que d'essayer de réactiver phpunit, je pense plutot modifier les tests intégrés qui sont activés par l'interface WEB pour générer directement les fichiers de résultats junit en XML, ceci afin de pouvoir réintégrer ces tests dans Jenkins.

* Porter les tests Selenium sous Laravel Dusk pour avoir également une base de tests end to end.

* Enrichir cette base de test minimal au fur et a mesure des évolutions limités de cette version de GVV.

Le portage sous PHP 8.x ou 9.x semble impossible sans remplacer tous les modules et librairies qu'il intègre pour gérer l'authentification, les graphismes, la génération de pdf, etc. Cela ne semble pas raisonable d'investir autant de temps pour faire survivre un logiciel qui a plus de 10 ans.

## Docker container

Il n'est pas impossible que je réactualise le support de GVV dans un container docker. Cela lui permettrait de pouvoir rester en PHP7. Cela pose néanmoins le problèmes des patchs de sécurité. Geler un logiciel déployé sur Internet sans mises à jour de sécurité n'est pas forcément une bonne idée.

