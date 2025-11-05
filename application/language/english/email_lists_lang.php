<?php
/**
 * English language file for Email Lists
 */

// General
$lang['email_lists_title'] = 'Email Distribution Lists';
$lang['email_lists_name'] = 'List Name';
$lang['email_lists_description'] = 'Description';
$lang['email_lists_active_member'] = 'Filter Members';
$lang['email_lists_visible'] = 'Visible';
$lang['email_lists_created'] = 'Created on';
$lang['email_lists_updated'] = 'Updated on';
$lang['email_lists_created_by'] = 'Created by';
$lang['email_lists_recipient_count'] = 'Recipients';

// Actions
$lang['email_lists_create'] = 'New List';
$lang['email_lists_edit'] = 'Edit List';
$lang['email_lists_view'] = 'View List';
$lang['email_lists_delete'] = 'Delete';
$lang['email_lists_delete_confirm'] = 'Are you sure you want to delete this list?';
$lang['email_lists_export'] = 'Export';
$lang['email_lists_copy'] = 'Copy';

// Tabs
$lang['email_lists_tab_criteria'] = 'By Criteria';
$lang['email_lists_tab_manual'] = 'Manual Selection';
$lang['email_lists_tab_external'] = 'External addresses';

// Criteria tab
$lang['email_lists_roles'] = 'Roles';
$lang['email_lists_sections'] = 'Sections';
$lang['email_lists_select_roles'] = 'Select roles and sections';
$lang['email_lists_active_members_only'] = 'Active members only';
$lang['email_lists_inactive_members_only'] = 'Inactive members only';
$lang['email_lists_all_members'] = 'All members';

// Manual tab
$lang['email_lists_manual_members'] = 'Manually Added Members';
$lang['email_lists_add_member'] = 'Add Member';
$lang['email_lists_remove_member'] = 'Remove';
$lang['email_lists_select_member'] = 'Select a member';

// External tab
$lang['email_lists_external_emails'] = 'External Addresses';
$lang['email_lists_add_external'] = 'Add Address';
$lang['email_lists_external_email'] = 'Email';
$lang['email_lists_external_name'] = 'Name';
$lang['email_lists_paste_emails'] = 'Paste addresses (one per line)';

// Import tab
$lang['email_lists_external_addresses'] = 'External addresses';
$lang['email_lists_import_csv'] = 'CSV Import';
$lang['email_lists_upload_file'] = 'Upload a file';
$lang['email_lists_parse'] = 'Parse';

// Export
$lang['email_lists_export_txt'] = 'Export TXT';
$lang['email_lists_export_md'] = 'Export Markdown';
$lang['email_lists_export_clipboard'] = 'Copy to Clipboard';
$lang['email_lists_separator'] = 'Separator';
$lang['email_lists_separator_comma'] = 'Comma';
$lang['email_lists_separator_semicolon'] = 'Semicolon';

// mailto
$lang['email_lists_mailto'] = 'Open Email Client';
$lang['email_lists_mailto_field'] = 'Field';
$lang['email_lists_mailto_to'] = 'To (TO)';
$lang['email_lists_mailto_cc'] = 'Copy (CC)';
$lang['email_lists_mailto_bcc'] = 'Blind Copy (BCC)';
$lang['email_lists_mailto_subject'] = 'Subject';
$lang['email_lists_mailto_body'] = 'Message Body';
$lang['email_lists_mailto_reply_to'] = 'Reply To';
$lang['email_lists_mailto_save_prefs'] = 'Save Preferences';

// Chunking
$lang['email_lists_chunk_size'] = 'Chunk Size';
$lang['email_lists_chunk_part'] = 'Part';
$lang['email_lists_chunk_of'] = 'of';

// Messages
$lang['email_lists_create_success'] = 'List created successfully';
$lang['email_lists_create_error'] = 'Error creating list';
$lang['email_lists_update_success'] = 'List updated successfully';
$lang['email_lists_update_error'] = 'Error updating list';
$lang['email_lists_delete_success'] = 'List deleted successfully';
$lang['email_lists_delete_error'] = 'Error deleting list';
$lang['email_lists_copy_success'] = 'Addresses copied to clipboard';
$lang['email_lists_copy_error'] = 'Error copying';
$lang['email_lists_no_recipients'] = 'No recipients';
$lang['email_lists_empty_list'] = 'This list contains no recipients';

