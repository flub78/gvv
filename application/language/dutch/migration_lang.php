<?php
/*
 * GVV Nederlandse vertaling
 */

# $this->lang->line("")
$lang['migration_title'] = "Bijwerken database.";

$lang['migration_explain'] = 'Het bijwerken van de dabase zorgt dat de laatste updates verwerkt worden in het systeem.';
$lang['migration_advice'] = 'Het is sterk aanbevolen alvorens bij te werken een backup te nemen.';

$lang['migration_program_level'] = 'Versie toepassing';
$lang['migration_base_level'] = 'Oorspronkelijke versie';
$lang['migration_target_level'] = 'Doelversie';
$lang['migration_uptodate'] = 'De database is bijgewerkt';

// Foutmeldingen vereist door system/libraries/Migration.php. Dit bestand
// vervangt het systeembestand met dezelfde naam volledig (geen samenvoeging):
// een ontbrekende sleutel hier laat CI_Migration::error_string() stil falen
// (sprintf(FALSE, ...) geeft een lege string terug), wat een lege foutpagina oplevert.
$lang['migration_none_found']          = "Geen migraties gevonden.";
$lang['migration_not_found']           = "Deze migratie kon niet worden gevonden.";
$lang['migration_multiple_version']    = "Er zijn meerdere migraties met hetzelfde versienummer: %d.";
$lang['migration_class_doesnt_exist']  = "De migratieklasse \"%s\" kon niet worden gevonden.";
$lang['migration_missing_up_method']   = "De migratieklasse \"%s\" heeft geen 'up'-methode.";
$lang['migration_missing_down_method'] = "De migratieklasse \"%s\" heeft geen 'down'-methode.";
$lang['migration_invalid_filename']    = "Migratie \"%s\" heeft een ongeldige bestandsnaam.";

