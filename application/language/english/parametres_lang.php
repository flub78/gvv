<?php
/**
 * English — Configuration page (unified settings)
 */

$lang['parametres_title']            = 'Configuration';
$lang['parametres_card_desc']        = 'Club and application settings';
$lang['parametres_no_tab']           = 'No setting is available to you.';
$lang['parametres_forbidden_tab']    = 'You do not have access to the "%s" tab.';
$lang['parametres_unknown_tab']      = 'The "%s" tab does not exist.';
$lang['parametres_save']             = 'Save';
$lang['parametres_read_only']        = 'This tab is read-only for you.';
$lang['parametres_default_value']    = 'GVV default value: nothing has been entered yet.';
$lang['parametres_last_update']      = 'Last modified on %s by %s';
$lang['parametres_unsaved_confirm']  = 'Some changes are not saved. Leave this tab anyway?';
$lang['parametres_saved']            = 'Settings saved:';
$lang['parametres_nothing_changed']  = 'Nothing to save.';
$lang['parametres_not_saved']        = 'Settings not saved:';
$lang['parametres_preview']          = 'Preview';
$lang['parametres_public_url']       = 'Public address';
$lang['parametres_copy']             = 'Copy';
$lang['parametres_copied']           = 'Address copied';

$lang['parametres_status_complete']   = 'Complete';
$lang['parametres_status_incomplete'] = 'Incomplete';
$lang['parametres_status_optional']   = 'Optional, not configured';

$lang['parametres_lang_french']  = 'Français';
$lang['parametres_lang_english'] = 'English';
$lang['parametres_lang_dutch']   = 'Nederlands';

$lang['parametres_tab_pages_publiques']      = 'Public pages';
$lang['parametres_tab_pages_publiques_help'] = 'Pages available without logging in. The privacy policy and terms of use are linked from the login page.';
$lang['parametres_legal_confidentialite']      = 'Privacy policy';
$lang['parametres_legal_confidentialite_help'] = 'Club-specific text, in Markdown. Left empty, a generic GVV text is used. Club information can be inserted: {nom_club}, {adresse_club}, {cp_club}, {ville_club}, {email_club}, {tel_club}, {url_club}, {contact_rgpd}.';
$lang['parametres_legal_conditions']           = 'Terms of use';
$lang['parametres_legal_conditions_help']      = 'Club-specific text, in Markdown. Left empty, a generic GVV text is used. Same club information as for the privacy policy can be inserted.';
$lang['parametres_newsletter_page']            = 'Newsletters';
$lang['parametres_generic_text']               = 'Generic GVV text (used as long as nothing is entered):';

$lang['parametres_err_unknown']          = 'unknown setting';
$lang['parametres_err_forbidden']        = 'you are not allowed to change it';
$lang['parametres_err_language']         = 'unknown language';
$lang['parametres_err_required']         = 'value required';
$lang['parametres_err_max_length']       = 'text too long';
$lang['parametres_err_email']            = 'invalid email address';
$lang['parametres_err_url']              = 'invalid web address (it must start with http:// or https://)';
$lang['parametres_err_integer']          = 'whole number expected';
$lang['parametres_err_db']               = 'database error';
$lang['parametres_err_file_missing_file'] = 'configuration file %s is missing on the server';
$lang['parametres_err_file_not_writable'] = 'file %s cannot be modified by the web server (on the server: chmod g+w %s)';
$lang['parametres_err_file_syntax_error'] = 'the change would make file %s invalid, it was not applied';
$lang['parametres_err_file_write_failed'] = 'writing file %s failed';
$lang['parametres_err_file_invalid_key']  = 'invalid setting name';
$lang['parametres_err_token']            = "The page has expired or the request did not come from the Configuration page: nothing was saved.";
$lang['parametres_reload']               = "Reload the page";


