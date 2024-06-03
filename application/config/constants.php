<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| File and Directory Modes
|--------------------------------------------------------------------------
|
| These prefs are used when checking and setting modes when working
| with the file system.  The defaults are fine on servers with proper
| security, but you may wish (or even need) to change the values in
| certain environments (Apache running a separate process for each
| user, PHP under CGI with Apache suEXEC, etc.).  Octal values should
| always be used to set the mode correctly.
|
*/
define('FILE_READ_MODE', 0644);
define('FILE_WRITE_MODE', 0666);
define('DIR_READ_MODE', 0755);
define('DIR_WRITE_MODE', 0777);

/*
|--------------------------------------------------------------------------
| File Stream Modes
|--------------------------------------------------------------------------
|
| These modes are used when working with fopen()/popen()
|
*/

define('FOPEN_READ',							'rb');
define('FOPEN_READ_WRITE',						'r+b');
define('FOPEN_WRITE_CREATE_DESTRUCTIVE',		'wb'); // truncates existing file data, use with care
define('FOPEN_READ_WRITE_CREATE_DESTRUCTIVE',	'w+b'); // truncates existing file data, use with care
define('FOPEN_WRITE_CREATE',					'ab');
define('FOPEN_READ_WRITE_CREATE',				'a+b');
define('FOPEN_WRITE_CREATE_STRICT',				'xb');
define('FOPEN_READ_WRITE_CREATE_STRICT',		'x+b');

/*
|--------------------------------------------------------------------------
| GVV constants
|--------------------------------------------------------------------------
|
*/

define ("DEFAULT_DATE", "");
define ("PER_PAGE", 50);

define('CREATION', 1);
define('MODIFICATION', 2);
define('VISUALISATION', 3);
define('MAJ', 3);
define('EMAIL', 4);
define('VALIDATION', 5);

if (!defined("PHPUNIT")) {
	# Execution normale
	define('PHPUNIT', false);
	define ('PCHART', './application/third_party/pChart/');
} else {
	# Execution sous le control de cppunit
	# Etonament les variables d'environement ne sont pas passées au script
	# quand il est controlé par phpunit
	define ('PCHART', getcwd() . '/../application/third_party/pChart/');
}
# echo "PCHART = " . PCHART . "\n";
# echo "PHPUNIT = " . PHPUNIT . "\n";

# Types de lancement
define('TREUIL', 1);
define('AUTONOME', 2);
define('REM', 3);
define('EXTERIEUR', 3);

# Types de vols planeur pour la facturation
define('STANDARD', 0);
define('VI', 1);
define('VE', 2);
define('CONCOURS', 3);

define('PROPRIO_PRIVE', 1);


# Valeurs pour Mniveau (champ de bits pour la fiche pilote)
define("INTERNET", 1);
define("PRESIDENT", 2);
define("VICE_PRESIDENT", 4);
define("TRESORIER", 8);
define("SECRETAIRE", 16);
define("SECRETAIRE_ADJ", 32);
define("CA", 64);
define("CHEF_PILOTE", 128);
define("VI_PLANEUR", 256);
define("VI_AVION", 512);
define("MECANO", 1024);
define("PILOTE_PLANEUR", 2048);
define("PILOTE_AVION", 4096);
define("REMORQUEUR", 8192);
define("PLIEUR", 16384);
define("ITP", 32768);
define("IVV", 65536);
define("FI_AVION", 131072);
define("FE_AVION", 262144);
define("TREUILLARD", 524288);
define("CHEF_DE_PISTE", 1048576);


/* End of file constants.php */
/* Location: ./application/config/constants.php */