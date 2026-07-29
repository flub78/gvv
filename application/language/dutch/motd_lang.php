<?php
/**
 * Language file for messages du jour / MOTD (Dutch)
 */

$lang['motd_title'] = 'Berichten van de dag';
$lang['motd_add'] = 'Nieuw bericht';
$lang['motd_edit'] = 'Bericht bewerken';
$lang['motd_view'] = 'Bericht bekijken';
$lang['motd_delete'] = 'Verwijderen';

$lang['motd_field_title'] = 'Titel';
$lang['motd_field_content'] = 'Inhoud';
$lang['motd_field_level'] = 'Niveau';
$lang['motd_field_start_date'] = 'Begin';
$lang['motd_field_end_date'] = 'Einde';
$lang['motd_field_target_type'] = 'Ontvangers';
$lang['motd_field_target_list_id'] = 'Mailinglijst';
$lang['motd_field_target_user_login'] = 'Gebruiker';
$lang['motd_field_origin'] = 'Oorsprong';

// field_long_name() looks these up (gvv_{table}_field_{field}) before
// falling back to the DB column COMMENT, which would otherwise leak our
// internal migration comments ("membres.mlogin", etc.) into form labels.
$lang['gvv_motd_messages_field_title'] = 'Titel';
$lang['gvv_motd_messages_field_content'] = 'Inhoud';
$lang['gvv_motd_messages_field_level'] = 'Niveau';
$lang['gvv_motd_messages_field_start_date'] = 'Begin';
$lang['gvv_motd_messages_field_end_date'] = 'Einde';
$lang['gvv_motd_messages_field_target_type'] = 'Ontvangers';
$lang['gvv_motd_messages_field_target_list_id'] = 'Mailinglijst';
$lang['gvv_motd_messages_field_target_user_login'] = 'Gebruiker';
$lang['gvv_motd_messages_field_origin'] = 'Oorsprong';

$lang['motd_level_urgent'] = 'Dringend';
$lang['motd_level_important'] = 'Belangrijk';
$lang['motd_level_info'] = 'Info';
$lang['motd_level_alerte'] = 'Alarm';

$lang['motd_target_all'] = 'Alle gebruikers';
$lang['motd_target_list'] = 'Mailinglijst';
$lang['motd_target_user'] = 'Eén gebruiker';

$lang['motd_origin_admin'] = 'Beheerder';
$lang['motd_origin_system'] = 'Gegenereerd door GVV';

$lang['motd_image_insert'] = 'Afbeelding invoegen';
$lang['motd_image_upload_error'] = 'Uploaden van de afbeelding mislukt';

$lang['motd_error_invalid_target'] = 'De geselecteerde ontvanger is ongeldig (lijst of gebruiker niet gevonden).';
$lang['motd_error_dates_incoherent'] = 'De einddatum moet gelijk zijn aan of na de begindatum liggen.';

$lang['motd_confirm_delete'] = 'Weet u zeker dat u dit bericht wilt verwijderen?';
$lang['motd_no_messages'] = 'Geen bericht gedefinieerd';

$lang['motd_no_title'] = '(geen titel)';
$lang['motd_sort_priority'] = 'Sorteren op prioriteit';
$lang['motd_sort_date'] = 'Sorteren op datum';
$lang['motd_replies_title'] = 'Reacties';

$lang['motd_action_hide'] = 'Verbergen';
$lang['motd_action_hide_all'] = 'Alle berichten verbergen';
$lang['motd_action_acknowledge'] = 'Ik heb hiervan kennis genomen';
$lang['motd_acknowledged_badge'] = 'Kennis van genomen';
$lang['motd_reply_placeholder'] = 'Uw reactie...';
$lang['motd_reply_submit'] = 'Beantwoorden';
$lang['motd_reply_to_reply'] = 'Beantwoorden';
$lang['motd_reply_cancel'] = 'Annuleren';
$lang['motd_reply_replying_to'] = 'Antwoord op';
$lang['motd_confirm_hide_all'] = 'Alle actieve berichten verbergen?';
$lang['motd_error_reply_empty'] = 'De reactie mag niet leeg zijn.';
$lang['motd_error_action_failed'] = 'Er is een fout opgetreden, probeer het opnieuw.';

$lang['motd_my_messages_title'] = 'Al mijn berichten';
$lang['motd_my_messages_link'] = 'Al mijn berichten';
$lang['motd_my_messages_empty'] = 'Er is momenteel geen bericht aan u gericht.';

/* End of file motd_lang.php */
/* Location: ./application/language/dutch/motd_lang.php */
