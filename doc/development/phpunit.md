# Exécution des tests PHPUnit

Il y a maintenant des tests

* unitaires
  * de helper
  * de libraries
  * de models
* d'intégration
  * d'ensemble de classe ou d'object
  * de modèles avec accès à la base MySql
* de controllers

'''
source setenv.sh 

frederic@frederic-HP:~/git/gvv$ php --version
PHP 7.4.33 (cli) (built: Jul  3 2025 16:41:49) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
    with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies
    with Xdebug v3.1.6, Copyright (c) 2002-2022, by Derick Rethans

frederic@frederic-HP:~/git/gvv$ phpunit --version
PHPUnit 8.5.44 by Sebastian Bergmann and contributors.


run/all_tests.sh

📊 Test Coverage Summary:
- Unit Tests: 32 tests (validation, models, libraries, controllers)
- Integration Tests: 25 tests (real database operations)
- Enhanced Tests: 40 tests (CI helpers and libraries)
- Controller Tests: 6 tests (JSON/HTML/CSV output parsing)
- Total: 103 tests across all categories

'''

**Rapports générés** :
- `build/logs/junit.xml` - Rapport JUnit XML compatible avec les outils CI/CD
- `build/logs/testdox.txt` - Documentation lisible des tests exécutés


It is possible to generate an html result file with:
'''
xunit-viewer -r build/logs/

'''



