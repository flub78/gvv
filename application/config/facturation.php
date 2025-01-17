<?php if (! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Fichier de configuration de la facturation
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Détermine si un vol peut-être facturé à un autre membre.
|--------------------------------------------------------------------------
|
| C'est pratique pour facturer les vols du remorqueur au pilote du planeur
| quand les remorqués sont facturés au centième.
|
*/
$config['payeur_non_pilote'] = TRUE;
$config['partage'] = TRUE;

$config['remorque_100eme'] = FALSE;

/*
|--------------------------------------------------------------------------
| Gestion de la pompe à essence par le programme
|--------------------------------------------------------------------------
*/
$config['gestion_pompes'] = TRUE;

/*
|--------------------------------------------------------------------------
| Date de gel, les vols et écritures avant cette date sont rejetés
|--------------------------------------------------------------------------
*/
$config['date_gel'] = '31/12/2024';

/* End of file facturation.php */
