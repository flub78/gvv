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
$lang['newsletter_config_review_subject'] = 'Sujet de l\'email de relecture';
$lang['newsletter_config_review_body']    = 'Corps de l\'email de relecture';
$lang['newsletter_config_context_path']   = 'Chemin du fichier de contexte IA';
$lang['newsletter_config_output_path']    = 'Répertoire de sortie IA';
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

// Cycle de la version 1 : éditions, consignes, import, relecture, validation
$lang['nl_editions_title']        = 'Éditions de la veille';
$lang['nl_no_editions']           = 'Aucune édition. Commencez par importer le brouillon d\'une période.';
$lang['nl_status']                = 'État';
$lang['nl_status_draft']          = 'Brouillon';
$lang['nl_status_reviewing']      = 'En relecture';
$lang['nl_status_validating']     = 'En validation';
$lang['nl_status_approved']       = 'Validée';
$lang['nl_status_published']      = 'Publiée';
$lang['nl_round']                 = 'Tour de relecture';
$lang['nl_updated_at']            = 'Mise à jour';
$lang['nl_open']                  = 'Ouvrir';
$lang['nl_edition']               = 'Édition';
$lang['nl_current_version']       = 'Version courante';
$lang['nl_versions']              = 'Versions';
$lang['nl_draft']                 = 'Brouillon';
$lang['nl_preview_pdf']           = 'Aperçu PDF';
$lang['nl_show_draft']            = 'Afficher le brouillon';
$lang['nl_draft_missing']         = 'Le fichier du brouillon est introuvable sur le serveur.';
$lang['nl_open_mentions']         = '%d mention(s) entre crochets restent à compléter :';
$lang['nl_next_steps']            = 'Étape suivante';
$lang['nl_back_to_edition']       = 'Retour à l\'édition';
$lang['nl_rendering']             = 'Rendu';
$lang['nl_diff_with']             = 'Différences avec la version %d';
$lang['nl_diff_with_active']      = 'Différences avec la version active (v%d)';
$lang['nl_diff_none']             = 'Aucune différence.';

$lang['nl_help_draft']            = 'Transmettez cette version aux relecteurs, ou validez-la directement s\'il n\'y a pas de remarques.';
$lang['nl_help_reviewing']        = 'Brouillon transmis aux relecteurs le %s ; date limite de réponse : %s. Récupérez ensuite les réponses reçues sur l\'adresse de veille, triez-les, puis demandez une nouvelle version.';
$lang['nl_deadline_not_reached']  = 'La date limite n\'est pas encore atteinte : d\'autres réponses peuvent arriver. Vous pourrez relancer la récupération plus tard.';
$lang['nl_help_revise']           = 'La nouvelle version intègre les suggestions acceptées ou modifiées de ce tour. Elle repasse à l\'état brouillon : vous pourrez la retransmettre ou la valider.';
$lang['nl_help_approved']         = 'Validée par %s le %s. La publication retire les notes internes, produit le PDF, l\'archive pour les membres et l\'envoie aux abonnés.';
$lang['nl_help_published']        = 'Publiée le %s.';
$lang['nl_help_other']            = 'Aucune action disponible dans cet état.';
$lang['nl_btn_send_review']       = 'Transmettre la v%d aux relecteurs (%d)';
$lang['nl_btn_validate']          = 'Valider la v%d';
$lang['nl_btn_delete']            = 'Supprimer';
$lang['nl_btn_collect']           = 'Récupérer les réponses';
$lang['nl_btn_revise']            = 'Demander une nouvelle version (%d suggestion(s))';
$lang['nl_btn_publish']           = 'Publier et envoyer aux abonnés';
$lang['nl_published_pdf']         = 'PDF publié';
$lang['nl_confirm_validate']      = 'Valider la version %d ? Mentions entre crochets restantes : %d. Le texte validé sera publié tel quel, notes internes en moins.';
$lang['nl_confirm_delete']        = 'Supprimer cette édition et ses versions ?';
$lang['nl_confirm_publish']       = 'Publier cette édition : archive membres et envoi par email à tous les abonnés ?';
$lang['nl_pending_count']         = '%d suggestion(s) sans décision.';

