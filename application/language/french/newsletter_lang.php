<?php
/**
 * Langue française — Newsletter de veille documentaire
 */

// Titres généraux
$lang['newsletter_title']          = 'Newsletter de veille';
$lang['newsletter_archive_title']  = 'Newsletters';
$lang['newsletter_admin_title']    = 'Gestion de la newsletter';
$lang['newsletter_subscribers']    = 'Abonnés';

// Page d'abonnement public
$lang['newsletter_subscribe_title']       = 'S\'abonner à la newsletter';
$lang['newsletter_subscribe_intro']       = 'Recevez nos newsletters de veille sécurité et réglementaire par email.';
$lang['newsletter_first_name']            = 'Prénom';
$lang['newsletter_last_name']             = 'Nom';
$lang['newsletter_email']                 = 'Adresse email';
$lang['newsletter_subscribe_btn']         = 'S\'abonner';
$lang['newsletter_subscribe_success']     = 'Un email de confirmation vous a été envoyé à %s. Cliquez sur le lien pour activer votre abonnement.';
$lang['newsletter_already_subscribed']    = 'Cette adresse est déjà abonnée à la newsletter.';
$lang['newsletter_subscribe_error']       = 'Une erreur est survenue. Veuillez réessayer.';

// Confirmation d'abonnement
$lang['newsletter_confirm_title']         = 'Abonnement confirmé';
$lang['newsletter_confirm_success']       = 'Votre abonnement est confirmé. Vous recevrez les prochaines newsletters par email.';
$lang['newsletter_confirm_invalid']       = 'Ce lien de confirmation est invalide ou a déjà été utilisé.';

// Désinscription
$lang['newsletter_unsubscribe_title']     = 'Se désabonner';
$lang['newsletter_unsubscribe_success']   = 'Vous êtes désinscrit(e) de la newsletter.';
$lang['newsletter_unsubscribe_invalid']   = 'Ce lien de désinscription est invalide.';
$lang['newsletter_unsubscribe_btn']       = 'Se désabonner';
$lang['newsletter_unsubscribe_confirm']   = 'Êtes-vous sûr(e) de vouloir vous désabonner de la newsletter ?';

// Archive membres
$lang['newsletter_no_newsletters']        = 'Aucune newsletter disponible pour le moment.';
$lang['newsletter_period']                = 'Période';
$lang['newsletter_published_at']          = 'Publiée le';
$lang['newsletter_open_pdf']              = 'Ouvrir';

// Administration
$lang['newsletter_no_subscribers']        = 'Aucun abonné.';
$lang['newsletter_subscriber_name']       = 'Nom';
$lang['newsletter_subscriber_email']      = 'Email';
$lang['newsletter_subscriber_confirmed']  = 'Confirmé';
$lang['newsletter_subscriber_date']       = 'Date d\'inscription';
$lang['newsletter_subscriber_status']     = 'Statut';
$lang['newsletter_status_active']         = 'Actif';
$lang['newsletter_status_pending']        = 'En attente';
$lang['newsletter_status_unsubscribed']   = 'Désabonné';
$lang['newsletter_delete_subscriber']     = 'Supprimer';
$lang['newsletter_delete_confirm']        = 'Supprimer cet abonné ?';
$lang['newsletter_delete_success']        = 'Abonné supprimé.';

// Configuration IMAP/SMTP
$lang['newsletter_config_title']          = 'Configuration veille réglementaire';
$lang['newsletter_config_saved']          = 'Configuration sauvegardée.';
$lang['newsletter_config_imap']           = 'Boîte mail de collecte (IMAP)';
$lang['newsletter_config_smtp']           = 'Serveur d\'envoi (SMTP)';
$lang['newsletter_config_smtp_host']      = 'Serveur SMTP';
$lang['newsletter_config_smtp_port']      = 'Port';
$lang['newsletter_config_smtp_crypto']    = 'Chiffrement (tls / ssl)';
$lang['newsletter_config_smtp_user']      = 'Utilisateur SMTP';
$lang['newsletter_config_smtp_password']  = 'Mot de passe SMTP (laisser vide pour conserver)';
$lang['newsletter_config_smtp_fallback']  = 'Laisser le serveur SMTP vide pour utiliser la configuration globale GVV (Brevo).';
$lang['newsletter_config_people']         = 'Relecteurs et validateurs';
$lang['newsletter_config_paths']          = 'Chemins agent IA';
$lang['newsletter_config_imap_host']      = 'Serveur IMAP';
$lang['newsletter_config_imap_port']      = 'Port';
$lang['newsletter_config_imap_user']      = 'Utilisateur';
$lang['newsletter_config_imap_password']  = 'Mot de passe (laisser vide pour conserver l\'actuel)';
$lang['newsletter_config_imap_ssl']       = 'SSL/TLS';
$lang['newsletter_config_imap_mailbox']   = 'Dossier IMAP';
$lang['newsletter_config_smtp_from']      = 'Adresse d\'expédition';
$lang['newsletter_config_smtp_from_name'] = 'Nom affiché';
$lang['newsletter_config_reviewers']      = 'Relecteurs (emails séparés par des virgules)';
$lang['newsletter_config_validators']     = 'Validateurs (emails séparés par des virgules)';
$lang['newsletter_config_context_path']   = 'Chemin du fichier de contexte IA';
$lang['newsletter_config_output_path']    = 'Répertoire de sortie IA';
$lang['newsletter_config_alerts']         = 'Alertes immédiates activées';
$lang['newsletter_config_save']           = 'Enregistrer';

// Tests d'infrastructure
$lang['newsletter_test_email_title']      = 'Test envoi email';
$lang['newsletter_test_imap_title']       = 'Test lecture IMAP';
$lang['newsletter_test_to']              = 'Envoyer à';
$lang['newsletter_test_send']            = 'Envoyer le test';
$lang['newsletter_test_run']             = 'Lancer les tests';
$lang['newsletter_test_since']           = 'Emails depuis le (format 01-Jan-2026)';
$lang['newsletter_test_pattern']         = 'Filtre sur le sujet (optionnel)';
$lang['newsletter_test_ok']              = 'OK';
$lang['newsletter_test_fail']            = 'ÉCHEC';

// Publication manuelle
$lang['newsletter_publish_title']         = 'Publier une newsletter';
$lang['newsletter_publish_period_start']  = 'Début de période';
$lang['newsletter_publish_period_end']    = 'Fin de période';
$lang['newsletter_publish_newsletter_title'] = 'Titre de la newsletter';
$lang['newsletter_publish_pdf']           = 'Fichier PDF';
$lang['newsletter_publish_btn']           = 'Publier et envoyer aux abonnés';
$lang['newsletter_publish_success']       = 'Newsletter publiée et envoyée à %d abonné(s).';
$lang['newsletter_publish_error']         = 'Erreur lors de la publication.';
$lang['newsletter_pdf_required']          = 'Un fichier PDF est requis.';
$lang['newsletter_pdf_invalid']           = 'Le fichier doit être un PDF.';
