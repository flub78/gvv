# GVV tests et validation

GVV a maintenant presque 10 ans et le projet a surtout été actif pendand les trois ou quatre premières années. Malgrè son age, il continue à être utilisé par plusieurs clubs. 

Donc comme il est toujours utile, il faut le maintenir. Cela veux dire, l'adapter aux changements de son environement, corriger les bugs et ajouter les fonctionnalités nécéssaires aux changement de reglementation.

GVV fonctionne encore bien sous PHP 7.4, néanmoins mon serveur jenkins c'est crashé lors de la dernière mise à jour de jenkins. Je n'ai donc plus aucun test automatique utilisable.

Les évolutions de l'environement comme le passage en PHP 7 ont introduit des bugs. Le plus souvent il s'agit de code qui était légal en PHP 5 qui ne fonctionne plus en PHP 7. L'adaptation génère un peu de travail sur le code qu'on a écrit, mais c'est beaucoup plus ennuyeux quand les problèmes arrivent dans des modules externes (modules d'autentification, module de génération des graphismes), etc. On doit alors aller faire les modifications dans du code qu'on ne connait pas ou il faut complétement remplacer le module.

De la même façon les tests phpunit ne fonctionnent plus avec les versions récentes de php. De plus les versions récentes de php génèrenet également un grand nombre de warnings. Même s'il n'empèchent pas le logiciel de fonctionner, ce sont des indices de futurs disfonctionements lorsque les prochaines versions de php seront encore plus strictes et n'accepteront simplement plus le code qui générait des warnings.

On se trouve donc dans une situation paradoxale, avec une augmentationdu nombre des modifs à apporter et une diminution des capacités de validation automatique.

Les efforts faits dans le passé, pour atteindre un haut niveau de validation automatique n'on jamais totalement abouti. Il n'a jamais été possible de dire: après le passage des tests, j'ai la garantie que toutes les fonctionnalités sont opérationnels et que tous les bugs qui ont été reportés ont été corrigés. Il est bien évident que l'on ne peut apporter cette certitude que dans un contexte unique (cela marche au moins sur une machine). Mais cela semble être le minimum pour pouvoir apporter des modifications de façon sereine dan GVV.

# Etapes de reprise en main

## Test unitaires



* Remettre en place un serveur jenkins sous possible sous AWS
* faire le bilan des test phpunit
* remettre en place des tests end to end
* faire le bilan des containers dockers

et on commence par réactiver l'environement de test autour des dernières modifications.



