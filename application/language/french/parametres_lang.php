<?php
/**
 * Langue française — Page Configuration (paramètres unifiés)
 */

$lang['parametres_title']            = 'Configuration';
$lang['parametres_card_desc']        = 'Paramètres du club et de l\'application';
$lang['parametres_no_tab']           = 'Aucun paramètre ne vous est accessible.';
$lang['parametres_forbidden_tab']    = 'Vous n\'avez pas accès à l\'onglet « %s ».';
$lang['parametres_unknown_tab']      = 'L\'onglet « %s » n\'existe pas.';
$lang['parametres_save']             = 'Enregistrer';
$lang['parametres_read_only']        = 'Cet onglet est en lecture seule pour vous.';
$lang['parametres_default_value']    = 'Valeur par défaut de GVV : rien n\'a encore été saisi.';
$lang['parametres_last_update']      = 'Dernière modification le %s par %s';
$lang['parametres_unsaved_confirm']  = 'Des modifications ne sont pas enregistrées. Quitter cet onglet quand même ?';
$lang['parametres_saved']            = 'Paramètres enregistrés :';
$lang['parametres_nothing_changed']  = 'Aucune modification à enregistrer.';
$lang['parametres_not_saved']        = 'Paramètres non enregistrés :';
$lang['parametres_preview']          = 'Aperçu';
$lang['parametres_public_url']       = 'Adresse publique';
$lang['parametres_copy']             = 'Copier';
$lang['parametres_copied']           = 'Adresse copiée';

// États des onglets
$lang['parametres_status_complete']   = 'Complet';
$lang['parametres_status_incomplete'] = 'Incomplet';
$lang['parametres_status_optional']   = 'Optionnel, non configuré';

// Langues
$lang['parametres_lang_french']  = 'Français';
$lang['parametres_lang_english'] = 'English';
$lang['parametres_lang_dutch']   = 'Nederlands';

// Onglet Pages publiques
$lang['parametres_tab_pages_publiques']      = 'Pages publiques';
$lang['parametres_tab_pages_publiques_help'] = 'Pages consultables sans connexion. Les règles de confidentialité et les conditions d\'utilisation sont accessibles depuis la page de connexion.';
$lang['parametres_legal_confidentialite']      = 'Règles de confidentialité';
$lang['parametres_legal_confidentialite_help'] = 'Texte propre au club, au format Markdown. Laissé vide, un texte générique de GVV est utilisé. Les informations du club peuvent être insérées : {nom_club}, {adresse_club}, {cp_club}, {ville_club}, {email_club}, {tel_club}, {url_club}, {contact_rgpd}.';
$lang['parametres_legal_conditions']           = 'Conditions d\'utilisation';
$lang['parametres_legal_conditions_help']      = 'Texte propre au club, au format Markdown. Laissé vide, un texte générique de GVV est utilisé. Mêmes informations du club insérables que pour les règles de confidentialité.';
$lang['parametres_newsletter_page']            = 'Newsletters';
$lang['parametres_generic_text']               = 'Texte générique de GVV (utilisé tant que rien n\'est saisi) :';

// Erreurs
$lang['parametres_err_unknown']          = 'paramètre inconnu';
$lang['parametres_err_forbidden']        = 'vous n\'avez pas le droit de le modifier';
$lang['parametres_err_language']         = 'langue inconnue';
$lang['parametres_err_required']         = 'valeur obligatoire';
$lang['parametres_err_max_length']       = 'texte trop long';
$lang['parametres_err_email']            = 'adresse e-mail invalide';
$lang['parametres_err_url']              = 'adresse web invalide (elle doit commencer par http:// ou https://)';
$lang['parametres_err_integer']          = 'nombre entier attendu';
$lang['parametres_err_db']               = 'erreur d\'enregistrement en base de données';
$lang['parametres_err_file_missing_file'] = 'le fichier de configuration %s est absent du serveur';
$lang['parametres_err_file_not_writable'] = 'le fichier %s n\'est pas modifiable par le serveur web (sur le serveur : chmod g+w %s)';
$lang['parametres_err_file_syntax_error'] = 'la modification rendrait le fichier %s invalide, elle n\'a pas été faite';
$lang['parametres_err_file_write_failed'] = 'l\'écriture du fichier %s a échoué';
$lang['parametres_err_file_invalid_key']  = 'nom de paramètre invalide';
$lang['parametres_err_token']            = "La page a expiré ou la demande ne provient pas de la page Configuration : rien n'a été enregistré.";
$lang['parametres_reload']               = "Recharger la page";


