<?php
/**
 * Language file for acceptance system (French)
 */

// Field labels
$lang['acceptance_title'] = 'Titre';
$lang['acceptance_category'] = 'Catégorie';
$lang['acceptance_target_type'] = 'Type cible';
$lang['acceptance_version_date'] = 'Date version';
$lang['acceptance_mandatory'] = 'Obligatoire';
$lang['acceptance_deadline'] = 'Date limite';
$lang['acceptance_dual_validation'] = 'Double validation';
$lang['acceptance_role_1'] = 'Rôle 1';
$lang['acceptance_role_2'] = 'Rôle 2';
$lang['acceptance_target_roles'] = 'Rôles cibles';
$lang['acceptance_active'] = 'Actif';
$lang['acceptance_created_by'] = 'Créé par';
$lang['acceptance_created_at'] = 'Créé le';
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
$lang['acceptance_add_item'] = 'Nouvel élément';
$lang['acceptance_edit_item'] = 'Modifier l\'élément';
$lang['acceptance_tracking'] = 'Suivi des acceptations';
$lang['acceptance_edit'] = 'Modifier';
$lang['acceptance_download_pdf'] = 'Télécharger PDF';
$lang['acceptance_current_pdf'] = 'PDF actuel';
$lang['acceptance_activate'] = 'Activer';
$lang['acceptance_deactivate'] = 'Désactiver';
$lang['acceptance_confirm_activate'] = 'Voulez-vous activer cet élément ?';
$lang['acceptance_confirm_deactivate'] = 'Voulez-vous désactiver cet élément ?';
$lang['acceptance_item_created'] = 'Élément créé avec succès';
$lang['acceptance_item_updated'] = 'Élément modifié avec succès';
$lang['acceptance_item_activated'] = 'Élément activé';
$lang['acceptance_item_deactivated'] = 'Élément désactivé';
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
$lang['acceptance_mandatory_help'] = 'Cet élément doit être accepté par les personnes ciblées';
$lang['acceptance_dual_validation_help'] = 'Nécessite la validation par deux personnes (ex: instructeur et élève)';
$lang['acceptance_role_1_placeholder'] = 'ex: instructeur';
$lang['acceptance_role_2_placeholder'] = 'ex: élève';
$lang['acceptance_target_roles_placeholder'] = 'ex: pilotes, instructeurs, bureau';
$lang['acceptance_target_roles_help'] = 'Rôles séparés par des virgules. Vide = tous les membres.';
$lang['acceptance_active_help'] = 'Seuls les éléments actifs sont présentés aux membres';

// Error messages
$lang['acceptance_error_title_required'] = 'Le titre est obligatoire';
$lang['acceptance_error_category_required'] = 'La catégorie est obligatoire';
$lang['acceptance_error_create'] = 'Erreur lors de la création';
$lang['acceptance_error_directory'] = 'Impossible de créer le répertoire de stockage';
$lang['acceptance_error_pilot_required'] = 'Veuillez sélectionner un pilote';
$lang['acceptance_error_link'] = 'Erreur lors du rattachement';

/* End of file acceptance_lang.php */
/* Location: ./application/language/french/acceptance_lang.php */
