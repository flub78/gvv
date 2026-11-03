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
$lang['parametres_err_token']            = "De pagina is verlopen of het verzoek kwam niet van de configuratiepagina: er is niets opgeslagen.";
$lang['parametres_reload']               = "Pagina opnieuw laden";


// Lot 2 : onglet Club
$lang['parametres_tab_club'] = 'Club';
$lang['parametres_tab_club_help'] = 'Identiteit van de club, gebruikt in documenten, e-mails en openbare pagina\'s. Bestuursleden kunnen ze inzien; alleen de clubbeheerder kan ze wijzigen.';
$lang['parametres_sigle_club'] = 'Afkorting';
$lang['parametres_sigle_club_help'] = 'Korte clubnaam, getoond in de koppen.';
$lang['parametres_nom_club'] = 'Naam';
$lang['parametres_nom_club_help'] = 'Volledige naam van de vereniging.';
$lang['parametres_code_club'] = 'Identificatiecode';
$lang['parametres_code_club_help'] = 'Federale clubcode (optioneel).';
$lang['parametres_adresse_club'] = 'Straat';
$lang['parametres_adresse_club_help'] = 'Postadres van de club.';
$lang['parametres_cp_club'] = 'Postcode';
$lang['parametres_cp_club_help'] = '';
$lang['parametres_ville_club'] = 'Plaats';
$lang['parametres_ville_club_help'] = '';
$lang['parametres_tel_club'] = 'Telefoon';
$lang['parametres_tel_club_help'] = '';
$lang['parametres_email_club'] = 'E-mail';
$lang['parametres_email_club_help'] = 'Contactadres van de club, ook gebruikt als afzender van e-mails.';
$lang['parametres_url_club'] = 'Website';
$lang['parametres_url_club_help'] = 'Adres van de clubwebsite, beginnend met https://.';
$lang['parametres_logo_club'] = 'Logo';
$lang['parametres_logo_club_help'] = 'PNG-, JPEG- of GIF-afbeelding van maximaal 500 KB. Het vorige logo blijft op de server bewaard, zodat u er via de geschiedenis naar kunt terugkeren.';
$lang['parametres_contact_rgpd'] = 'AVG-contactpersoon';
$lang['parametres_contact_rgpd_help'] = 'Persoon tot wie verzoeken over persoonsgegevens gericht moeten worden, vermeld in het privacybeleid. Leeg: "de voorzitter van de vereniging".';

// Lot 2 : onglet Modules
$lang['parametres_tab_modules'] = 'Modules';
$lang['parametres_tab_modules_help'] = 'Functies die voor de club zijn ingeschakeld. Het menu wordt vanaf de volgende pagina bijgewerkt. De configuratiepagina blijft altijd beschikbaar, ongeacht de actieve modules.';
$lang['parametres_gestion_vd'] = 'Kennismakingsvluchten';
$lang['parametres_gestion_vd_help'] = 'Bonnen voor kennismakingsvluchten, per e-mail verzonden.';
$lang['parametres_gestion_reservations'] = 'Reserveringen';
$lang['parametres_gestion_reservations_help'] = 'Reservering van luchtvaartuigen.';
$lang['parametres_gestion_formations'] = 'Opleidingen';
$lang['parametres_gestion_formations_help'] = 'Opvolging van de opleiding van leerlingen.';
$lang['parametres_gestion_documentaire'] = 'Documentbeheer';
$lang['parametres_gestion_documentaire_help'] = 'Gearchiveerde documenten van leden en club.';
$lang['parametres_gestion_paiements'] = 'HelloAsso-betalingen';
$lang['parametres_gestion_paiements_help'] = 'Online betalingen. De instellingen per sectie blijven voorlopig in het scherm Online betalingen > Configuratie.';
$lang['parametres_gestion_ulm'] = 'ULM';
$lang['parametres_gestion_ulm_help'] = 'Beheer van ULM\'s.';
$lang['parametres_gestion_tickets'] = 'Vluchttickets';
$lang['parametres_gestion_tickets_help'] = '';
$lang['parametres_gestion_pompes'] = 'Brandstofpompen';
$lang['parametres_gestion_pompes_help'] = '';
$lang['parametres_gestion_rapprochements'] = 'Bankafstemming';
$lang['parametres_gestion_rapprochements_help'] = '';
$lang['parametres_gestion_of'] = 'Opleidingsaanbod';
$lang['parametres_gestion_of_help'] = '';
$lang['parametres_veille'] = 'Nieuwsbrief';
$lang['parametres_veille_help'] = 'Nieuwsbrief over veiligheid en regelgeving, zonder aanmelding te raadplegen.';
$lang['parametres_auto_planchiste'] = 'Piloten voeren hun vluchten in';
$lang['parametres_auto_planchiste_help'] = 'Piloten kunnen hun eigen vluchten invoeren als ze geen startadministrateur zijn.';
$lang['parametres_module_incomplete'] = 'De module "%s" is actief maar verplichte instellingen ontbreken: vul het tabblad %s aan.';

