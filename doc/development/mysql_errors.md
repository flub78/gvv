# MySql Errors

La vérification des retours des requêtes de la base de données n'est pas un point fort de GVV. Il y a encore beaucoup d'endroit ou le status des requête n'est pas testé. Dans l'énorme majorité des cas, cela ne pose pas de problèmes, le logiciel a été déployé depuis longtemps et les requêtes ont été corrigées au fil du temps.

Néanmoins quand il y a un problème, les messages d'erreurs ne remontent pas et il est difficile de savoir ce qui s'est passé.

Il set donc important d'améliorer ce point au fil des modifications.

## Méthode de vérification des requêtes

Les requêtes retournent faux quand quelque chose c'est mal passé.

    $this->db->_error_message()

La bonne approche est donc à minima de tester le status de retour des requêtes et d'écrire le message d'erreur dans les journaux au cas ou.

Un problème est que les requêtes à la base retourne un object résultat afin de pouvoir être chaînées. La logique n'est pas respectée par la fonction get qui retourner un booleen faux en cas d'erreur. Dans ce cas la fonction result_array génère une seconde erreur puisqu'elle essaye de déréférencer un booleen.

La fonction safe_get() dans common model garantie que les erreurs sont bien décrite dans les fichiers log.

## Enabling Database error reporting

Enable database debugging in your database configuration file (application/config/database.php):

    $db['default']['db_debug'] = TRUE;

with that:

    Une erreur de la base de données s'est produite.
    Error Number: 1146

    Table 'gvv2.sections' doesn't exist

    SELECT * FROM (`sections`) WHERE `id` = 0

    Filename: /var/www/html/gvv.net/models/common_model.php

    Line Number: 32