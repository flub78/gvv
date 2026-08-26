<?php
/**
 * Language file for forms module (French)
 */

// General
$lang['forms_title_forms']                = 'Formulaires';
$lang['forms_subtitle_admin']             = 'Administration du module formulaires.';
$lang['forms_list_forms_title']           = 'Liste des formulaires';

// Status
$lang['forms_status_draft']               = 'Brouillon';
$lang['forms_status_published']           = 'Publié';
$lang['forms_status_archived']            = 'Archivé';

// Labels - common
$lang['forms_label_code']                 = 'Code';
$lang['forms_label_title']                = 'Titre';
$lang['forms_label_description']          = 'Description';
$lang['forms_label_section']              = 'Section';
$lang['forms_label_status']               = 'Statut';
$lang['forms_label_public_link']          = 'Lien public';
$lang['forms_label_actions']              = 'Actions';
$lang['forms_label_global']               = 'Global';
$lang['forms_label_id']                   = 'ID';
$lang['forms_label_uuid']                 = 'UUID';
$lang['forms_label_date']                 = 'Date';
$lang['forms_label_last_modified']        = 'Dernière modification';
$lang['forms_label_type']                 = 'Type';
$lang['forms_label_value']                = 'Valeur';
$lang['forms_label_field']                = 'Champ';
$lang['forms_label_order']                = 'Ordre';
$lang['forms_label_label']                = 'Libellé';
$lang['forms_label_technical_name']       = 'Nom technique';
$lang['forms_label_required']             = 'Requis';
$lang['forms_label_page_number']          = 'Numéro de page';
$lang['forms_label_format']               = 'Format source';
$lang['forms_label_content']              = 'Contenu';
$lang['forms_label_preview']              = 'Aperçu';
$lang['forms_label_submitted_by']         = 'Soumis par';
$lang['forms_label_anonymous']            = 'Anonyme';
$lang['forms_label_reference']            = 'Référence';
$lang['forms_label_files_attached']       = 'Fichiers joints';
$lang['forms_label_css_scope']            = 'CSS scope';
$lang['forms_label_display_order']        = 'Ordre d\'affichage';
$lang['forms_label_options']              = 'Options';
$lang['forms_label_name_optional']        = 'Nom (optionnel)';
$lang['forms_label_email_optional']       = 'Email (optionnel)';
$lang['forms_label_page']                 = 'Page';
$lang['forms_label_number']               = '#';
$lang['forms_label_identifier']           = 'Identification';
$lang['forms_checkbox_identifier']        = 'Identifiant de réponse';
$lang['forms_help_identifier']            = 'Les champs cochés sont concaténés (séparés par un espace) pour former l\'identifiant visible dans la liste des réponses.';
$lang['forms_help_fields_readonly']       = 'Liste en lecture seule, détectée automatiquement depuis le HTML de la page. Pour modifier un champ (nom, type, obligatoire), éditez le HTML de la page.';