// Lot 2 : onglet Messagerie
$lang['parametres_tab_messagerie'] = 'E-mail';
$lang['parametres_tab_messagerie_help'] = 'Server voor het verzenden van e-mails en omleiding van berichten tijdens tests.';
$lang['parametres_protocol'] = 'Verzendmethode';
$lang['parametres_protocol_help'] = '"mail (PHP)" bij gedeelde hosting; "SMTP" om een verzendserver te gebruiken (Brevo…).';
$lang['parametres_smtp_host'] = 'SMTP-server';
$lang['parametres_smtp_host_help'] = 'Verplicht in SMTP-modus, bijvoorbeeld smtp-relay.brevo.com.';
$lang['parametres_smtp_port'] = 'SMTP-poort';
$lang['parametres_smtp_port_help'] = '587 met TLS, 465 met SSL.';
$lang['parametres_smtp_crypto'] = 'Versleuteling';
$lang['parametres_smtp_crypto_help'] = '';
$lang['parametres_smtp_user'] = 'SMTP-gebruikersnaam';
$lang['parametres_smtp_user_help'] = 'Voor Brevo: Brevo-dashboard → SMTP & API → SMTP-gegevens.';
$lang['parametres_smtp_pass'] = 'SMTP-wachtwoord';
$lang['parametres_smtp_pass_help'] = 'Voor Brevo, de SMTP-sleutel (begint met xsmtpsib-).';
$lang['parametres_test_email'] = 'Omleiding van e-mails (tests)';
$lang['parametres_test_email_help'] = 'Indien ingevuld, gaan alle e-mails van GVV naar dit adres in plaats van naar hun ontvangers. Leeg laten in productie.';
$lang['parametres_test_phone'] = 'Omleiding van sms\'en (tests)';
$lang['parametres_test_phone_help'] = 'Indien ingevuld, gaan alle sms\'en van GVV naar dit nummer. Leeg laten in productie.';
$lang['parametres_test_email_title'] = 'Test-e-mail verzenden';
$lang['parametres_test_email_card_help'] = 'De test gebruikt de hierboven opgeslagen instellingen: sla eerst uw wijzigingen op. De omleiding van test-e-mails geldt niet voor dit bericht.';
$lang['parametres_test_email_to'] = 'Ontvanger';
$lang['parametres_test_email_send'] = 'Verzenden';
$lang['parametres_test_email_subject'] = 'GVV: test-e-mail';
$lang['parametres_test_email_body'] = '<p>Deze test-e-mail is verzonden vanaf de configuratiepagina van GVV door %s op %s.</p><p>Als u dit leest, werkt het verzenden van e-mails.</p>';
$lang['parametres_test_email_sent'] = 'Test-e-mail verzonden naar %s. Controleer of hij aankomt.';
$lang['parametres_test_email_failed'] = 'Verzenden van de test-e-mail mislukt (verzendmethode: %s, afzender: %s).';
$lang['parametres_test_email_bad_address'] = 'Ongeldig adres van de ontvanger: "%s". Er is geen e-mail verzonden.';
$lang['parametres_test_email_debug'] = 'Technische details';

// Lot 2 : secrets, lecture seule
$lang['parametres_required'] = 'Verplicht';
$lang['parametres_yes'] = 'Ja';
$lang['parametres_no'] = 'Nee';
$lang['parametres_secret_defined'] = 'Waarde ingesteld';
$lang['parametres_secret_undefined'] = 'Geen waarde';
$lang['parametres_secret_placeholder'] = 'Leeg laten om de huidige waarde te behouden';
$lang['parametres_secret_clear'] = 'Opgeslagen waarde wissen';

// Lot 2 : historique
$lang['parametres_history'] = 'Geschiedenis';
$lang['parametres_history_title'] = 'Wijzigingsgeschiedenis';
$lang['parametres_history_help'] = 'Meest recente wijzigingen eerst. Terugkeren naar de vorige waarde wordt gecontroleerd als een invoer en zelf vastgelegd. Een geheim kan niet worden hersteld.';
$lang['parametres_history_empty'] = 'Voor dit tabblad is nog geen wijziging vastgelegd.';
$lang['parametres_history_forbidden'] = 'De geschiedenis van het tabblad "%s" is voorbehouden aan wie het kan wijzigen.';
$lang['parametres_history_unavailable'] = 'De geschiedenis is nog niet beschikbaar: de database moet worden bijgewerkt (migratie).';
$lang['parametres_history_secret'] = 'geheim (waarde niet bewaard)';
$lang['parametres_history_default'] = 'niet ingesteld';
$lang['parametres_history_action_modification'] = 'Wijziging';
$lang['parametres_history_action_effacement'] = 'Wissen';
$lang['parametres_history_action_retour'] = 'Terugzetten';
$lang['parametres_restore'] = 'Terugzetten';
$lang['parametres_restore_help'] = 'Terugkeren naar de waarde van voor deze wijziging';
$lang['parametres_restored'] = '"%s" is teruggezet naar de vorige waarde.';
$lang['parametres_restore_unchanged'] = '"%s" heeft deze waarde al: er is niets gewijzigd.';
$lang['parametres_restore_failed'] = '"%s" is niet gewijzigd: %s';
$lang['parametres_back_to_tab'] = 'Terug naar het tabblad';
$lang['gvv_vue_parametres_history_short_field_date'] = 'Datum';
$lang['gvv_vue_parametres_history_short_field_auteur'] = 'Door';
$lang['gvv_vue_parametres_history_short_field_parametre'] = 'Instelling';
$lang['gvv_vue_parametres_history_short_field_action'] = 'Actie';
$lang['gvv_vue_parametres_history_short_field_ancienne'] = 'Vorige waarde';
$lang['gvv_vue_parametres_history_short_field_nouvelle'] = 'Nieuwe waarde';
$lang['gvv_vue_parametres_history_short_field_retour'] = '';

// Lot 2 : erreurs
$lang['parametres_err_option'] = 'waarde niet beschikbaar';
$lang['parametres_err_range'] = 'getal buiten de toegestane grenzen';
$lang['parametres_err_history_unknown'] = 'Deze wijziging bestaat niet in de geschiedenis.';
$lang['parametres_err_restore_secret'] = 'een geheim kan niet vanuit de geschiedenis worden hersteld';
