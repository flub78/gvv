## En cas de problèmes

## Fichiers de log

Regardez les fichiers journaux. Ils contiennent des informations sur les erreurs.

### Logs GVV

* `gvv/application/logs/log-2025-03-05.php` 

### Logs Apache et PHP
* `/var/log/apache2/error.log`
* `/var/log/apache2/access.log`
* `/var/log/apache2/other_vhosts_access.log`
 
### Logs des tests Dusk
* `dusk_gvv/storage/logs/laravel.log`

## Investigation sur une machine locale

## Le mode debug dans index.php

Le mode development vous donne beaucoup plus de messages d'erreur, particulièrement pour les erreurs MySql.

dans le fichier `index.php`
```
// define('ENVIRONMENT', 'development');
define('ENVIRONMENT', 'production');
```

### Les tests Dusk

* messages d'erreur
* les logs d'execution du test
* Les copies d'écran en cours de test et en cas de détection d'erreur

### Xdebug
* Vous pouvez executer GVV pas à pas, mettre des breakpoints et visualiser les variables

### Si vous n'avez pas trouvé

* En cas de bug avéré, vous pouvez me contacter, mais je ne garantie plus les corrections sur cette version. 

Certains marabous résolvent les problèmes informatiques, on peut trouver leur adresse sur les flyers qu'ils distribuent dans les boites aux lettres...
