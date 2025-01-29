# Views Design

## Justification d'un changement d'approche 

** décembre 2024**

HTML est un format qui décrit le contenu et la structure d'une page web. Au début du WEB, les réseaux étaient lents et on cherchait à minimiser les requêtes. Une requête HTTP retournait une page complète. Ce n'est que par la suite qu'on a pris l'habitude de scinder les données d'une page et à considérer normal d'avoir plusieurs requêtes HTTP pour afficher une seule page (AJAX, React, etc.).

Pour ces raisons, HTML n'a jamais eu la modularité qu'on trouve dans les languages de programmation. En HTML si on affiche plusieurs fois la même chose, on la répète. Ce n'est qu'avec l'arrivée de framework comme React qu'on a commencé à retrouver de la modularité. 

On a essayé de palier à ces limites en utilisant des outils de génération. Aucun n'a réussi à s'imposer dans la durée. Il est encore habituel d'éditer le HTML à la main et les éditeurs de code modernes facilitent considérablement ce travail.

En tant que programmeur traditionnel (pas programmeur d'applications WEB), l'absence de modularité m'était insupportable. Je considère toujours que la réplication va à l'encontre de l'efficacité. J'ai donc généré les vues de GVV en utilisant au maximum des fonctions PHP qui retournent du HTML. Ca permet de retrouver de la modularité. L'inconvénient est qu'on pert de lisibilité sur la structure de la page. Il n'y a plus d'endroit ou l'on garde une vue d’ensemble sur la page.

De plus certaine fonctions GVV PHP qui génèrent du HTML utilisent des styles obsolètes comme les tableaux pour la mise en page.

Il me semble donc judicieux de retourner à une approche traditionnelle des vues et de les écrire en HTML moderne et de n'utiliser le PHP que pour les éléments dynamique. Cela va dans le sens d'une meilleure modularité (separation of concerns).

Je n'ai pas l'intention de rétrofiter toutes les vues existantes, mais les nouvelles fonctionnalités ainsi que les modifications des vues existantes devraient aller dans ce sens. Les vues de 'attachments' sont un bon exemple de ce que je veux dire.

Note: c'est toujours un peu frustrant en informatique de voir que les solutions qu'on a mises en place pour résoudre un problème sont devenues inutiles avec le temps. La communauté à fini par résoudre les mêmes problèmes avec d'autres solutions.