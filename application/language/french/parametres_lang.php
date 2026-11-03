<?php
/**
 * Langue française — Page Configuration (paramètres unifiés)
 */

$lang['parametres_title']            = 'Configuration';
$lang['parametres_card_desc']        = 'Paramètres du club et de l\'application';
$lang['parametres_no_tab']           = 'Aucun paramètre ne vous est accessible.';
$lang['parametres_forbidden_tab']    = 'Vous n\'avez pas accès à l\'onglet « %s ».';
$lang['parametres_unknown_tab']      = 'L\'onglet « %s » n\'existe pas.';
$lang['parametres_save']             = 'Enregistrer';
$lang['parametres_read_only']        = 'Cet onglet est en lecture seule pour vous.';
$lang['parametres_default_value']    = 'Valeur par défaut de GVV : rien n\'a encore été saisi.';
$lang['parametres_last_update']      = 'Dernière modification le %s par %s';
$lang['parametres_unsaved_confirm']  = 'Des modifications ne sont pas enregistrées. Quitter cet onglet quand même ?';
$lang['parametres_saved']            = 'Paramètres enregistrés :';
$lang['parametres_nothing_changed']  = 'Aucune modification à enregistrer.';
$lang['parametres_not_saved']        = 'Paramètres non enregistrés :';
$lang['parametres_preview']          = 'Aperçu';
$lang['parametres_public_url']       = 'Adresse publique';
$lang['parametres_copy']             = 'Copier';
$lang['parametres_copied']           = 'Adresse copiée';

// États des onglets
$lang['parametres_status_complete']   = 'Complet';
$lang['parametres_status_incomplete'] = 'Incomplet';
$lang['parametres_status_optional']   = 'Optionnel, non configuré';

// Langues
$lang['parametres_lang_french']  = 'Français';
$lang['parametres_lang_english'] = 'English';
$lang['parametres_lang_dutch']   = 'Nederlands';

// Onglet Pages publiques
$lang['parametres_tab_pages_publiques']      = 'Pages publiques';
$lang['parametres_tab_pages_publiques_help'] = 'Pages consultables sans connexion. Les règles de confidentialité et les conditions d\'utilisation sont accessibles depuis la page de connexion.';
$lang['parametres_legal_confidentialite']      = 'Règles de confidentialité';
$lang['parametres_legal_confidentialite_help'] = 'Texte propre au club, au format Markdown. Laissé vide, un texte générique de GVV est utilisé. Les informations du club peuvent être insérées : {nom_club}, {adresse_club}, {cp_club}, {ville_club}, {email_club}, {tel_club}, {url_club}, {contact_rgpd}.';
$lang['parametres_legal_conditions']           = 'Conditions d\'utilisation';
$lang['parametres_legal_conditions_help']      = 'Texte propre au club, au format Markdown. Laissé vide, un texte générique de GVV est utilisé. Mêmes informations du club insérables que pour les règles de confidentialité.';
$lang['parametres_newsletter_page']            = 'Newsletters';
$lang['parametres_generic_text']               = 'Texte générique de GVV (utilisé tant que rien n\'est saisi) :';

// Erreurs
$lang['parametres_err_unknown']          = 'paramètre inconnu';
$lang['parametres_err_forbidden']        = 'vous n\'avez pas le droit de le modifier';
$lang['parametres_err_language']         = 'langue inconnue';
$lang['parametres_err_required']         = 'valeur obligatoire';
$lang['parametres_err_max_length']       = 'texte trop long';
$lang['parametres_err_email']            = 'adresse e-mail invalide';
$lang['parametres_err_url']              = 'adresse web invalide (elle doit commencer par http:// ou https://)';
$lang['parametres_err_integer']          = 'nombre entier attendu';
$lang['parametres_err_db']               = 'erreur d\'enregistrement en base de données';
$lang['parametres_err_file_missing_file'] = 'le fichier de configuration %s est absent du serveur';
$lang['parametres_err_file_not_writable'] = 'le fichier %s n\'est pas modifiable par le serveur web (sur le serveur : chmod g+w %s)';
$lang['parametres_err_file_syntax_error'] = 'la modification rendrait le fichier %s invalide, elle n\'a pas été faite';
$lang['parametres_err_file_write_failed'] = 'l\'écriture du fichier %s a échoué';
$lang['parametres_err_file_invalid_key']  = 'nom de paramètre invalide';
$lang['parametres_err_token']            = "La page a expiré ou la demande ne provient pas de la page Configuration : rien n'a été enregistré.";
$lang['parametres_reload']               = "Recharger la page";
