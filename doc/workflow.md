# Developpement workflow

Ce fichier pour documenter les étapes de développement.

## Génération de la migration

dans application/migrations

Naming conventions for CodeIgniter 2.x Migration Classes:

* File Names: Snake_case with numeric prefixes (no specific timestamp format was enforced).
* Class Names: CamelCase prefixed with Migration_.
Methods: Classes must define up() and down() methods.

Update application/config/migration.php

## Génération du modèle

dans application/models

## Génération des fichiers de traduction

language/french/attachments_lang.php
  
## Génération du contrôleur

dans application/controllers

### Création d'un test unitaire du modèle 

http://gvv.net/index.php/attachments/test

Tests are assertion based

    $this->load->library('unit_test');
    $this->unit->run($this->model->method(), 'expected_result', 'test_name');
    echo $this->unit->report();

and test execution

http://gvv.net/index.php/attachments/test


