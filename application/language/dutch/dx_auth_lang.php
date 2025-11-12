<?php

/*
	It is recommended for you to change 'auth_login_incorrect_password' and 'auth_login_username_not_exist' into something vague.
	For example: Username and password do not match.
*/

$lang['auth_login_incorrect_password'] = "Uw wachtwoord was foutief";
$lang['auth_login_username_not_exist'] = "Gebruikersnaam bestaat niet.";

$lang['auth_username_or_email_not_exist'] = "Gebruikersnaam of e-mailadres bestaat niet.";
$lang['auth_not_activated'] = "Uw account werd nog niet geactiveerd, gelieve uw mailbox te controleren.";
$lang['auth_no_user_role'] = "U heeft geen toestemming om in te loggen op deze sectie.";
$lang['auth_request_sent'] = "Uw aanvraag voor wijzigen wachtwoord werd reeds gestuurd, gelieve uw mailbox te controleren..";
$lang['auth_incorrect_old_password'] = "Het oude wachtwoord is foutief.";
$lang['auth_incorrect_password'] = "Uw wachtwoord is foutief.";

// Email subject
$lang['auth_account_subject'] = "%s account details";
$lang['auth_activate_subject'] = "%s activatie";
$lang['auth_forgot_password_subject'] = "Aanvraag nieuw wachtwoord";

// Email content
$lang['auth_account_content'] = "Welkom bij %s,

Bedankt om te registreren, uw account werd succesvol aangemaakt.

U kan inloggen met volgende gegevens:

Login: %s
E-mail: %s
Wachtwoord: %s

U kan aanloggen door volgende link te volgen: %s


Groeten,
Het %s Team";

$lang['auth_activate_content'] = "Welkom bij %s,

Om uw account te activeren gelieve onderstaande link te volgen:
%s

Gelieve uw account binnen de %s uren te activeren, anders dient u de registratie opnieuw te doorlopen.

U kan inloggen met volgende gegevens:

Login: %s
Email: %s
Password: %s


Groeten,
Het %s Team";

$lang['auth_forgot_password_content'] = "%s,

U heeft een aanvraag voor een nieuw paswoord gedaan.
Gelieve volgende link te volgen om dit te bevestigen:
%s

Nieuw paswoord: %s
Activatiesleutel: %s

Nadat u de procedure hebt doorlopen kan u opnieuw inloggen met bovenstaand paswoord.
In geval van problemen contacteer:  %s.

Groeten,
Het %s Team";

/* End of file dx_auth_lang.php */
/* Location: ./application/language/english/dx_auth_lang.php */