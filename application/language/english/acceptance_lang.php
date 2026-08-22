<?php
/**
 * Language file for acceptance system (English)
 */

// Field labels
$lang['acceptance_title'] = 'Title';
$lang['acceptance_category'] = 'Category';
$lang['acceptance_target_type'] = 'Target type';
$lang['acceptance_version_date'] = 'Version date';
$lang['acceptance_mandatory'] = 'Obligation level';
$lang['acceptance_mandatory_optional'] = 'Optional';
$lang['acceptance_mandatory_soft'] = 'Mandatory, non-blocking';
$lang['acceptance_mandatory_hard'] = 'Mandatory, blocking';
$lang['acceptance_deadline'] = 'Deadline';
$lang['acceptance_dual_validation'] = 'Dual validation';
$lang['acceptance_role_1'] = 'Role 1';
$lang['acceptance_role_2'] = 'Role 2';
$lang['acceptance_target_roles'] = 'Target roles';
$lang['acceptance_target_user_login'] = 'Target user';
$lang['acceptance_target_mode'] = 'Targeting';
$lang['acceptance_target_mode_roles'] = 'Categories';
$lang['acceptance_target_mode_user'] = 'Individual user';
$lang['acceptance_active'] = 'Active';
$lang['acceptance_created_by'] = 'Created by';
$lang['acceptance_approvals'] = 'Approvals';
$lang['acceptance_created_at'] = 'Created at';
$lang['acceptance_never_opened'] = 'Never opened';
$lang['acceptance_updated_at'] = 'Updated at';
$lang['acceptance_status'] = 'Status';
$lang['acceptance_user'] = 'User';
$lang['acceptance_external_name'] = 'External name';
$lang['acceptance_validation_role'] = 'Validation role';
$lang['acceptance_formula'] = 'Formula';
$lang['acceptance_acted_at'] = 'Action date';
$lang['acceptance_initiated_by'] = 'Initiated by';
$lang['acceptance_signature_mode'] = 'Signature mode';
$lang['acceptance_linked_pilot'] = 'Linked pilot';
$lang['acceptance_linked_by'] = 'Linked by';
$lang['acceptance_linked_at'] = 'Linked at';
$lang['acceptance_signer_first_name'] = 'Signer first name';
$lang['acceptance_signer_last_name'] = 'Signer last name';
$lang['acceptance_signer_quality'] = 'Quality';
$lang['acceptance_beneficiary_first_name'] = 'Beneficiary first name';
$lang['acceptance_beneficiary_last_name'] = 'Beneficiary last name';
$lang['acceptance_signature_type'] = 'Signature type';
$lang['acceptance_signed_at'] = 'Signed at';
$lang['acceptance_pilot_attestation'] = 'Pilot attestation';
$lang['acceptance_token'] = 'Token';
$lang['acceptance_mode'] = 'Mode';
$lang['acceptance_expires_at'] = 'Expires at';
$lang['acceptance_used'] = 'Used';
$lang['acceptance_used_at'] = 'Used at';
$lang['acceptance_item'] = 'Item';
$lang['acceptance_pdf_path'] = 'PDF file';
$lang['acceptance_archived_document'] = 'Archived document';

// Category enum values
$lang['acceptance_category_document'] = 'Document';
$lang['acceptance_category_formation'] = 'Training';
$lang['acceptance_category_controle'] = 'Check';
$lang['acceptance_category_briefing'] = 'Briefing';
$lang['acceptance_category_autorisation'] = 'Authorization';

// Target type enum values
$lang['acceptance_target_type_internal'] = 'Internal';
$lang['acceptance_target_type_external'] = 'External';

// Status enum values
$lang['acceptance_status_pending'] = 'Pending';
$lang['acceptance_status_accepted'] = 'Accepted';
$lang['acceptance_status_refused'] = 'Refused';

// Signature mode enum values
$lang['acceptance_mode_direct'] = 'Direct';
$lang['acceptance_mode_link'] = 'Link';
$lang['acceptance_mode_qrcode'] = 'QR Code';
$lang['acceptance_mode_paper'] = 'Paper';

// Signature type enum values
$lang['acceptance_signature_tactile'] = 'Tactile';
$lang['acceptance_signature_upload'] = 'Upload';

// Messages
$lang['acceptance_no_items'] = 'No items';
$lang['acceptance_no_records'] = 'No records';
$lang['acceptance_unknown_item'] = 'Unknown item';
$lang['acceptance_unknown_record'] = 'Unknown record';

// Admin interface
$lang['acceptance_admin_title'] = 'Acceptance administration';
$lang['acceptance_admin_menu'] = 'Acceptances';
$lang['acceptance_add_item'] = 'New item';
$lang['acceptance_choose_document'] = 'Choose this document';
$lang['acceptance_add_item_for_document'] = 'New acceptance request for this document';
$lang['acceptance_filtered_by_document'] = 'Acceptances linked to document: %s';
$lang['acceptance_clear_document_filter'] = 'View all acceptances';
$lang['acceptance_edit_item'] = 'Edit item';
$lang['acceptance_tracking'] = 'Acceptance tracking';
$lang['acceptance_edit'] = 'Edit';
$lang['acceptance_download_pdf'] = 'Download PDF';
$lang['acceptance_view_pdf'] = 'View document';
$lang['acceptance_current_pdf'] = 'Current PDF';
$lang['acceptance_activate'] = 'Activate';
$lang['acceptance_deactivate'] = 'Deactivate';
$lang['acceptance_delete'] = 'Delete';
$lang['acceptance_confirm_activate'] = 'Do you want to activate this item?';
$lang['acceptance_confirm_deactivate'] = 'Do you want to deactivate this item?';
$lang['acceptance_confirm_delete'] = 'Do you really want to delete this item? Acceptances and refusals already recorded for it will also be deleted. This action is irreversible.';
$lang['acceptance_item_created'] = 'Item created successfully';
$lang['acceptance_item_updated'] = 'Item updated successfully';
$lang['acceptance_item_activated'] = 'Item activated';
$lang['acceptance_item_deactivated'] = 'Item deactivated';
$lang['acceptance_item_deleted'] = 'Item deleted';
$lang['acceptance_item_not_found'] = 'Item not found';
$lang['acceptance_record_not_found'] = 'Record not found';
$lang['acceptance_pilot_linked'] = 'Acceptance linked to pilot successfully';
$lang['acceptance_link_to_pilot'] = 'Link to pilot';
$lang['acceptance_back_to_list'] = 'Back to list';
$lang['acceptance_total'] = 'Total';
$lang['acceptance_linked'] = 'Linked';
$lang['acceptance_unlinked'] = 'Unlinked';
$lang['acceptance_link_status'] = 'Link status';
$lang['acceptance_overdue'] = 'Overdue';
$lang['acceptance_filter_all'] = 'All';
$lang['acceptance_yes'] = 'Yes';
$lang['acceptance_no'] = 'No';

