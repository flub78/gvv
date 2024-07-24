# Installation

La machine utilisée lors de la rédaction de cette documentation est une machine virtuelle Oracle free tier sur laquelle est installé Ubuntu 22.04 et Hestia Control Panel. https://hestiacp.com/docs/introduction/getting-started.html.

C'est un environment entièrement gratuit, à vie, sans publicité et sans limite d'utilisation. Il est donc possible de l'utiliser pour tester et déployer GVV.

## Prérequis

* une machine avec PHP 7.4 et MySql 5.x (linux ou windows, linux recommandé)
* un serveur web (Apache ou Nginx)

## Étapes d'installation

1. Configurer le serveur WEB, Apache ou Nginx, voir https://www.digitalocean.com/community/tutorials/how-to-install-linux-apache-mysql-php-lamp-stack-on-ubuntu-18-04. 

Installez une page WEB de test pour vérifier que le serveur web est bien configuré et accessible sur votre domaine.

Installez les certificats SSL.

Installez MySql et créez une base de données.

Notez que pour les utilisateurs de Hestia Control Panel, il est possible de réaliser ces étapes directement depuis l'interface web.

2. Téléchargez GVV à partir de https://github.com/flub78/gvv.

Connectez vous à votre serveur avec SSH et allez dans le répertoire web. Dans mon cas ~/web/gvvg.flub78.net.

Donnez les droits d'écriture sur le répertoire.

git clone https://github.com/flub78/gvv.git

Renommez le répertoire gvv en public_html

Vérifiez l'accès https://gvvg.flub78.net/install/

![Image fenetre installation](./images/installation1.png)
