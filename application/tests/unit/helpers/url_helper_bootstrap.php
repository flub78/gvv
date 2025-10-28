<?php

// Custom bootstrap for URL helper tests
// This bootstrap avoids loading real models with database dependencies

// Define some constants that might be needed
if (!defined('BASEPATH')) {
    define('BASEPATH', dirname(__FILE__) . '/../../../../system/');
}
if (!defined('APPPATH')) {
    define('APPPATH', dirname(__FILE__) . '/../../../');
}
if (!defined('ENVIRONMENT')) {
    define('ENVIRONMENT', 'testing');
}

// Load CodeIgniter core
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'core/Config.php';

// Load helpers
require_once BASEPATH . 'helpers/url_helper.php';
require_once APPPATH . 'helpers/assets_helper.php';

// Mock config class
class MockConfig {
    private $config = array();

    public function item($item) {
        return isset($this->config[$item]) ? $this->config[$item] : NULL;
    }

    public function set_item($item, $value) {
        $this->config[$item] = $value;
    }

    public function site_url($uri = '', $protocol = NULL) {
        $base_url = $this->item('base_url');
        $index_page = $this->item('index_page');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        if (!empty($index_page)) {
            $base_url .= $index_page . '/';
        }

        return $base_url . ltrim($uri, '/');
    }

    public function base_url($uri = '', $protocol = NULL) {
        $base_url = $this->item('base_url');

        if (empty($base_url)) {
            $base_url = 'http://localhost/';
        }

        if (substr($base_url, -1) !== '/') {
            $base_url .= '/';
        }

        return $base_url . ltrim($uri, '/');
    }

    public function slash_item($item) {
        $value = $this->item($item);
        if (empty($value)) {
            return '';
        }
        return rtrim($value, '/') . '/';
    }
}

// Mock loader that doesn't actually load models
class MockLoader {
    private $CI;

    public function __construct(&$CI) {
        $this->CI =& $CI;
    }

    public function model($name) {
        // Don't actually load models - use the mocks already set on CI
        return TRUE;
    }
}

// Set up get_instance() function
if (!function_exists('get_instance')) {
    function &get_instance() {
        static $CI;
        if (!isset($CI)) {
            $CI = new stdClass();
            $CI->config = new MockConfig();
            $CI->load = new MockLoader($CI);
        }
        return $CI;
    }
}
