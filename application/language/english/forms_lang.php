<?php
/**
 * Language file for forms module (English)
 */

// General
$lang['forms_title_forms']                = 'Forms';
$lang['forms_subtitle_admin']             = 'Forms module administration.';
$lang['forms_list_forms_title']           = 'Forms list';

// Status
$lang['forms_status_draft']               = 'Draft';
$lang['forms_status_published']           = 'Published';
$lang['forms_status_archived']            = 'Archived';

// Labels - common
$lang['forms_label_code']                 = 'Code';
$lang['forms_label_title']                = 'Title';
$lang['forms_label_description']          = 'Description';
$lang['forms_label_section']              = 'Section';
$lang['forms_label_status']               = 'Status';
$lang['forms_label_public_link']          = 'Public link';
$lang['forms_label_actions']              = 'Actions';
$lang['forms_label_global']               = 'Global';
$lang['forms_label_id']                   = 'ID';
$lang['forms_label_uuid']                 = 'UUID';
$lang['forms_label_date']                 = 'Date';
$lang['forms_label_last_modified']        = 'Last modified';
$lang['forms_label_type']                 = 'Type';
$lang['forms_label_value']                = 'Value';
$lang['forms_label_field']                = 'Field';
$lang['forms_label_order']                = 'Order';
$lang['forms_label_label']                = 'Label';
$lang['forms_label_technical_name']       = 'Technical name';
$lang['forms_label_required']             = 'Required';
$lang['forms_label_page_number']          = 'Page number';
$lang['forms_label_format']               = 'Source format';
$lang['forms_label_content']              = 'Content';
$lang['forms_label_preview']              = 'Preview';
$lang['forms_label_submitted_by']         = 'Submitted by';
$lang['forms_label_anonymous']            = 'Anonymous';
$lang['forms_label_reference']            = 'Reference';
$lang['forms_label_files_attached']       = 'Attached files';
$lang['forms_label_css_scope']            = 'CSS scope';
$lang['forms_label_display_order']        = 'Display order';
$lang['forms_label_options']              = 'Options';
$lang['forms_label_name_optional']        = 'Name (optional)';
$lang['forms_label_email_optional']       = 'Email (optional)';
$lang['forms_label_page']                 = 'Page';
$lang['forms_label_number']               = '#';
$lang['forms_label_identifier']           = 'Identification';
$lang['forms_checkbox_identifier']        = 'Response identifier';
$lang['forms_help_identifier']            = 'Checked fields are concatenated (space-separated) to form the identifier shown in the submissions list.';
$lang['forms_help_fields_readonly']       = 'Read-only list, auto-detected from the page HTML. To change a field (name, type, required), edit the page HTML.';

// Buttons
$lang['forms_button_new_form']            = 'New form';
$lang['forms_button_edit']                = 'Edit';
$lang['forms_button_save']                = 'Save';
$lang['forms_button_create']              = 'Create';
$lang['forms_button_cancel']              = 'Cancel';
$lang['forms_button_delete']              = 'Delete';
$lang['forms_button_duplicate']           = 'Duplicate';
$lang['forms_button_publish']             = 'Publish';
$lang['forms_button_copy_link']           = 'Copy link';
$lang['forms_button_manage_pages']        = 'Manage pages';
$lang['forms_button_view_responses']      = 'View responses';
$lang['forms_button_preview_css']         = 'CSS preview';
$lang['forms_button_export_html']         = 'Export HTML';
$lang['forms_button_export_txt']          = 'Export TXT';
$lang['forms_button_backup']              = 'Backup (ZIP)';
$lang['forms_button_restore']             = 'Restore from ZIP';
$lang['forms_button_upload_image']        = 'Upload image';
$lang['forms_button_import_html']         = 'Import from HTML';
$lang['forms_button_import_zip']          = 'Import from backup';
$lang['forms_title_import_zip']           = 'Create a form from a backup';
$lang['forms_button_add_field']           = 'Add field';
$lang['forms_button_fields']              = 'Fields';
$lang['forms_button_responses']           = 'Responses';
$lang['forms_button_open']                = 'Open';
$lang['forms_button_close']               = 'Close';
$lang['forms_button_preview']             = 'Preview';
$lang['forms_button_download']            = 'Download';
$lang['forms_button_back_responses']      = 'View responses';
$lang['forms_button_back_to_responses']   = 'View responses';
$lang['forms_button_back_form']           = 'Edit form';
$lang['forms_button_back_pages']          = 'Back to pages';
$lang['forms_button_back_fields']         = 'Back to fields';
$lang['forms_button_pdf']                 = 'Generate PDF';
$lang['forms_button_back_submissions']    = 'Back to responses';
$lang['forms_button_back_edit']           = 'Back to edit';
$lang['forms_button_previous_page']       = 'Previous page';
$lang['forms_button_next_page']           = 'Next page';
$lang['forms_button_submit']              = 'Send my response';
$lang['forms_button_pages']               = 'Pages';
$lang['forms_button_upload_response']     = 'Upload a filled-in form';
$lang['forms_button_edit_submission']     = 'Edit';
$lang['forms_edit_button_save']           = 'Save changes';

