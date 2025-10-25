<?php
/*
 * GVV English translation
*/

$lang['gvv_comptes_title'] = "Account";
$lang['gvv_comptes_title_balance'] = "Accounts general balance";
$lang['gvv_comptes_title_detailed_balance'] = "Detailed balance of accounts";

$lang['gvv_comptes_title_bilan'] = "Balance sheet";
$lang['gvv_comptes_title_resultat'] = "End of year results";
$lang['gvv_comptes_title_cloture'] = "Closing of the period";
$lang['gvv_comptes_title_cash'] = "Cash";
$lang['gvv_comptes_title_journaux'] = "Acounts listing";
$lang['gvv_comptes_title_financial'] = "Financial report for the period";
$lang['gvv_comptes_title_sales'] = "Sales";

$lang['gvv_comptes_field_nom'] = "Account name";
$lang['gvv_comptes_field_codec'] = "Accounting code";
$lang['gvv_comptes_field_desc'] = "Description";
$lang['gvv_comptes_field_debit'] = "Debit";
$lang['gvv_comptes_field_credit'] = "Credit";
$lang['gvv_comptes_field_saisie_par'] = "Creator";
$lang['gvv_comptes_field_pilote'] = "Associated pilot reference";

$lang['gvv_vue_comptes_short_field_codec'] = "Code";
$lang['gvv_vue_comptes_short_field_id'] = "Account";
$lang['gvv_vue_comptes_short_field_solde_debit'] = "Debit balance";
$lang['gvv_vue_comptes_short_field_solde_credit'] = "Credit balance";
$lang['gvv_vue_comptes_short_field_nom'] = "Account";
$lang['gvv_vue_comptes_short_field_section_name'] = "Section";

$lang['comptes_filter_active_select'] = array(0 => 'All', 1 => 'Debit', 2 => 'Not nuls', 3 => 'Credit');

$lang['comptes_label_totals'] = "Totals";
$lang['comptes_label_total'] = "Total";
$lang['comptes_label_totals_balance'] = "Totals balance";
$lang['comptes_label_balance'] = "Balance";
$lang['comptes_label_soldes'] = "Balance";
$lang['comptes_label_date'] = "Date";
$lang['comptes_label_class'] = "Class";
$lang['comptes_label_to'] = "to";
$lang['comptes_label_expenses'] = "Expenses";
$lang['comptes_label_earnings'] = "Incomes";
$lang['comptes_label_total_incomes'] = "Total incomes";
$lang['comptes_label_total_expenses'] = "Total expenses";
$lang['comptes_label_total_pertes'] = "Losses";
$lang['comptes_label_total_benefices'] = "Profits";

$lang['comptes_warning'] = "Accounts containing entries cannot be deleted";

$lang['comptes_bilan_actif'] = "Financial assets";
$lang['comptes_bilan_valeur_brute'] = "Gross value";
$lang['comptes_bilan_amortissement'] = "Amortization";
$lang['comptes_bilan_valeur_nette'] = "Net value";
$lang['comptes_bilan_passif'] = "Liabilities";
$lang['comptes_bilan_immobilise'] = "Fixed assets";
$lang['comptes_bilan_fonds_propres'] = "Capital";
$lang['comptes_bilan_immobilisations_corp'] = "Tangible assets";
$lang['comptes_bilan_fonds_associatifs'] = "Capital";
$lang['comptes_bilan_report_debt'] = "Retained earnings credit";
$lang['comptes_bilan_report_cred'] = "Retained earnings debit";
$lang['comptes_bilan_dispo'] = "Available";
$lang['comptes_bilan_dettes_court_terme'] = "Short term debt";
$lang['comptes_bilan_creances_tiers'] = "Third party credentials";
$lang['comptes_bilan_dettes_tiers'] = "Third party debt";
$lang['comptes_bilan_dettes_banques'] = "Bank loans";
$lang['comptes_bilan_comptes_financiers'] = "Financial accounts";
$lang['comptes_bilan_total'] = "Total";
$lang['comptes_bilan_total_actif'] = "Total financial assets";
$lang['comptes_bilan_total_passif'] = "Total liabilities";
$lang['comptes_bilan_resultat'] = "Budget year's results";

$lang['comptes_button_cloture'] = "End or year operations";

$lang['comptes_table_header'] = array(
	'N°',
	'Account',
	'Nb',
	'Debit',
	'Credit',
	'Debit balance',
	'Credit balance',
	''
);

$lang['comptes_list_header'] = array(
	'Code',
	'Name',
	'Debit',
	'Credit',
	'Debit balance',
	'Credit balance'
);

$lang['comptes_cloture'] = "Closing of the period";
$lang['comptes_cloture_impossible'] = "Closing impossible, operations are frozen on";
$lang['comptes_cloture_reintegration_resultat'] = "reintégration of the result of the period";
$lang['comptes_cloture_raz_charges'] = "reset of the expenses account";
$lang['comptes_cloture_raz_produits'] = "reset of the incomes account";
$lang['comptes_cloture_date_fin'] = "End of period date";
$lang['comptes_cloture_date_gel'] = "Lines date of freeze";
$lang['comptes_cloture_title_result'] = "Account in which to integrate the result of previous period";
$lang['comptes_cloture_title_previous'] = "Accounts to reintegrate in capital";
$lang['comptes_cloture_title_charges_a_integrer'] = "Expense accounts used to compute the result";
$lang['comptes_cloture_title_produits_a_integrer'] = "Income accounts used to compute the result";

$lang['comptes_cloture_error_120'] = "Closing impossible, no account 120";
$lang['comptes_cloture_error_129'] = "Closing impossible, no account 129";

$lang['comptes_balance_general'] = array('Detailed' => 0, 'General' => 1);

// Masked accounts
$lang['gvv_comptes_comment_masked'] = "Check to hide the account from selectors and reports (only possible if balance is 0)";
$lang['gvv_comptes_error_cannot_mask_non_zero_balance'] = "Cannot mask an account with a non-zero balance. Current balance: %s €";
$lang['gvv_comptes_field_masked'] = "Masked";
$lang['gvv_comptes_masked_warning'] = "Warning: an account with a balance of %s € cannot be masked.";
$lang['gvv_comptes_can_mask'] = "Balance is 0, account can be masked.";

$lang['gvv_comptes_title_dashboard'] = "Dashboard";
$lang['comptes_bilan_prets'] = "Loans";
