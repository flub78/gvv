<?php
/**
 * Language file for acceptance system (French)
 */

// Field labels
$lang['acceptance_title'] = 'Titre';
$lang['acceptance_category'] = 'Catégorie';
$lang['acceptance_target_type'] = 'Type cible';
$lang['acceptance_version_date'] = 'Date version';
$lang['acceptance_mandatory'] = 'Niveau d\'obligation';
$lang['acceptance_mandatory_optional'] = 'Facultatif';
$lang['acceptance_mandatory_soft'] = 'Obligatoire non bloquant';
$lang['acceptance_mandatory_hard'] = 'Obligatoire bloquant';
$lang['acceptance_deadline'] = 'Date limite';
$lang['acceptance_dual_validation'] = 'Double validation';
$lang['acceptance_role_1'] = 'Rôle 1';
$lang['acceptance_role_2'] = 'Rôle 2';
$lang['acceptance_target_roles'] = 'Rôles cibles';
$lang['acceptance_target_user_login'] = 'Utilisateur cible';
$lang['acceptance_target_mode'] = 'Ciblage';
$lang['acceptance_target_mode_roles'] = 'Catégories';
$lang['acceptance_target_mode_user'] = 'Utilisateur individuel';
$lang['acceptance_active'] = 'Actif';
$lang['acceptance_created_by'] = 'Créé par';
$lang['acceptance_approvals'] = 'Approbations';
$lang['acceptance_created_at'] = 'Créé le';
$lang['acceptance_never_opened'] = 'Jamais consulté';
$lang['acceptance_updated_at'] = 'Modifié le';
$lang['acceptance_status'] = 'Statut';
$lang['acceptance_user'] = 'Utilisateur';
$lang['acceptance_external_name'] = 'Nom externe';
$lang['acceptance_validation_role'] = 'Rôle validation';
$lang['acceptance_formula'] = 'Formule';
$lang['acceptance_acted_at'] = 'Date action';
$lang['acceptance_initiated_by'] = 'Initié par';
$lang['acceptance_signature_mode'] = 'Mode signature';
$lang['acceptance_linked_pilot'] = 'Pilote rattaché';
$lang['acceptance_linked_by'] = 'Rattaché par';
$lang['acceptance_linked_at'] = 'Rattaché le';
$lang['acceptance_signer_first_name'] = 'Prénom signataire';
$lang['acceptance_signer_last_name'] = 'Nom signataire';
$lang['acceptance_signer_quality'] = 'Qualité';
$lang['acceptance_beneficiary_first_name'] = 'Prénom bénéficiaire';
$lang['acceptance_beneficiary_last_name'] = 'Nom bénéficiaire';
$lang['acceptance_signature_type'] = 'Type signature';
$lang['acceptance_signed_at'] = 'Signé le';
$lang['acceptance_pilot_attestation'] = 'Attestation pilote';
$lang['acceptance_token'] = 'Token';
$lang['acceptance_mode'] = 'Mode';
$lang['acceptance_expires_at'] = 'Expire le';
$lang['acceptance_used'] = 'Utilisé';
$lang['acceptance_used_at'] = 'Utilisé le';
$lang['acceptance_item'] = 'Élément';
$lang['acceptance_pdf_path'] = 'Fichier PDF';
$lang['acceptance_archived_document'] = 'Document archivé';

// Category enum values
$lang['acceptance_category_document'] = 'Document';
$lang['acceptance_category_formation'] = 'Formation';
$lang['acceptance_category_controle'] = 'Contrôle';
$lang['acceptance_category_briefing'] = 'Briefing';
$lang['acceptance_category_autorisation'] = 'Autorisation';

// Target type enum values
$lang['acceptance_target_type_internal'] = 'Interne';
$lang['acceptance_target_type_external'] = 'Externe';

// Status enum values
$lang['acceptance_status_pending'] = 'En attente';
$lang['acceptance_status_accepted'] = 'Accepté';
$lang['acceptance_status_refused'] = 'Refusé';

// Signature mode enum values
$lang['acceptance_mode_direct'] = 'Direct';
$lang['acceptance_mode_link'] = 'Lien';
$lang['acceptance_mode_qrcode'] = 'QR Code';
$lang['acceptance_mode_paper'] = 'Papier';

// Signature type enum values
$lang['acceptance_signature_tactile'] = 'Tactile';
$lang['acceptance_signature_upload'] = 'Upload';

