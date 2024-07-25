# Exécution des tests PHPUnit

Il faut que la base soit définie dans application/config/database.php

GRANT ALL PRIVILEGES ON `gvv\_test` . * TO 'gvv_user'@'localhost' WITH GRANT OPTION ;

Importer install/test_database_17.sql

Pour les controllers, toujours le problème de connection. Il faut:
export TEST=1 (mais cela ne fonctionne que pour les tests individuels)

Donc on sait tester
    * les contrôleurs
    * les librairies
    * les helpers
    * les modèles
    
