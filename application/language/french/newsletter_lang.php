<?php
/**
 * Langue française — Newsletter de veille documentaire
 */

// Titres généraux
$lang['newsletter_title']          = 'Newsletter de veille';
$lang['newsletter_archive_title']  = 'Newsletters';
$lang['newsletter_admin_title']    = 'Abonnement à la newsletter';
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
$lang['newsletter_confirm_success_sent'] = 'Votre abonnement est confirmé. Le dernier numéro paru vient de vous être envoyé par email ; vous recevrez aussi les prochains.';
$lang['newsletter_confirm_success_none'] = 'Votre abonnement est confirmé. Vous recevrez les prochaines newsletters par email.';
$lang['newsletter_confirm_success_already'] = 'Votre abonnement est confirmé. Vous avez déjà reçu le dernier numéro paru ; vous recevrez les prochains par email.';
$lang['newsletter_confirm_success_failed'] = 'Votre abonnement est confirmé, mais l\'envoi du dernier numéro paru a échoué ; il vous sera renvoyé. Vous recevrez aussi les prochaines newsletters par email.';
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
$lang['newsletter_config_people']         = 'Relecteurs';
$lang['newsletter_config_paths']          = 'Stockage et API Anthropic';
$lang['newsletter_config_imap_host']      = 'Serveur IMAP';
$lang['newsletter_config_imap_port']      = 'Port';
$lang['newsletter_config_imap_user']      = 'Utilisateur';
$lang['newsletter_config_imap_password']  = 'Mot de passe (laisser vide pour conserver l\'actuel)';
$lang['newsletter_config_imap_ssl']       = 'SSL/TLS';
$lang['newsletter_config_imap_mailbox']   = 'Dossier IMAP';
$lang['newsletter_config_smtp_from']      = 'Adresse d\'expédition';
$lang['newsletter_config_smtp_from_name'] = 'Nom affiché';
$lang['newsletter_config_reviewers']      = 'Relecteurs (emails séparés par des virgules)';
$lang['newsletter_config_review_subject'] = 'Sujet de l\'email de relecture';
$lang['newsletter_config_review_body']    = 'Corps de l\'email de relecture';
$lang['newsletter_config_alerts']         = 'Alertes immédiates activées';
$lang['newsletter_config_save']           = 'Enregistrer';

// Test IA
$lang['newsletter_test_ai_title']         = 'Test agent IA (Anthropic)';
$lang['newsletter_test_ai_prompt']        = 'Prompt';
$lang['newsletter_test_ai_model']         = 'Modèle';
$lang['newsletter_test_ai_max_tokens']    = 'Tokens max';
$lang['newsletter_test_ai_send']          = 'Envoyer à Claude';
$lang['newsletter_test_ai_result']        = 'Réponse';
$lang['newsletter_test_ai_no_key']        = 'Clé API Anthropic non configurée.';
$lang['newsletter_config_anthropic_key']  = 'Clé API Anthropic';
$lang['newsletter_config_anthropic_model'] = 'Modèle par défaut';

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

// Abonnés et éditions
$lang['newsletter_public_page']           = 'Page publique d\'abonnement';
$lang['newsletter_public_page_help']      = 'Page à diffuser pour s\'abonner. Le désabonnement se fait par le lien personnel présent dans chaque email.';
$lang['newsletter_publish_newsletter_title'] = 'Titre de la newsletter';

