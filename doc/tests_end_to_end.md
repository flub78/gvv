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
