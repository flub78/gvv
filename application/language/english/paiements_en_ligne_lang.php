<?php
$lang['gvv_bar_title']                   = "Pay my bar tab";
$lang['gvv_bar_intro']                   = "Declare your bar purchases and debit your pilot balance directly.";
$lang['gvv_bar_label_solde']             = "Your current balance";
$lang['gvv_bar_label_section']           = "Section";
$lang['gvv_bar_montant']                 = "Amount";
$lang['gvv_bar_montant_help']            = "Minimum amount: €1 (whole euros only)";
$lang['gvv_bar_description']             = "Description of purchases";
$lang['gvv_bar_description_placeholder'] = "E.g.: 2 coffees, 1 sandwich – 28/03/2026";
$lang['gvv_bar_description_help']        = "Describe what you consumed (required).";
$lang['gvv_bar_button_valider']          = "Confirm payment";
$lang['gvv_bar_button_link']             = "Pay my bar tab";
$lang['gvv_provision_button_link']       = "Top up my account online";
$lang['gvv_bar_success']                 = "Payment of €%s recorded. Your balance has been updated.";
$lang['gvv_bar_error_section']           = "Please select a section before making a payment.";
$lang['gvv_bar_error_no_bar']            = "This section does not have a bar.";
$lang['gvv_bar_error_no_account']        = "The bar revenue account is not configured for this section. Contact the administrator.";
$lang['gvv_bar_error_no_pilot_account']  = "Your pilot account could not be found in this section.";
$lang['gvv_bar_error_montant_min']       = "The amount must be a whole number of euros, minimum €1.";
$lang['gvv_bar_error_description']       = "A description of your purchases is required.";
$lang['gvv_bar_error_solde']             = "Insufficient balance: you have €%.2f available.";
$lang['gvv_bar_error_creation']          = "An error occurred while recording the payment. Please try again.";

// Bar by card — authenticated pilot (UC1)
$lang['gvv_bar_carte_title']             = "Pay my bar tab by card";
$lang['gvv_bar_carte_intro']             = "Pay your bar purchases directly by card via HelloAsso.";
$lang['gvv_bar_carte_button_link']       = "Pay my bar tab by card";
$lang['gvv_bar_carte_button_valider']    = "Pay by card";
$lang['gvv_bar_carte_helloasso_notice']  = "You will be redirected to HelloAsso to complete the card payment.";
$lang['gvv_bar_carte_error_disabled']    = "Online payments are not enabled for this section. Contact the administrator.";
$lang['gvv_bar_carte_error_checkout']    = "Unable to initiate HelloAsso payment. Please try again or contact the administrator.";

// Index / pilot transactions (EF6)
$lang['gvv_pel_index_title']         = "My online payments";
$lang['gvv_pel_index_intro']         = "History of your online payments (HelloAsso).";
$lang['gvv_pel_index_empty']         = "No payments recorded.";
$lang['gvv_pel_col_date']            = "Date";
$lang['gvv_pel_col_montant']         = "Amount";
$lang['gvv_pel_col_statut']          = "Status";
$lang['gvv_pel_col_plateforme']      = "Platform";
$lang['gvv_pel_statut_pending']      = "Pending";
$lang['gvv_pel_statut_completed']    = "Paid";
$lang['gvv_pel_statut_failed']       = "Failed";
$lang['gvv_pel_statut_cancelled']    = "Cancelled";

// Confirmation / cancellation / error (EF6)
$lang['gvv_pel_confirm_title']       = "Payment confirmed";
$lang['gvv_pel_confirm_intro']       = "Your payment has been successfully recorded.";
$lang['gvv_pel_confirm_back']        = "Back to my account";
$lang['gvv_pel_cancel_title']        = "Payment cancelled";
$lang['gvv_pel_cancel_intro']        = "You have cancelled the payment. No amount has been debited.";
$lang['gvv_pel_cancel_back']         = "Back to my account";
$lang['gvv_pel_error_title']         = "Payment error";
$lang['gvv_pel_error_intro']         = "An error occurred during the payment. Please try again or contact the administrator.";
$lang['gvv_pel_error_back']          = "Back to my account";

