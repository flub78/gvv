## Les défauts de GVV

### GVV n'est pas multi organisation
Chaque club doit en déployer une instance. L'avantage est que cela permet une indépendance absolue des données mais au prix d'une complexité certaine.

### La facturation est configurée avec un module PHP. 
Les trésoriers des associations de vol en planeur ont une imagination sans limite quand il s'agit d'inventer des méthodes de facturation. Sur les six ou sept clubs qui emploient GVV, il n'y en a pas deux qui facturent de la même façon.

Pour prendre en compte cette diversité, j'ai considéré que la façon la plus simple est que chaque club fournisse son module de facturation en PHP. L'avantage est que cela permet de satisfaire les désirs les plus fous des trésoriers et cela permet d'éviter un système de configuration qui peux rapidement devenir complexe. Le module de facturation est relativement simple. Il est possible de s'inspirer des modules existants, néanmoins le développement
d'un module PHP même simple reste un exercise hors d'atteinte pour tous les clubs qui ne disposent pas d'un informaticien. La prochaine version aura un système de configuration de la facturation.

### La couverture de tests est toujours insuffisante. 
Pendant toute la durée du projet j'ai couru derrière les tests, m'approchant sans jamais l'atteindre d'un taux de couverture acceptable. (Je considère acceptable un taux de couverture qui permette d'intervenir dans le logiciel tout en gardant un bon niveau de confiance qu'aucune regression n'a été introduite.)

Les raisons en sont multiples, les version initiales de CodeIgniter n'étaient pas intégrés avec phpunit, elles utilisait un système de test qui était propre à CodeIgniter et difficile à intégrer dans les systèmes d'intégration continue (jenkins). De plus les contrôleurs ne pouvaient pas être testé avec le système de test. Certains développeur ont bien tenté de palier à ces limitations mais comme il s'agissait de modules externes, ils n'ont pas suivi les évolutions de CodeIgniter.

Lest tests sont donc constitués de tests unitaires de bas niveau et de tests de bout en bout Playwright. Les tests Selenium, Watir et Dusk sont obsolètes.

**Fin 2025, une nouvelle migration des tests est en cours. Les tests CodeIgniter ont commencé à être migrés vers phpunit et les tests de bout en bout vers playwright**

En 2026 les tests actifs sont des tests phpunit et playwright.