// Form help texts
$lang['acceptance_pdf_help'] = 'PDF format only, 10 MB maximum';
$lang['acceptance_archived_document_help'] = 'This document comes from the document archiving module. It cannot be replaced from this form.';
$lang['acceptance_mandatory_help'] = 'Optional: free acceptance, refusal or postponement. Mandatory non-blocking: the associated message of the day cannot be hidden until validated. Mandatory blocking: access to GVV is also blocked until the item is validated (except logout and the validation page).';
$lang['acceptance_dual_validation_help'] = 'Requires validation by two persons (e.g. instructor and student)';
$lang['acceptance_role_1_placeholder'] = 'e.g. instructor';
$lang['acceptance_role_2_placeholder'] = 'e.g. student';
$lang['acceptance_target_roles_placeholder'] = 'e.g. pilots, instructors, board';
$lang['acceptance_target_roles_help'] = 'Check one or more roles, in a specific section or all sections. Empty = all members.';
$lang['acceptance_target_user_help'] = 'An item targets either an individual user or one or more categories, never both.';
$lang['acceptance_select_document_help'] = 'A new item to accept must reference an already archived document. All sections combined.';
$lang['acceptance_version_date_archived_help'] = 'Archived document deposit date, not editable from this form.';
$lang['acceptance_active_help'] = 'Only active items are presented to members';

// Error messages
$lang['acceptance_error_title_required'] = 'Title is required';
$lang['acceptance_error_category_required'] = 'Category is required';
$lang['acceptance_error_create'] = 'Error during creation';
$lang['acceptance_error_directory'] = 'Cannot create storage directory';
$lang['acceptance_error_pilot_required'] = 'Please select a pilot';
$lang['acceptance_error_link'] = 'Error during linking';
$lang['acceptance_reset_approval'] = 'Reset (ask for approval again)';
$lang['acceptance_confirm_reset'] = 'Reset this approval? The person will have to approve again.';
$lang['acceptance_reset_success'] = 'Approval reset, the person will have to approve again.';
$lang['acceptance_error_reset'] = 'Error while resetting';
$lang['acceptance_archived_document_not_found'] = 'Archived document not found';

// Member interface (Lot 4)
$lang['acceptance_menu_my_acceptances'] = 'My acceptances';
$lang['acceptance_dashboard_title'] = 'Items to accept';
$lang['acceptance_dashboard_intro'] = 'Here are the items that require your validation.';
$lang['acceptance_dashboard_empty'] = 'You have no item pending acceptance.';
$lang['acceptance_dashboard_deadline'] = 'To accept before %s';
$lang['acceptance_btn_read_accept'] = 'Read and accept';
$lang['acceptance_btn_later'] = 'Later';
$lang['acceptance_btn_later_help'] = 'This item stays in your list, you can process it later.';
$lang['acceptance_read_instruction'] = 'Please read the entire document. The accept button will appear at the end.';
$lang['acceptance_btn_accept'] = 'Accept';
$lang['acceptance_btn_refuse'] = 'Refuse';
$lang['acceptance_confirm_refuse'] = 'Do you really want to refuse this item?';
$lang['acceptance_already_accepted'] = 'You accepted this item on %s.';
$lang['acceptance_already_refused'] = 'You refused this item on %s.';
$lang['acceptance_accept_success'] = 'Acceptance recorded successfully.';
$lang['acceptance_refuse_success'] = 'Refusal recorded.';
$lang['acceptance_history_title'] = 'My acceptance history';
$lang['acceptance_history_empty'] = 'You have not processed any item yet.';
$lang['acceptance_history_reread'] = 'Reread';
$lang['acceptance_back_to_list'] = 'Back to list';
$lang['acceptance_formula_member'] = 'I, the undersigned %s, member of the club identified by the system, acknowledge having read and accept %s on %s.';
$lang['acceptance_motd_title'] = 'To accept: %s';
$lang['acceptance_motd_content'] = 'A validation is required: **%s**. [Read and accept](%s)';
$lang['acceptance_my_documents_title'] = 'My documents to accept';
$lang['acceptance_my_documents_card_desc'] = 'Documents to review';
$lang['acceptance_my_documents_empty'] = 'You have no document to accept.';
$lang['acceptance_status_to_accept'] = 'To accept';
$lang['acceptance_status_accepted_on'] = 'Accepted on %s';
$lang['acceptance_status_refused_on'] = 'Refused on %s';
$lang['acceptance_error_no_member_record'] = 'Your account is not linked to a member record: this action is not available.';

/* End of file acceptance_lang.php */
/* Location: ./application/language/english/acceptance_lang.php */
