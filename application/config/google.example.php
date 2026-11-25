<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/*
|--------------------------------------------------------------------------
| Fichier de configuration des accès au compte Google (calendrier des présences)
|--------------------------------------------------------------------------
| Copier ce fichier en google.php (non suivi par git) et renseigner les valeurs
| du client OAuth créé dans la console Google Cloud du club.
| Ne jamais mettre de vrais identifiants dans ce fichier d'exemple : il est
| publié avec le code source.
*/

$config['client_id'] = '';      // identifiant du client OAuth (...apps.googleusercontent.com)
$config['client_secret'] = '';  // secret du client OAuth
$config['api_key'] = '';        // clé d'API
$config['token'] = '';          // jeton obtenu lors de la première autorisation (JSON)
/* End of file google.php */