$lang['nl_suggestions']           = 'Suggestions des relecteurs';
$lang['nl_no_suggestions']        = 'Aucune suggestion pour cette édition.';
$lang['nl_postponed']             = 'Suggestions d\'articles reportées des éditions précédentes';
$lang['nl_kind']                  = 'Nature';
$lang['nl_kind_remarque']         = 'Remarque';
$lang['nl_kind_article']          = 'Article';
$lang['nl_decision']              = 'Décision';
$lang['nl_decision_en_attente']   = 'En attente';
$lang['nl_decision_acceptee']     = 'Acceptée';
$lang['nl_decision_modifiee']     = 'Modifiée';
$lang['nl_decision_rejetee']      = 'Rejetée';
$lang['nl_decision_reportee']     = 'Reportée à l\'édition suivante';
$lang['nl_admin_text']            = 'Texte retenu (si modifiée)';
$lang['nl_btn_decide']            = 'Enregistrer';
$lang['nl_btn_attach']            = 'Reprendre dans cette édition';
$lang['nl_decision_saved']        = 'Décision enregistrée : %s.';
$lang['nl_decision_error_not_found']              = 'Suggestion introuvable.';
$lang['nl_decision_error_invalid_decision']       = 'Décision inconnue.';
$lang['nl_decision_error_postpone_article_only']  = 'Seule une suggestion d\'article peut être reportée. Choisissez la nature « Article ».';
$lang['nl_decision_error_modified_text_required'] = 'Saisissez le texte retenu pour une suggestion modifiée.';
$lang['nl_attach_ok']             = 'Suggestion reprise dans cette édition, en attente de décision.';
$lang['nl_attach_error']          = 'Impossible de reprendre cette suggestion (déjà rattachée à une édition ?).';

$lang['nl_no_reviewers']          = 'Aucun relecteur configuré. <a href="%s">Configurer les relecteurs</a>.';
$lang['nl_no_sender']             = 'Adresse d\'expédition non configurée (configuration SMTP de la newsletter).';
$lang['nl_send_not_draft']        = 'Seul un brouillon peut être transmis aux relecteurs.';
$lang['nl_send_success']          = 'Version %d transmise à : %s. Date limite de réponse : %s.';
$lang['nl_send_some_failed']      = 'Échec de l\'envoi à : %s.';
$lang['nl_send_all_failed']       = 'Aucun email n\'a pu être envoyé (%s). L\'édition reste à l\'état brouillon.';
$lang['nl_collect_not_reviewing'] = 'Les réponses ne se récupèrent que pour une édition en relecture.';
$lang['nl_collect_imap_error']    = 'Lecture de la boîte de veille impossible : %s';
$lang['nl_collect_summary']       = '%d message(s) lu(s) : %d réponse(s) retenue(s), %d écartée(s).';
$lang['nl_revise_not_reviewing']  = 'Une nouvelle version se demande pour une édition en relecture.';
$lang['nl_revise_nothing']        = 'Aucune suggestion acceptée ou modifiée dans ce tour : rien à transmettre à l\'agent IA.';
$lang['nl_revise_failed']         = 'L\'agent IA n\'a pas produit de nouvelle version : %s. Le brouillon courant est inchangé.';
$lang['nl_revise_truncated']      = 'La réponse de l\'agent IA a été tronquée (limite de longueur). Le brouillon courant est inchangé.';
$lang['nl_revise_save_error']     = 'La nouvelle version n\'a pas pu être enregistrée sur le serveur.';
$lang['nl_revise_success']        = 'Version %d créée à partir de %d suggestion(s) (%d jetons en entrée, %d en sortie). <a href="%s">Voir les différences</a>.';
$lang['nl_validate_not_draft']    = 'Seule une version à l\'état brouillon peut être validée.';
$lang['nl_validate_success']      = 'Version %d validée.';
$lang['nl_validate_error']        = 'La validation a échoué.';
$lang['nl_publish_not_approved']  = 'Seule une édition validée peut être publiée.';
$lang['nl_publish_write_error']   = 'Impossible d\'écrire le PDF dans %s.';
$lang['nl_publish_success']       = 'Newsletter publiée et envoyée à %d abonné(s). <a href="%s" target="_blank">Voir le PDF</a>.';
$lang['nl_delete_only_draft']     = 'Seule une édition à l\'état brouillon peut être supprimée.';
$lang['nl_edition_deleted']       = 'Édition « %s » supprimée.';

