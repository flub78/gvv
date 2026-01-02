# XDebug

Parfois un debugger peut s’avérer utile.

## Installation

### Windows

copier le résultat de phpinfo

	http://localhost/gvv2/index.php/admin/info
	
et le coller dans

	https://xdebug.org/wizard

suivre les instructions (avec certains versions de xampp il il a déjà un fichier php_xdebug.dll

```
[xdebug]
xdebug.log=c:\logs\xdebug.log
; enable/disable xdebug in Eclipse in commenting in or out the following line (and restarting Apache)
; xdebug.mode = debug
xdebug.start_with_request = yes
xdebug.client_port = 9003

zend_extension = xdebug
```
### Linux

https://www.cloudways.com/blog/php-debug/

```
	sudo apt install php7.4-xdebug
	
	sudo vi /etc/php/7.4/mods-available/xdebug.ini
	sudo service apache2 restart

	/usr/bin/php7.4 -v
		PHP 7.4.33 (cli) (built: Nov 24 2024 08:41:15) ( NTS )
		Copyright (c) The PHP Group
		Zend Engine v3.4.0, Copyright (c) Zend Technologies
			with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies
			with Xdebug v3.1.6, Copyright (c) 2002-2022, by Derick Rethans
```

## Configuration Eclipse

	https://wiki.eclipse.org/Debugging_using_XDebug
	

In Eclipse, click Window > Preferences > PHP > Debug > Installed Debuggers.
Select XDebug.
Click Configure.
From the Accept remote session (JIT) list, select prompt. ...
Click OK, then click OK again.
Create a PHP project that contains the files that you want to debug.

## Utilisation with Visual Studio Code

Install the PHP Debug extension.

Run -> Add Configuration...


### To debug GVV served locally with Apache

#### Check configuration

```
php --version
PHP 7.4.33 (cli) (built: Jul  3 2025 16:41:49) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
	with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies	
	with Xdebug v3.1.6, Copyright (c) 2002-2022, by Derick Rethans
``` 

##### launch.json
```
	"configurations": [
		
		{
			"name": "Listen for Xdebug",
			"type": "php",
			"request": "launch",
			"port": 9003,
			"log": true,
			// "pathMappings": {
			//     "/var/www/html/gvv.net": "${workspaceFolder}",
			//     "/home/frederic/git/gvv": "${workspaceFolder}" // Adjust paths
			// },
		},
```

Run - Start Debugging

Si le programme s’arrête quand il y a une instruction xdebug_break() mais pas quand on pause les breakpoints dans VSC 

pathMappings incorrect
Xdebug reçoit un chemin serveur, VSCode un chemin local. S'ils ne correspondent pas, les breakpoints ne matchent pas.

string(32) "/home/frederic/git/gvv/index.php"

/etc/php/7.4/cli/conf.d/20-xdebug.ini,

replaced 
xdebug.start_with_request=trigger
with
xdebug.start_with_request=yes

sudo systemctl restart apache2

### To debug phpunit tests


#### Check configuration

```
php --version
PHP 7.4.33 (cli) (built: Jul  3 2025 16:41:49) ( NTS )
Copyright (c) The PHP Group
Zend Engine v3.4.0, Copyright (c) Zend Technologies
	with Zend OPcache v7.4.33, Copyright (c), by Zend Technologies	
	with Xdebug v3.1.6, Copyright (c) 2002-2022, by Derick Rethans

php -i | grep xdebug.mode
php -i | grep xdebug.client_port
php -i | grep xdebug.start_with_request
             Enabled Features (through 'xdebug.mode' setting)             
xdebug.mode => debug => debug
xdebug.client_port => 9003 => 9003
xdebug.start_with_request => trigger => trigger
```

#### launch.json
```
    "configurations": [
        
        {
            "name": "Listen for Xdebug",
            "type": "php",
            "request": "launch",
            "port": 9003,
            "log": true,
            // "pathMappings": {
            //     "/var/www/html/gvv.net": "${workspaceFolder}",
            //     "/home/frederic/git/gvv": "${workspaceFolder}" // Adjust paths
            // },
        },
```

#### Test execution

```
XDEBUG_SESSION=1 php /usr/local/bin/phpunit --configuration phpunit_mysql.xml --no-coverage --filter testSoldeCompteGestionWithCodecRange application/tests/mysql/EcrituresModelSoldeCompteGestionMySqlTest.php
```