// HelloAsso admin config (EF5)
$lang['gvv_admin_config_title']              = "Online Payment Configuration (HelloAsso)";
$lang['gvv_admin_config_section']            = "Section";
$lang['gvv_admin_config_select_section']     = "— Select a section —";
$lang['gvv_admin_config_helloasso_title']    = "HelloAsso Credentials";
$lang['gvv_admin_config_client_id']         = "Client ID";
$lang['gvv_admin_config_client_secret']     = "Client Secret";
$lang['gvv_admin_config_secret_set']        = "● set (leave blank to keep current)";
$lang['gvv_admin_config_secret_empty']      = "not set";
$lang['gvv_admin_config_secret_help']       = "Leave blank to keep the current secret.";
$lang['gvv_admin_config_account_slug']      = "Organisation Slug";
$lang['gvv_admin_config_slug_help']         = "Your association's identifier in HelloAsso (e.g. aeroclub-de-xxx).";
$lang['gvv_admin_config_environment']       = "Environment";
$lang['gvv_admin_config_webhook_secret']    = "Webhook Secret";
$lang['gvv_admin_config_webhook_url']       = "Webhook URL (copy to HelloAsso)";
$lang['gvv_admin_config_webhook_url_help']  = "Copy this URL into your HelloAsso interface to receive payment confirmations.";
$lang['gvv_admin_config_test_btn']          = "Test connection";
$lang['gvv_admin_config_test_ok']           = "HelloAsso connection established successfully.";
$lang['gvv_admin_config_test_fail']         = "HelloAsso connection failed. Check your credentials.";
$lang['gvv_admin_config_test_pending']      = "Testing…";
$lang['gvv_admin_config_test_error']        = "Network error during test.";
$lang['gvv_admin_config_bar_title']         = "Bar Configuration";
$lang['gvv_admin_config_has_bar']           = "This section has a bar";
$lang['gvv_admin_config_bar_account']       = "Bar revenue account (7xx)";
$lang['gvv_admin_config_bar_account_help']  = "Account credited when bar purchases are settled.";
$lang['gvv_admin_config_transaction_title'] = "Transaction Parameters";
$lang['gvv_admin_config_compte_passage']    = "Transit account (HelloAsso debit)";
$lang['gvv_admin_config_compte_passage_help'] = "Account debited for HelloAsso payments, pending platform transfer (e.g. 467).";
$lang['gvv_admin_config_montant_min']       = "Minimum amount (€)";
$lang['gvv_admin_config_montant_max']       = "Maximum amount (€)";
$lang['gvv_admin_config_enabled']           = "Enable online payments for this section";
$lang['gvv_admin_config_enabled_help']      = "When disabled, online payment buttons are hidden for all users.";
$lang['gvv_admin_config_saved']             = "Configuration saved.";
$lang['gvv_admin_config_error_no_section']  = "Please select a section.";

// Pilot account top-up (EF1)
$lang['gvv_provision_title']                = "Top up my pilot account";
$lang['gvv_provision_intro']                = "Recharge your pilot account by card via HelloAsso.";
$lang['gvv_provision_montant_help']         = "Amount between €%s and €%s.";
$lang['gvv_provision_montant_help_multi']   = "Choose an amount in multiples of €100.";
$lang['gvv_provision_select_montant']       = "— Choose an amount —";
$lang['gvv_provision_error_montant_multiple'] = "The amount must be a multiple of €100.";
$lang['gvv_provision_button_valider']       = "Top up by card";
$lang['gvv_provision_checkout_description'] = "Pilot account top-up — %s";
$lang['gvv_provision_error_montant_min']    = "The minimum amount is €%s.";
$lang['gvv_provision_error_montant_max']    = "The maximum amount is €%s.";
$lang['gvv_provision_error_limit_day']      = "You have reached the limit of 5 pending requests per day. Try again tomorrow or contact the administrator.";
$lang['gvv_button_cancel']                  = "Cancel";