// Messages
$lang['acceptance_no_items'] = 'Aucun élément';
$lang['acceptance_no_records'] = 'Aucun enregistrement';
$lang['acceptance_unknown_item'] = 'Élément inconnu';
$lang['acceptance_unknown_record'] = 'Enregistrement inconnu';

// Admin interface
$lang['acceptance_admin_title'] = 'Administration des acceptations';
$lang['acceptance_admin_menu'] = 'Acceptations';
$lang['acceptance_add_item'] = 'Ajout d\'une demande d\'approbation';
$lang['acceptance_choose_document'] = 'Choisir ce document';
$lang['acceptance_add_item_for_document'] = 'Nouvelle demande d\'acceptation pour ce document';
$lang['acceptance_filtered_by_document'] = 'Acceptations liées au document : %s';
$lang['acceptance_clear_document_filter'] = 'Voir toutes les acceptations';
$lang['acceptance_edit_item'] = 'Modifier l\'élément';
$lang['acceptance_tracking'] = 'Suivi des acceptations';
$lang['acceptance_edit'] = 'Modifier';
$lang['acceptance_download_pdf'] = 'Télécharger PDF';
$lang['acceptance_view_pdf'] = 'Visualiser le document';
$lang['acceptance_current_pdf'] = 'PDF actuel';
$lang['acceptance_activate'] = 'Activer';
$lang['acceptance_deactivate'] = 'Désactiver';
$lang['acceptance_delete'] = 'Supprimer';
$lang['acceptance_confirm_activate'] = 'Voulez-vous activer cet élément ?';
$lang['acceptance_confirm_deactivate'] = 'Voulez-vous désactiver cet élément ?';
$lang['acceptance_confirm_delete'] = 'Voulez-vous vraiment supprimer cet élément ? Les acceptations et refus déjà enregistrés pour cet élément seront également supprimés. Cette action est irréversible.';
$lang['acceptance_item_created'] = 'Élément créé avec succès';
$lang['acceptance_item_updated'] = 'Élément modifié avec succès';
$lang['acceptance_item_activated'] = 'Élément activé';
$lang['acceptance_item_deactivated'] = 'Élément désactivé';
$lang['acceptance_item_deleted'] = 'Élément supprimé';
$lang['acceptance_item_not_found'] = 'Élément introuvable';
$lang['acceptance_record_not_found'] = 'Enregistrement introuvable';
$lang['acceptance_pilot_linked'] = 'Acceptation rattachée au pilote avec succès';
$lang['acceptance_link_to_pilot'] = 'Rattacher à un pilote';
$lang['acceptance_back_to_list'] = 'Retour à la liste';
$lang['acceptance_total'] = 'Total';
$lang['acceptance_linked'] = 'Rattaché';
$lang['acceptance_unlinked'] = 'Non rattaché';
$lang['acceptance_link_status'] = 'Rattachement';
$lang['acceptance_overdue'] = 'En retard';
$lang['acceptance_filter_all'] = 'Tous';
$lang['acceptance_yes'] = 'Oui';
$lang['acceptance_no'] = 'Non';

// Form help texts
$lang['acceptance_pdf_help'] = 'Format PDF uniquement, 10 Mo maximum';
$lang['acceptance_archived_document_help'] = 'Ce document provient du module d\'archivage documentaire. Il ne peut pas être remplacé depuis ce formulaire.';
$lang['acceptance_mandatory_help'] = 'Facultatif : acceptation, refus ou report libres. Obligatoire non bloquant : le message du jour associé ne peut pas être masqué tant que la validation n\'est pas faite. Obligatoire bloquant : en plus, l\'accès à GVV est bloqué tant que l\'élément n\'est pas validé (sauf déconnexion et page de validation).';
$lang['acceptance_dual_validation_help'] = 'Nécessite la validation par deux personnes (ex: instructeur et élève)';
$lang['acceptance_role_1_placeholder'] = 'ex: instructeur';
$lang['acceptance_role_2_placeholder'] = 'ex: élève';
$lang['acceptance_target_roles_placeholder'] = 'ex: pilotes, instructeurs, bureau';
$lang['acceptance_target_roles_help'] = 'Cochez un ou plusieurs rôles, dans une section précise ou toutes sections. Vide = tous les membres.';
$lang['acceptance_target_user_help'] = 'Un élément cible soit un utilisateur individuel, soit une ou plusieurs catégories, jamais les deux.';
$lang['acceptance_select_document_help'] = 'Un nouvel élément à accepter doit référencer un document déjà archivé. Toutes sections confondues.';
$lang['acceptance_version_date_archived_help'] = 'Date de dépôt du document archivé, non modifiable depuis ce formulaire.';
$lang['acceptance_active_help'] = 'Seuls les éléments actifs sont présentés aux membres';

