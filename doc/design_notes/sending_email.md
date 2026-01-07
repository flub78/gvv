# Envoie d'e-mails

GVV peut envoyer des emails pour différentes raisons, telles que la réinitialisation des mots de passe, les notifications aux utilisateurs, l'envoie des bons vols de découverte, etc. Cette section décrit la conception et l'implémentation de la fonctionnalité d'envoi d'e-mails dans GVV.

Le fonctionnalité d'envoi d'e-mails directement aux membres est considéré comme obsolète. Elle a été remplacé par la fonctionnalité de gestion des adresses emails. On ne peut plus envoyer d'email à une liste mais on peut facilement gérer les listes d'adresse email via l'interface d'administration. 

En fait la fonctionnalité d'envoie était incomplète puisqu'elle ne permettait pas la reception des réponses aux emails envoyés.

## Configuration

Pour les mots de passe oubliés, la configuration des emails se fait dans le fichier application/config/email.php.

CodeIgniter est compatible avec le proxy SMTP de Brevo, avec la configuration par défaut de Ionos, etc.

Attention si vous utilisez Brevo, il faut que l'adresse de réponse soit une adresse validée dans votre compte Brevo.