// Cycle de relecture : éditions, import, annotation, relecture, consignes de modification, validation
$lang['nl_editions_title']                 = 'Veille réglementaire';
$lang['nl_no_editions']                    = 'Aucune édition. Commencez par importer le brouillon d\'une période produit par l\'agent de rédaction.';
$lang['nl_status']                         = 'État';
$lang['nl_status_draft']                   = 'Brouillon';
$lang['nl_status_reviewing']               = 'En relecture';
$lang['nl_status_validating']              = 'En validation';
$lang['nl_status_approved']                = 'Validée';
$lang['nl_status_published']               = 'Publiée';
$lang['nl_round']                          = 'Tour de relecture';
$lang['nl_updated_at']                     = 'Mise à jour';
$lang['nl_open']                           = 'Ouvrir';
$lang['nl_edition']                        = 'Édition';
$lang['nl_current_version']                = 'Version courante';
$lang['nl_versions']                       = 'Versions';
$lang['nl_draft']                          = 'Brouillon';
$lang['nl_preview_pdf']                    = 'Aperçu PDF';
$lang['nl_draft_missing']                  = 'Le fichier du brouillon est introuvable sur le serveur.';
$lang['nl_open_mentions']                  = '%d mention(s) entre crochets restent à compléter :';
$lang['nl_next_steps']                     = 'Étape suivante';
$lang['nl_back_to_edition']                = 'Retour à l\'édition';
$lang['nl_rendering']                      = 'Rendu';
$lang['nl_diff_with']                      = 'Différences avec la version %d';
$lang['nl_diff_none']                      = 'Aucune différence.';

$lang['nl_help_draft']                     = 'Annotez si besoin le brouillon ci-dessous, puis transmettez cette version aux relecteurs, ou validez-la et publiez-la directement s\'il n\'y a pas de remarques.';
$lang['nl_help_reviewing']                 = 'Brouillon transmis aux relecteurs le %s ; date limite de réponse : %s. Relevez les réponses reçues sur l\'adresse de veille et triez les remarques, puis téléchargez les consignes de modification pour l\'agent de rédaction et importez la version qu\'il produit.';
$lang['nl_deadline_not_reached']           = 'La date limite n\'est pas encore atteinte : d\'autres réponses peuvent arriver. Vous pourrez relever les réponses à nouveau plus tard.';
$lang['nl_help_directives']                = 'Les consignes de modification contiennent les commentaires de cette version (statut APPLIQUER), les remarques acceptées ou modifiées de ce tour (APPLIQUER) et les remarques reportées (REPORTER), avec leurs images ; les remarques rejetées n\'y figurent pas. Fournissez-les à l\'agent de rédaction, puis importez la version qu\'il produit : elle remplace la version courante.';
$lang['nl_help_approved']                  = 'Validée par %s le %s, mais la publication n\'a pas abouti. Relancez-la : elle retire les notes internes, ajoute la mention « Validé le … par … », produit le PDF, l\'archive pour les membres et l\'envoie aux abonnés.';
$lang['nl_help_published']                 = 'Publiée le %s.';
$lang['nl_help_other']                     = 'Aucune action disponible dans cet état.';
$lang['nl_btn_send_review']                = 'Transmettre la v%d aux relecteurs (%d)';
$lang['nl_btn_validate']                   = 'Valider et publier la v%d';
$lang['nl_btn_delete']                     = 'Supprimer';
$lang['nl_btn_collect']                    = 'Relever les réponses';
$lang['nl_btn_directives']                 = 'Consignes de modification (%d commentaire(s), %d remarque(s))';
$lang['nl_btn_import_next']                = 'Importer la version suivante';
$lang['nl_btn_publish']                    = 'Relancer la publication';
$lang['nl_published_pdf']                  = 'PDF publié';
$lang['nl_confirm_validate']               = 'Valider et publier la version %d ? Mentions entre crochets restantes : %d. La newsletter, notes internes retirées et mention de validation ajoutée, sera archivée pour les membres et envoyée par email à tous les abonnés.';
$lang['nl_confirm_delete']                 = 'Supprimer cette édition et ses versions ?';
$lang['nl_confirm_publish']                = 'Publier cette édition : archive membres et envoi par email à tous les abonnés ?';
$lang['nl_pending_count']                  = '%d remarque(s) sans décision.';

