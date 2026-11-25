<?php
if (!defined('BASEPATH')) exit('No direct script access allowed');
/**
 * Synchronisation des réservations avec Google Agenda
 */

$lang['agenda_google_titre'] = 'Google Calendar';
$lang['agenda_google_etat_active'] = 'Synchronisation active';
$lang['agenda_google_etat_suspendue'] = 'Synchronisation suspended';
$lang['agenda_google_etat_inactive'] = 'Synchronisation inactive';
$lang['agenda_google_explication'] = 'Link your Google Calendar to find your bookings there automatically, as pilot and as instructor. They are updated when changed or cancelled, whoever makes the change.';
$lang['agenda_google_compte'] = 'Google account';
$lang['agenda_google_derniere_maj'] = 'Last successful update';
$lang['agenda_google_jamais'] = 'none yet';
$lang['agenda_google_suspendue_texte'] = 'Your calendar is no longer updated: GVV is no longer allowed to write to it. Link your Google Calendar again to resume the synchronisation.';
$lang['agenda_google_cause'] = 'Cause:';
$lang['agenda_google_btn_activer'] = 'Link my Google Calendar';
$lang['agenda_google_btn_relier_nouveau'] = 'Link my calendar again';
$lang['agenda_google_btn_arreter'] = 'Stop the synchronisation';
$lang['agenda_google_arret_titre'] = 'Stop the synchronisation?';
$lang['agenda_google_arret_avertissement'] = 'The bookings already in your Google Calendar will stay there but will no longer be updated. You can delete them yourself in Google Calendar. Your bookings in GVV do not change.';
$lang['agenda_google_annuler'] = 'Cancel';
$lang['agenda_google_btn_confirmer_arret'] = 'Stop';
$lang['agenda_google_active_ok'] = 'Synchronisation active with Google account %s.';
$lang['agenda_google_active_nombre'] = '%d upcoming booking(s) will be added to your calendar within the next few minutes.';
$lang['agenda_google_arret_ok'] = 'Synchronisation stopped. The events already present stay in your Google Calendar.';
$lang['agenda_google_arret_revocation'] = 'Google could not confirm that access was removed: you can remove it yourself in your Google account security settings (third-party apps).';
$lang['agenda_google_err_etat'] = 'The link with Google failed: the request expired or did not come from this page. Start again from "My bookings".';
$lang['agenda_google_err_refus'] = 'Synchronisation not enabled: access to your calendar was not granted on the Google screen. Start again and tick calendar access.';
$lang['agenda_google_err_passagere'] = 'Synchronisation not enabled: Google did not respond. Try again in a few minutes.';
$lang['agenda_google_err_activation'] = 'Synchronisation not enabled: Google refused the link. Start again; if the problem persists, tell the club administrator.';
$lang['agenda_google_err_pas_active'] = 'The synchronisation was not active: nothing was changed.';
$lang['agenda_google_indisponible'] = 'Synchronisation with Google Calendar is not available for your account.';
$lang['agenda_google_evt_titre'] = 'Booking %s';
$lang['agenda_google_evt_pilote'] = 'Pilot: %s';
$lang['agenda_google_evt_instructeur'] = 'Instructor: %s';
$lang['agenda_google_evt_commentaire'] = 'Comment: %s';
$lang['agenda_google_evt_lien'] = 'Booking in GVV: %s';
$lang['agenda_google_mail_suspension_sujet'] = 'Your Google Calendar is no longer updated';
$lang['agenda_google_mail_suspension_corps'] = '<p>Hello %1$s,</p><p>GVV is no longer allowed to write to your Google Calendar: your bookings are no longer updated there. This happens when GVV\'s access was removed in your Google account, or when the account or calendar is no longer available.</p><p>To resume the synchronisation, open <a href="%2$s">My bookings</a> and link your Google Calendar again.</p>';
$lang['agenda_google_retour_configuration'] = 'Configuration';
$lang['agenda_google_liste_titre'] = 'Suspended Google Calendar synchronisations';
$lang['agenda_google_liste_aide'] = 'Members whose Google Calendar is no longer updated because the authorisation given to GVV is no longer valid. Each member concerned was notified by email and must link their calendar again from "My bookings".';
$lang['agenda_google_liste_vide'] = 'No suspended synchronisation.';
$lang['agenda_google_liste_module_inactif'] = 'Synchronisation with Google Calendar is not enabled for the club: no calendar is updated.';
$lang['gvv_vue_agenda_google_suspendues_short_field_membre'] = 'Member';
$lang['gvv_vue_agenda_google_suspendues_short_field_etat'] = 'Status';
$lang['gvv_vue_agenda_google_suspendues_short_field_cause_suspension'] = 'Cause';
$lang['gvv_vue_agenda_google_suspendues_short_field_updated_at'] = 'Suspended on';
$lang['gvv_vue_agenda_google_suspendues_short_field_derniere_reussite_at'] = 'Last successful update';
