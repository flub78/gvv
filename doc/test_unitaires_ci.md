# Tests unitaires CodeIgniter

Mars 2023

Il y a quelques tests unitaires qui fonctionnent encore avec GVV. Ils utilisent la librairie de test de CodeIgniter.

* Ils sont activables depuis le controller "tests"
* Ils ne sont pas automatiques
* Les tests sont implémentés par la fonction test de chaque controller

'''

        $this->unit_test = TRUE;
        $this->load->library('unit_test');

        $this->unit->run(true, true, "Tests $this->controller");
        $this->tests_results($format);
'''

* Ils utilisent la librairie My_Unit_test.php
* qui enrichit la classe Unit Testing de CodeIgniter https://gvv.flub78.net/gvv/user_guide/libraries/unit_testing.html 

La verification d'assertion ce fait avec

	$this->unit->run

* en mars 2023 il y avait 89 assertions vérifiés par ces tests (c'est pas beaucoup).

* Il y a un formatter XML_result qui génère un fichier XML. 

## A faire

* évaluer la couverture, probablement assez faible
* Verifier la génération du format XML
* activer en utilisant les appels CLI de CodeIgniter

Note: un fois que les tests seront automatisés, il sera possible de vérifier la couverture de code avec Xdebug.
