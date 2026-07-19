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
define('VP_PO', 4);    // Vol porte ouverte planeur
define('VP_BIA', 5);   // Vol BIA planeur

# Catégories de vols avion supplémentaires
define('PO', 5);        // Vol porte ouverte
define('BIA', 6);       // Vol BIA
define('CONVOYAGE', 7);      // Vol de convoyage (facturé à demi-tarif)
define('REMISE_EN_VOL', 8);  // Remise en vol (heures facturées, pas de supplément DC)
define('STANDARDISATION', 9); // Vol de standardisation (gratuit)

define('PROPRIO_PRIVE', 1);



/* End of file constants.php */
/* Location: ./application/config/constants.php */