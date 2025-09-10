<?php

// Real database integration test bootstrap - loads full CodeIgniter framework with real DB

// Define constants that CodeIgniter needs
define('BASEPATH', dirname(__FILE__) . '/../../system/');
define('APPPATH', dirname(__FILE__) . '/../');
define('ENVIRONMENT', 'testing');

// Suppress notices for cleaner test output
error_reporting(E_ALL & ~E_NOTICE & ~E_STRICT & ~E_DEPRECATED);

// Mock some globals that CI expects
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REQUEST_URI'] = '/test';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['QUERY_STRING'] = '';
$_SERVER['PHP_SELF'] = '/index.php';

// Load CodeIgniter core functions
require_once BASEPATH . 'core/Common.php';

// Create a test-specific database configuration
$test_db_config = [
    'hostname' => 'localhost',
    'username' => 'gvv_user',
    'password' => 'lfoyfgbj',
    'database' => 'gvv2',
    'dbdriver' => 'mysqli',
    'dbprefix' => '',
    'pconnect' => FALSE, // Use non-persistent connections for tests
    'db_debug' => FALSE, // Don't show errors in tests, let tests handle them
    'cache_on' => FALSE,
    'cachedir' => '',
    'char_set' => 'utf8',
    'dbcollat' => 'utf8_general_ci',
    'swap_pre' => '',
    'autoinit' => TRUE,
    'stricton' => FALSE
];

// Temporarily override database config for testing
$original_config_file = APPPATH . 'config/database.php';
$backup_config_file = APPPATH . 'config/database.php.backup.' . time();
$temp_config_created = false;

// Backup original config and create test config
if (file_exists($original_config_file)) {
    copy($original_config_file, $backup_config_file);
    
    // Create test database config
    $config_content = "<?php\n";
    $config_content .= "\$active_group = 'default';\n";
    $config_content .= "\$active_record = TRUE;\n";
    $config_content .= "\$db['default'] = " . var_export($test_db_config, true) . ";\n";
    
    file_put_contents($original_config_file, $config_content);
    $temp_config_created = true;
}

// Load CodeIgniter framework classes
require_once BASEPATH . 'core/Controller.php';
require_once BASEPATH . 'core/Model.php';
require_once BASEPATH . 'core/Loader.php';
require_once BASEPATH . 'libraries/Database.php';
require_once BASEPATH . 'database/DB.php';

// Define CI_Model if not already defined
if (!class_exists('CI_Model')) {
    class CI_Model extends CI_Model_core {
        // Use the original CodeIgniter model class
    }
}

// Create a simplified CI instance for testing
class TestCI {
    public $load;
    public $db;
    public $session;
    public $config;
    
    public function __construct() {
        // Load config
        $this->config = new CI_Config();
        $this->config->load('database');
        
        // Initialize database
        $this->db = DB();
        
        // Create mock session
        $this->session = new MockSession();
        
        // Create loader
        $this->load = new TestLoader($this);
    }
}

class TestLoader {
    private $ci;
    
    public function __construct($ci) {
        $this->ci = $ci;
    }
    
    public function model($model, $name = '', $db_conn = FALSE) {
        $model_file = APPPATH . 'models/' . $model . '.php';
        if (file_exists($model_file)) {
            require_once $model_file;
            
            // Instantiate the model
            $model_class = ucfirst($model);
            $this->ci->$model = new $model_class();
            return true;
        }
        return false;
    }
    
    public function library($library, $params = NULL, $object_name = NULL) {
        // For testing, we'll mock libraries we need
        if ($library === 'gvvmetadata') {
            $this->ci->gvvmetadata = new MockGvvMetadata();
        }
        return true;
    }
    
    public function database($db = '', $return = FALSE, $active_record = NULL) {
        // Database already loaded in constructor
        return true;
    }
    
    public function helper($helpers = array()) {
        // Mock helper loading
        return true;
    }
}

// Mock session for testing
class MockSession {
    private $userdata = ['section' => 1];
    
    public function userdata($key) {
        return isset($this->userdata[$key]) ? $this->userdata[$key] : null;
    }
    
    public function set_userdata($key, $value = null) {
        if (is_array($key)) {
            $this->userdata = array_merge($this->userdata, $key);
        } else {
            $this->userdata[$key] = $value;
        }
    }
}

// Mock GVV metadata
class MockGvvMetadata {
    public function store_table($name, $data, $query = '') {
        // Mock metadata storage
        return true;
    }
}

// Create CI_Config class if needed
if (!class_exists('CI_Config')) {
    class CI_Config {
        private $config = array();
        
        public function load($file, $use_sections = FALSE, $fail_gracefully = FALSE) {
            $file_path = APPPATH . 'config/' . $file . '.php';
            if (file_exists($file_path)) {
                include($file_path);
                // Database config variables should now be available
                if (isset($db)) {
                    $this->config['database'] = $db;
                }
                return TRUE;
            }
            return FALSE;
        }
        
        public function item($item) {
            return isset($this->config[$item]) ? $this->config[$item] : FALSE;
        }
    }
}

// Add missing functions
if (!function_exists('gvv_error')) {
    function gvv_error($message) {
        error_log("GVV Error: " . $message);
    }
}

if (!function_exists('log_message')) {
    function log_message($level, $message) {
        error_log("CI $level: " . $message);
    }
}

// Set up the global CI instance
global $CI;
$CI = new TestCI();

// Ensure get_instance() function works
if (!function_exists('get_instance')) {
    function &get_instance() {
        global $CI;
        return $CI;
    }
}

// Test database connection
try {
    $CI->db->get('categorie', 1); // Try to get one record to test connection
    echo "Database connection successful for testing\n";
} catch (Exception $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    // Don't fail here, let the tests handle it
}

// Load required models
require_once APPPATH . 'models/common_model.php';
require_once APPPATH . 'models/categorie_model.php';

// Clean up function to restore original database config
function restore_database_config() {
    global $backup_config_file, $original_config_file, $temp_config_created;
    
    if ($temp_config_created && file_exists($backup_config_file)) {
        copy($backup_config_file, $original_config_file);
        unlink($backup_config_file);
    }
}

// Register cleanup function
register_shutdown_function('restore_database_config');
