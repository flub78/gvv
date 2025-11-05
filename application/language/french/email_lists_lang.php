<?php
/**
 * French language file for Email Lists
 */

// General
$lang['email_lists_title'] = 'Listes de diffusion email';
$lang['email_lists_name'] = 'Nom de la liste';
$lang['email_lists_description'] = 'Description';
$lang['email_lists_active_member'] = 'Filtrer membres';
$lang['email_lists_visible'] = 'Visible';
$lang['email_lists_created'] = 'Créée le';
$lang['email_lists_updated'] = 'Mise à jour le';
$lang['email_lists_created_by'] = 'Créée par';
$lang['email_lists_recipient_count'] = 'Destinataires';

// Actions
$lang['email_lists_create'] = 'Nouvelle liste';
$lang['email_lists_edit'] = 'Modifier la liste';
$lang['email_lists_view'] = 'Voir la liste';
$lang['email_lists_delete'] = 'Supprimer';
$lang['email_lists_delete_confirm'] = 'Êtes-vous sûr de vouloir supprimer la liste {name} ?';
$lang['email_lists_export'] = 'Exporter';
$lang['email_lists_copy'] = 'Copier';
$lang['email_lists_back'] = 'Retour à la liste';

// Tabs
$lang['email_lists_tab_criteria'] = 'Par critères';
$lang['email_lists_tab_manual'] = 'Sélection manuelle';
$lang['email_lists_tab_external'] = 'Adresses externes';

// Criteria tab
$lang['email_lists_roles'] = 'Rôles';
$lang['email_lists_sections'] = 'Sections';
$lang['email_lists_select_roles'] = 'Sélectionner les rôles et sections';
$lang['email_lists_active_members_only'] = 'Membres actifs seulement';
$lang['email_lists_inactive_members_only'] = 'Membres inactifs seulement';
$lang['email_lists_all_members'] = 'Tous les membres';

// Manual tab
$lang['email_lists_manual_members'] = 'Membres ajoutés manuellement';
$lang['email_lists_add_member'] = 'Ajouter un membre';
$lang['email_lists_remove_member'] = 'Retirer';
$lang['email_lists_select_member'] = 'Sélectionner un membre';

// External tab
$lang['email_lists_external_emails'] = 'Adresses externes';
$lang['email_lists_add_external'] = 'Ajouter une adresse';
$lang['email_lists_external_email'] = 'Email';
$lang['email_lists_external_name'] = 'Nom';
$lang['email_lists_paste_emails'] = 'Coller les adresses (une par ligne)';

// Import tab
$lang['email_lists_external_addresses'] = 'Adresses externes';
$lang['email_lists_import_csv'] = 'Import CSV';
$lang['email_lists_upload_file'] = 'Télécharger un fichier';
$lang['email_lists_parse'] = 'Analyser';

// Export
$lang['email_lists_export_txt'] = 'Exporter TXT';
$lang['email_lists_export_md'] = 'Exporter Markdown';
$lang['email_lists_export_clipboard'] = 'Copier dans le presse-papier';
$lang['email_lists_separator'] = 'Séparateur';
$lang['email_lists_separator_comma'] = 'Virgule';
$lang['email_lists_separator_semicolon'] = 'Point-virgule';

// mailto
$lang['email_lists_mailto'] = 'Ouvrir client email';
$lang['email_lists_mailto_field'] = 'Champ';
$lang['email_lists_mailto_to'] = 'À (TO)';
$lang['email_lists_mailto_cc'] = 'Copie (CC)';
$lang['email_lists_mailto_bcc'] = 'Copie cachée (BCC)';
$lang['email_lists_mailto_subject'] = 'Sujet';
$lang['email_lists_mailto_body'] = 'Corps du message';
$lang['email_lists_mailto_reply_to'] = 'Répondre à';
$lang['email_lists_mailto_save_prefs'] = 'Sauvegarder les préférences';

// Chunking
$lang['email_lists_chunk_size'] = 'Taille des parties';
$lang['email_lists_chunk_part'] = 'Partie';
$lang['email_lists_chunk_of'] = 'sur';