// Dashboard — Mes paiements
$lang['gvv_dashboard_payments_title']       = "My payments";
$lang['gvv_dashboard_pay_cotisation']       = "Pay my membership fee";
$lang['gvv_dashboard_pay_cotisation_sub']   = "Online renewal";
$lang['gvv_dashboard_pay_section_active']   = "Active section: %s";
$lang['gvv_dashboard_pay_section_required'] = "Select an active section";
$lang['gvv_dashboard_pay_bar']              = "Pay my bar tab";
$lang['gvv_dashboard_pay_bar_sub']          = "Balance debit or card";
$lang['gvv_dashboard_provision_account']    = "Top up my %s account (card)";
$lang['gvv_dashboard_provision_sub']        = "HelloAsso online payment";

// Bar hub
$lang['gvv_bar_hub_title']                  = "Pay my bar tab";
$lang['gvv_bar_hub_intro']                  = "Choose your payment method for bar purchases.";
$lang['gvv_bar_hub_debit_title']            = "Debit my account";
$lang['gvv_bar_hub_debit_sub']              = "Deduct amount from your available pilot balance";
$lang['gvv_bar_hub_carte_title']            = "Online payment (card)";
$lang['gvv_bar_hub_carte_sub']              = "Pay by credit card via HelloAsso";
$lang['gvv_bar_hub_back']                   = "Back";

// Membership fee by card — treasurer (UC6)
$lang['gvv_cotisation_helloasso_button']        = "Pay by card (HelloAsso)";
$lang['gvv_cotisation_helloasso_error_user']    = "Pilot not found. Please check the selection.";
$lang['gvv_cotisation_helloasso_error_tx']      = "Error creating the transaction. Please try again.";
$lang['gvv_cotisation_helloasso_error_checkout']= "Unable to initiate HelloAsso payment. Please retry or validate manually.";
$lang['gvv_cotisation_qr_title']               = "Membership fee payment by card — HelloAsso";
$lang['gvv_cotisation_qr_intro']               = "Ask the pilot to scan the QR code or open the link on this screen to proceed with card payment.";
$lang['gvv_cotisation_qr_scan_title']          = "Scan with smartphone";
$lang['gvv_cotisation_qr_scan_intro']          = "The pilot scans this QR code with their phone to pay directly.";
$lang['gvv_cotisation_qr_direct_title']        = "Pay on this screen";
$lang['gvv_cotisation_qr_direct_intro']        = "Opens the HelloAsso payment page on this device.";
$lang['gvv_cotisation_qr_direct_button']       = "Open HelloAsso";
$lang['gvv_cotisation_qr_back']                = "Back to membership form";
$lang['gvv_cotisation_qr_url_missing']         = "Payment URL not available. Contact your administrator.";

// Pilot account top-up by card — treasurer (UC7)
$lang['gvv_credit_tresorier_title']             = "Top up a pilot account by card";
$lang['gvv_credit_tresorier_intro']             = "Select the pilot and amount, then choose the payment method.";
$lang['gvv_credit_tresorier_button']            = "Pay by card (HelloAsso)";
$lang['gvv_credit_tresorier_error_user']        = "Pilot not found. Please check the selection.";
$lang['gvv_credit_tresorier_error_tx']          = "Error creating the transaction. Please try again.";
$lang['gvv_credit_tresorier_error_checkout']    = "Unable to initiate HelloAsso payment. Please retry or validate manually.";
$lang['gvv_credit_tresorier_success']           = "Top-up of €%s recorded for pilot %s.";
$lang['gvv_credit_qr_title']                    = "Top up pilot account by card — HelloAsso";
$lang['gvv_credit_qr_intro']                    = "Ask the pilot to scan the QR code or open the link on this screen to proceed with card payment.";
$lang['gvv_credit_qr_scan_title']               = "Scan with smartphone";
$lang['gvv_credit_qr_scan_intro']               = "The pilot scans this QR code with their phone to pay directly.";
$lang['gvv_credit_qr_direct_title']             = "Pay on this screen";
$lang['gvv_credit_qr_direct_intro']             = "Opens the HelloAsso payment page on this device.";
$lang['gvv_credit_qr_direct_button']            = "Open HelloAsso";
$lang['gvv_credit_qr_back']                     = "Back to top-up form";
$lang['gvv_credit_qr_url_missing']              = "Payment URL not available. Contact your administrator.";
$lang['gvv_credit_tresorier_menu']              = "Top up pilot account (card)";

