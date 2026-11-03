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
