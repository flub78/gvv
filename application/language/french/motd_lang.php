<?php
/**
 * Language file for messages du jour / MOTD (French)
 */

$lang['motd_title'] = 'Messages du jour';
$lang['motd_active_count'] = '%d message(s) actif(s)';
$lang['motd_add'] = 'Nouveau message';
$lang['motd_edit'] = 'Modifier le message';
$lang['motd_view'] = 'Voir le message';
$lang['motd_delete'] = 'Supprimer';

$lang['motd_field_title'] = 'Titre';
$lang['motd_field_content'] = 'Contenu';
$lang['motd_field_level'] = 'Niveau';
$lang['motd_field_start_date'] = 'Début';
$lang['motd_field_end_date'] = 'Fin';
$lang['motd_field_target_type'] = 'Destinataires';
$lang['motd_field_target_list_id'] = 'Liste de diffusion';
$lang['motd_field_target_user_login'] = 'Utilisateur';
$lang['motd_field_origin'] = 'Origine';
$lang['motd_field_recipient_count'] = 'Nb. destinataires';
$lang['motd_field_acknowledged_count'] = 'Pris connaissance';

// field_long_name() looks these up (gvv_{table}_field_{field}) before
// falling back to the DB column COMMENT, which would otherwise leak our
// internal migration comments ("membres.mlogin", etc.) into form labels.
$lang['gvv_motd_messages_field_title'] = 'Titre';
$lang['gvv_motd_messages_field_content'] = 'Contenu';
$lang['gvv_motd_messages_field_level'] = 'Niveau';
$lang['gvv_motd_messages_field_start_date'] = 'Début';
$lang['gvv_motd_messages_field_end_date'] = 'Fin';
$lang['gvv_motd_messages_field_target_type'] = 'Destinataires';
$lang['gvv_motd_messages_field_target_list_id'] = 'Liste de diffusion';
$lang['gvv_motd_messages_field_target_user_login'] = 'Utilisateur';
$lang['gvv_motd_messages_field_origin'] = 'Origine';
$lang['gvv_motd_messages_field_id'] = 'ID';
$lang['gvv_motd_messages_field_source_type'] = 'Type de source';
$lang['gvv_motd_messages_field_source_ref'] = 'Référence source';
$lang['gvv_motd_messages_field_created_at'] = 'Date de création';
$lang['gvv_motd_messages_field_updated_at'] = 'Date de modification';
$lang['gvv_motd_messages_field_created_by'] = 'Créé par';
$lang['gvv_motd_messages_field_updated_by'] = 'Modifié par';

// field_name() looks these up (gvv_vue_{table}_short_field_{field}) for the
// compact admin table view (bs_tableView.php).
$lang['gvv_vue_motd_messages_short_field_title'] = 'Titre';
$lang['gvv_vue_motd_messages_short_field_level'] = 'Niveau';
$lang['gvv_vue_motd_messages_short_field_start_date'] = 'Début';
$lang['gvv_vue_motd_messages_short_field_end_date'] = 'Fin';
$lang['gvv_vue_motd_messages_short_field_target_label'] = 'Destinataires';
$lang['gvv_vue_motd_messages_short_field_origin'] = 'Origine';

$lang['motd_level_urgent'] = 'Urgent';
$lang['motd_level_important'] = 'Important';
$lang['motd_level_info'] = 'Info';
$lang['motd_level_alerte'] = 'Alerte';

$lang['motd_target_all'] = 'Tous les utilisateurs';
$lang['motd_target_list'] = 'Liste de diffusion';
$lang['motd_target_user'] = 'Utilisateur unique';

$lang['motd_origin_admin'] = 'Administrateur';
$lang['motd_origin_system'] = 'Généré par GVV';

$lang['motd_image_insert'] = 'Insérer une image';
$lang['motd_image_upload_error'] = "Échec de l'envoi de l'image";

$lang['motd_error_invalid_target'] = "Le destinataire sélectionné n'est pas valide (liste ou utilisateur introuvable).";
$lang['motd_error_dates_incoherent'] = 'La date de fin doit être postérieure ou égale à la date de début.';

$lang['motd_confirm_delete'] = 'Êtes-vous sûr de vouloir supprimer ce message ?';
$lang['motd_no_messages'] = 'Aucun message défini';

$lang['motd_no_title'] = '(sans titre)';
$lang['motd_sort_priority'] = 'Trier par priorité';
$lang['motd_sort_date'] = 'Trier par date';
$lang['motd_replies_title'] = 'Réponses';

$lang['motd_action_hide'] = 'Masquer';
$lang['motd_action_hide_all'] = 'Masquer tous les messages';
$lang['motd_action_acknowledge'] = "J'ai pris connaissance";
$lang['motd_acknowledged_badge'] = 'Pris connaissance';
$lang['motd_reply_placeholder'] = 'Votre réponse...';
$lang['motd_reply_submit'] = 'Répondre';
$lang['motd_reply_to_reply'] = 'Répondre';
$lang['motd_reply_cancel'] = 'Annuler';
$lang['motd_reply_replying_to'] = 'En réponse à';
$lang['motd_error_reply_empty'] = 'La réponse ne peut pas être vide.';
$lang['motd_error_action_failed'] = 'Une erreur est survenue, veuillez réessayer.';
$lang['motd_error_not_dismissible'] = 'Ce message ne peut pas être masqué tant que la validation associée n\'est pas faite.';
$lang['motd_not_dismissible_badge'] = 'Non masquable';

$lang['motd_section_empty'] = 'Aucun message ne vous est actuellement adressé.';
$lang['motd_section_all_hidden'] = "Vous n'avez aucun message non masqué. Utilisez le bouton \"Afficher tous les messages\" ci-dessus pour les retrouver.";
$lang['motd_action_show_all'] = 'Afficher tous les messages';

/* End of file motd_lang.php */
/* Location: ./application/language/french/motd_lang.php */
