# Envoie d'e-mails

GVV peut envoyer des emails pour différentes raisons, telles que la réinitialisation des mots de passe, les notifications aux utilisateurs, l'envoie des bons vols de découverte, etc. Cette section décrit la conception et l'implémentation de la fonctionnalité d'envoi d'e-mails dans GVV.

Le fonctionnalité d'envoi d'e-mails directement aux membres est considéré comme obsolète. Elle a été remplacé par la fonctionnalité de gestion des adresses emails. On ne peut plus envoyer d'email à une liste mais on peut facilement gérer les listes d'adresse email via l'interface d'administration. 

En fait la fonctionnalité d'envoie était incomplète puisqu'elle ne permettait pas la reception des réponses aux emails envoyés.

## Configuration

Pour les mots de passe oubliés, la configuration des emails se fait dans le fichier application/config/email.php.

CodeIgniter est compatible avec le proxy SMTP de Brevo, avec la configuration par défaut de Ionos, etc.

Attention si vous utilisez Brevo, il faut que l'adresse de réponse soit une adresse validée dans votre compte Brevo.

## Utilisation de Brevo comme proxy SMTP

Le serveur de test sur Oracle free tier ne permet pas d'envoyer des emails directement. Il faut utiliser un proxy SMTP. J'ai choisi Brevo (anciennement Sendinblue) pour sa simplicité d'utilisation et son offre gratuite généreuse.

https://www.brevo.com/fr/

Il est également installé sur gvv.net sur ma machine de test.

Voir Transactionnel
    Temps réel
    Logs

### Paramètres de configuration

Pour configurer la whiteliste Parametres - Sécurité IPs autorisées

utiliser curl ifconfig.me pour trouver votre adresse IP publique et l'ajouter à la liste des IPs autorisées dans Brevo.

### Générer une clé API 

Utiliser une clé différente pour chaque usage

Générer une clé et créer un fichier application/config/email.php.

Copier la clé dans $config['smtp_pass']

Les paramètres 
'''
$config['protocol']    = 'smtp';
$config['smtp_host']   = 'smtp-relay.brevo.com';
$config['smtp_port']   = 587;              // 587=STARTTLS, 465=SSL, 2525=STARTTLS
$config['smtp_crypto'] = 'tls';            // 'tls' for 587/2525, 'ssl' for 465

// Brevo credentials à recopier depuis la page de génération de la clé API
$config['smtp_user']   = 'xxxxxxxxxxx@smtp-brevo.com';    

// Clé Brevo oracle_gvvg générér le 05/04/2026
$config['smtp_pass']   = 'xsmtpsib-xxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxxx';

Tester en envoyant un VD