// Buttons
$lang['forms_button_new_form']            = 'Nouveau formulaire';
$lang['forms_button_edit']                = 'Modifier';
$lang['forms_button_save']                = 'Enregistrer';
$lang['forms_button_create']              = 'Créer';
$lang['forms_button_cancel']              = 'Annuler';
$lang['forms_button_delete']              = 'Supprimer';
$lang['forms_button_duplicate']           = 'Dupliquer';
$lang['forms_button_publish']             = 'Publier';
$lang['forms_button_copy_link']           = 'Copier le lien';
$lang['forms_button_manage_pages']        = 'Gérer les pages';
$lang['forms_button_view_responses']      = 'Voir les réponses';
$lang['forms_button_preview_css']         = 'Preview CSS';
$lang['forms_button_export_html']         = 'Export HTML';
$lang['forms_button_export_txt']          = 'Export TXT';
$lang['forms_button_backup']              = 'Sauvegarder (ZIP)';
$lang['forms_button_restore']             = 'Restaurer depuis ZIP';
$lang['forms_button_upload_image']        = 'Envoyer l\'image';
$lang['forms_button_import_html']         = 'Import depuis HTML';
$lang['forms_button_import_zip']          = 'Import depuis sauvegarde';
$lang['forms_title_import_zip']           = 'Créer un formulaire depuis une sauvegarde';
$lang['forms_button_add_field']           = 'Ajouter un champ';
$lang['forms_button_fields']              = 'Champs';
$lang['forms_button_responses']           = 'Réponses';
$lang['forms_button_open']                = 'Ouvrir';
$lang['forms_button_close']               = 'Fermer';
$lang['forms_button_preview']             = 'Preview';
$lang['forms_button_download']            = 'Télécharger';
$lang['forms_button_back_responses']      = 'Voir les réponses';
$lang['forms_button_back_to_responses']   = 'Voir les réponses';
$lang['forms_button_back_form']           = 'Modification du formulaire';
$lang['forms_button_back_pages']          = 'Retour pages';
$lang['forms_button_back_fields']         = 'Retour champs';
$lang['forms_button_pdf']                 = 'Générer PDF';
$lang['forms_button_back_submissions']    = 'Retour réponses';
$lang['forms_button_back_edit']           = 'Retour édition';
$lang['forms_button_previous_page']       = 'Page précédente';
$lang['forms_button_next_page']           = 'Page suivante';
$lang['forms_button_submit']              = 'Envoyer ma réponse';
$lang['forms_button_pages']               = 'Pages';
$lang['forms_button_upload_response']     = 'Télécharger un formulaire prérempli';
$lang['forms_button_edit_submission']     = 'Modifier';
$lang['forms_edit_button_save']           = 'Enregistrer les modifications';

// Upload response modal
$lang['forms_upload_modal_title']         = 'Télécharger un formulaire prérempli';
$lang['forms_upload_modal_comment_label'] = 'Commentaire (optionnel)';
$lang['forms_upload_modal_submit']        = 'Valider';
$lang['forms_upload_error_disabled']      = 'Ce formulaire n\'accepte pas les réponses par téléchargement.';
$lang['forms_upload_error_no_file']       = 'Veuillez sélectionner un fichier à téléverser.';
$lang['forms_upload_error_storage']       = 'Impossible de préparer le répertoire de stockage.';
$lang['forms_upload_error_generic']       = 'Impossible d\'enregistrer votre réponse pour le moment.';
$lang['forms_upload_error_file_type']     = 'Fichier refusé (formats acceptés : PDF, JPG, PNG, GIF, WEBP).';

// Blank PDF template (Lot 16 / EF18)
$lang['forms_title_pdf_template']         = 'Formulaire vierge (PDF)';
$lang['forms_help_pdf_template']          = 'PDF vierge (formulaire imprimable) proposé au téléchargement sur la page publique lorsque la soumission par téléchargement est activée. Un seul fichier : un nouvel envoi remplace le précédent. Facultatif — 10 Mo maximum.';
$lang['forms_button_upload_pdf_template'] = 'Envoyer le PDF';
$lang['forms_button_download_pdf_template'] = 'Télécharger le PDF actuel';
$lang['forms_confirm_delete_pdf_template'] = 'Supprimer le PDF vierge ? Le lien de téléchargement disparaîtra de la page publique.';
$lang['forms_success_pdf_template_uploaded'] = 'PDF vierge enregistré.';
$lang['forms_success_pdf_template_deleted'] = 'PDF vierge supprimé.';
$lang['forms_error_pdf_template_missing'] = 'Veuillez sélectionner un fichier PDF.';
$lang['forms_error_pdf_template_too_large'] = 'Fichier trop volumineux (10 Mo maximum).';
$lang['forms_error_pdf_template_invalid'] = 'Le fichier doit être un PDF valide.';
$lang['forms_button_download_blank_pdf']  = 'Télécharger le formulaire vierge pour soumission papier (PDF)';