// Lot 2 : onglet Club
$lang['parametres_tab_club'] = 'Club';
$lang['parametres_tab_club_help'] = 'Club identity, used in documents, e-mails and public pages. Board members can view it; only the club administrator can change it.';
$lang['parametres_sigle_club'] = 'Acronym';
$lang['parametres_sigle_club_help'] = 'Short club name, shown in headers.';
$lang['parametres_nom_club'] = 'Name';
$lang['parametres_nom_club_help'] = 'Full name of the association.';
$lang['parametres_code_club'] = 'Identification code';
$lang['parametres_code_club_help'] = 'Federal club code (optional).';
$lang['parametres_adresse_club'] = 'Street';
$lang['parametres_adresse_club_help'] = 'Postal address of the club.';
$lang['parametres_cp_club'] = 'Postcode';
$lang['parametres_cp_club_help'] = '';
$lang['parametres_ville_club'] = 'City';
$lang['parametres_ville_club_help'] = '';
$lang['parametres_tel_club'] = 'Phone';
$lang['parametres_tel_club_help'] = '';
$lang['parametres_email_club'] = 'E-mail';
$lang['parametres_email_club_help'] = 'Club contact address, also used as sender of e-mails.';
$lang['parametres_url_club'] = 'Website';
$lang['parametres_url_club_help'] = 'Club website address, starting with https://.';
$lang['parametres_logo_club'] = 'Logo';
$lang['parametres_logo_club_help'] = 'PNG, JPEG or GIF image, 500 KB at most. The previous logo is kept on the server, so you can go back to it from the history.';
$lang['parametres_contact_rgpd'] = 'GDPR contact';
$lang['parametres_contact_rgpd_help'] = 'Person to whom personal data requests should be addressed, shown in the privacy policy. If empty: "the president of the association".';

// Lot 2 : onglet Modules
$lang['parametres_tab_modules'] = 'Modules';
$lang['parametres_tab_modules_help'] = 'Features enabled for the club. The menu is updated from the next page on. The Configuration page always remains available, whatever modules are active.';
$lang['parametres_gestion_vd'] = 'Discovery flights';
$lang['parametres_gestion_vd_help'] = 'Discovery flight vouchers, sent by e-mail.';
$lang['parametres_gestion_reservations'] = 'Bookings';
$lang['parametres_gestion_reservations_help'] = 'Aircraft booking.';
$lang['parametres_gestion_formations'] = 'Training';
$lang['parametres_gestion_formations_help'] = 'Student training follow-up.';
$lang['parametres_gestion_documentaire'] = 'Document management';
$lang['parametres_gestion_documentaire_help'] = 'Archived member and club documents.';
$lang['parametres_gestion_paiements'] = 'HelloAsso payments';
$lang['parametres_gestion_paiements_help'] = 'Online payments. Their per-section settings remain for now in the Online payments > Configuration screen.';
$lang['parametres_gestion_ulm'] = 'Microlights';
$lang['parametres_gestion_ulm_help'] = 'Microlight management.';
$lang['parametres_gestion_tickets'] = 'Flight tickets';
$lang['parametres_gestion_tickets_help'] = '';
$lang['parametres_gestion_pompes'] = 'Fuel pumps';
$lang['parametres_gestion_pompes_help'] = '';
$lang['parametres_gestion_rapprochements'] = 'Bank reconciliation';
$lang['parametres_gestion_rapprochements_help'] = '';
$lang['parametres_gestion_of'] = 'Training offers';
$lang['parametres_gestion_of_help'] = '';
$lang['parametres_veille'] = 'Watch newsletter';
$lang['parametres_veille_help'] = 'Safety and regulatory watch newsletter, published without login.';
$lang['parametres_auto_planchiste'] = 'Pilots enter their flights';
$lang['parametres_auto_planchiste_help'] = 'Pilots can enter their own flights when they are not flight loggers.';
$lang['parametres_module_incomplete'] = 'The "%s" module is active but required settings are missing: complete the %s tab.';

