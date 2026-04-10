<?php
/*
 * GVV English translation
*/

# Accounting

$lang['gvv_compta_title_line'] = "Accounting entry";
$lang['gvv_compta_title_recette'] = "Revenue input";
$lang['gvv_compta_title_wire'] = "Wire transfer";
$lang['gvv_compta_title_depense'] = "Expense input";
$lang['gvv_compta_title_manual'] = "Manual billing of a pilot";
$lang['gvv_compta_title_remboursement'] = "Reimbursement of expense paid by a pilot";
$lang['gvv_compta_title_paiement'] = "Top up pilot account";
$lang['gvv_compta_title_avance'] = "Pilote refund";
$lang['gvv_compta_title_avoir'] = "Supplier credit recording";
$lang['gvv_compta_title_avoir_use'] = "Expense paid with supplier credit";
$lang['gvv_compta_title_depot'] = "Cash deposit";
$lang['gvv_compta_title_retrait'] = "Cash withdrawal";
$lang['gvv_compta_title_remb_capital'] = "Loan capital repayment";
$lang['gvv_compta_title_mise_disposition_emprunt'] = "Loan disbursement";
$lang['gvv_compta_title_amortissement'] = "Depreciation entry";
$lang['gvv_compta_title_encaissement_section'] = "Collection for a section";
$lang['gvv_compta_title_reversement_section'] = "Section reversal";
$lang['gvv_compta_title_saisie_cotisation'] = "Membership Fee Entry";
$lang['gvv_comptes_title_journal'] = "Extensive booking journal";
$lang['gvv_comptes_title_error'] = "Error";
$lang['gvv_comptes_error_no_account'] = "The logged user has no billing account: ";
$lang['gvv_compta_title_entries'] = "Account entries";

$lang['gvv_ecritures_field_date_op'] = "Operation date";
$lang['gvv_ecritures_field_compte1'] = "Emploi";
$lang['gvv_ecritures_field_compte2'] = "Resource";
$lang['gvv_ecritures_field_montant'] = "Amount";
$lang['gvv_ecritures_field_description'] = "Description";
$lang['gvv_ecritures_field_num_cheque'] = "Accounting document reference";
$lang['gvv_ecritures_field_gel'] = "Checked";


# $this->data['message'] = $this->lang->line("gvv_compta_message_advice_manual");

$lang['gvv_compta_message_advice_manual'] = "Rather use product purchase (if possible)"
	. " it also records a sale.";

$lang['gvv_compta_message_advice_wire'] = "Emploi is the account to credit, Resource is the account to debit";

$lang['gvv_compta_comptes'] = "Accounts";
$lang['gvv_compta_compte'] = "Account";
$lang['gvv_compta_date'] = "Date";
$lang['gvv_compta_jusqua'] = "to";
$lang['gvv_compta_type_ecriture'] = array(0 => 'All', 1 => 'Checked', 2 => 'Unchecked');
$lang['gvv_compta_emploi'] = "Emploi";
$lang['gvv_compta_resource'] = "Resource";
$lang['gvv_compta_montant_min'] = "Minimum amount";
$lang['gvv_compta_montant_max'] = "maximum";
$lang['gvv_compta_selector_debit_credit'] = array(0 => 'Debits and credits', 1 => 'Debits', 2 => 'Credits');

$lang['gvv_compta_button_freeze'] = "Freeze";

$lang['gvv_vue_journal_short_field_id'] = "Id";
$lang['gvv_vue_journal_short_field_date_op'] = "Date";
$lang['gvv_vue_journal_short_field_code1'] = "Code";
$lang['gvv_vue_journal_short_field_compte1'] = "Debit";
$lang['gvv_vue_journal_short_field_code2'] = "Code";
$lang['gvv_vue_journal_short_field_compte2'] = "Credit";
$lang['gvv_vue_journal_short_field_description'] = "Description";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Reference";
$lang['gvv_vue_journal_short_field_montant'] = "Amount";
$lang['gvv_vue_journal_short_field_gel'] = "Frozen";
$lang['gvv_ecritures_short_field_nom_compte2'] = "Account";

