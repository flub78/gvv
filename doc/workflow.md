# Developpement workflow

Ce fichier pour documenter les étapes de développement.

## Génération de la migration

dans application/migrations

Naming conventions for CodeIgniter 2.x Migration Classes:

* File Names: Snake_case with numeric prefixes (no specific timestamp format was enforced).
* Class Names: CamelCase prefixed with Migration_.
Methods: Classes must define up() and down() methods.

Uptade application/config/migration.php

