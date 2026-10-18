<?php
/**
 * Gestion des alarmes (abonnements, notifications)
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 */

$lang['alarm_label_document_personal'] = "Mijn documenten: %s";
$lang['alarm_label_document_supervision'] = "%s (toezicht)";
$lang['alarm_label_document_target'] = "%s — %s (toezicht)";
$lang['alarm_label_missing_personal'] = "Mijn ontbrekende verplichte documenten";
$lang['alarm_label_missing_supervision'] = "Ontbrekende verplichte documenten (toezicht)";
$lang['alarm_item_missing'] = "%s ontbreekt";
$lang['alarm_error_unknown'] = "Onbekend alarm.";
$lang['alarm_error_no_right'] = "Het alarm \"%s\" is niet toegankelijk voor %s.";
$lang['alarm_error_save'] = "Fout bij het opslaan van het abonnement.";
$lang['alarm_error_duplicate'] = "Dit abonnement bestaat al.";
$lang['alarm_error_mandatory'] = "Het abonnement op \"%s\" is verplicht en kan niet worden verwijderd.";

$lang['alarm_notification_title'] = "GVV: %d alarm(en) voor u";
$lang['alarm_notification_hello'] = "Hallo %s,";
$lang['alarm_notification_digest_intro'] = "Hier zijn de actieve of komende alarmen waarop u geabonneerd bent.";
$lang['alarm_notification_trigger_intro'] = "Er zijn nieuwe alarmen afgegaan waarop u geabonneerd bent.";
$lang['alarm_notification_open'] = "Openen";
$lang['alarm_notification_all'] = "Al mijn alarmen bekijken";
$lang['alarm_notification_settings'] = "Mijn alarmen instellen";
$lang['alarm_col_alarm'] = "Alarm";
$lang['alarm_col_concerns'] = "Betreft";
$lang['alarm_col_state'] = "Status";
$lang['alarm_col_due'] = "Vervaldatum";
$lang['alarm_state_upcoming'] = "Komend";
$lang['alarm_state_active'] = "Actief";
$lang['alarm_sms_text'] = "GVV: %d alarm(en) voor u:";

$lang['alarm_config_title'] = "Alarminstellingen";
$lang['alarm_my_alarms_title'] = "Mijn alarmen";
$lang['alarm_config_member'] = "Lid";
$lang['alarm_config_alarms'] = "Alarmen";
$lang['alarm_config_alarms_help'] = "Vink de alarmen aan die u wilt ontvangen. Zonder aangevinkt alarm ontvangt u niets.";
$lang['alarm_config_none_available'] = "Er is geen alarm beschikbaar voor dit lid.";
$lang['alarm_config_group_personal'] = "Persoonlijk";
$lang['alarm_config_group_supervision'] = "Toezicht";
$lang['alarm_config_mandatory'] = "Verplicht";
$lang['alarm_config_by_category'] = "Geabonneerd via categorie";
$lang['alarm_config_by_admin'] = "Aangemaakt door %s";
$lang['alarm_config_delivery'] = "Ontvangst";
$lang['alarm_config_channels'] = "Kanalen (minstens één als u alarmen hebt):";
$lang['alarm_config_channel_email'] = "E-mail";
$lang['alarm_config_channel_motd'] = "Bericht van de dag in GVV";
$lang['alarm_config_frequency'] = "Frequentie";
$lang['alarm_frequency_immediate'] = "Onmiddellijk (bij elke melding)";
$lang['alarm_frequency_weekly'] = "Wekelijks (op maandag)";
$lang['alarm_frequency_monthly'] = "Maandelijks (eerste maandag van de maand)";
$lang['alarm_config_sms'] = "Herinnering per sms";
$lang['alarm_config_sms_help'] = "Een korte sms met de link naar uw alarmen, op maandag (eerste maandag van de maand bij maandelijkse frequentie), als er alarmen zijn. Door dit aan te vinken geeft u toestemming.";
$lang['alarm_config_sms_no_mobile'] = "De herinnering per sms vereist een gsm-nummer in de ledenfiche.";
$lang['alarm_config_save'] = "Opslaan";
$lang['alarm_config_unknown_member'] = "Onbekend lid.";
$lang['alarm_config_channel_required'] = "Kies minstens één kanaal (e-mail of bericht van de dag) om uw alarmen te ontvangen.";
$lang['alarm_config_saved'] = "Alarminstellingen opgeslagen.";
$lang['alarm_my_alarms_no_subscription'] = "U bent op geen enkel alarm geabonneerd: kies er op deze pagina.";
$lang['alarm_my_alarms_none'] = "Geen actief of komend alarm.";
$lang['alarm_filter_all'] = "Alle";
$lang['alarm_filter_apply'] = "Filteren";
$lang['alarm_col_section'] = "Sectie";
$lang['alarm_admin_title'] = "Beheer van alarmen";
$lang['alarm_admin_member_config'] = "Alarmen van een lid instellen";
$lang['alarm_admin_uncovered'] = "Actieve of komende alarmen zonder abonnee";
$lang['alarm_admin_uncovered_none'] = "Elk alarm heeft minstens één abonnee.";
$lang['alarm_admin_categories'] = "Abonnementen van ledencategorieën";
$lang['alarm_admin_categories_help'] = "Leden met de rol (in de gekozen sectie) zijn geabonneerd; ze zijn het niet meer als ze de rol verliezen. Een verplicht abonnement kan het lid niet uitvinken.";
$lang['alarm_admin_role'] = "Rol";
$lang['alarm_admin_add'] = "Toevoegen";
$lang['alarm_admin_categories_none'] = "Geen categorieabonnement.";
$lang['alarm_admin_delete'] = "Verwijderen";
$lang['alarm_admin_catalogue'] = "Alarmcatalogus";
$lang['alarm_admin_active'] = "Actief";
$lang['alarm_admin_kind'] = "Type";
$lang['alarm_admin_lead_days'] = "Waarschuwingstermijn (dagen)";
$lang['alarm_admin_catalogue_saved'] = "Alarmcatalogus opgeslagen.";
$lang['alarm_admin_category_added'] = "Categorieabonnement toegevoegd.";
$lang['alarm_admin_category_deleted'] = "Categorieabonnement verwijderd.";
$lang['alarm_menu_config'] = "Alarminstellingen";
$lang['alarm_menu_my_alarms'] = "Mijn alarmen";
$lang['alarm_menu_admin'] = "Beheer van alarmen";
$lang['alarm_card_config_desc'] = "Mijn alarmen kiezen";
$lang['alarm_card_my_alarms_desc'] = "Actieve alarmen";
