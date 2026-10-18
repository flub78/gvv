<?php
/**
 * Gestion des alarmes (abonnements, notifications)
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 */

$lang['alarm_label_document_personal'] = "My documents: %s";
$lang['alarm_label_document_supervision'] = "%s (supervision)";
$lang['alarm_label_document_target'] = "%s — %s (supervision)";
$lang['alarm_label_missing_personal'] = "My missing mandatory documents";
$lang['alarm_label_missing_supervision'] = "Missing mandatory documents (supervision)";
$lang['alarm_item_missing'] = "%s missing";
$lang['alarm_error_unknown'] = "Unknown alarm.";
$lang['alarm_error_no_right'] = "The alarm \"%s\" is not available to %s.";
$lang['alarm_error_save'] = "Error while saving the subscription.";
$lang['alarm_error_duplicate'] = "This subscription already exists.";
$lang['alarm_error_mandatory'] = "The subscription to \"%s\" is mandatory and cannot be removed.";

$lang['alarm_notification_title'] = "GVV: %d alarm(s) concern you";
$lang['alarm_notification_hello'] = "Hello %s,";
$lang['alarm_notification_digest_intro'] = "Here are the active or upcoming alarms you are subscribed to.";
$lang['alarm_notification_trigger_intro'] = "New alarms you are subscribed to have been triggered.";
$lang['alarm_notification_open'] = "Open";
$lang['alarm_notification_all'] = "See all my alarms";
$lang['alarm_notification_settings'] = "Configure my alarms";
$lang['alarm_col_alarm'] = "Alarm";
$lang['alarm_col_concerns'] = "Concerns";
$lang['alarm_col_state'] = "State";
$lang['alarm_col_due'] = "Due date";
$lang['alarm_state_upcoming'] = "Upcoming";
$lang['alarm_state_active'] = "Active";
$lang['alarm_sms_text'] = "GVV: %d alarm(s) concern you:";

$lang['alarm_config_title'] = "Alarm settings";
$lang['alarm_my_alarms_title'] = "My alarms";
$lang['alarm_config_member'] = "Member";
$lang['alarm_config_alarms'] = "Alarms";
$lang['alarm_config_alarms_help'] = "Tick the alarms you want to receive. With no alarm ticked, you receive nothing.";
$lang['alarm_config_none_available'] = "No alarm is available for this member.";
$lang['alarm_config_group_personal'] = "Personal";
$lang['alarm_config_group_supervision'] = "Supervision";
$lang['alarm_config_mandatory'] = "Mandatory";
$lang['alarm_config_by_category'] = "Subscribed by category";
$lang['alarm_config_by_admin'] = "Created by %s";
$lang['alarm_config_delivery'] = "Delivery";
$lang['alarm_config_channels'] = "Channels (at least one if you have alarms):";
$lang['alarm_config_channel_email'] = "Email";
$lang['alarm_config_channel_motd'] = "Message of the day in GVV";
$lang['alarm_config_frequency'] = "Frequency";
$lang['alarm_frequency_immediate'] = "Immediate (on each trigger)";
$lang['alarm_frequency_weekly'] = "Weekly (on Monday)";
$lang['alarm_frequency_monthly'] = "Monthly (first Monday of the month)";
$lang['alarm_config_sms'] = "SMS reminder";
$lang['alarm_config_sms_help'] = "A short SMS with the link to your alarms, on Monday (first Monday of the month for the monthly frequency), when there are alarms. Ticking this box gives your consent.";
$lang['alarm_config_sms_no_mobile'] = "The SMS reminder requires a mobile number in the member record.";
$lang['alarm_config_save'] = "Save";
$lang['alarm_config_unknown_member'] = "Unknown member.";
$lang['alarm_config_channel_required'] = "Choose at least one channel (email or message of the day) to receive your alarms.";
$lang['alarm_config_saved'] = "Alarm settings saved.";
$lang['alarm_my_alarms_no_subscription'] = "You are not subscribed to any alarm: choose some on this page.";
$lang['alarm_my_alarms_none'] = "No active or upcoming alarm.";
$lang['alarm_filter_all'] = "All";
$lang['alarm_filter_apply'] = "Filter";
$lang['alarm_col_section'] = "Section";
$lang['alarm_admin_title'] = "Alarm administration";
$lang['alarm_admin_member_config'] = "Configure a member's alarms";
$lang['alarm_admin_uncovered'] = "Active or upcoming alarms without subscriber";
$lang['alarm_admin_uncovered_none'] = "Every alarm has at least one subscriber.";
$lang['alarm_admin_categories'] = "Member category subscriptions";
$lang['alarm_admin_categories_help'] = "Members holding the role (in the chosen section) are subscribed; they no longer are when they lose the role. A mandatory subscription cannot be unticked by the member.";
$lang['alarm_admin_role'] = "Role";
$lang['alarm_admin_add'] = "Add";
$lang['alarm_admin_categories_none'] = "No category subscription.";
$lang['alarm_admin_delete'] = "Delete";
$lang['alarm_admin_catalogue'] = "Alarm catalogue";
$lang['alarm_admin_active'] = "Active";
$lang['alarm_admin_kind'] = "Type";
$lang['alarm_admin_lead_days'] = "Lead time (days)";
$lang['alarm_admin_catalogue_saved'] = "Alarm catalogue saved.";
$lang['alarm_admin_category_added'] = "Category subscription added.";
$lang['alarm_admin_category_deleted'] = "Category subscription deleted.";
$lang['alarm_menu_config'] = "Alarm settings";
$lang['alarm_menu_my_alarms'] = "My alarms";
$lang['alarm_menu_admin'] = "Alarm administration";
$lang['alarm_card_config_desc'] = "Choose my alarms";
$lang['alarm_card_my_alarms_desc'] = "Active alarms";
