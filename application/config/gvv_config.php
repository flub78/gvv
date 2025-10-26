<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/*
|--------------------------------------------------------------------------
| GVV Application Configuration
|--------------------------------------------------------------------------
|
| Custom configuration settings for GVV application features.
|
*/

/*
|--------------------------------------------------------------------------
| Authorization System
|--------------------------------------------------------------------------
|
| use_new_authorization: Enable the new structured authorization system
|
| Set to TRUE to use the new Gvv_Authorization library with role_permissions
| and data_access_rules tables.
|
| Set to FALSE to use the legacy DX_Auth system with PHP-serialized permissions.
|
| DEFAULT: FALSE (use legacy system until migration is complete)
|
| @see /doc/plans/2025_authorization_refactoring_plan.md
| @see application/libraries/Gvv_Authorization.php
| @see application/libraries/DX_Auth.php
|
*/
$config['use_new_authorization'] = false;

/*
|--------------------------------------------------------------------------
| Authorization Debug Mode
|--------------------------------------------------------------------------
|
| Enable detailed logging of authorization checks (access granted/denied)
|
| When TRUE, all authorization decisions are logged to application/logs/
|
| DEFAULT: FALSE (only log access denied in production)
|
*/
$config['authorization_debug'] = FALSE;

/*
|--------------------------------------------------------------------------
| Progressive Migration
|--------------------------------------------------------------------------
|
| Enable per-user progressive migration to new authorization system
|
| When TRUE, users can be migrated individually based on
| authorization_migration_status table.
|
| When FALSE, all users use the same system (based on use_new_authorization)
|
| DEFAULT: FALSE (global setting only)
|
*/
$config['authorization_progressive_migration'] = FALSE;

/* End of file gvv_config.php */
/* Location: ./application/config/gvv_config.php */