$lang['nl_annotated_draft']                = 'Brouillon annoté';
$lang['nl_annotated_help']                 = 'Les commentaires sont insérés sous le passage choisi. Ils figurent dans le PDF transmis aux relecteurs, dans la newsletter publiée et dans les consignes de modification.';
$lang['nl_annotation_default_label']       = 'Commentaire du DTO';
$lang['nl_btn_add_comment']                = 'Commenter';
$lang['nl_annotation_label']               = 'Libellé';
$lang['nl_annotation_content']             = 'Commentaire';
$lang['nl_btn_save_comment']               = 'Insérer le commentaire';
$lang['nl_btn_cancel']                     = 'Annuler';
$lang['nl_confirm_delete_comment']         = 'Supprimer ce commentaire ?';
$lang['nl_annotate_not_editable']          = 'Les commentaires ne se modifient que sur un brouillon ou une édition en relecture.';
$lang['nl_annotate_empty']                 = 'Saisissez un libellé et un commentaire.';
$lang['nl_annotate_bad_block']             = 'Le passage à commenter est introuvable dans la version courante.';
$lang['nl_annotate_ok']                    = 'Commentaire inséré.';
$lang['nl_annotate_error']                 = 'Le commentaire n\'a pas pu être enregistré.';
$lang['nl_annotation_deleted']             = 'Commentaire supprimé.';

$lang['nl_remarks']                        = 'Remarques des relecteurs';
$lang['nl_no_remarks']                     = 'Aucune remarque pour cette édition.';
$lang['nl_postponed']                      = 'Remarques reportées par d\'autres éditions';
$lang['nl_decision']                       = 'Décision';
$lang['nl_decision_en_attente']            = 'En attente';
$lang['nl_decision_acceptee']              = 'Acceptée';
$lang['nl_decision_modifiee']              = 'Modifiée';
$lang['nl_decision_rejetee']               = 'Rejetée';
$lang['nl_decision_reportee']              = 'Reportée à une prochaine édition';
$lang['nl_admin_text']                     = 'Texte retenu (si modifiée)';
$lang['nl_btn_decide']                     = 'Enregistrer';
$lang['nl_btn_attach']                     = 'Reprendre dans cette édition';
$lang['nl_decision_saved']                 = 'Décision enregistrée : %s.';
$lang['nl_decision_error_not_found']       = 'Remarque introuvable.';
$lang['nl_decision_error_invalid_decision'] = 'Décision inconnue.';
$lang['nl_decision_error_modified_text_required'] = 'Saisissez le texte retenu pour une remarque modifiée.';
$lang['nl_attach_ok']                      = 'Remarque reprise dans cette édition, en attente de décision.';
$lang['nl_attach_error']                   = 'Impossible de reprendre cette remarque (déjà rattachée à une édition ?).';

$lang['nl_no_reviewers']                   = 'Aucun relecteur configuré. <a href="%s">Configurer les relecteurs</a>.';
$lang['nl_no_sender']                      = 'Adresse d\'expédition non configurée (configuration SMTP de la newsletter).';
$lang['nl_send_not_draft']                 = 'Seul un brouillon peut être transmis aux relecteurs.';
$lang['nl_send_success']                   = 'Version %d transmise à : %s. Date limite de réponse : %s.';
$lang['nl_send_some_failed']               = 'Échec de l\'envoi à : %s.';
$lang['nl_send_all_failed']                = 'Aucun email n\'a pu être envoyé (%s). L\'édition reste à l\'état brouillon.';
$lang['nl_collect_not_sent']               = 'Les réponses ne se relèvent qu\'après la transmission d\'une version aux relecteurs, et avant la validation.';
$lang['nl_collect_imap_error']             = 'Lecture de la boîte de veille impossible : %s';
$lang['nl_collect_summary']                = '%d message(s) lu(s) : %d réponse(s) retenue(s), %d écartée(s).';