// Treasurer list (EF4)
$lang['gvv_liste_title']                    = "Online Payments";
$lang['gvv_liste_filter_from']              = "From";
$lang['gvv_liste_filter_to']               = "To";
$lang['gvv_liste_filter_statut']            = "Status";
$lang['gvv_liste_filter_plateforme']        = "Platform";
$lang['gvv_liste_filter_section']           = "Section";
$lang['gvv_liste_filter_all']               = "All";
$lang['gvv_liste_filter_apply']             = "Filter";
$lang['gvv_liste_filter_reset']             = "Reset";
$lang['gvv_liste_stat_count']               = "Completed this month";
$lang['gvv_liste_stat_total']               = "Total amount";
$lang['gvv_liste_stat_commissions']         = "Commissions";
$lang['gvv_liste_export_csv']               = "Export CSV";
$lang['gvv_liste_empty']                    = "No payments for the selected period.";
$lang['gvv_liste_col_pilote']               = "Pilot";
$lang['gvv_liste_col_commission']           = "Commission";
$lang['gvv_liste_col_reference']            = "Reference";
$lang['gvv_liste_col_section']              = "Section";
$lang['gvv_liste_menu']                     = "Online payments";

// Bar externe via QR Code — external person without GVV account (UC2)
$lang['gvv_public_bar_title']              = "Pay my bar tab";
$lang['gvv_public_bar_intro']              = "Pay your bar purchases by card via HelloAsso —";
$lang['gvv_public_bar_prenom']             = "First name";
$lang['gvv_public_bar_nom']               = "Last name";
$lang['gvv_public_bar_email']             = "Email (optional)";
$lang['gvv_public_bar_email_placeholder'] = "your@email.com";
$lang['gvv_public_bar_email_help']        = "To receive a payment confirmation.";
$lang['gvv_public_bar_montant_help']      = "Minimum amount: €2.00";
$lang['gvv_public_bar_button_valider']    = "Pay by card";
$lang['gvv_public_bar_helloasso_notice']  = "You will be redirected to HelloAsso to complete the card payment.";
$lang['gvv_public_bar_error_club']        = "Missing or invalid section identifier. Please use the QR code provided by the club.";
$lang['gvv_public_bar_error_no_bar']      = "This section does not have a bar or online payment is not enabled.";
$lang['gvv_public_bar_error_disabled']    = "Online payments are not enabled for this section.";
$lang['gvv_public_bar_error_nom']         = "Last name is required.";
$lang['gvv_public_bar_error_prenom']      = "First name is required.";
$lang['gvv_public_bar_error_email']       = "The email address is not valid.";
$lang['gvv_public_bar_error_montant_min'] = "The minimum amount is €2.00.";
$lang['gvv_public_bar_error_checkout']    = "Unable to initiate HelloAsso payment. Please try again.";
$lang['gvv_public_bar_confirm_title']     = "Payment recorded";
$lang['gvv_public_bar_confirm_intro']     = "Your payment has been successfully recorded. Thank you!";
$lang['gvv_public_bar_confirm_section']   = "Section:";