// Messages
$lang['email_lists_create_success'] = 'Liste créée avec succès';
$lang['email_lists_create_error'] = 'Erreur lors de la création de la liste';
$lang['email_lists_update_success'] = 'Liste mise à jour avec succès';
$lang['email_lists_update_error'] = 'Erreur lors de la mise à jour de la liste';
$lang['email_lists_delete_success'] = 'Liste supprimée avec succès';
$lang['email_lists_delete_error'] = 'Erreur lors de la suppression de la liste';
$lang['email_lists_copy_success'] = 'Adresses copiées dans le presse-papier';
$lang['email_lists_copy_error'] = 'Erreur lors de la copie';
$lang['email_lists_no_recipients'] = 'Aucun destinataire';
$lang['email_lists_empty_list'] = 'Cette liste ne contient aucun destinataire';

// Validation
$lang['email_lists_name_required'] = 'Le nom est obligatoire';
$lang['email_lists_name_duplicate'] = 'Ce nom de liste existe déjà. Veuillez choisir un nom différent.';
$lang['email_lists_invalid_email'] = 'Adresse email invalide';

// View labels
$lang['email_lists_sources'] = 'Sources de la liste';
$lang['email_lists_source_roles'] = 'Par rôles';
$lang['email_lists_source_manual'] = 'Membres manuels';
$lang['email_lists_source_external'] = 'Adresses externes';
$lang['email_lists_total'] = 'Total';
$lang['email_lists_recipients_list'] = 'Liste des destinataires';
$lang['email_lists_recipients'] = 'destinataires';
$lang['email_lists_actions'] = 'Actions';
$lang['email_lists_no_lists'] = 'Aucune liste de diffusion disponible';
$lang['email_lists_email_addresses'] = 'Adresses email';
$lang['email_lists_criteria_help'] = 'Sélectionnez les rôles et sections pour inclure automatiquement les membres correspondants';
$lang['email_lists_no_roles_available'] = 'Aucun rôle disponible';
$lang['email_lists_global_roles'] = 'Rôles globaux';
$lang['email_lists_no_roles_for_section'] = 'Aucun rôle disponible pour cette section';
$lang['email_lists_preview_count'] = 'Prévisualiser le nombre';
$lang['email_lists_select_at_least_one_role'] = 'Sélectionnez au moins un rôle';
$lang['email_lists_preview_error'] = 'Erreur lors de la prévisualisation';
$lang['email_lists_manual_help'] = 'Ajoutez des membres spécifiques à cette liste';
$lang['email_lists_select_member_first'] = 'Veuillez sélectionner un membre';
$lang['email_lists_member_already_added'] = 'Ce membre est déjà dans la liste';
$lang['email_lists_external_help'] = 'Ajoutez des adresses email externes (non-membres)';
$lang['email_lists_enter_email'] = 'Veuillez saisir une adresse email';
$lang['email_lists_import_pasted'] = 'Importer les adresses';
$lang['email_lists_emails_added'] = 'adresses ajoutées';
$lang['email_lists_emails_invalid'] = 'adresses invalides';
$lang['email_lists_external_addresses_help'] = 'Saisissez ou collez des adresses, une par ligne. Les adresses peuvent être suivies par un nom.';
$lang['email_lists_paste_addresses'] = 'Saisissez ou collez les adresses ici';
$lang['email_lists_import_csv_help'] = 'Collez du CSV avec colonnes configurables';
$lang['email_lists_paste_csv'] = 'Collez le CSV ici';
$lang['email_lists_csv_delimiter'] = 'Délimiteur';
$lang['email_lists_comma'] = 'Virgule';
$lang['email_lists_semicolon'] = 'Point-virgule';
$lang['email_lists_tab'] = 'Tabulation';
$lang['email_lists_email_column'] = 'Colonne email';
$lang['email_lists_name_column'] = 'Colonne nom';
$lang['email_lists_column_index_help'] = '0 = première colonne';
$lang['email_lists_column_optional'] = '-1 si pas de colonne nom';
$lang['email_lists_csv_has_header'] = 'Le CSV contient une ligne d\'en-tête';
$lang['email_lists_parse_import'] = 'Analyser et importer';
$lang['email_lists_import_results'] = 'Résultats de l\'import';
$lang['email_lists_valid_emails'] = 'Adresses valides';
$lang['email_lists_errors'] = 'Erreurs';
$lang['email_lists_show_errors'] = 'Afficher les erreurs';
$lang['email_lists_preview'] = 'Aperçu';
$lang['email_lists_confirm_import'] = 'Confirmer l\'import';
$lang['email_lists_no_text_to_import'] = 'Aucun texte à importer';
$lang['email_lists_no_csv_to_import'] = 'Aucun CSV à importer';
$lang['email_lists_emails_imported'] = 'adresses importées';
$lang['email_lists_chunk_emails'] = 'Découper la liste';
$lang['email_lists_showing'] = 'Affichage';
$lang['email_lists_mailto_help'] = 'Ouvre votre client email avec les adresses pré-remplies';
$lang['email_lists_mailto_too_long'] = 'Liste trop longue pour mailto. Copier dans le presse-papier à la place ?';
$lang['email_lists_prefs_saved'] = 'Préférences sauvegardées';