// Titles
$lang['forms_title_new_form']             = 'Nouveau formulaire';
$lang['forms_title_edit_form']            = 'Modifier le formulaire';
$lang['forms_title_pages']                = 'Pages du formulaire';
$lang['forms_title_content_archive']      = 'Contenu du formulaire (archive)';
$lang['forms_title_images']               = 'Images';
$lang['forms_title_fields']               = 'Champs — page';
$lang['forms_title_import_html']          = 'Créer un formulaire depuis une page HTML';
$lang['forms_title_add_field']            = 'Ajouter un champ';
$lang['forms_title_edit_field']           = 'Modifier le champ';
$lang['forms_title_submissions']          = 'Réponses au formulaire';
$lang['forms_title_submission_detail']    = 'Détail de réponse';
$lang['forms_edit_title']                 = 'Modifier la réponse';
$lang['forms_title_preview']              = 'Prévisualisation';
$lang['forms_title_thank_you']            = 'Merci pour votre réponse';

// Sections
$lang['forms_section_submitted_values']   = 'Valeurs soumises';
$lang['forms_section_uploaded_files']     = 'Fichiers uploadés';
$lang['forms_section_received_files']     = 'Fichiers reçus';
$lang['forms_section_submission']         = 'Soumission #';

// Empty states
$lang['forms_empty_no_forms']             = 'Aucun formulaire.';
$lang['forms_empty_section']              = 'Aucun formulaire pour la section active (et aucun formulaire global).';
$lang['forms_empty_no_pages']             = 'Aucune page pour ce formulaire.';
$lang['forms_empty_no_fields']            = 'Aucun champ défini pour cette page.';
$lang['forms_empty_no_submissions']       = 'Aucune réponse enregistrée.';
$lang['forms_empty_no_values']            = 'Aucune valeur.';
$lang['forms_empty_no_files']             = 'Aucun fichier pour cette soumission.';

// Alerts and messages
$lang['forms_alert_section_active']       = 'Section active :';
$lang['forms_alert_no_section']           = 'Aucune section active : le formulaire sera créé comme global.';
$lang['forms_alert_global_checkbox']      = 'Choisissez "Globale (toutes sections)" dans le champ Section pour créer un formulaire visible dans toutes les sections.';
$lang['forms_alert_published_warning']    = 'Ce formulaire est <strong>publié</strong> et accessible via le lien public. Toute modification est immédiatement visible.';
$lang['forms_alert_preview_mode']         = 'Mode prévisualisation — le formulaire n\'est pas soumissible ici.';
$lang['forms_alert_no_pages']             = 'Ce formulaire ne contient aucune page.';
$lang['forms_alert_no_content']           = 'Aucun contenu configuré sur cette page.';
$lang['forms_alert_no_fields_page']       = 'Aucun champ configuré sur cette page.';
$lang['forms_message_submitted']          = 'Votre formulaire "%s" a bien été enregistré.';
$lang['forms_message_no_preview']         = 'Aperçu inline non disponible pour ce type de fichier.';

// Confirmations
$lang['forms_confirm_delete_form']        = 'Supprimer ce formulaire ?';
$lang['forms_confirm_delete_workflow_form'] = 'Attention : ce formulaire est utilisé par un workflow GVV (briefing passager). Le supprimer rendra cette fonctionnalité indisponible. Continuer ?';
$lang['forms_confirm_unpublish_workflow_form'] = 'Attention : ce formulaire est utilisé par un workflow GVV (briefing passager). Le dépublier ou l\'archiver rendra cette fonctionnalité indisponible. Continuer ?';
$lang['forms_confirm_delete_page']        = 'Supprimer cette page ?';
$lang['forms_confirm_delete_field']       = 'Supprimer ce champ ?';
$lang['forms_modal_title_delete']         = 'Supprimer la réponse';
$lang['forms_modal_confirm_delete']       = 'Confirmer la suppression de la réponse';
$lang['forms_modal_help_delete']          = 'Cette action est irréversible et supprime également les fichiers associés.';

// Field types
$lang['forms_type_text']                  = 'Texte';
$lang['forms_type_email']                 = 'Email';
$lang['forms_type_date']                  = 'Date';
$lang['forms_type_number']                = 'Nombre';
$lang['forms_type_textarea']              = 'Zone de texte';
$lang['forms_type_select']                = 'Liste déroulante';
$lang['forms_type_radio']                 = 'Boutons radio';
$lang['forms_type_checkbox']              = 'Cases à cocher';
$lang['forms_type_file']                  = 'Fichier';