// Lot 2 : onglet Messagerie
$lang['parametres_tab_messagerie'] = 'E-mail';
$lang['parametres_tab_messagerie_help'] = 'E-mail sending server and redirection of messages during tests.';
$lang['parametres_protocol'] = 'Sending method';
$lang['parametres_protocol_help'] = '"mail (PHP)" on shared hosting; "SMTP" to use a sending server (Brevo…).';
$lang['parametres_smtp_host'] = 'SMTP server';
$lang['parametres_smtp_host_help'] = 'Required in SMTP mode, for example smtp-relay.brevo.com.';
$lang['parametres_smtp_port'] = 'SMTP port';
$lang['parametres_smtp_port_help'] = '587 with TLS, 465 with SSL.';
$lang['parametres_smtp_crypto'] = 'Encryption';
$lang['parametres_smtp_crypto_help'] = '';
$lang['parametres_smtp_user'] = 'SMTP login';
$lang['parametres_smtp_user_help'] = 'For Brevo: Brevo dashboard → SMTP & API → SMTP credentials.';
$lang['parametres_smtp_pass'] = 'SMTP password';
$lang['parametres_smtp_pass_help'] = 'For Brevo, the SMTP key (starts with xsmtpsib-).';
$lang['parametres_test_email'] = 'E-mail redirection (tests)';
$lang['parametres_test_email_help'] = 'If set, all e-mails sent by GVV go to this address instead of their recipients. Leave empty in production.';
$lang['parametres_test_phone'] = 'SMS redirection (tests)';
$lang['parametres_test_phone_help'] = 'If set, all text messages sent by GVV go to this number. Leave empty in production.';
$lang['parametres_test_email_title'] = 'Send a test e-mail';
$lang['parametres_test_email_card_help'] = 'The test uses the settings saved above: save your changes first. The test e-mail redirection does not apply to this message.';
$lang['parametres_test_email_to'] = 'Recipient';
$lang['parametres_test_email_send'] = 'Send';
$lang['parametres_test_email_subject'] = 'GVV: test e-mail';
$lang['parametres_test_email_body'] = '<p>This test e-mail was sent from the GVV Configuration page by %s on %s.</p><p>If you can read it, sending e-mails works.</p>';
$lang['parametres_test_email_sent'] = 'Test e-mail sent to %s. Check that it arrives.';
$lang['parametres_test_email_failed'] = 'Sending the test e-mail failed (sending method: %s, sender: %s).';
$lang['parametres_test_email_bad_address'] = 'Invalid recipient address: "%s". No e-mail was sent.';
$lang['parametres_test_email_debug'] = 'Technical details';

// Lot 2 : secrets, lecture seule
$lang['parametres_required'] = 'Required';
$lang['parametres_yes'] = 'Yes';
$lang['parametres_no'] = 'No';
$lang['parametres_secret_defined'] = 'Value set';
$lang['parametres_secret_undefined'] = 'No value';
$lang['parametres_secret_placeholder'] = 'Leave empty to keep the current value';
$lang['parametres_secret_clear'] = 'Clear the stored value';

// Lot 2 : historique
$lang['parametres_history'] = 'History';
$lang['parametres_history_title'] = 'Change history';
$lang['parametres_history_help'] = 'Most recent changes first. Going back to the previous value is checked like an entry, and is itself recorded. A secret cannot be restored.';
$lang['parametres_history_empty'] = 'No change has been recorded for this tab yet.';
$lang['parametres_history_forbidden'] = 'The history of the "%s" tab is reserved for people who can change it.';
$lang['parametres_history_unavailable'] = 'The history is not available yet: the database must be updated (migration).';
$lang['parametres_history_secret'] = 'secret (value not kept)';
$lang['parametres_history_default'] = 'not set';
$lang['parametres_history_action_modification'] = 'Change';
$lang['parametres_history_action_effacement'] = 'Clearing';
$lang['parametres_history_action_retour'] = 'Rollback';
$lang['parametres_restore'] = 'Restore';
$lang['parametres_restore_help'] = 'Go back to the value before this change';
$lang['parametres_restored'] = '"%s" was set back to its previous value.';
$lang['parametres_restore_unchanged'] = '"%s" already has this value: nothing was changed.';
$lang['parametres_restore_failed'] = '"%s" was not changed: %s';
$lang['parametres_back_to_tab'] = 'Back to the tab';
$lang['gvv_vue_parametres_history_short_field_date'] = 'Date';
$lang['gvv_vue_parametres_history_short_field_auteur'] = 'By';
$lang['gvv_vue_parametres_history_short_field_parametre'] = 'Setting';
$lang['gvv_vue_parametres_history_short_field_action'] = 'Action';
$lang['gvv_vue_parametres_history_short_field_ancienne'] = 'Previous value';
$lang['gvv_vue_parametres_history_short_field_nouvelle'] = 'New value';
$lang['gvv_vue_parametres_history_short_field_retour'] = '';

// Lot 2 : erreurs
$lang['parametres_err_option'] = 'value not offered';
$lang['parametres_err_range'] = 'number out of the allowed range';
$lang['parametres_err_history_unknown'] = 'This change does not exist in the history.';
$lang['parametres_err_restore_secret'] = 'a secret cannot be restored from the history';
