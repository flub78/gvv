<?php
/**
 * Gestion des alarmes (abonnements, notifications)
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 */

$lang['alarm_label_document_personal'] = "Mes documents : %s";
$lang['alarm_label_document_supervision'] = "%s (supervision)";
$lang['alarm_label_document_target'] = "%s — %s (supervision)";
$lang['alarm_label_missing_personal'] = "Mes documents obligatoires manquants";
$lang['alarm_label_missing_supervision'] = "Documents obligatoires manquants (supervision)";
$lang['alarm_item_missing'] = "%s manquant";
$lang['alarm_error_unknown'] = "Alarme inconnue.";
$lang['alarm_error_no_right'] = "L'alarme « %s » n'est pas accessible à %s.";
$lang['alarm_error_save'] = "Erreur lors de l'enregistrement de l'abonnement.";
$lang['alarm_error_duplicate'] = "Cet abonnement existe déjà.";
$lang['alarm_error_mandatory'] = "L'abonnement à « %s » est obligatoire et ne peut pas être retiré.";

$lang['alarm_notification_title'] = "GVV : %d alarme(s) vous concernent";
$lang['alarm_notification_hello'] = "Bonjour %s,";
$lang['alarm_notification_digest_intro'] = "Voici les alarmes actives ou à venir auxquelles vous êtes abonné.";
$lang['alarm_notification_trigger_intro'] = "De nouvelles alarmes auxquelles vous êtes abonné se sont déclenchées.";
$lang['alarm_notification_open'] = "Ouvrir";
$lang['alarm_notification_all'] = "Voir toutes mes alarmes";
$lang['alarm_notification_settings'] = "Configurer mes alarmes";
$lang['alarm_col_alarm'] = "Alarme";
$lang['alarm_col_concerns'] = "Concerne";
$lang['alarm_col_state'] = "État";
$lang['alarm_col_due'] = "Échéance";
$lang['alarm_state_upcoming'] = "À venir";
$lang['alarm_state_active'] = "Active";
$lang['alarm_sms_text'] = "GVV : %d alarme(s) vous concernent :";

$lang['alarm_config_title'] = "Configuration des alarmes";
$lang['alarm_my_alarms_title'] = "Mes alarmes";
$lang['alarm_config_member'] = "Membre";
$lang['alarm_config_alarms'] = "Alarmes";
$lang['alarm_config_alarms_help'] = "Cochez les alarmes que vous souhaitez recevoir. Sans aucune alarme cochée, vous ne recevez rien.";
$lang['alarm_config_none_available'] = "Aucune alarme n'est disponible pour ce membre.";
$lang['alarm_config_group_personal'] = "Personnelles";
$lang['alarm_config_group_supervision'] = "Supervision";
$lang['alarm_config_mandatory'] = "Obligatoire";
$lang['alarm_config_by_category'] = "Abonné par catégorie";
$lang['alarm_config_by_admin'] = "Créé par %s";
$lang['alarm_config_delivery'] = "Réception";
$lang['alarm_config_channels'] = "Canaux (au moins un si vous avez des alarmes) :";
$lang['alarm_config_channel_email'] = "Email";
$lang['alarm_config_channel_motd'] = "Message du jour dans GVV";
$lang['alarm_config_frequency'] = "Fréquence";
$lang['alarm_frequency_immediate'] = "Immédiate (à chaque déclenchement)";
$lang['alarm_frequency_weekly'] = "Hebdomadaire (le lundi)";
$lang['alarm_frequency_monthly'] = "Mensuelle (le premier lundi du mois)";
$lang['alarm_config_sms'] = "Rappel par SMS";
$lang['alarm_config_sms_help'] = "Un SMS court avec le lien vers vos alarmes, le lundi (le premier lundi du mois en fréquence mensuelle), s'il y a des alarmes. Cocher cette case vaut consentement.";
$lang['alarm_config_sms_no_mobile'] = "Le rappel par SMS demande un numéro de mobile dans la fiche membre.";
$lang['alarm_config_save'] = "Enregistrer";
$lang['alarm_config_unknown_member'] = "Membre inconnu.";
$lang['alarm_config_channel_required'] = "Choisissez au moins un canal (email ou message du jour) pour recevoir vos alarmes.";
$lang['alarm_config_saved'] = "Configuration des alarmes enregistrée.";
$lang['alarm_my_alarms_no_subscription'] = "Vous n'êtes abonné à aucune alarme : choisissez-en sur cette page.";
$lang['alarm_my_alarms_none'] = "Aucune alarme active ou à venir.";
$lang['alarm_filter_all'] = "Toutes";
$lang['alarm_filter_apply'] = "Filtrer";
$lang['alarm_col_section'] = "Section";
$lang['alarm_admin_title'] = "Administration des alarmes";
$lang['alarm_admin_member_config'] = "Configurer les alarmes d'un membre";
$lang['alarm_admin_uncovered'] = "Alarmes actives ou à venir sans abonné";
$lang['alarm_admin_uncovered_none'] = "Toutes les alarmes ont au moins un abonné.";
$lang['alarm_admin_categories'] = "Abonnements de catégories de membres";
$lang['alarm_admin_categories_help'] = "Les membres qui ont le rôle (dans la section choisie) sont abonnés ; ils ne le sont plus s'ils perdent le rôle. Un abonnement obligatoire ne peut pas être décoché par le membre.";
$lang['alarm_admin_role'] = "Rôle";
$lang['alarm_admin_add'] = "Ajouter";
$lang['alarm_admin_categories_none'] = "Aucun abonnement de catégorie.";
$lang['alarm_admin_delete'] = "Supprimer";
$lang['alarm_admin_catalogue'] = "Catalogue des alarmes";
$lang['alarm_admin_active'] = "Active";
$lang['alarm_admin_kind'] = "Type";
$lang['alarm_admin_lead_days'] = "Délai d'anticipation (jours)";
$lang['alarm_admin_catalogue_saved'] = "Catalogue des alarmes enregistré.";
$lang['alarm_admin_category_added'] = "Abonnement de catégorie ajouté.";
$lang['alarm_admin_category_deleted'] = "Abonnement de catégorie supprimé.";
$lang['alarm_menu_config'] = "Configuration des alarmes";
$lang['alarm_menu_my_alarms'] = "Mes alarmes";
$lang['alarm_menu_admin'] = "Administration des alarmes";
$lang['alarm_card_config_desc'] = "Choisir mes alarmes";
$lang['alarm_card_my_alarms_desc'] = "Alarmes actives";