// Upload response modal
$lang['forms_upload_modal_title']         = 'Upload a filled-in form';
$lang['forms_upload_modal_comment_label'] = 'Comment (optional)';
$lang['forms_upload_modal_submit']        = 'Submit';
$lang['forms_upload_error_disabled']      = 'This form does not accept upload responses.';
$lang['forms_upload_error_no_file']       = 'Please select a file to upload.';
$lang['forms_upload_error_storage']       = 'Unable to prepare the storage directory.';
$lang['forms_upload_error_generic']       = 'Unable to save your response at this time.';
$lang['forms_upload_error_file_type']     = 'File rejected (accepted formats: PDF, JPG, PNG, GIF, WEBP).';

// Blank PDF template (Lot 16 / EF18)
$lang['forms_title_pdf_template']         = 'Blank form (PDF)';
$lang['forms_help_pdf_template']          = 'Blank PDF (printable form) offered for download on the public page when upload submission is enabled. Single file: a new upload replaces the previous one. Optional — 10 MB maximum.';
$lang['forms_button_upload_pdf_template'] = 'Upload PDF';
$lang['forms_button_download_pdf_template'] = 'Download current PDF';
$lang['forms_confirm_delete_pdf_template'] = 'Delete the blank PDF? The download link will disappear from the public page.';
$lang['forms_success_pdf_template_uploaded'] = 'Blank PDF saved.';
$lang['forms_success_pdf_template_deleted'] = 'Blank PDF deleted.';
$lang['forms_error_pdf_template_missing'] = 'Please select a PDF file.';
$lang['forms_error_pdf_template_too_large'] = 'File too large (10 MB maximum).';
$lang['forms_error_pdf_template_invalid'] = 'The file must be a valid PDF.';
$lang['forms_button_download_blank_pdf']  = 'Download blank form (PDF)';

// Titles
$lang['forms_title_new_form']             = 'New form';
$lang['forms_title_edit_form']            = 'Edit form';
$lang['forms_title_pages']                = 'Form pages';
$lang['forms_title_import_html']          = 'Create a form from an HTML page';
$lang['forms_title_backup_restore']       = 'Backup and restore';
$lang['forms_title_content_archive']      = 'Form content (archive)';
$lang['forms_title_images']               = 'Images';
$lang['forms_title_fields']               = 'Fields — page';
$lang['forms_title_add_field']            = 'Add field';
$lang['forms_title_edit_field']           = 'Edit field';
$lang['forms_title_submissions']          = 'Form responses';
$lang['forms_title_submission_detail']    = 'Response detail';
$lang['forms_edit_title']                 = 'Edit response';
$lang['forms_title_preview']              = 'Preview';
$lang['forms_title_thank_you']            = 'Thank you for your response';

// Sections
$lang['forms_section_submitted_values']   = 'Submitted values';
$lang['forms_section_uploaded_files']     = 'Uploaded files';
$lang['forms_section_received_files']     = 'Received files';
$lang['forms_section_submission']         = 'Submission #';

// Empty states
$lang['forms_empty_no_forms']             = 'No forms.';
$lang['forms_empty_section']              = 'No forms for the active section (and no global forms).';
$lang['forms_empty_no_pages']             = 'No pages for this form.';
$lang['forms_empty_no_fields']            = 'No fields defined for this page.';
$lang['forms_empty_no_submissions']       = 'No responses recorded.';
$lang['forms_empty_no_values']            = 'No values.';
$lang['forms_empty_no_files']             = 'No files for this submission.';