// Preview panel
$lang['email_lists_list_under_construction'] = 'Liste en construction';
$lang['email_lists_total_recipients'] = 'Total destinataires';
$lang['email_lists_from_criteria'] = 'Depuis critères';
$lang['email_lists_select_criteria_to_preview'] = 'Sélectionnez des critères pour prévisualiser la liste';
$lang['email_lists_refresh_preview'] = 'Actualiser l\'aperçu';
$lang['email_lists_delete_via_tabs_hint'] = 'Pour supprimer des adresses, utilisez les icônes dans les onglets sources';
$lang['email_lists_email'] = 'Email';
$lang['email_lists_name'] = 'Nom';

// Manual tab - external addresses (v1.3)
$lang['email_lists_bulk_import_hint'] = 'Pour importer plusieurs adresses à la fois, utilisez l\'onglet "Import de fichiers"';

// Import tab (v1.3 - file upload)
$lang['email_lists_import_files'] = 'Import de fichiers';
$lang['email_lists_import_files_help'] = 'Importez des adresses depuis un fichier texte ou CSV';
$lang['email_lists_choose_file'] = 'Choisir un fichier';
$lang['email_lists_upload_button'] = 'Importer';
$lang['email_lists_accepted_formats'] = 'Formats acceptés';
$lang['email_lists_uploaded_files'] = 'Fichiers importés';
$lang['email_lists_no_files_uploaded'] = 'Aucun fichier importé';
$lang['email_lists_uploaded_on'] = 'Importé le';
$lang['email_lists_addresses_count'] = 'Nombre d\'adresses';
$lang['email_lists_delete_file'] = 'Supprimer le fichier';
$lang['email_lists_save_before_upload'] = 'Veuillez sauvegarder la liste avant d\'importer des fichiers';
$lang['email_lists_uploading'] = 'Importation en cours';
$lang['email_lists_file_uploaded_success'] = 'Fichier importé avec succès !';
$lang['email_lists_addresses_imported'] = 'adresses importées';
$lang['email_lists_addresses_invalid'] = 'adresses invalides';
$lang['email_lists_invalid_file_format'] = 'Format de fichier invalide. Seuls .txt et .csv sont acceptés.';
$lang['email_lists_upload_error'] = 'Erreur lors de l\'import du fichier';
$lang['email_lists_confirm_delete_file'] = 'Êtes-vous sûr de vouloir supprimer ce fichier et toutes ses adresses associées ?';
$lang['email_lists_file_deleted'] = 'Fichier supprimé avec succès';
$lang['email_lists_addresses'] = 'adresses';

// Workflow v1.4 - Separation creation/modification
$lang['email_lists_add_remove_addresses'] = 'Ajout et suppression d\'adresses email';
$lang['email_lists_save_first_to_add_addresses'] = 'Veuillez d\'abord enregistrer la liste avant de pouvoir ajouter des adresses email';

// Addresses view
$lang['email_lists_select_list'] = 'Liste de destinataires';
$lang['email_lists_select_list_placeholder'] = 'Sélectionnez une liste';
