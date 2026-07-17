<?php
/*
 * GVV Nederlands — Reserveringsherinneringen
 */

$lang['mes_reservations_title']          = "Mijn toekomstige reserveringen";
$lang['mes_reservations_no_resa']        = "U heeft geen aankomende reserveringen.";
$lang['mes_reservations_col_date']       = "Datum / Tijd";
$lang['mes_reservations_col_aircraft']   = "Vliegtuig";
$lang['mes_reservations_col_role']       = "Rol";
$lang['mes_reservations_col_status']     = "Type";
$lang['mes_reservations_col_actions']    = "Acties";
$lang['mes_reservations_role_pilot']     = "Piloot";
$lang['mes_reservations_role_instructor']= "Instructeur";
$lang['mes_reservations_btn_add']        = "Reservering toevoegen";
$lang['mes_reservations_btn_edit']       = "Bewerken";
$lang['mes_reservations_btn_delete']     = "Verwijderen";
$lang['mes_reservations_confirm_delete'] = "Deze reservering verwijderen?";
$lang['mes_reservations_deleted_ok']     = "Reservering verwijderd.";
$lang['mes_reservations_deleted_error']  = "Fout bij het verwijderen.";
$lang['mes_reservations_not_found']      = "Reservering niet gevonden of niet gemachtigd.";

$lang['mes_reservations_prefs_title']    = "Mijn herinneringsvoorkeuren";
$lang['mes_reservations_prefs_channel']  = "Meldingskanaal";
$lang['mes_reservations_prefs_period']   = "Herinneringstijd (uren voor vertrek)";
$lang['mes_reservations_prefs_save']     = "Voorkeuren opslaan";
$lang['mes_reservations_prefs_ok']       = "Voorkeuren opgeslagen.";
$lang['mes_reservations_prefs_error']    = "Fout bij opslaan voorkeuren.";
$lang['mes_reservations_channel_email']  = "Alleen e-mail";
$lang['mes_reservations_channel_sms']    = "Alleen SMS";
$lang['mes_reservations_channel_both']   = "E-mail + SMS";
$lang['mes_reservations_channel_none']   = "Geen meldingen";

$lang['reminder_type_scheduled']   = "Reserveringsherinnering";
$lang['reminder_intro_scheduled']  = "U heeft een aankomende reservering.";
$lang['reminder_event_create']     = "Nieuwe reservering";
$lang['reminder_event_update']     = "Reservering gewijzigd";
$lang['reminder_event_cancel']     = "Reservering geannuleerd";
$lang['reminder_intro_event']      = "Een reservering betreft u.";
$lang['reminder_role_pilot']       = "Piloot";
$lang['reminder_role_instructor']  = "Instructeur";

// Labels van de samenvattingstabel (e-mail)
$lang['label_date_heure']      = "Datum / Tijd";
$lang['label_aeronef']         = "Vliegtuig";
$lang['label_pilote']          = "Piloot";
$lang['label_instructeur']     = "Instructeur";
$lang['label_statut']          = "Status";
$lang['label_votre_role']      = "Uw rol";
$lang['label_type_message']    = "Berichttype";
$lang['label_declenchement']   = "Trigger";
$lang['label_heure']           = "Tijd";

// Onderwerpen van de herinneringsmails
$lang['subject_rappel_reservation'] = "Reserveringsherinnering";
$lang['subject_rappels_journee']    = "Reserveringsherinneringen voor";

// Titel dagoverzicht en voettekst van de e-mails
$lang['daily_summary_heading'] = "Reserveringsherinneringen voor";
$lang['footer_auto_message']   = "Automatisch bericht verzonden door %s – GVV. Beantwoord deze e-mail niet.";

// SMS
$lang['sms_rappel_vol']        = "Vluchtherinnering";
$lang['sms_event_create']      = "Nieuw";
$lang['sms_event_update']      = "Gewijzigd";
$lang['sms_event_cancel']      = "Geannuleerd";
$lang['sms_reservation_word']  = "reservering";
$lang['sms_role_label']        = "rol";
$lang['sms_instr_label']       = "instr";
$lang['sms_multi_reservations']= "U heeft %1\$d reserveringen voor %2\$s. Details op %3\$s";
$lang['connector_le']          = "op";

// Dagen van de week
$lang['jour_1'] = "Maandag";
$lang['jour_2'] = "Dinsdag";
$lang['jour_3'] = "Woensdag";
$lang['jour_4'] = "Donderdag";
$lang['jour_5'] = "Vrijdag";
$lang['jour_6'] = "Zaterdag";
$lang['jour_7'] = "Zondag";

// Testpagina herinneringen
$lang['test_rappel_titre']             = "Test reserveringsherinneringen";
$lang['test_rappel_form_title']        = "Testparameters";
$lang['test_rappel_label_reservation'] = "Reservering";
$lang['test_rappel_select_resa']       = "Selecteer een reservering";
$lang['test_rappel_no_resa']           = "Geen reserveringen beschikbaar.";
$lang['test_rappel_label_canal']       = "Kanaal";
$lang['test_rappel_label_type']        = "Meldingstype";
$lang['test_rappel_label_destinataire']= "Ontvanger";
$lang['test_rappel_btn_envoyer']       = "Testherinnering verzenden";
$lang['test_rappel_redirection_info']  = "Berichten worden omgeleid naar:";
$lang['test_rappel_no_redirection']    = "Geen testomleiding geconfigureerd — berichten worden naar echte ontvangers gestuurd.";