// Alerts and messages
$lang['forms_alert_section_active']       = 'Active section:';
$lang['forms_alert_no_section']           = 'No active section: the form will be created as global.';
$lang['forms_alert_global_checkbox']      = 'Choose "Globale (toutes sections)" in the Section field to create a form visible in all sections.';
$lang['forms_alert_published_warning']    = 'This form is <strong>published</strong> and accessible via the public link. Any modification is immediately visible.';
$lang['forms_alert_preview_mode']         = 'Preview mode — the form cannot be submitted here.';
$lang['forms_alert_no_pages']             = 'This form contains no pages.';
$lang['forms_alert_no_content']           = 'No content configured on this page.';
$lang['forms_alert_no_fields_page']       = 'No fields configured on this page.';
$lang['forms_message_submitted']          = 'Your form "%s" has been successfully recorded.';
$lang['forms_message_no_preview']         = 'Inline preview not available for this file type.';

// Confirmations
$lang['forms_confirm_delete_form']        = 'Delete this form?';
$lang['forms_confirm_delete_workflow_form'] = 'Warning: this form is used by a GVV workflow (passenger briefing). Deleting it will make that feature unavailable. Continue?';
$lang['forms_confirm_unpublish_workflow_form'] = 'Warning: this form is used by a GVV workflow (passenger briefing). Unpublishing or archiving it will make that feature unavailable. Continue?';
$lang['forms_confirm_delete_page']        = 'Delete this page?';
$lang['forms_confirm_delete_field']       = 'Delete this field?';
$lang['forms_modal_title_delete']         = 'Delete response';
$lang['forms_modal_confirm_delete']       = 'Confirm deletion of response';
$lang['forms_modal_help_delete']          = 'This action is irreversible and also deletes associated files.';

// Field types
$lang['forms_type_text']                  = 'Text';
$lang['forms_type_email']                 = 'Email';
$lang['forms_type_date']                  = 'Date';
$lang['forms_type_number']                = 'Number';
$lang['forms_type_textarea']              = 'Text area';
$lang['forms_type_select']                = 'Dropdown';
$lang['forms_type_radio']                 = 'Radio buttons';
$lang['forms_type_checkbox']              = 'Checkboxes';
$lang['forms_type_file']                  = 'File';

// Help texts
$lang['forms_help_code']                  = 'Stable identifier in snake_case or kebab-case.';
$lang['forms_help_technical_name']        = 'Letters, digits, underscore, dash. Used as field identifier.';
$lang['forms_help_display_order']         = 'Leave empty to add at the end of the list.';
$lang['forms_help_options_format']        = '(one per line)';
$lang['forms_help_options_usage']         = 'Each line is an option presented to the user.';
$lang['forms_help_content_archive']       = 'Pages, CSS and content metadata are edited only by depositing an archive (ZIP) — there is no HTML/CSS text box in GVV. Download the current archive, edit it (with a text editor or an AI assistant), then deposit it: this is the normal way to evolve a form, not just a fallback.';
$lang['forms_help_restore']              = 'Fully replaces the content (title, description, CSS, pages, images) with the one in the deposited archive. Code, status and public link remain unchanged.';
$lang['forms_help_backup']                = 'Downloads the form\'s current content (metadata, CSS, HTML pages and images) as a ZIP file, ready to be edited and deposited again.';
$lang['forms_confirm_restore']            = 'Deposit this archive? The current content (pages, CSS, images) will be replaced.';
$lang['forms_help_pages_via_archive']     = 'Page content is edited by depositing an archive, not from this list — see the';
$lang['forms_help_images']                = 'Images usable in the pages\' HTML (logo, etc.). Copy the path shown below each image into a src="..." attribute — it matches the form archive\'s images/ folder. Accepted formats: PNG, JPEG, GIF, WEBP — 2 MB maximum.';
$lang['forms_confirm_delete_image']       = 'Delete this image? Pages referencing it will no longer display it.';

