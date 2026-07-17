<?php
/*
 * GVV English — Reservation reminders
 */

$lang['mes_reservations_title']          = "My upcoming reservations";
$lang['mes_reservations_no_resa']        = "You have no upcoming reservations.";
$lang['mes_reservations_col_date']       = "Date / Time";
$lang['mes_reservations_col_aircraft']   = "Aircraft";
$lang['mes_reservations_col_role']       = "Role";
$lang['mes_reservations_col_status']     = "Type";
$lang['mes_reservations_col_actions']    = "Actions";
$lang['mes_reservations_role_pilot']     = "Pilot";
$lang['mes_reservations_role_instructor']= "Instructor";
$lang['mes_reservations_btn_add']        = "Add a reservation";
$lang['mes_reservations_btn_edit']       = "Edit";
$lang['mes_reservations_btn_delete']     = "Delete";
$lang['mes_reservations_confirm_delete'] = "Delete this reservation?";
$lang['mes_reservations_deleted_ok']     = "Reservation deleted.";
$lang['mes_reservations_deleted_error']  = "Error while deleting.";
$lang['mes_reservations_not_found']      = "Reservation not found or not authorized.";

$lang['mes_reservations_prefs_title']    = "My reminder preferences";
$lang['mes_reservations_prefs_channel']  = "Notification channel";
$lang['mes_reservations_prefs_period']   = "Reminder lead time (hours before departure)";
$lang['mes_reservations_prefs_save']     = "Save my preferences";
$lang['mes_reservations_prefs_ok']       = "Preferences saved.";
$lang['mes_reservations_prefs_error']    = "Error saving preferences.";
$lang['mes_reservations_channel_email']  = "Email only";
$lang['mes_reservations_channel_sms']    = "SMS only";
$lang['mes_reservations_channel_both']   = "Email + SMS";
$lang['mes_reservations_channel_none']   = "No notifications";

$lang['reminder_type_scheduled']   = "Reservation reminder";
$lang['reminder_intro_scheduled']  = "You have an upcoming reservation.";
$lang['reminder_event_create']     = "New reservation";
$lang['reminder_event_update']     = "Reservation updated";
$lang['reminder_event_cancel']     = "Reservation cancelled";
$lang['reminder_intro_event']      = "A reservation concerns you.";
$lang['reminder_role_pilot']       = "Pilot";
$lang['reminder_role_instructor']  = "Instructor";

// Summary table labels (email)
$lang['label_date_heure']      = "Date / Time";
$lang['label_aeronef']         = "Aircraft";
$lang['label_pilote']          = "Pilot";
$lang['label_instructeur']     = "Instructor";
$lang['label_statut']          = "Status";
$lang['label_votre_role']      = "Your role";
$lang['label_type_message']    = "Message type";
$lang['label_declenchement']   = "Trigger";
$lang['label_heure']           = "Time";

// Reminder email subjects
$lang['subject_rappel_reservation'] = "Reservation reminder";
$lang['subject_rappels_journee']    = "Reservation reminders for";

// Daily summary heading and email footer
$lang['daily_summary_heading'] = "Reservation reminders for";
$lang['footer_auto_message']   = "Automatic message sent by %s – GVV. Do not reply to this email.";

// SMS
$lang['sms_rappel_vol']        = "Flight reminder";
$lang['sms_event_create']      = "New";
$lang['sms_event_update']      = "Updated";
$lang['sms_event_cancel']      = "Cancelled";
$lang['sms_reservation_word']  = "reservation";
$lang['sms_role_label']        = "role";
$lang['sms_instr_label']       = "instr";
$lang['sms_multi_reservations']= "You have %1\$d reservations for %2\$s. Details at %3\$s";
$lang['connector_le']          = "on";

// Days of the week
$lang['jour_1'] = "Monday";
$lang['jour_2'] = "Tuesday";
$lang['jour_3'] = "Wednesday";
$lang['jour_4'] = "Thursday";
$lang['jour_5'] = "Friday";
$lang['jour_6'] = "Saturday";
$lang['jour_7'] = "Sunday";

// Reminder test page
$lang['test_rappel_titre']             = "Reservation reminder test";
$lang['test_rappel_form_title']        = "Test parameters";
$lang['test_rappel_label_reservation'] = "Reservation";
$lang['test_rappel_select_resa']       = "Select a reservation";
$lang['test_rappel_no_resa']           = "No reservations available.";
$lang['test_rappel_label_canal']       = "Channel";
$lang['test_rappel_label_type']        = "Notification type";
$lang['test_rappel_label_destinataire']= "Recipient";
$lang['test_rappel_btn_envoyer']       = "Send test reminder";
$lang['test_rappel_redirection_info']  = "Messages are redirected to:";
$lang['test_rappel_no_redirection']    = "No test redirection configured — messages will be sent to real recipients.";
