<?php
/*
 * GVV French translation
*/

# $this->lang->line("")
$lang['migration_title'] = "Migration de la base de données.";

$lang['migration_explain'] = 'La migration de la base de données, met à jour la structure afin de bénéficier des dernières fonctionnalités du logiciel.';
$lang['migration_advice'] = 'Il est fortement conseillé de réaliser une sauvegarde de la base avant chaque migration.';

$lang['migration_program_level'] = 'Niveau du programme';
$lang['migration_base_level'] = 'Niveau de la base';
$lang['migration_target_level'] = 'Niveau de destination de la base';
$lang['migration_uptodate'] = 'La base de données est à jour';

// Messages d'erreur requis par system/libraries/Migration.php. Ce fichier
// remplace entièrement (et non fusionne) le fichier système du même nom :
// toute clé absente ici fait échouer silencieusement CI_Migration::error_string()
// (sprintf(FALSE, ...) renvoie une chaîne vide), d'où une page d'erreur vide.
$lang['migration_none_found']          = "Pas de migrations trouvé.";
$lang['migration_not_found']           = "Cette migration n'existe pas.";
$lang['migration_multiple_version']    = "Il y a plusieurs migrations avec le numéro de version: %d.";
$lang['migration_class_doesnt_exist']  = "La classe de migration \"%s\" n'existe pas.";
$lang['migration_missing_up_method']   = "La classe de migration \"%s\" n'a pas de méthode 'up'.";
$lang['migration_missing_down_method'] = "La classe de migration \"%s\" n'a pas de méthode 'down'.";
$lang['migration_invalid_filename']    = "La migration \"%s\" a un nom de fichier invalide.";