// Form fields
$lang['forms_checkbox_global_form']       = 'Global form (not attached to a section)';
$lang['forms_checkbox_allow_upload_response'] = 'Allow submission by upload (scan)';
$lang['forms_help_allow_upload_response'] = 'Lets the user upload a scan or photo of the printed and hand-filled form instead of filling it in online.';
$lang['forms_checkbox_required']          = 'Required field';
$lang['forms_subtitle_form_container']    = 'Create the form container before adding pages and fields.';
$lang['forms_placeholder_select']         = 'Select...';
$lang['forms_label_yes']                  = 'Yes';
$lang['forms_label_no']                   = 'No';
$lang['forms_unit_bytes']                 = 'bytes';
$lang['forms_label_form_context']         = 'Form:';

// Config params
$lang['forms_config_title']               = 'Configuration parameters';
$lang['forms_config_subtitle']            = 'Configurable values usable in forms via the config.* source.';
$lang['forms_config_label_key']           = 'Technical key';
$lang['forms_config_label_value']         = 'Value';
$lang['forms_config_label_label']         = 'Label';
$lang['forms_config_label_description']   = 'Description';
$lang['forms_config_label_scope']         = 'Scope';
$lang['forms_config_scope_global']        = 'Global';
$lang['forms_config_help_key']            = 'Alphanumeric identifier (letters, digits, _). E.g.: organisme_formation';
$lang['forms_config_help_source']         = 'Reference this parameter in a form with data-gvv-source="config.KEYHERE"';
$lang['forms_config_empty']               = 'No configuration parameters defined.';
$lang['forms_config_button_new']          = 'New parameter';
$lang['forms_config_button_edit']         = 'Edit';
$lang['forms_config_button_delete']       = 'Delete';
$lang['forms_config_button_save']         = 'Save';
$lang['forms_config_button_cancel']       = 'Cancel';
$lang['forms_config_confirm_delete']      = 'Delete this parameter?';
$lang['forms_config_created']             = 'Parameter created.';
$lang['forms_config_updated']             = 'Parameter updated.';
$lang['forms_config_deleted']             = 'Parameter deleted.';
$lang['forms_config_error_key_exists']    = 'This key already exists for this scope.';
$lang['forms_config_error_key_required']  = 'The technical key is required.';
$lang['forms_config_error_label_required']= 'The label is required.';
$lang['forms_config_error_invalid_key']   = 'The key may only contain letters, digits and _.';
$lang['forms_config_card_title']          = 'Configuration';
$lang['forms_config_card_description']    = 'Key/value parameters usable in forms.';

/* required_params */
$lang['forms_label_required_params']  = 'GVV context';
$lang['forms_help_required_params']   = 'Selectors needed to pre-fill fields from GVV (members, instructors, events).';
$lang['forms_required_none']          = 'Public form (no GVV pre-fill)';
$lang['forms_required_pilot']         = 'Member selection (candidate/pilot)';
$lang['forms_required_instructor']    = 'Instructor selection';
$lang['forms_required_both']          = 'Member + instructor selection';

/* handler_class */
$lang['forms_label_handler_class']    = 'Post-submission action';
$lang['forms_help_handler_class']     = 'Triggers a GVV action (e.g. updating a discovery flight) right after the response is saved.';
$lang['forms_handler_class_none']     = 'None';

/* generate page */
$lang['forms_button_generate']          = 'Generate';
$lang['forms_generate_title']           = 'Generate pre-filled form';
$lang['forms_generate_pilot']           = 'Candidate / Pilot';
$lang['forms_generate_instructor']      = 'Instructor';
$lang['forms_generate_button']          = 'Fill form';
$lang['forms_generate_select_placeholder'] = '— Select —';
$lang['forms_generate_error_not_found'] = 'Form not found or not published.';
$lang['forms_generate_error_pilot']     = 'Please select a candidate.';
$lang['forms_generate_error_instructor']= 'Please select an instructor.';

/* export to a GVV creation form (Lot 12) */
$lang['forms_label_target_url']   = 'Target GVV creation form (export)';
$lang['forms_label_target_label'] = 'Export button label';
$lang['forms_help_target_export'] = 'If both fields are set, a button appears on each response to open this GVV form pre-filled with the response values (e.g. member/create).';

/* subforms (Lot 11) */
$lang['forms_badge_subform_unattached']      = 'Unattached';
$lang['forms_help_badge_subform_unattached'] = 'This response was submitted as a sub-form, but its master form was never finalized.';

/* End of file forms_lang.php */
/* Location: ./application/language/english/forms_lang.php */