$lang['gvv_ecritures_short_field_id'] = "Id";
$lang['gvv_ecritures_short_field_date_op'] = "Date";
$lang['gvv_ecritures_short_field_code1'] = "Code";
$lang['gvv_ecritures_short_field_compte1'] = "Debit";
$lang['gvv_ecritures_short_field_code2'] = "Code";
$lang['gvv_ecritures_short_field_compte2'] = "Credit";
$lang['gvv_ecritures_short_field_description'] = "Description";
$lang['gvv_ecritures_short_field_num_cheque'] = "Reference";
$lang['gvv_ecritures_short_field_montant'] = "Amount";
$lang['gvv_ecritures_short_field_gel'] = "Frozen";

$lang['gvv_vue_journal_selector'] = array(
	0 => "Select ...",
	1 => "Expenses", // Emploi 600 - 700
	2 => "Earnings", // ressources 700 - 800
	3 => "Pilots paiements", // Ressources 411
	4 => "Fix assets operations" // Emploi 200-300
);

$lang['gvv_compta_fieldset_addresses'] = "Adress";
$lang['gvv_compta_fieldset_association'] = "Association";
$lang['gvv_compta_fieldset_pilote'] = "Pilot";
$lang['gvv_compta_fieldset_compte'] = "Account";
$lang['gvv_compta_fieldset_achats'] = "Purchase";
$lang['gvv_compta_filter_tooltip'] = 'Click to hide/display';

$lang['gvv_compta_label_accounting_code'] = 'Acounting code';
$lang['gvv_compta_label_description'] = 'Description';
$lang['gvv_compta_label_balance_before'] = 'Balance before';
$lang['gvv_compta_label_debitor'] = 'debitor';
$lang['gvv_compta_label_creditor'] = 'creditor';
$lang['gvv_compta_label_balance_at'] = 'Balance on';
$lang['gvv_compta_label_section'] = 'Section';
$lang['gvv_compta_label_compte_banque'] = 'Bank account (512)';
$lang['gvv_compta_label_compte_pilote'] = 'Pilot account (411)';
$lang['gvv_compta_label_compte_recette'] = 'Membership revenue account (700)';
$lang['gvv_compta_label_annee_cotisation'] = 'Membership year';
$lang['gvv_compta_label_montant'] = 'Transaction amount';
$lang['gvv_compta_label_pilote'] = 'Member';
$lang['gvv_compta_error_double_cotisation'] = 'This member already has a membership for this year';
$lang['gvv_compta_success_cotisation'] = 'Membership fee successfully recorded';
$lang['gvv_compta_error_cotisation'] = 'Error while recording membership fee';

$lang['gvv_compta_purchase_headers'] =  array("Date", "Product", "Quantity", "Comment", "");

$lang['gvv_vue_journal_short_field_date_op'] = "Date";
$lang['gvv_vue_journal_short_field_autre_compte'] = "Account";
$lang['gvv_vue_journal_short_field_description'] = "Description";
$lang['gvv_vue_journal_short_field_num_cheque'] = "Reference";
$lang['gvv_vue_journal_short_field_prix'] = "Unitary price";
$lang['gvv_vue_journal_short_field_quantite'] = "Quantity";
$lang['gvv_vue_journal_short_field_debit'] = "Debit";
$lang['gvv_vue_journal_short_field_credit'] = "Credit";
$lang['gvv_vue_journal_short_field_solde'] = "Solde";
$lang['gvv_vue_journal_short_field_gel'] = "Frozen";
$lang['gvv_vue_journal_short_field_section'] = "Section";


$lang['gvv_compta_csv_header'] = array('Date', 'Code', 'Account', 'Description', 'Reference', 'Debit', 'Credit', 'Balance');
$lang['gvv_compta_csv_header_411'] = array('Date', 'Description', 'Reference', 'Price', 'Quantity', 'Debit', 'Credit', 'Balance');

$lang['gvv_compta_error_same_accounts'] = "In an accounting entry, the accounts must be different.";
$lang['gvv_compta_frozen_line_cannot_modify'] = "Modification of a frozen entry is forbidden.";
$lang['gvv_compta_frozen_line_cannot_delete'] = "Deletion of a frozen entry is forbidden.";

