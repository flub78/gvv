## Les défauts de GVV

### GVV n'est pas multi organisation
Chaque club doit en déployer une instance. L'avantage est que cela permet une indépendance absolue des données mais au prix d'une complexité certaine.

### La facturation est configurée avec un module PHP. 
Les trésoriers des associations de vol en planeur ont une imagination sans limite quand il s'agit d'inventer des méthodes de facturation. Sur les six ou sept clubs qui emploient GVV, il n'y en a pas deux qui facturent de la même façon.

Pour prendre en compte cette diversité, j'ai considéré que la façon la plus simple est que chaque club fournisse son module de facturation en PHP. L'avantage est que cela permet de satisfaire les désirs les plus fous des trésoriers et cela permet d'éviter un système de configuration qui peux rapidement devenir complexe. Le module de facturation est relativement simple. Il est possible de s'inspirer des modules existants, néanmoins le développement
d'un module PHP même simple reste un exercise hors d'atteinte pour tous les clubs qui ne disposent pas d'un informaticien. La prochaine version aura un système de configuration de la facturation.

### La couverture de tests

Elle a longtemps été insuffisante. En juin 2026 avec plus de 1300 tests phpunit et 600 tests playwright, on peut dire que la couverture de test est devenue un point fort du projet.

### Toujours sur php 7.4

Une migration vers php 8.x est prévue mais elle n'est pas encore réalisée. 
