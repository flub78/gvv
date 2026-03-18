<?php
defined('BASEPATH') OR exit('No direct script access allowed');

if (!class_exists('MY_Controller')) {
    require_once(APPPATH . 'core/MY_Controller.php');
}

/**
 * GVV Controller (non-CRUD)
 *
 * Base class for controllers without CRUD/metadata operations (Welcome, Auth,
 * Login_as, etc.). Inherits authentication from MY_Controller.
 *
 * @package    GVV
 * @subpackage Core
 */
class Gvv_Controller extends MY_Controller
{
}
