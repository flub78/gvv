<?php
/**
 * Nederlands — Configuratiepagina (gebundelde instellingen)
 */

$lang['parametres_title']            = 'Configuratie';
$lang['parametres_card_desc']        = 'Instellingen van de club en de toepassing';
$lang['parametres_no_tab']           = 'Er is geen instelling voor u beschikbaar.';
$lang['parametres_forbidden_tab']    = 'U hebt geen toegang tot het tabblad "%s".';
$lang['parametres_unknown_tab']      = 'Het tabblad "%s" bestaat niet.';
$lang['parametres_save']             = 'Opslaan';
$lang['parametres_read_only']        = 'Dit tabblad is voor u alleen-lezen.';
$lang['parametres_default_value']    = 'Standaardwaarde van GVV: er is nog niets ingevuld.';
$lang['parametres_last_update']      = 'Laatst gewijzigd op %s door %s';
$lang['parametres_unsaved_confirm']  = 'Sommige wijzigingen zijn niet opgeslagen. Dit tabblad toch verlaten?';
$lang['parametres_saved']            = 'Opgeslagen instellingen:';
$lang['parametres_nothing_changed']  = 'Niets om op te slaan.';
$lang['parametres_not_saved']        = 'Niet opgeslagen instellingen:';
$lang['parametres_preview']          = 'Voorbeeld';
$lang['parametres_public_url']       = 'Openbaar adres';
$lang['parametres_copy']             = 'Kopiëren';
$lang['parametres_copied']           = 'Adres gekopieerd';

$lang['parametres_status_complete']   = 'Volledig';
$lang['parametres_status_incomplete'] = 'Onvolledig';
$lang['parametres_status_optional']   = 'Optioneel, niet ingesteld';

$lang['parametres_lang_french']  = 'Français';
$lang['parametres_lang_english'] = 'English';
$lang['parametres_lang_dutch']   = 'Nederlands';

$lang['parametres_tab_pages_publiques']      = 'Openbare pagina\'s';
$lang['parametres_tab_pages_publiques_help'] = 'Pagina\'s die zonder aanmelding toegankelijk zijn. Het privacybeleid en de gebruiksvoorwaarden zijn bereikbaar vanaf de aanmeldpagina.';
$lang['parametres_legal_confidentialite']      = 'Privacybeleid';
$lang['parametres_legal_confidentialite_help'] = 'Eigen tekst van de club, in Markdown. Leeg gelaten wordt een algemene GVV-tekst gebruikt. Clubgegevens kunnen worden ingevoegd: {nom_club}, {adresse_club}, {cp_club}, {ville_club}, {email_club}, {tel_club}, {url_club}, {contact_rgpd}.';
$lang['parametres_legal_conditions']           = 'Gebruiksvoorwaarden';
$lang['parametres_legal_conditions_help']      = 'Eigen tekst van de club, in Markdown. Leeg gelaten wordt een algemene GVV-tekst gebruikt. Dezelfde clubgegevens als voor het privacybeleid kunnen worden ingevoegd.';
$lang['parametres_newsletter_page']            = 'Nieuwsbrieven';
$lang['parametres_generic_text']               = 'Algemene GVV-tekst (gebruikt zolang er niets is ingevuld):';

$lang['parametres_err_unknown']          = 'onbekende instelling';
$lang['parametres_err_forbidden']        = 'u mag deze niet wijzigen';
$lang['parametres_err_language']         = 'onbekende taal';
$lang['parametres_err_required']         = 'waarde verplicht';
$lang['parametres_err_max_length']       = 'tekst te lang';
$lang['parametres_err_email']            = 'ongeldig e-mailadres';
$lang['parametres_err_url']              = 'ongeldig webadres (het moet beginnen met http:// of https://)';
$lang['parametres_err_integer']          = 'geheel getal verwacht';
$lang['parametres_err_db']               = 'fout bij opslaan in de database';
$lang['parametres_err_file_missing_file'] = 'configuratiebestand %s ontbreekt op de server';
$lang['parametres_err_file_not_writable'] = 'bestand %s kan niet door de webserver worden gewijzigd (op de server: chmod g+w %s)';
$lang['parametres_err_file_syntax_error'] = 'de wijziging zou bestand %s ongeldig maken, ze werd niet doorgevoerd';
$lang['parametres_err_file_write_failed'] = 'schrijven van bestand %s is mislukt';
$lang['parametres_err_file_invalid_key']  = 'ongeldige naam van instelling';