// Cotisation online — pilot (UC3)
$lang['gvv_cotisation_form_title']          = "Pay my membership fee online";
$lang['gvv_cotisation_form_intro']          = "Select your membership and pay by card via HelloAsso.";
$lang['gvv_cotisation_form_no_produits']    = "No membership available for this section. Contact your treasurer.";
$lang['gvv_cotisation_form_choose']         = "Choose your membership";
$lang['gvv_cotisation_form_button']         = "Pay by card (HelloAsso)";
$lang['gvv_cotisation_helloasso_notice']    = "You will be redirected to HelloAsso to complete the card payment.";
$lang['gvv_cotisation_error_produit']       = "Invalid or unavailable membership product.";
$lang['gvv_cotisation_error_already_paid']  = "You have already paid your membership for %d.";
$lang['gvv_cotisation_error_tx']            = "Error creating transaction. Please try again.";
$lang['gvv_cotisation_error_checkout']      = "Unable to initiate HelloAsso payment. Please try again.";

// Discovery voucher via public link / QR (UC4)
$lang['gvv_decouverte_menu']                        = "Discovery voucher (card payment)";
$lang['gvv_decouverte_manager_title']               = "Discovery voucher - Card payment";
$lang['gvv_decouverte_manager_intro']               = "Generate a public payment link or QR code for a discovery voucher in section";
$lang['gvv_decouverte_product']                     = "Product";
$lang['gvv_decouverte_product_choose']              = "- Choose a discovery product -";
$lang['gvv_decouverte_beneficiaire']                = "Beneficiary";
$lang['gvv_decouverte_de_la_part']                  = "From";
$lang['gvv_decouverte_email']                       = "Beneficiary email (optional)";
$lang['gvv_decouverte_email_help']                  = "Used to send voucher confirmation after payment.";
$lang['gvv_decouverte_helloasso_notice']            = "The beneficiary can pay by scanning the QR code or opening the HelloAsso link.";
$lang['gvv_decouverte_generate_button']             = "Generate QR code";
$lang['gvv_decouverte_payer_cb_label']              = "Card payment";
$lang['gvv_decouverte_payer_cb_button']             = "Pay by card (HelloAsso)";
$lang['gvv_decouverte_error_product']               = "Please select a valid discovery product.";
$lang['gvv_decouverte_error_beneficiaire']          = "Beneficiary name is required.";
$lang['gvv_decouverte_error_email']                 = "Beneficiary email address is invalid.";
$lang['gvv_decouverte_error_amount']                = "Discovery product amount is invalid.";
$lang['gvv_decouverte_error_tx']                    = "Error while creating discovery transaction.";
$lang['gvv_decouverte_error_checkout']              = "Unable to start HelloAsso checkout for this discovery voucher.";
$lang['gvv_decouverte_qr_title']                    = "Discovery voucher - HelloAsso card payment";
$lang['gvv_decouverte_qr_intro']                    = "Ask the customer to scan the QR code or open the direct link to pay the discovery voucher.";
$lang['gvv_decouverte_qr_scan_title']               = "Scan with smartphone";
$lang['gvv_decouverte_qr_scan_intro']               = "The customer scans this QR code to open the payment page.";
$lang['gvv_decouverte_qr_direct_title']             = "Pay on this screen";
$lang['gvv_decouverte_qr_direct_intro']             = "Opens the HelloAsso payment page on this device.";
$lang['gvv_decouverte_qr_direct_button']            = "Open HelloAsso";
$lang['gvv_decouverte_qr_url_missing']              = "Payment URL not available. Contact your administrator.";
$lang['gvv_decouverte_qr_back']                     = "Back to discovery voucher form";
$lang['gvv_decouverte_public_confirm_title']        = "Payment recorded";
$lang['gvv_decouverte_public_confirm_intro']        = "Your discovery voucher payment has been successfully recorded.";
$lang['gvv_decouverte_email_subject']               = "Discovery voucher confirmed";
