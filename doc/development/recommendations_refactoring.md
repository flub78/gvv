# Recommandations sur le temps consacré au refactoring

## Le consensus de l'industrie

La fourchette généralement citée est **15 à 20% du temps de développement** consacré au refactoring et à la réduction de la dette technique. Certaines sources vont jusqu'à 25%.

Martin Fowler et le mouvement Software Craftsmanship prônent plutôt le **refactoring continu** (opportuniste) — on améliore le code qu'on touche au fil des features, plutôt que de réserver des "sprints de refactoring" dédiés.

## Les différentes approches

**La règle du Boy Scout** (Robert C. Martin) : "Laisse le code plus propre que tu ne l'as trouvé." Pas de budget dédié, c'est intégré dans chaque tâche. En pratique ça représente ~10-15% de surcoût par feature.

**Le modèle Google/Meta** : environ 20% du temps d'ingénierie est consacré à l'infrastructure, au refactoring et à la réduction de dette. C'est souvent formalisé dans les OKR.

**La règle des 20/80 pragmatique** : identifier les 20% du code qui causent 80% des problèmes, et concentrer l'effort de refactoring là.

## Argumentation pour un contexte legacy (PHP 7 / CodeIgniter 2)

Avec une application PHP 7 / CodeIgniter 2 maintenue depuis 2011, la dette technique est structurelle (framework obsolète, PHP en fin de support). Dans ce contexte, une allocation de **20-25%** est recommandée, pour plusieurs raisons :

- **Le coût de la dette augmente de façon non-linéaire** — plus on attend, plus chaque changement coûte cher. Des études (comme celles de Stripe en 2018) estimaient que les développeurs perdent ~42% de leur temps à cause de la dette technique.
- **Le refactoring réduit le coût des futures features** — c'est un investissement, pas une dépense. Ward Cunningham (inventeur du terme "dette technique") insistait sur ce point.
- **La sécurité** — PHP 7 n'est plus maintenu, chaque mois sans migration augmente le risque.

## Approche pratique recommandée : modèle hybride

- **~10-15% de refactoring opportuniste** intégré dans chaque tâche (règle du Boy Scout)
- **~5-10% de temps dédié** à des chantiers structurels planifiés (migration PHP 8, modernisation progressive du framework)

> ⚠️ L'erreur classique est de ne jamais refactorer par manque de temps, puis de se retrouver avec une réécriture complète obligatoire — qui est toujours plus risquée et coûteuse.
>
> ## Refactoring de GVV
>
>Le point le plus important est la dépendance à php 7.4 et codeigniter 2.0. Le plan est créer un nouveau projet avec Laravel 10 et PHP 8.2 qui soit compatible avec le schéma de données. Puis de trouver un moyen pour que certaines routes soient servies par l'ancien projet et d'autres par le nouveau. Cela permettra de faire une migration progressive, route par route.

Les autres point sont moins critiques et sont surtout relatifs à l'architecture de répertoire qui pourrait être réorganisée périodiquement.

Pour le reste cela fait longtemps qu'on applique la règle du boy scout et on a déjà fait pas mal de refactoring opportuniste.
