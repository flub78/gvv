<?php
/**
 * Gestion des alarmes (abonnements, notifications)
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 */

$lang['alarm_label_document_target'] = "%s — %s";
$lang['alarm_label_missing'] = "Missing mandatory documents";
$lang['alarm_item_missing'] = "%s missing";
$lang['alarm_error_unknown'] = "Unknown alarm.";
$lang['alarm_error_no_right'] = "The alarm \"%s\" (column %s) is not available to %s.";
$lang['alarm_error_save'] = "Error while saving the subscription.";
$lang['alarm_error_duplicate'] = "This subscription already exists.";
$lang['alarm_error_mandatory'] = "The subscription to \"%s\" (column %s) is mandatory and cannot be removed.";

$lang['alarm_notification_title'] = "GVV: %d alarm(s) concern you";
$lang['alarm_notification_hello'] = "Hello %s,";
$lang['alarm_notification_digest_intro'] = "Here are your active or upcoming alarms: your own documents and those you follow.";
$lang['alarm_notification_trigger_intro'] = "New alarms concern you: your own documents or those you follow.";
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
$lang['alarm_config_mandatory'] = "Mandatory";
$lang['alarm_config_by_category'] = "Subscribed by category";
$lang['alarm_config_by_admin'] = "Created by %s";
$lang['alarm_config_delivery'] = "Delivery";
$lang['alarm_config_channels'] = "Channels (without a channel, your alarms are only shown on this page):";
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
$lang['alarm_config_saved'] = "Alarm settings saved.";
$lang['alarm_my_alarms_none'] = "No active or upcoming alarm on your documents.";
$lang['alarm_my_alarms_none_followed'] = "No active or upcoming alarm, neither on your documents nor among those you follow.";
$lang['alarm_filter_all'] = "All";
$lang['alarm_filter_apply'] = "Filter";
$lang['alarm_col_section'] = "Section";
$lang['alarm_admin_title'] = "Alarm administration";
$lang['alarm_admin_member_config'] = "Configure a member's alarms";
$lang['alarm_admin_uncovered'] = "Active or upcoming alarms nobody receives";
$lang['alarm_admin_uncovered_none'] = "Every alarm is received by at least one member.";
$lang['alarm_admin_categories'] = "Member category subscriptions";
$lang['alarm_admin_categories_help'] = "For every member holding the role, the subscription ticks the box of the chosen section (all their sections for the role and the Club column if no section is chosen); it no longer applies when they lose the role. A mandatory box cannot be unticked by the member.";
$lang['alarm_admin_role'] = "Role";
$lang['alarm_admin_add'] = "Add";
$lang['alarm_admin_categories_none'] = "No category subscription.";
$lang['alarm_admin_delete'] = "Delete";
$lang['alarm_admin_catalogue'] = "Alarm catalogue";
$lang['alarm_admin_active'] = "Active";
$lang['alarm_admin_lead_days'] = "Lead time (days)";
$lang['alarm_admin_catalogue_saved'] = "Alarm catalogue saved.";
$lang['alarm_admin_category_added'] = "Category subscription added.";
$lang['alarm_admin_category_deleted'] = "Category subscription deleted.";
$lang['alarm_menu_my_alarms'] = "My alarms";
$lang['alarm_menu_admin'] = "Alarm administration";
$lang['alarm_card_my_alarms_desc'] = "My alarms and their settings";
$lang['alarm_column_club'] = "Club";
$lang['alarm_config_own_help'] = "Your own alarms (your expiring documents, your missing mandatory documents) are always followed and cannot be disabled.";
$lang['alarm_config_no_supervision'] = "You have no supervision right: no other alarm can be followed.";
$lang['alarm_config_matrix_help'] = "For each alarm, tick the sections you want to follow: only active pilots, aircraft and documents of these sections will be included. The Club column covers documents not attached to any section.";
$lang['alarm_config_origin_legend'] = "Box ticked by an administrator or by category (hover the icon)";
$lang['alarm_list_title'] = "Active and upcoming alarms";
$lang['alarm_badge_own'] = "Me";
$lang['alarm_card_active_count'] = "Active alarms";
$lang['alarm_config_toggle_column'] = "Tick or untick the whole %s column";
$lang['alarm_config_toggle_row'] = "Tick or untick the whole \"%s\" row";
$lang['alarm_config_toggle_help'] = "The box at the head of each row or column ticks or unticks the whole row or column, the corner one (next to \"Alarm\") the whole matrix; mandatory boxes do not change.";
$lang['alarm_config_toggle_all'] = "Tick or untick all boxes";
$lang['alarm_label_rule_vols_sans_cotisation'] = "Flights without membership fee";
$lang['alarm_label_rule_vols_sans_licence'] = "Flights without licence";
$lang['alarm_item_vols_sans_cotisation'] = "Flights without membership fee %d";
$lang['alarm_item_vols_sans_licence'] = "%s flights without licence %d";
$lang['alarm_activity_planeur'] = "Glider";
$lang['alarm_activity_avion'] = "Aeroplane";
$lang['alarm_activity_ulm'] = "Microlight";
$lang['alarm_item_vols_detail'] = "%s — %d flight(s), last on %s";