// Help texts
$lang['forms_help_code']                  = 'Identifiant stable en snake_case ou kebab-case.';
$lang['forms_help_technical_name']        = 'Lettres, chiffres, underscore, tiret. Utilisé comme identifiant du champ.';
$lang['forms_help_display_order']         = 'Laissez vide pour ajouter en fin de liste.';
$lang['forms_help_options_format']        = '(une par ligne)';
$lang['forms_help_options_usage']         = 'Chaque ligne correspond à une option proposée à l\'utilisateur.';
$lang['forms_help_content_archive']       = 'Les pages, le CSS et les métadonnées de contenu du formulaire s\'éditent uniquement par dépôt d\'archive (ZIP) — plus de zone de texte HTML/CSS dans GVV. Téléchargez l\'archive actuelle, modifiez-la (avec un éditeur de texte ou un assistant IA), puis déposez-la : c\'est le fonctionnement normal pour faire évoluer un formulaire, pas seulement un secours.';
$lang['forms_help_restore']              = 'Remplace intégralement le contenu (titre, description, CSS, pages, images) par celui de l\'archive déposée. Le code, le statut et le lien public restent inchangés.';
$lang['forms_help_backup']                = 'Télécharge le contenu actuel du formulaire (métadonnées, CSS, pages HTML et images) dans un fichier ZIP, prêt à être modifié puis redéposé.';
$lang['forms_confirm_restore']            = 'Déposer cette archive ? Le contenu actuel (pages, CSS, images) sera remplacé.';
$lang['forms_help_pages_via_archive']     = 'Le contenu des pages s\'édite par dépôt d\'archive, pas depuis cette liste — voir la section';
$lang['forms_help_images']                = 'Images utilisables dans le HTML des pages (logo, etc.). Copiez le chemin affiché sous chaque image dans un attribut src="..." — il correspond au dossier images/ de l\'archive du formulaire. Formats acceptés : PNG, JPEG, GIF, WEBP — 2 Mo maximum.';
$lang['forms_confirm_delete_image']       = 'Supprimer cette image ? Les pages qui la référencent ne l\'afficheront plus.';

// Form fields
$lang['forms_checkbox_global_form']       = 'Formulaire global (non rattaché à une section)';
$lang['forms_checkbox_allow_upload_response'] = 'Autoriser la soumission par téléchargement (scan)';
$lang['forms_help_allow_upload_response'] = 'Permet à l\'utilisateur de télécharger un scan ou une photo du formulaire imprimé et rempli à la main, à la place de la saisie en ligne.';
$lang['forms_checkbox_required']          = 'Champ obligatoire';
$lang['forms_subtitle_form_container']    = 'Création du conteneur formulaire avant ajout des pages et champs.';
$lang['forms_placeholder_select']         = 'Sélectionner...';
$lang['forms_label_yes']                  = 'Oui';
$lang['forms_label_no']                   = 'Non';
$lang['forms_unit_bytes']                 = 'octets';
$lang['forms_label_form_context']         = 'Formulaire :';

