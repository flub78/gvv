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
