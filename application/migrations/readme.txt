Processus de migration du schema de la base de données.

1)  Installation

    Lors de l'installation, la base est crée et initialisé avec les scripts:
        * install/gvv_structure.sql
        * install/gvv_defaults.sql 
        * install/initial_users.sql

    Il existe une version anglaise de gvv_defaults.sql mais elle n'est pas maintenue.
    
    C'est le fichier gvv_default.sql qui contient le numéro de version de la base.
    Il doit correspondre au contenu de config/migration.sql pour une révision donnée.
    
2) Migration

    Après installation de nouveaux fichiers php, fonctionnant avec une nouvelle
    version de base, il suffit de lancer une migration depuis la page admin/migration.
    
3) Evolution de la structure de la base.

    Toutes les evolutions de la base doivent être réalisées par des migrations:
    
    * Ecrire le script php de migration (voir exemple sous migration)
    * Mettre à jour le numéro courant dans config/migration.php
    * mettre à jour le fichier install/gvv_structure.sql
    * mettre à jour le fichier install/gvv_defaults.sql avec la version du schéma
    