// Lot 2 : onglet Club
$lang['parametres_tab_club'] = 'Club';
$lang['parametres_tab_club_help'] = 'Identité du club, utilisée dans les documents, les e-mails et les pages publiques. Le conseil d\'administration la consulte ; seul l\'administrateur du club la modifie.';
$lang['parametres_sigle_club'] = 'Sigle';
$lang['parametres_sigle_club_help'] = 'Nom court du club, affiché dans les en-têtes.';
$lang['parametres_nom_club'] = 'Nom';
$lang['parametres_nom_club_help'] = 'Nom complet de l\'association.';
$lang['parametres_code_club'] = 'Code d\'identification';
$lang['parametres_code_club_help'] = 'Code fédéral du club (facultatif).';
$lang['parametres_adresse_club'] = 'Rue';
$lang['parametres_adresse_club_help'] = 'Adresse postale du club.';
$lang['parametres_cp_club'] = 'Code postal';
$lang['parametres_cp_club_help'] = '';
$lang['parametres_ville_club'] = 'Ville';
$lang['parametres_ville_club_help'] = '';
$lang['parametres_tel_club'] = 'Téléphone';
$lang['parametres_tel_club_help'] = '';
$lang['parametres_email_club'] = 'E-mail';
$lang['parametres_email_club_help'] = 'Adresse de contact du club, aussi utilisée comme expéditeur des e-mails.';
$lang['parametres_url_club'] = 'Site web';
$lang['parametres_url_club_help'] = 'Adresse du site du club, commençant par https://.';
$lang['parametres_logo_club'] = 'Logo';
$lang['parametres_logo_club_help'] = 'Image PNG, JPEG ou GIF de 500 Ko au plus. Le logo précédent est conservé sur le serveur, ce qui permet d\'y revenir depuis l\'historique.';
$lang['parametres_contact_rgpd'] = 'Contact RGPD';
$lang['parametres_contact_rgpd_help'] = 'Personne à qui adresser les demandes liées aux données personnelles, affichée dans les règles de confidentialité. Laissé vide : « le président de l\'association ».';

// Lot 2 : onglet Modules
$lang['parametres_tab_modules'] = 'Modules';
$lang['parametres_tab_modules_help'] = 'Fonctionnalités activées pour le club. Le menu est mis à jour dès la page suivante. La page Configuration reste toujours accessible, quels que soient les modules actifs.';
$lang['parametres_gestion_vd'] = 'Vols de découverte';
$lang['parametres_gestion_vd_help'] = 'Bons de vols de découverte, envoyés par e-mail.';
$lang['parametres_gestion_reservations'] = 'Réservations';
$lang['parametres_gestion_reservations_help'] = 'Réservation des aéronefs.';
$lang['parametres_gestion_formations'] = 'Formations';
$lang['parametres_gestion_formations_help'] = 'Suivi de la formation des élèves.';
$lang['parametres_gestion_documentaire'] = 'Gestion documentaire';
$lang['parametres_gestion_documentaire_help'] = 'Documents archivés des membres et du club.';
$lang['parametres_gestion_paiements'] = 'Paiements HelloAsso';
$lang['parametres_gestion_paiements_help'] = 'Paiements en ligne. Ses réglages par section restent pour l\'instant dans l\'écran Paiements en ligne > Configuration.';
$lang['parametres_gestion_ulm'] = 'ULM';
$lang['parametres_gestion_ulm_help'] = 'Gestion des ULM.';
$lang['parametres_gestion_tickets'] = 'Tickets de vol';
$lang['parametres_gestion_tickets_help'] = '';
$lang['parametres_gestion_pompes'] = 'Pompes carburant';
$lang['parametres_gestion_pompes_help'] = '';
$lang['parametres_gestion_rapprochements'] = 'Rapprochements bancaires';
$lang['parametres_gestion_rapprochements_help'] = '';
$lang['parametres_gestion_of'] = 'Offres de formation';
$lang['parametres_gestion_of_help'] = '';
$lang['parametres_veille'] = 'Newsletter de veille';
$lang['parametres_veille_help'] = 'Newsletter de veille sécurité et réglementaire, publiée sans connexion.';
$lang['parametres_auto_planchiste'] = 'Saisie des vols par les pilotes';
$lang['parametres_auto_planchiste_help'] = 'Les pilotes peuvent saisir leurs propres vols quand ils ne sont pas planchistes.';
$lang['parametres_module_incomplete'] = 'Le module « %s » est actif mais des paramètres obligatoires manquent : complétez l\'onglet %s.';