// Validation
$lang['email_lists_name_required'] = 'Name is required';
$lang['email_lists_invalid_email'] = 'Invalid email address';

// View labels
$lang['email_lists_sources'] = 'List Sources';
$lang['email_lists_source_roles'] = 'By Roles';
$lang['email_lists_source_manual'] = 'Manual Members';
$lang['email_lists_source_external'] = 'External Addresses';
$lang['email_lists_total'] = 'Total';
$lang['email_lists_recipients_list'] = 'Recipients List';
$lang['email_lists_recipients'] = 'recipients';
$lang['email_lists_actions'] = 'Actions';
$lang['email_lists_no_lists'] = 'No distribution lists available';
$lang['email_lists_email_addresses'] = 'Email Addresses';
$lang['email_lists_criteria_help'] = 'Select roles and sections to automatically include matching members';
$lang['email_lists_no_roles_available'] = 'No roles available';
$lang['email_lists_global_roles'] = 'Global Roles';
$lang['email_lists_no_roles_for_section'] = 'No roles available for this section';
$lang['email_lists_preview_count'] = 'Preview Count';
$lang['email_lists_select_at_least_one_role'] = 'Select at least one role';
$lang['email_lists_preview_error'] = 'Preview error';
$lang['email_lists_manual_help'] = 'Add specific members to this list';
$lang['email_lists_select_member_first'] = 'Please select a member';
$lang['email_lists_member_already_added'] = 'This member is already in the list';
$lang['email_lists_external_help'] = 'Add external email addresses (non-members)';
$lang['email_lists_enter_email'] = 'Please enter an email address';
$lang['email_lists_import_pasted'] = 'Import Addresses';
$lang['email_lists_emails_added'] = 'addresses added';
$lang['email_lists_emails_invalid'] = 'invalid addresses';
$lang['email_lists_external_addresses_help'] = 'Enter or paste addresses, one per line. Addresses can be followed by a name.';
$lang['email_lists_paste_addresses'] = 'Enter or paste addresses here';
$lang['email_lists_import_csv_help'] = 'Paste CSV with configurable columns';
$lang['email_lists_paste_csv'] = 'Paste CSV here';
$lang['email_lists_csv_delimiter'] = 'Delimiter';
$lang['email_lists_comma'] = 'Comma';
$lang['email_lists_semicolon'] = 'Semicolon';
$lang['email_lists_tab'] = 'Tab';
$lang['email_lists_email_column'] = 'Email Column';
$lang['email_lists_name_column'] = 'Name Column';
$lang['email_lists_column_index_help'] = '0 = first column';
$lang['email_lists_column_optional'] = '-1 if no name column';
$lang['email_lists_csv_has_header'] = 'CSV contains header row';
$lang['email_lists_parse_import'] = 'Parse and Import';
$lang['email_lists_import_results'] = 'Import Results';
$lang['email_lists_valid_emails'] = 'Valid Addresses';
$lang['email_lists_errors'] = 'Errors';
$lang['email_lists_show_errors'] = 'Show Errors';
$lang['email_lists_preview'] = 'Preview';
$lang['email_lists_confirm_import'] = 'Confirm Import';
$lang['email_lists_no_text_to_import'] = 'No text to import';
$lang['email_lists_no_csv_to_import'] = 'No CSV to import';
$lang['email_lists_emails_imported'] = 'addresses imported';
$lang['email_lists_chunk_emails'] = 'Split List';
$lang['email_lists_showing'] = 'Showing';
$lang['email_lists_mailto_help'] = 'Opens your email client with pre-filled addresses';
$lang['email_lists_mailto_too_long'] = 'List too long for mailto. Copy to clipboard instead?';
$lang['email_lists_prefs_saved'] = 'Preferences saved';

// Preview panel
$lang['email_lists_list_under_construction'] = 'List under construction';
$lang['email_lists_total_recipients'] = 'Total recipients';
$lang['email_lists_from_criteria'] = 'From criteria';
$lang['email_lists_select_criteria_to_preview'] = 'Select criteria to preview the list';
$lang['email_lists_refresh_preview'] = 'Refresh preview';

// Workflow v1.4 - Separation creation/modification
$lang['email_lists_add_remove_addresses'] = 'Add and remove email addresses';
$lang['email_lists_save_first_to_add_addresses'] = 'Please save the list first before you can add email addresses';
