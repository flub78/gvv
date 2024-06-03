# Ce script permet l'activation manuelle des tests PHPUnit
# Il a le même effet que l'activation de la suite AllTests.php
# ---------------------------------------------------------------

phpunit PhpunitReset.php
phpunit PhpunitLoginAdminTest.php
phpunit PhpunitConfig.php
phpunit PhpunitBasicNavigation.php
phpunit PhpunitBasicMember.php
phpunit PhpunitAsterix.php

Execution sans Serveur
----------------------
Si tous les tests sont sautés (Skipped) il est probable que le serveur selenium ne soit pas lancé.

[frederic@wisky tests]$ phpunit PhpunitBasicNavigation.php
PHPUnit 3.6.12 by Sebastian Bergmann.

setUpBeforeClass
SSS

Time: 1 second, Memory: 3.75Mb

OK, but incomplete or skipped tests!
Tests: 3, Assertions: 0, Skipped: 3.


Execution avec erreurs
----------------------

[frederic@wisky tests]$ phpunit PhpunitBasicNavigation.php
PHPUnit 3.6.12 by Sebastian Bergmann.

setUpBeforeClass
EEF

Time: 23 seconds, Memory: 3.75Mb

There were 2 errors:

1) PhpunitBasicNavigation::testMyTestCase

Invalid response while accessing the Selenium Server at 'http://localhost:4444/selenium-server/driver/': ERROR: Element link=Administrateur club not found


2) PhpunitBasicNavigation::testStatsSansVols

Invalid response while accessing the Selenium Server at 'http://localhost:4444/selenium-server/driver/': ERROR: Element link=Statistiques not found


--


There was 1 failure:

1) PhpunitBasicNavigation::testAchat
assert true
Failed asserting that 1 is true.

/var/www/html/gvv2/tests/PhpunitGVVSelenium.php:37

FAILURES!
Tests: 3, Assertions: 6, Failures: 1, Errors: 2.


# Création des comptes
# --------------------

101 Capital
512 Compte courant
401 Fournisseur de ruban adésif

601 Essence et Huile
615 Entretien
616 Assurance
756 Heures de vol et remorqués
746 Subvention

# Création des tarifs
20  Heure de vol monoplace
27  Heure de vol biplace
10  Heure de vol au forfait
120 Heure de vol avion
300 Forfait heures
30  Remorqué
7   Treuillé

# Les planeurs
Asw20   Alexander Schleicher        F-CERP  UP  1           Privé       0.00    0.00    0   vols        
G103    Grob                        F-CFYD  T83     2           Club        27.00   10.00   180     vols        
Pégase  Centrair                    F-CGFN  3S  1           Privé       0.00    0.00    0   vols        
Asw20   Centrair                    F-CGKS  WE  1           Privé       0.00    0.00    0   vols        
Pégase  Centrair                    F-CGNP  Y31     1           Club        27.00   10.00   180     vols        
DG400   Glaser-Dirks                F-CGRD  RD  1           Privé       0.00    0.00    0   vols        
PW-5    Politechnika Warszawska     F-CICA  CA  1           Club        18.00   10.00   180     vols        
Ask21   Alexander Schleicher        F-CJRG  RG  2           Club        27.00   10.00   180     vols        

# Les avions
MS893-L     Socata  F-BLIT  2
DR400       Robin   F-BERK  4


# Organisation des tests
# ----------------------

pear config-show
cd /usr/share/pear/
cd /usr/share/pear/PHPUnit/Extensions


accabs

Scénario gestion des tickets

*   configurer la facturation
*   passer la facturation à accabs
*   Créer un tarif paquet de 11 remorqué 
*   Panoramix achète un pack de remorqués
*   Il fait deux vols, on vérifie qu'il lui en reste 9
*   On passe un de ses vols en treuillé, on vérifie qu'il lui en reste 10


Scénario Facturation
    1) Facturation planeur
        * Forcer mode facturation à Abbeville
        * Lire le soldes des pilotes A et B, euros et remorqués
        * Lire solde du compte heure de vol
        * Créer un vol pilote A
        * Vérifier solde pilote A = solde inital + cout, solde remorqués inchangé
        * Passer vol à 50 %
        * Vérifier soldes A et B = solde initial + 50 % cout
        * Vérifier compte heure de vol modifié
        * Passer vol à 100 %
        * Vérifier solde A = Initial, Solde B = initial + cout
        * Passer en non facturé, vérifier retour au solde initiaux
        * Détruire le vol, vérifier retour au solde initiaux
        
        A = Panoramix, solde rem = 10, solde créditeur = 394.67, 756 = HDV + Rem = 1565.33
        B = Astérix, solde rem = 0 (non affiché), solde créditeur = 0
        
    1) Facturation avion
        * Lire le soldes des pilotes A et B, euros
        * Lire solde du compte heure de vol
        * Créer un vol pilote A
        * Vérifier solde pilote A = solde inital + cout
        * Passer vol à 50 %
        * Vérifier soldes A et B = solde initial + 50 % cout
        * Passer vol à 100 %
        * Vérifier solde A = Initial, Solde B = initial + cout
        * Détruire le vol, vérifier retour au solde initiaux
        

# Coverage
# --------
Le test suivant mesure la couverture de test. Par contre ce n'est pas un test Selenium
est il est difficile d'invoquer les classes CodeIgniter directement (Les classes CodeIgniter vérifient qu'elles
sont invoquées depuis le Framework).

Pour l'instant je n'ai pas réussi à mesurer la couverture des tests Selenium.

phpunit CoverageTest.php

Résultats XML dans clover.xml, HTML sous code-coverage-report/