// Attachment upload (Phase 1)
$lang['gvv_choose_files'] = "Choose Files";
$lang['gvv_optional'] = "optional";
$lang['gvv_supported_formats'] = "Supported formats";
$lang['gvv_confirm_remove_file'] = "Are you sure you want to remove this file?";
$lang['gvv_attachment_description'] = "Description (optional)"; // PRD CA1.9

$lang['valid_numeric'] = "Non decimal value";

// Entry transfer
$lang['gvv_transfert_title']            = "Entry transfer";
$lang['gvv_transfert_select_ecriture']  = "Entry";
$lang['gvv_transfert_add']              = "Add";
$lang['gvv_transfert_selection']        = "Selected entries";
$lang['gvv_transfert_empty']            = "No entry selected.";
$lang['gvv_transfert_export']           = "Export";
$lang['gvv_transfert_already_added']    = "This entry is already in the list.";
$lang['gvv_transfert_col_date']         = "Date";
$lang['gvv_transfert_col_emploi']       = "Debit";
$lang['gvv_transfert_col_ressource']    = "Credit";
$lang['gvv_transfert_col_montant']      = "Amount";
$lang['gvv_transfert_col_description']  = "Description";
$lang['gvv_transfert_col_actions']      = "";
$lang['gvv_transfert_preview_json']     = "Preview JSON";
$lang['gvv_transfert_preview_modal_title'] = "Export JSON preview";
$lang['gvv_transfert_copy_json']        = "Copy to clipboard";
$lang['gvv_transfert_copy_ok']          = "JSON copied to clipboard.";
$lang['gvv_transfert_copy_ko']          = "Automatic copy failed. Please copy manually.";
$lang['gvv_import_title']               = "Import entries";
$lang['gvv_import_file_label']          = "Export file (.json)";
$lang['gvv_import_submit']              = "Import";
$lang['gvv_import_text_label']          = "Or paste exported JSON";
$lang['gvv_import_text_submit']         = "Import from text area";
$lang['gvv_import_success']             = "entries imported successfully.";
$lang['gvv_import_error_json']          = "Invalid JSON file.";
$lang['gvv_import_error_codec']         = "Account not found for codec";
$lang['gvv_import_error_insert']        = "Insert error for entry dated";
$lang['gvv_import_errors_title']        = "Errors";
$lang['gvv_import_skipped']             = "No entries imported due to errors.";
$lang['gvv_transfert_menu']             = "Entry transfer";
$lang['gvv_import_error_old_format']       = "File is in v1.0 format — please re-export from the source instance.";
$lang['gvv_import_error_compte_not_found'] = "Account not found:";
$lang['gvv_import_error_codec_mismatch']   = "Accounting code mismatch:";
$lang['gvv_import_error_nom_mismatch']     = "Account name mismatch:";
$lang['gvv_import_error_section_mismatch'] = "Both accounts belong to different sections:";
$lang['gvv_import_nothing_selected']       = "No entry selected.";
$lang['gvv_import_preview_title']          = "Import preview";
$lang['gvv_import_col_status']             = "Status";
$lang['gvv_import_col_date']               = "Date";
$lang['gvv_import_col_emploi']             = "Debit";
$lang['gvv_import_col_ressource']          = "Credit";
$lang['gvv_import_col_montant']            = "Amount";
$lang['gvv_import_col_description']        = "Description";
$lang['gvv_import_confirm']                = "Import selection";
$lang['gvv_import_result_title']           = "Import result";
$lang['gvv_import_rollback']               = "No entries inserted due to errors (rollback).";
$lang['gvv_import_select_all']             = "Select all";
$lang['gvv_import_deselect_all']           = "Deselect all";
$lang['gvv_import_create_missing_account'] = "Create missing account";
$lang['gvv_import_missing_account_context_msg'] = "Account %s %s does not exist on this instance. Complete and submit the form to return to import.";
$lang['gvv_import_missing_account_invalid_data'] = "Cannot prepare account creation: incomplete data.";
$lang['gvv_compta_title_remb_capital'] = "Loan capital repayment";
