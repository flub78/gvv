# Les tests de bout en bout

Ils simulent un utilisateur qui interagit avec l'application à l'aide d'un navigateur. Les tests pilotent le navigateur et vérifient ce qui est affiché. 

* Le répertoire de test watir est obsolete.
  
* Utilisez (mars 2023) pour tester GVV de bout en bout. https://github.com/flub78/dusk_gvv


## Lancement des Tests

Sous Windows, la synchro Dropbox doit être désactivée. Modifiez setenv.bat pour changer le serveur de test.

    cd Dropbox\xampp\htdocs\dusk_gvv
    setenv.bat

    php artisan dusk

    ERRORS!
    Tests: 75, Assertions: 981, Errors: 5, Failures: 24, Skipped: 11.
    
ou, pour afficher chrome:

    php artisan dusk --browse

    Tests: 75, Assertions: 1035, Errors: 9, Failures: 17, Skipped: 11

Vous trouverez les copies d'écran des tests dans le répertoire tests/Browser/screenshots.

## Individual test execution

    php artisan dusk --color=always --browse tests/Browser/ExampleTest.php
    php artisan dusk --color=always --browse tests/Browser/CIUnitTest.php
    php artisan dusk --color=always --browse tests/Browser/InstallationTest.php
    php artisan dusk --color=always --browse tests/Browser/AdminAccessTest.php
    php artisan dusk --color=always --browse tests/Browser/BureauAccessTest.php
    php artisan dusk --color=always --browse tests/Browser/CAAccessTest.php
    php artisan dusk --color=always --browse tests/Browser/PlanchisteAccessTest.php
    php artisan dusk --color=always --browse tests/Browser/UserAccessTest.php
    php artisan dusk --color=always --browse tests/Browser/PlaneurTest.php
    php artisan dusk --color=always --browse tests/Browser/TerrainTest.php

    php artisan dusk --color=always --browse tests/Browser/SmokeTest.php

    php artisan dusk --color=always --browse tests/Browser/DbInitTest.php
    php artisan dusk --color=always --browse tests/Browser/GliderFlightTest.php

## En cas d'erreur

* Relancez les tests individuellement
* Vérifier les copies d'écran failure_Test_Browser_...
* Relancez et debugguez sur un serveur local