// Error messages
$lang['acceptance_error_title_required'] = 'Le titre est obligatoire';
$lang['acceptance_error_category_required'] = 'La catégorie est obligatoire';
$lang['acceptance_error_create'] = 'Erreur lors de la création';
$lang['acceptance_error_directory'] = 'Impossible de créer le répertoire de stockage';
$lang['acceptance_error_pilot_required'] = 'Veuillez sélectionner un pilote';
$lang['acceptance_error_link'] = 'Erreur lors du rattachement';
$lang['acceptance_reset_approval'] = 'Réinitialiser (redemander l\'approbation)';
$lang['acceptance_confirm_reset'] = 'Réinitialiser cette approbation ? La personne devra approuver à nouveau.';
$lang['acceptance_reset_success'] = 'Approbation réinitialisée, la personne devra approuver à nouveau.';
$lang['acceptance_error_reset'] = 'Erreur lors de la réinitialisation';
$lang['acceptance_archived_document_not_found'] = 'Document archivé introuvable';

// Member interface (Lot 4)
$lang['acceptance_menu_my_acceptances'] = 'Mes acceptations';
$lang['acceptance_dashboard_title'] = 'Éléments à accepter';
$lang['acceptance_dashboard_intro'] = 'Voici les éléments qui nécessitent votre validation.';
$lang['acceptance_dashboard_empty'] = 'Vous n\'avez aucun élément en attente d\'acceptation.';
$lang['acceptance_dashboard_deadline'] = 'À accepter avant le %s';
$lang['acceptance_btn_read_accept'] = 'Lire et accepter';
$lang['acceptance_btn_later'] = 'Plus tard';
$lang['acceptance_btn_later_help'] = 'Cet élément reste dans votre liste, vous pourrez le traiter plus tard.';
$lang['acceptance_read_instruction'] = 'Veuillez lire l\'intégralité du document. Le bouton d\'acceptation apparaîtra à la fin.';
$lang['acceptance_btn_accept'] = 'Accepter';
$lang['acceptance_btn_refuse'] = 'Refuser';
$lang['acceptance_confirm_refuse'] = 'Voulez-vous vraiment refuser cet élément ?';
$lang['acceptance_already_accepted'] = 'Vous avez accepté cet élément le %s.';
$lang['acceptance_already_refused'] = 'Vous avez refusé cet élément le %s.';
$lang['acceptance_accept_success'] = 'Acceptation enregistrée avec succès.';
$lang['acceptance_refuse_success'] = 'Refus enregistré.';
$lang['acceptance_history_title'] = 'Historique de mes acceptations';
$lang['acceptance_history_empty'] = 'Vous n\'avez encore traité aucun élément.';
$lang['acceptance_history_reread'] = 'Relire';
$lang['acceptance_back_to_list'] = 'Retour à la liste';
$lang['acceptance_formula_member'] = 'Je soussigné(e) %s, membre du club identifié par le système, reconnais avoir pris connaissance et accepter %s en date du %s.';
$lang['acceptance_motd_title'] = 'À accepter : %s';
$lang['acceptance_motd_content'] = 'Une validation est nécessaire : **%s**. [Lire et accepter](%s)';
$lang['acceptance_my_documents_title'] = 'Mes documents à accepter';
$lang['acceptance_my_documents_card_desc'] = 'Documents à valider';
$lang['acceptance_my_documents_empty'] = 'Vous n\'avez aucun document à accepter.';
$lang['acceptance_status_to_accept'] = 'À accepter';
$lang['acceptance_status_accepted_on'] = 'Accepté le %s';
$lang['acceptance_status_refused_on'] = 'Refusé le %s';
$lang['acceptance_error_no_member_record'] = 'Votre compte n\'est pas rattaché à une fiche membre : cette action n\'est pas disponible.';

/* End of file acceptance_lang.php */
/* Location: ./application/language/french/acceptance_lang.php */