// Config params
$lang['forms_config_title']               = 'Paramètres de configuration';
$lang['forms_config_subtitle']            = 'Valeurs configurables utilisées dans les formulaires via la source config.*.';
$lang['forms_config_label_key']           = 'Clé technique';
$lang['forms_config_label_value']         = 'Valeur';
$lang['forms_config_label_label']         = 'Libellé';
$lang['forms_config_label_description']   = 'Description';
$lang['forms_config_label_scope']         = 'Portée';
$lang['forms_config_scope_global']        = 'Global';
$lang['forms_config_help_key']            = 'Identifiant alphanumérique (lettres, chiffres, _). Ex : organisme_formation';
$lang['forms_config_help_source']         = 'Référencez ce paramètre dans un formulaire avec data-gvv-source="config.CLÉICI"';
$lang['forms_config_empty']               = 'Aucun paramètre de configuration défini.';
$lang['forms_config_button_new']          = 'Nouveau paramètre';
$lang['forms_config_button_edit']         = 'Modifier';
$lang['forms_config_button_delete']       = 'Supprimer';
$lang['forms_config_button_save']         = 'Enregistrer';
$lang['forms_config_button_cancel']       = 'Annuler';
$lang['forms_config_confirm_delete']      = 'Supprimer ce paramètre ?';
$lang['forms_config_created']             = 'Paramètre créé.';
$lang['forms_config_updated']             = 'Paramètre mis à jour.';
$lang['forms_config_deleted']             = 'Paramètre supprimé.';
$lang['forms_config_error_key_exists']    = 'Cette clé existe déjà pour cette portée.';
$lang['forms_config_error_key_required']  = 'La clé technique est obligatoire.';
$lang['forms_config_error_label_required']= 'Le libellé est obligatoire.';
$lang['forms_config_error_invalid_key']   = 'La clé ne doit contenir que des lettres, chiffres et _.';
$lang['forms_config_card_title']          = 'Configuration';
$lang['forms_config_card_description']    = 'Paramètres clé/valeur utilisables dans les formulaires.';

/* required_params */
$lang['forms_label_required_params']  = 'Contexte GVV';
$lang['forms_help_required_params']   = 'Sélecteurs nécessaires pour pré-remplir les champs issus de GVV (membres, instructeurs, événements).';
$lang['forms_required_none']          = 'Formulaire public (sans pré-remplissage GVV)';
$lang['forms_required_pilot']         = 'Sélection d\'un membre (candidat/pilote)';
$lang['forms_required_instructor']    = 'Sélection d\'un instructeur';
$lang['forms_required_both']          = 'Sélection membre + instructeur';
$lang['forms_required_machine']       = 'Sélection d\'une machine';
$lang['forms_required_pilot_machine'] = 'Sélection membre + machine';
$lang['forms_required_instructor_machine'] = 'Sélection instructeur + machine';
$lang['forms_required_all']           = 'Sélection membre + instructeur + machine';

/* handler_class */
$lang['forms_label_handler_class']    = 'Traitement après soumission';
$lang['forms_help_handler_class']     = 'Déclenche une action GVV (ex. mise à jour d\'un vol de découverte) juste après l\'enregistrement de la réponse.';
$lang['forms_handler_class_none']     = 'Aucun';

/* generate page */
$lang['forms_button_generate']          = 'Générer';
$lang['forms_generate_title']           = 'Générer un formulaire pré-rempli';
$lang['forms_generate_pilot']           = 'Candidat / Pilote';
$lang['forms_generate_instructor']      = 'Instructeur';
$lang['forms_generate_machine']         = 'Machine';
$lang['forms_generate_button']          = 'Remplir le formulaire';
$lang['forms_generate_select_placeholder'] = '— Sélectionner —';
$lang['forms_generate_error_not_found'] = 'Formulaire introuvable ou non publié.';
$lang['forms_generate_error_pilot']     = 'Veuillez sélectionner un candidat.';
$lang['forms_generate_error_instructor']= 'Veuillez sélectionner un instructeur.';
$lang['forms_generate_error_machine']   = 'Veuillez sélectionner une machine.';

/* export vers formulaire de creation GVV (Lot 12) */
$lang['forms_label_target_url']   = 'Formulaire de création cible (export)';
$lang['forms_label_target_label'] = 'Libellé du bouton export';
$lang['forms_help_target_export'] = 'Si les deux champs sont renseignés, un bouton apparaît sur chaque réponse pour ouvrir ce formulaire GVV pré-rempli avec les valeurs de la réponse (ex : membre/create).';

/* sous-formulaires (Lot 11) */
$lang['forms_badge_subform_unattached']      = 'Non rattaché';
$lang['forms_help_badge_subform_unattached'] = 'Cette réponse a été soumise comme sous-formulaire, mais son formulaire maître n\'a jamais été validé.';

/* End of file forms_lang.php */
/* Location: ./application/language/french/forms_lang.php */
