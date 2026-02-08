<?php
/**
 * Language file for acceptance system (English)
 */

// Field labels
$lang['acceptance_title'] = 'Title';
$lang['acceptance_category'] = 'Category';
$lang['acceptance_target_type'] = 'Target type';
$lang['acceptance_version_date'] = 'Version date';
$lang['acceptance_mandatory'] = 'Mandatory';
$lang['acceptance_deadline'] = 'Deadline';
$lang['acceptance_dual_validation'] = 'Dual validation';
$lang['acceptance_role_1'] = 'Role 1';
$lang['acceptance_role_2'] = 'Role 2';
$lang['acceptance_target_roles'] = 'Target roles';
$lang['acceptance_active'] = 'Active';
$lang['acceptance_created_by'] = 'Created by';
$lang['acceptance_created_at'] = 'Created at';
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
$lang['acceptance_edit_item'] = 'Edit item';
$lang['acceptance_tracking'] = 'Acceptance tracking';
$lang['acceptance_edit'] = 'Edit';
$lang['acceptance_download_pdf'] = 'Download PDF';
$lang['acceptance_current_pdf'] = 'Current PDF';
$lang['acceptance_activate'] = 'Activate';
$lang['acceptance_deactivate'] = 'Deactivate';
$lang['acceptance_confirm_activate'] = 'Do you want to activate this item?';
$lang['acceptance_confirm_deactivate'] = 'Do you want to deactivate this item?';
$lang['acceptance_item_created'] = 'Item created successfully';
$lang['acceptance_item_updated'] = 'Item updated successfully';
$lang['acceptance_item_activated'] = 'Item activated';
$lang['acceptance_item_deactivated'] = 'Item deactivated';
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
$lang['acceptance_mandatory_help'] = 'This item must be accepted by targeted persons';
$lang['acceptance_dual_validation_help'] = 'Requires validation by two persons (e.g. instructor and student)';
$lang['acceptance_role_1_placeholder'] = 'e.g. instructor';
$lang['acceptance_role_2_placeholder'] = 'e.g. student';
$lang['acceptance_target_roles_placeholder'] = 'e.g. pilots, instructors, board';
$lang['acceptance_target_roles_help'] = 'Roles separated by commas. Empty = all members.';
$lang['acceptance_active_help'] = 'Only active items are presented to members';

// Error messages
$lang['acceptance_error_title_required'] = 'Title is required';
$lang['acceptance_error_category_required'] = 'Category is required';
$lang['acceptance_error_create'] = 'Error during creation';
$lang['acceptance_error_directory'] = 'Cannot create storage directory';
$lang['acceptance_error_pilot_required'] = 'Please select a pilot';
$lang['acceptance_error_link'] = 'Error during linking';

/* End of file acceptance_lang.php */
/* Location: ./application/language/english/acceptance_lang.php */
