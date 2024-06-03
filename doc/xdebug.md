# XDebug

Parfois un debugger peut s'averer utile.

## Installation

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

## Configuration Eclipse

	https://wiki.eclipse.org/Debugging_using_XDebug
	

In Eclipse, click Window > Preferences > PHP > Debug > Installed Debuggers.
Select XDebug.
Click Configure.
From the Accept remote session (JIT) list, select prompt. ...
Click OK, then click OK again.
Create a PHP project that contains the files that you want to debug.


```