$lang['nl_directives_nothing']             = 'Aucun commentaire sur cette version ni aucune remarque acceptée, modifiée ou reportée dans ce tour : pas de consignes de modification.';
$lang['nl_btn_postponed']                  = 'Remarques reportées (%d)';
$lang['nl_postponed_help']                 = 'Remarques reportées par les éditions précédentes, à fournir à l\'agent pour la version initiale de la prochaine édition.';
$lang['nl_postponed_none']                 = 'Aucune remarque reportée.';
$lang['nl_zip_error']                      = 'L\'archive zip n\'a pas pu être créée.';

$lang['nl_validate_not_draft']             = 'Seule une version à l\'état brouillon peut être validée et publiée.';
$lang['nl_validate_error']                 = 'La validation a échoué.';
$lang['nl_validated_mention']              = 'Validé le %s par %s';
$lang['nl_publish_not_approved']           = 'Seule une édition validée peut être publiée.';
$lang['nl_publish_write_error']            = 'Impossible d\'écrire le PDF dans %s.';
$lang['nl_publish_success']                = 'Version %d validée et publiée (<a href="%s" target="_blank">voir le PDF</a>).';
$lang['nl_resend_not_published']           = 'Seule une newsletter publiée peut être renvoyée aux abonnés.';
$lang['nl_resend_none']                    = 'Tous les abonnés ont déjà reçu cette newsletter.';
$lang['nl_resend_done']                    = 'Nouvel envoi aux abonnés non atteints.';
$lang['nl_confirm_resend']                 = 'Envoyer cette newsletter aux %d abonné(s) qui ne l\'ont pas reçue ?';
$lang['nl_btn_resend']                     = 'Renvoyer aux abonnés non atteints (%d)';
$lang['nl_unsent_count']                   = '%d abonné(s) n\'ont pas reçu cette newsletter (envoi en échec ou interrompu).';
$lang['nl_sending_partial']                = 'Envoyée à %d abonné(s) ; échec pour %d abonné(s) : %s. Corrigez la cause (configuration d\'envoi, adresse), puis utilisez « Renvoyer aux abonnés non atteints ».';
$lang['nl_sending_ok']                     = 'Envoyée à %d abonné(s).';
$lang['nl_publish_error']                  = 'La newsletter n\'a pas pu passer à l\'état publiée : elle reste validée, relancez la publication.';
$lang['nl_mail_subject'] = "[%s] Newsletter de veille — du %s au %s";
$lang['nl_mail_greeting'] = "Bonjour %s %s,";
$lang['nl_mail_intro'] = "Voici la newsletter de veille sécurité et réglementation de %s pour la période du %s au %s. Vous la trouverez en pièce jointe.";
$lang['nl_mail_welcome_intro'] = "Merci de votre abonnement. Voici le dernier numéro paru de la newsletter de veille sécurité et réglementation de %s, pour la période du %s au %s. Vous le trouverez en pièce jointe.";
$lang['nl_mail_feedback'] = "Vos retours nous intéressent : remarques, corrections, et surtout toute information qui aurait dû figurer dans cette lettre et qui n'y est pas. Écrivez-nous à %s en précisant, si vous la connaissez, la source de l'information (lien, référence du document, émetteur).";
$lang['nl_mail_signature'] = "Cordiales salutations aéronautiques,\nL'équipe de %s";
$lang['nl_mail_unsubscribe'] = "Pour vous désabonner : %s";
$lang['nl_edition_deleted']                = 'Édition « %s » supprimée.';
$lang['nl_delete_error']                   = 'La newsletter « %s » n\'a pas pu être supprimée.';
$lang['nl_confirm_delete_published']       = 'Supprimer définitivement la newsletter « %s » ? Le PDF publié, ses versions, ses remarques et le journal des envois seront supprimés.';
$lang['nl_confirm_delete_edition']         = 'Supprimer définitivement l\'édition « %s », ses versions, ses commentaires et ses remarques ?';