$lang['nl_import_title']          = 'Importer un brouillon';
$lang['nl_import_intro']          = 'Importez le brouillon de newsletter d\'une période produit par l\'agent IA de bureau, avec son rapport d\'activité et l\'inventaire mis à jour. Les entrées de l\'agent sont dans le <a href="%s">kit</a>.';
$lang['nl_import_context']        = 'L\'exécution sera rattachée aux consignes actives (v%d).';
$lang['nl_import_draft']          = 'Brouillon';
$lang['nl_import_report']         = 'Rapport d\'activité';
$lang['nl_import_inventory']      = 'Inventaire';
$lang['nl_model']                 = 'Modèle';
$lang['nl_cost']                  = 'Coût (USD)';
$lang['nl_btn_import']            = 'Importer';
$lang['nl_import_failed']         = 'L\'import a échoué :';
$lang['nl_import_success']        = 'Brouillon « %s » importé (exécution %s).';
$lang['nl_import_bad_cost']       = 'Le coût doit être un nombre.';
$lang['nl_upload_failed']         = 'Le fichier %s n\'a pas pu être reçu.';
$lang['nl_upload_bad_type']       = 'Le fichier %s n\'a pas un type accepté (%s).';
$lang['nl_upload_too_big']        = 'Le fichier %s dépasse 5 Mo.';

$lang['nl_context_title']         = 'Consignes de l\'agent';
$lang['nl_context_intro']         = 'Consignes fournies à l\'agent IA : registre des sources, flotte, critères de sélection, règles de rédaction. Chaque enregistrement crée une nouvelle version ; les précédentes restent consultables et restaurables.';
$lang['nl_context_content']       = 'Consignes (Markdown)';
$lang['nl_context_or_file']       = 'Ou remplacer par un fichier';
$lang['nl_context_comment']       = 'Motif de la modification';
$lang['nl_btn_save_context']      = 'Enregistrer une nouvelle version';
$lang['nl_btn_restore']           = 'Restaurer cette version';
$lang['nl_context_none']          = 'Aucune version enregistrée.';
$lang['nl_active']                = 'active';
$lang['nl_context_empty']         = 'Les consignes sont vides : rien n\'a été enregistré.';
$lang['nl_context_unchanged']     = 'Les consignes n\'ont pas changé : aucune nouvelle version créée.';
$lang['nl_context_saved']         = 'Version %d des consignes enregistrée et active.';
$lang['nl_context_save_error']    = 'Les consignes n\'ont pas pu être enregistrées.';
$lang['nl_context_version_title'] = 'Consignes — version %d';
$lang['nl_no_context_hint']       = 'Aucune consigne n\'est encore enregistrée dans GVV. <a href="%s">Enregistrer les consignes de l\'agent</a>.';

$lang['nl_kit_title']             = 'Kit pour l\'agent de bureau';
$lang['nl_kit_intro']             = 'Fournissez ces fichiers à l\'agent IA de bureau (Claude Desktop) avant de générer le brouillon d\'une période.';
$lang['nl_kit_context']           = 'Consignes de l\'agent';
$lang['nl_kit_context_version']   = 'Version %d du %s';
$lang['nl_kit_inventory']         = 'Inventaire courant';
$lang['nl_kit_inventory_from']    = 'Issu de l\'exécution %s';
$lang['nl_kit_inventory_none']    = 'Aucun inventaire importé : l\'agent fera un relevé initial.';
$lang['nl_kit_postponed']         = 'Suggestions d\'articles reportées';
$lang['nl_kit_postponed_count']   = '%d suggestion(s) en attente.';
$lang['nl_kit_then_import']       = 'Une fois le brouillon produit, <a href="%s">importez-le</a> avec le rapport d\'activité et l\'inventaire mis à jour.';

$lang['nl_runs_title']            = 'Exécutions de l\'agent';
$lang['nl_runs_none']             = 'Aucune exécution enregistrée.';
$lang['nl_run']                   = 'Exécution';
$lang['nl_origin']                = 'Origine';
$lang['nl_origin_desktop']        = 'Agent de bureau';
$lang['nl_origin_schedule']       = 'Calendrier';
$lang['nl_origin_manual']         = 'Lancement manuel';
$lang['nl_run_requested']         = 'Demandée';
$lang['nl_run_running']           = 'En cours';
$lang['nl_run_succeeded']         = 'Réussie';
$lang['nl_run_failed']            = 'En échec';
$lang['nl_imported_at']           = 'Enregistrée le';
$lang['nl_activity_report']       = 'Rapport d\'activité';
$lang['nl_no_report']             = 'Aucun rapport d\'activité n\'a été joint à cette exécution.';

$lang['nl_config_review_delay']   = 'Délai de réponse des relecteurs (jours)';
$lang['nl_config_storage_path']   = 'Répertoire de stockage des brouillons et exécutions';
$lang['nl_config_storage_help']   = 'Hors de l\'espace servi directement par le serveur web. Vide : répertoire par défaut indiqué.';
