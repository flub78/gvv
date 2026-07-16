<?php
/*
 * GVV English translation
*/

# $this->lang->line("")
$lang['migration_title'] = "Database migration.";

$lang['migration_explain'] = 'Database migration updates the database structure to get the latest features.';
$lang['migration_advice'] = 'It is recommended to make a database backup before to migrate.';

$lang['migration_program_level'] = 'Program level';
$lang['migration_base_level'] = 'Database level';
$lang['migration_target_level'] = 'Target level';
$lang['migration_uptodate'] = 'The database is up to date.';

// Error messages required by system/libraries/Migration.php. This file fully
// replaces (does not merge with) the system file of the same name: any key
// missing here makes CI_Migration::error_string() fail silently
// (sprintf(FALSE, ...) returns an empty string), producing a blank error page.
$lang['migration_none_found']          = "No migrations were found.";
$lang['migration_not_found']           = "This migration could not be found.";
$lang['migration_multiple_version']    = "This are multiple migrations with the same version number: %d.";
$lang['migration_class_doesnt_exist']  = "The migration class \"%s\" could not be found.";
$lang['migration_missing_up_method']   = "The migration class \"%s\" is missing an 'up' method.";
$lang['migration_missing_down_method'] = "The migration class \"%s\" is missing an 'down' method.";
$lang['migration_invalid_filename']    = "Migration \"%s\" has an invalid filename.";

