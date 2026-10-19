<?php
/**
 * Gestion des alarmes (abonnements, notifications)
 *
 * @see doc/prds/gestion_alarmes_generiques_prd.md
 */

$lang['alarm_label_document_target'] = "%s — %s";
$lang['alarm_label_missing'] = "Documents obligatoires manquants";
$lang['alarm_item_missing'] = "%s manquant";
$lang['alarm_error_unknown'] = "Alarme inconnue.";
$lang['alarm_error_no_right'] = "L'alarme « %s » (colonne %s) n'est pas accessible à %s.";
$lang['alarm_error_save'] = "Erreur lors de l'enregistrement de l'abonnement.";
$lang['alarm_error_duplicate'] = "Cet abonnement existe déjà.";
$lang['alarm_error_mandatory'] = "L'abonnement à « %s » (colonne %s) est obligatoire et ne peut pas être retiré.";

$lang['alarm_notification_title'] = "GVV : %d alarme(s) vous concernent";
$lang['alarm_notification_hello'] = "Bonjour %s,";
$lang['alarm_notification_digest_intro'] = "Voici vos alarmes actives ou à venir : vos propres documents et ceux que vous suivez.";
$lang['alarm_notification_trigger_intro'] = "De nouvelles alarmes vous concernent : vos propres documents ou ceux que vous suivez.";
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
$lang['alarm_config_mandatory'] = "Obligatoire";
$lang['alarm_config_by_category'] = "Abonné par catégorie";
$lang['alarm_config_by_admin'] = "Créé par %s";
$lang['alarm_config_delivery'] = "Réception";
$lang['alarm_config_channels'] = "Canaux (sans canal, vos alarmes restent seulement affichées sur cette page) :";
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
$lang['alarm_config_saved'] = "Configuration des alarmes enregistrée.";
$lang['alarm_my_alarms_none'] = "Aucune alarme active ou à venir sur vos documents.";
$lang['alarm_my_alarms_none_followed'] = "Aucune alarme active ou à venir, ni sur vos documents ni parmi celles que vous suivez.";
$lang['alarm_filter_all'] = "Toutes";
$lang['alarm_filter_apply'] = "Filtrer";
$lang['alarm_col_section'] = "Section";
$lang['alarm_admin_title'] = "Administration des alarmes";
$lang['alarm_admin_member_config'] = "Configurer les alarmes d'un membre";
$lang['alarm_admin_uncovered'] = "Alarmes actives ou à venir que personne ne reçoit";
$lang['alarm_admin_uncovered_none'] = "Toutes les alarmes sont reçues par au moins un membre.";
$lang['alarm_admin_categories'] = "Abonnements de catégories de membres";
$lang['alarm_admin_categories_help'] = "Chez chaque membre qui a le rôle, l'abonnement coche la case de la section choisie (toutes ses sections du rôle et la colonne Club si aucune section n'est choisie) ; il ne l'est plus s'il perd le rôle. Une case obligatoire ne peut pas être décochée par le membre.";
$lang['alarm_admin_role'] = "Rôle";
$lang['alarm_admin_add'] = "Ajouter";
$lang['alarm_admin_categories_none'] = "Aucun abonnement de catégorie.";
$lang['alarm_admin_delete'] = "Supprimer";
$lang['alarm_admin_catalogue'] = "Catalogue des alarmes";
$lang['alarm_admin_active'] = "Active";
$lang['alarm_admin_lead_days'] = "Délai d'anticipation (jours)";
$lang['alarm_admin_catalogue_saved'] = "Catalogue des alarmes enregistré.";
$lang['alarm_admin_category_added'] = "Abonnement de catégorie ajouté.";
$lang['alarm_admin_category_deleted'] = "Abonnement de catégorie supprimé.";
$lang['alarm_menu_my_alarms'] = "Mes alarmes";
$lang['alarm_menu_admin'] = "Administration des alarmes";
$lang['alarm_card_my_alarms_desc'] = "Mes alarmes et leur configuration";
$lang['alarm_column_club'] = "Club";
$lang['alarm_config_own_help'] = "Vos propres alarmes (vos documents qui expirent, vos documents obligatoires manquants) sont toujours suivies et ne peuvent pas être désactivées.";
$lang['alarm_config_no_supervision'] = "Vous n'avez pas de droit de supervision : aucune autre alarme ne peut être suivie.";
$lang['alarm_config_matrix_help'] = "Cochez, pour chaque alarme, les sections que vous voulez suivre : seuls les pilotes actifs, machines et documents de ces sections seront retenus. La colonne Club couvre les documents rattachés à aucune section.";
$lang['alarm_config_origin_legend'] = "Case cochée par un administrateur ou par catégorie (survolez l'icône)";
$lang['alarm_list_title'] = "Alarmes actives et à venir";
$lang['alarm_badge_own'] = "Moi";
$lang['alarm_card_active_count'] = "Alarmes actives";
