<?php

// Simuler l'environnement web
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/';
$_SERVER['REQUEST_METHOD'] = 'GET';


// Définir le répertoire racine du projet
define('FCPATH', realpath(dirname(__FILE__) . '/../../') . '/');

// Chemins vers les dossiers CI
$system_path = FCPATH . 'system';
$application_folder = FCPATH . 'application';

// Constantes CodeIgniter
define('BASEPATH', $system_path . '/');
define('APPPATH', $application_folder . '/');
define('ENVIRONMENT', 'testing');

// Inclure les fichiers de configuration
if (!defined('FILE_READ_MODE')) {
    require_once APPPATH . 'config/constants.php';
}
require_once APPPATH . 'config/config.php';
require_once BASEPATH . 'core/Common.php';


// If you need models/libraries directly:
require __DIR__ . '/../models/achats_model.php';

// Optionally, fake the CI instance if your models rely on it
class CI_Controller {}
class CI_Model {
    public function __construct() {}
}

$CI =& get_instance();
$CI->config = new class {
    public function item($key) { return null; }
};

