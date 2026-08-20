<?php if (! defined('BASEPATH')) exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| Authorization System
|--------------------------------------------------------------------------
|
| use_new_authorization: Enable the new structured authorization system
| (Gvv_Authorization / code-based permissions) instead of the legacy
| DX_Auth PHP-serialized permissions.
|
| DEFAULT: TRUE (new system is the maintained one)
| See doc/plans/2025_authorization_refactoring_plan.md
|
*/
$config['use_new_authorization'] = TRUE;

/*
|--------------------------------------------------------------------------
| OpenFlyers integration
|--------------------------------------------------------------------------
|
| If FALSE or not defined, the OpenFlyers controller is blocked (404) and
| related UI is hidden.
|
*/
$config['openflyers_enabled'] = FALSE;

/* End of file gvv_config.example.php */
/* Location: ./application/config/gvv_config.example.php */
