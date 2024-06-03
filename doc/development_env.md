# Environmenent de developpement

GVV est developpé sour Windows avec XAMP et PHP 7.

Puis déployé sur des machines Linux avec Apache.

## Local execution sous Windows

### XAMPP

- Lancer Apache
- Lancer MySQL

	http://localhost/gvv2/index.php
	
## Manual test execution

	cd C:\Users\frede\Dropbox\xampp\htdocs\gvv2
	setenv.bat
	php -version
	
Les tests phpunit ne fonctionnent pas/plus sous windows ...

## Tests phpunit sous Linux

- Serveur jenkins pastille : pb lors de la dernière mise à jour.

