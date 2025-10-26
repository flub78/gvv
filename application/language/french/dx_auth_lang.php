<?php

/*
	It is recommended for you to change 'auth_login_incorrect_password' and 'auth_login_username_not_exist' into something vague.
	For example: Username and password do not match.
*/

$lang['auth_login_incorrect_password'] = "Votre mot de passe est incorrect.";
$lang['auth_login_username_not_exist'] = "Utilisateur inconnu.";

$lang['auth_username_or_email_not_exist'] = "Utilisateur ou courriel inconnu.";
$lang['auth_not_activated'] = "Votre compte n'a pas encore été activé. Relevez votre courrier électronique SVP.";
$lang['auth_no_user_role'] = "Vous n'avez pas le droit de vous connecter à cette section.";
$lang['auth_request_sent'] = "Vous avez déjà demandé à changer de mot de passe. Relevez votre courrier électronique SVP.";
$lang['auth_incorrect_old_password'] = "Mot de passe précédant incorrect.";
$lang['auth_incorrect_password'] = "Mot de passe incorrect.";

// Email subject
$lang['auth_account_subject'] = "%s détails du compte";
$lang['auth_activate_subject'] = "%s activation";
$lang['auth_forgot_password_subject'] = "Demande de nouveau mot de passe";

// Email content
$lang['auth_account_content'] = "Bienvenue à %s,

Merci de vous être enregistré. Votre compte a été créé.

Vous pouvez vous connecter avec votre identifiant ou votre adresse email:

Identifiant: %s
Email: %s
Mot de passe: %s

Vous pouvez vous connecter à l'adresse %s

Nous espérons que vous apprécierez le service.

Cordialement,
L'équipe %s";

$lang['auth_activate_content'] = "Bienvenue %s,

Pour activer votre compte, cliquez sur le lien d'activation ci-dessous:
%s

Faites le avant %s heures, sinon votre enregistrement deviendra obsolète et vous aurez à vous enregistrer à nouveau.

Vous pouvez vous connecter avec votre identifiant ou votre adresse email:

Identifiant: %s
Email: %s
Mot de passe: %s

Vous pouvez vous connecter à l'adresse %s

Nous espérons que vous apprécierez le service.

Cordialement,
L'équipe %s
";

$lang['auth_forgot_password_content'] = "%s,

Vous avez demandé à changer votre mot de passe.
Cliquez sur le lien suivant pour compléter le changement: 
%s

Votre nouveau mot de passe: %s
Votre clé d'activation: %s

Une fois la procédure terminée, vous pourrez changer ce mot de passe pour un autre.
En cas de problème, contactez %s.

Cordialement,
L'équipe %s
";

/* End of file dx_auth_lang.php */
/* Location: ./application/language/english/dx_auth_lang.php */