// Lot 2 : onglet Messagerie
$lang['parametres_tab_messagerie'] = 'Messagerie';
$lang['parametres_tab_messagerie_help'] = 'Serveur d\'envoi des e-mails et redirection des envois pendant les essais.';
$lang['parametres_protocol'] = 'Mode d\'envoi';
$lang['parametres_protocol_help'] = '« mail (PHP) » sur un hébergement mutualisé ; « SMTP » pour passer par un serveur d\'envoi (Brevo…).';
$lang['parametres_smtp_host'] = 'Serveur SMTP';
$lang['parametres_smtp_host_help'] = 'Obligatoire en mode SMTP, par exemple smtp-relay.brevo.com.';
$lang['parametres_smtp_port'] = 'Port SMTP';
$lang['parametres_smtp_port_help'] = '587 avec TLS, 465 avec SSL.';
$lang['parametres_smtp_crypto'] = 'Chiffrement';
$lang['parametres_smtp_crypto_help'] = '';
$lang['parametres_smtp_user'] = 'Identifiant SMTP';
$lang['parametres_smtp_user_help'] = 'Pour Brevo : tableau de bord Brevo → SMTP & API → Identifiants SMTP.';
$lang['parametres_smtp_pass'] = 'Mot de passe SMTP';
$lang['parametres_smtp_pass_help'] = 'Pour Brevo, la clé SMTP (commence par xsmtpsib-).';
$lang['parametres_test_email'] = 'Redirection des e-mails (essais)';
$lang['parametres_test_email_help'] = 'Si renseignée, tous les e-mails envoyés par GVV partent vers cette adresse au lieu de leurs destinataires. À laisser vide en production.';
$lang['parametres_test_phone'] = 'Redirection des SMS (essais)';
$lang['parametres_test_phone_help'] = 'Si renseigné, tous les SMS envoyés par GVV partent vers ce numéro. À laisser vide en production.';
$lang['parametres_test_email_title'] = 'Envoi d\'un e-mail de test';
$lang['parametres_test_email_card_help'] = 'Le test utilise les valeurs enregistrées ci-dessus : enregistrez d\'abord vos modifications. La redirection des e-mails d\'essai ne s\'applique pas à cet envoi.';
$lang['parametres_test_email_to'] = 'Destinataire';
$lang['parametres_test_email_send'] = 'Envoyer';
$lang['parametres_test_email_subject'] = 'GVV : e-mail de test';
$lang['parametres_test_email_body'] = '<p>Cet e-mail de test a été envoyé depuis la page Configuration de GVV par %s, le %s.</p><p>Si vous le lisez, l\'envoi des e-mails fonctionne.</p>';
$lang['parametres_test_email_sent'] = 'E-mail de test envoyé à %s. Vérifiez sa réception.';
$lang['parametres_test_email_failed'] = 'Échec de l\'envoi de l\'e-mail de test (mode d\'envoi : %s, expéditeur : %s).';
$lang['parametres_test_email_bad_address'] = 'Adresse du destinataire invalide : « %s ». Aucun e-mail n\'a été envoyé.';
$lang['parametres_test_email_debug'] = 'Détails techniques';

// Lot 2 : secrets, lecture seule
$lang['parametres_required'] = 'Obligatoire';
$lang['parametres_yes'] = 'Oui';
$lang['parametres_no'] = 'Non';
$lang['parametres_secret_defined'] = 'Valeur définie';
$lang['parametres_secret_undefined'] = 'Aucune valeur';
$lang['parametres_secret_placeholder'] = 'Laisser vide pour conserver la valeur actuelle';
$lang['parametres_secret_clear'] = 'Effacer la valeur enregistrée';

// Lot 2 : historique
$lang['parametres_history'] = 'Historique';
$lang['parametres_history_title'] = 'Historique des modifications';
$lang['parametres_history_help'] = 'Les modifications les plus récentes d\'abord. Le retour à la valeur précédente est vérifié comme une saisie, et lui-même tracé. Un secret ne peut pas être restauré.';
$lang['parametres_history_empty'] = 'Aucune modification n\'a encore été enregistrée pour cet onglet.';
$lang['parametres_history_forbidden'] = 'L\'historique de l\'onglet « %s » est réservé aux personnes qui peuvent le modifier.';
$lang['parametres_history_unavailable'] = 'L\'historique n\'est pas encore disponible : la base de données doit être mise à jour (migration).';
$lang['parametres_history_secret'] = 'secret (valeur non conservée)';
$lang['parametres_history_default'] = 'non défini';
$lang['parametres_history_action_modification'] = 'Modification';
$lang['parametres_history_action_effacement'] = 'Effacement';
$lang['parametres_history_action_retour'] = 'Retour arrière';
$lang['parametres_restore'] = 'Revenir';
$lang['parametres_restore_help'] = 'Revenir à la valeur d\'avant cette modification';
$lang['parametres_restored'] = '« %s » est revenu à sa valeur précédente.';
$lang['parametres_restore_unchanged'] = '« %s » a déjà cette valeur : rien n\'a été modifié.';
$lang['parametres_restore_failed'] = '« %s » n\'a pas été modifié : %s';
$lang['parametres_back_to_tab'] = 'Retour à l\'onglet';
$lang['gvv_vue_parametres_history_short_field_date'] = 'Date';
$lang['gvv_vue_parametres_history_short_field_auteur'] = 'Par';
$lang['gvv_vue_parametres_history_short_field_parametre'] = 'Paramètre';
$lang['gvv_vue_parametres_history_short_field_action'] = 'Action';
$lang['gvv_vue_parametres_history_short_field_ancienne'] = 'Ancienne valeur';
$lang['gvv_vue_parametres_history_short_field_nouvelle'] = 'Nouvelle valeur';
$lang['gvv_vue_parametres_history_short_field_retour'] = '';

// Lot 2 : erreurs
$lang['parametres_err_option'] = 'valeur non proposée';
$lang['parametres_err_range'] = 'nombre hors des limites autorisées';
$lang['parametres_err_history_unknown'] = 'Cette modification n\'existe pas dans l\'historique.';
$lang['parametres_err_restore_secret'] = 'un secret ne peut pas être restauré depuis l\'historique';