$lang['nl_import_title']                   = 'Importer un brouillon';
$lang['nl_import_intro']                   = 'Importez un brouillon produit par l\'agent de rédaction : un fichier Markdown, ou une archive zip contenant le Markdown et son sous-répertoire d\'images. Le titre (premier titre « # »), la période (ligne « Période : du … au … ») et la version (mention « BROUILLON vN ») sont lus dans le brouillon. Un brouillon dont la période correspond à une édition en cours en devient la version suivante ; sinon, une nouvelle édition est créée.';
$lang['nl_import_file']                    = 'Brouillon';
$lang['nl_btn_import']                     = 'Importer';
$lang['nl_import_failed']                  = 'L\'import a échoué :';
$lang['nl_import_success_new']             = 'Version %d importée : nouvelle édition « %s » (%s).';
$lang['nl_import_success_version']         = 'Version %d importée dans l\'édition « %s » (%s). Elle remplace la version précédente, qui reste consultable.';
$lang['nl_import_no_file']                 = 'Choisissez le fichier du brouillon (.md ou .zip).';
$lang['nl_upload_failed']                  = 'Le fichier %s n\'a pas pu être reçu.';
$lang['nl_upload_too_big']                 = 'Le fichier %s dépasse 30 Mo.';
$lang['nl_import_bad_type']                = 'Le fichier %s n\'est ni un Markdown (.md) ni une archive zip.';
$lang['nl_import_bad_zip']                 = 'L\'archive %s est illisible.';
$lang['nl_import_zip_too_many_files']      = 'L\'archive %s contient %d fichiers : %d au plus sont acceptés.';
$lang['nl_import_md_too_big']              = 'Le fichier %s dépasse %d Mo : un brouillon Markdown ne peut pas être aussi volumineux.';
$lang['nl_import_image_too_big']           = 'L\'image %s dépasse %d Mo.';
$lang['nl_import_zip_unzipped_too_big']    = 'Une fois décompressée, l\'archive %s dépasse %d Mo.';
$lang['nl_import_zip_many_md']             = 'L\'archive contient plusieurs fichiers Markdown (%s) : elle doit n\'en contenir qu\'un.';
$lang['nl_import_zip_no_md']               = 'L\'archive ne contient aucun fichier Markdown (.md).%s';
$lang['nl_import_empty']                   = 'Le brouillon est vide.';
$lang['nl_import_no_title']                = 'Titre introuvable : le brouillon doit commencer par un titre « # Titre ».';
$lang['nl_import_no_period']               = 'Période introuvable : le brouillon doit contenir une ligne « Période : du 21 septembre au 21 octobre 2026 » (ou « du 21/09/2026 au 21/10/2026 »).';
$lang['nl_import_no_version']              = 'Version introuvable : le brouillon doit contenir la mention « BROUILLON vN ».';
$lang['nl_import_missing_image']           = 'Image %s référencée par le brouillon mais absente de l\'envoi : elle ne sera pas affichée.';
$lang['nl_import_already_validated']       = 'L\'édition « %s » de cette période est déjà validée : elle ne peut plus recevoir de nouvelle version.';
$lang['nl_import_version_exists']          = 'La version %d est déjà importée dans l\'édition « %s » (dernière version : v%d). Faites produire une version supérieure par l\'agent.';
$lang['nl_import_db_error']                = 'L\'édition n\'a pas pu être créée en base de données.';
$lang['nl_import_storage_error']           = 'Le brouillon n\'a pas pu être enregistré dans %s.';
$lang['nl_import_status_unchanged']        = 'La nouvelle version est importée, mais l\'édition n\'a pas pu repasser à l\'état brouillon.';

$lang['nl_pdf_period']                     = 'Période du %s';
$lang['nl_pdf_badge']                      = 'Brouillon v%d';

$lang['nl_config_review_delay']            = 'Délai de réponse des relecteurs (jours)';
$lang['nl_config_storage_path']            = 'Répertoire de stockage des brouillons';
$lang['nl_config_storage_help']            = 'Hors de l\'espace servi directement par le serveur web. Vide : répertoire par défaut indiqué.';
