# 2011 GVV Authorization System

## Overview

GVV implements a comprehensive role-based access control (RBAC) system using the DX_Auth library for CodeIgniter 2.x. The system provides hierarchical role inheritance and URI-based permissions to control user access to different parts of the application.

This is the description of the legacy system. The mechanism is going to be migrated to a system that takes the section into account.

## Architecture

The authorization system consists of three main components:

1. **User Management** - Managed through `backend/users` controller
2. **Role Hierarchy** - Managed through `backend/roles` controller
3. **URI Permissions** - Managed through `backend/uri_permissions` controller

## Database Schema

### 1. Users Table

The `users` table stores all user accounts and their authentication information.

```sql
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL DEFAULT 1,
  `username` varchar(25) NOT NULL,
  `password` varchar(34) NOT NULL,
  `email` varchar(100) NOT NULL,
  `banned` tinyint(1) NOT NULL DEFAULT 0,
  `ban_reason` varchar(255) DEFAULT NULL,
  `newpass` varchar(34) DEFAULT NULL,
  `newpass_key` varchar(32) DEFAULT NULL,
  `newpass_time` datetime DEFAULT NULL,
  `last_ip` varchar(40) NOT NULL,
  `last_login` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `created` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
  `modified` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key fields:**
- `id`: Primary key, auto-incremented user identifier
- `role_id`: Foreign key to `roles` table, determines user's role
- `username`: Unique username (max 25 chars)
- `password`: MD5 hashed password (34 chars)
- `email`: User email address for notifications and password reset
- `banned`: Flag indicating if user is banned (0=active, 1=banned)
- `ban_reason`: Text reason for ban (if applicable)
- `newpass` / `newpass_key` / `newpass_time`: Password reset mechanism fields
- `last_ip` / `last_login`: Audit trail for security
- `created` / `modified`: Timestamp tracking

### 2. Roles Table

The `roles` table defines all available roles in a hierarchical structure.

```sql
CREATE TABLE `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `parent_id` int(11) NOT NULL DEFAULT 0,
  `name` varchar(30) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key fields:**
- `id`: Primary key, role identifier
- `parent_id`: Foreign key to parent role (0 = no parent, root role)
- `name`: Role name (e.g., 'admin', 'editor', 'user')

**Hierarchical Structure:**
- Roles form a tree structure through `parent_id`
- Child roles inherit permissions from parent roles
- A user with role X automatically has permissions of all parent roles up to the root
- The 'admin' role has special privileges (bypasses URI permission checks)

### 3. Permissions Table

The `permissions` table stores serialized permission data for each role.

```sql
CREATE TABLE `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `data` text DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

**Key fields:**
- `id`: Primary key
- `role_id`: Foreign key to `roles` table
- `data`: Serialized PHP array containing permission data

**Permission Data Structure:**
The `data` field contains a serialized array with keys:
- `'uri'`: Array of allowed URI patterns (e.g., `['/controller/', '/controller/action/']`)
- Custom keys for other permission types (e.g., `'edit'`, `'delete'`)

Example serialized data:
```php
[
    'uri' => ['/', '/welcome/', '/membre/', '/membre/edit/'],
    'edit' => true,
    'delete' => false
]
```

### 4. Additional Role Tables (Section-based roles)

These tables are for futur, non hierarchical, section based role management.

GVV also implements section-specific roles through two additional tables:

**types_roles** - Role types applicable within sections:
```sql
CREATE TABLE `types_roles` (
  `id` int(11) NOT NULL,
  `nom` varchar(64) NOT NULL,
  `description` varchar(128) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

Default role types:
1. `user` - Capacity to login and see user data
2. `auto_planchiste` - Capacity to create, modify and delete the user's own data
5. `planchiste` - Authorization to create, modify and delete flight data
6. `ca` - Capacity to see all data for a section including global financial data
7. `bureau` - Capacity to see all data for a section including personal financial data
8. `tresorier` - Capacity to edit financial data for one section
9. `super-tresorier` - Capacity to see and edit financial data for all sections
10. `club-admin` - Capacity to access all data and change everything

**user_roles_per_section** - Maps users to role types per section:
```sql
CREATE TABLE `user_roles_per_section` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `types_roles_id` int(11) NOT NULL,
  `section_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  FOREIGN KEY (`user_id`) REFERENCES `users` (`id`),
  FOREIGN KEY (`types_roles_id`) REFERENCES `types_roles` (`id`),
  FOREIGN KEY (`section_id`) REFERENCES `sections` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

This allows users to have different roles in different sections (e.g., 'tresorier' in section 1, 'user' in section 2).

## Controller Management

### 1. Backend Controller (backend.php)

Location: `application/controllers/backend.php`

This controller manages all aspects of users, roles, and permissions.

**Key Methods:**

#### users($offset = 0)
Manages user listing and user actions (ban, unban, password reset).

```php
function users($offset = 0)
```

Features:
- Lists all users with pagination
- Allows bulk ban/unban operations
- Password reset functionality via email
- Joins with `roles` table to display role names

#### unactivated_users()
Displays and activates users pending email confirmation.

```php
function unactivated_users()
```

Features:
- Lists users from `user_temp` table (unactivated accounts)
- Allows admin to manually activate accounts

#### roles()
Manages role creation and deletion.

```php
function roles()
```

Features:
- Display all roles with their hierarchy
- Create new roles with parent role selection
- Delete roles (with cascading implications)

#### uri_permissions()
Manages URI-based permissions for each role.

```php
function uri_permissions()
```

Features:
- Display allowed URIs for selected role
- Edit URI permissions via textarea (one URI per line)
- Stores permissions in serialized format in `permissions.data` field

**URI Format:**
- `/` - Root access (usually granted to all authenticated users)
- `/controller/` - Access to entire controller
- `/controller/action/` - Access to specific controller action

#### custom_permissions()
Manages custom permission flags (edit, delete, etc.).

```php
function custom_permissions()
```

Features:
- Set boolean or custom permission values
- Stored alongside URI permissions in `permissions.data`

#### create()
Creates a new user account.

```php
function create()
```

Features:
- Form for username, email, password, role selection
- Validation for unique username
- Uses DX_Auth library for registration

#### delete($id)
Deletes a user account.

```php
function delete($id)
```

#### formValidation($action)
Validates user creation/edit forms.

```php
public function formValidation($action)
```

Validation rules:
- `username`: required, max 25 chars, unique (on creation)
- `password`: max 34 chars
- `passconf`: must match password
- `email`: required, valid email format

### 2. Model Classes

#### Users Model (dx_auth/users.php)

Location: `application/models/dx_auth/users.php`

Key methods:
- `get_all($offset, $row_count)` - Retrieve users with pagination
- `get_user_by_id($user_id)` - Fetch user by ID
- `get_user_by_username($username)` - Fetch user by username
- `get_user_by_email($email)` - Fetch user by email
- `ban_user($user_id, $reason)` - Ban a user
- `unban_user($user_id)` - Unban a user
- `set_role($user_id, $role_id)` - Change user role

#### Roles Model (dx_auth/roles.php)

Location: `application/models/dx_auth/roles.php`

Key methods:
- `get_all()` - Retrieve all roles
- `get_role_by_id($role_id)` - Fetch role by ID
- `create_role($name, $parent_id)` - Create new role
- `delete_role($role_id)` - Delete role
- `selector($where)` - Generate select options for forms

#### Permissions Model (dx_auth/permissions.php)

Location: `application/models/dx_auth/permissions.php`

Key methods:
- `get_permission_data($role_id)` - Get all permission data for role (unserialized)
- `get_permission_value($role_id, $key)` - Get specific permission value
- `set_permission_data($role_id, $permission_data)` - Set all permission data
- `set_permission_value($role_id, $key, $value)` - Set specific permission value

**Serialization handling:**
- `_serialize($data)` - Serializes data with slash preservation
- `_unserialize($data)` - Unserializes and restores slashes

## Authorization Mechanism

### 1. DX_Auth Library

Location: `application/libraries/DX_Auth.php`

The core authorization library that handles login, session management, and permission checking.

**Key Methods:**

#### check_uri_permissions($allow = TRUE)

This is the main authorization checkpoint called by controllers.

```php
function check_uri_permissions($allow = TRUE)
```

**Flow:**
1. Check if user is logged in
   - If not → redirect to login page
2. Check if user is admin
   - If admin → grant access (bypass all checks)
3. Extract current URI: `/controller/action/`
4. Get URI permissions for user's role and all parent roles
5. Check if any of these permissions grant access:
   - `'/'` - Root access
   - `/controller/` - Controller-level access
   - `/controller/action/` - Specific action access
6. If no match found → deny access (403 forbidden)

**Algorithm:**
```php
$controller = '/' . $this->ci->uri->rsegment(1) . '/';
$action = $controller . $this->ci->uri->rsegment(2) . '/';

$roles_allowed_uris = $this->get_permissions_value('uri');

foreach ($roles_allowed_uris as $allowed_uris) {
    if ($this->_array_in_array(['/', $controller, $action], $allowed_uris)) {
        $have_access = true;
        break;
    }
}

if (!$have_access) {
    $this->deny_access();
}
```

#### get_permissions_value($key, $array_key = 'default')

Retrieves permission values for the current user, including inherited permissions from parent roles.

```php
function get_permissions_value($key, $array_key = 'default')
```

**Flow:**
1. Get current user's role permission for `$key`
2. Get all parent roles' permissions for `$key`
3. Return array of permission values (user + all parents)

This ensures role hierarchy: if your role doesn't have permission but a parent role does, you inherit it.

#### get_permission_value($key, $check_parent = TRUE)

Get a single permission value for the current user.

```php
function get_permission_value($key, $check_parent = TRUE)
```

**Flow:**
1. Check user's direct role permission for `$key`
2. If not found and `$check_parent` is TRUE:
   - Traverse parent roles until permission is found
3. Return permission value or NULL

#### _get_role_data($role_id)

Retrieves complete role information including hierarchy and permissions.

```php
function _get_role_data($role_id)
```

**Flow:**
1. Load role from database
2. Recursively traverse parent roles:
   - Build arrays: `parent_roles_id`, `parent_roles_name`
   - Stop when `parent_id == 0` or not found
3. Load permissions for user role
4. Load permissions for all parent roles
5. Return comprehensive role data structure

**Returned data structure:**
```php
[
    'role_name' => 'editor',
    'parent_roles_id' => [2, 1],           // Array of parent role IDs
    'parent_roles_name' => ['user', 'guest'],  // Array of parent role names
    'permission' => [...],                  // User's direct permissions
    'parent_permissions' => [...]           // Array of parent permissions
]
```

#### is_admin()

Special check for admin role.

```php
function is_admin()
```

Returns `true` if the current user's role name is 'admin' (case-insensitive).

**Note:** Admin users bypass all URI permission checks.

#### _set_session($data)

Called after successful login to initialize session data.

```php
function _set_session($data)
```

**Session variables set:**
- `DX_user_id` - User ID
- `DX_username` - Username
- `DX_role_id` - Role ID
- `DX_role_name` - Role name
- `DX_parent_roles_id` - Array of parent role IDs
- `DX_parent_roles_name` - Array of parent role names
- `DX_permission` - User's permission data
- `DX_parent_permissions` - Parent roles' permission data
- `DX_logged_in` - Login flag (TRUE)

### 2. Gvv_Controller Base Class

Location: `application/libraries/Gvv_Controller.php`

All GVV controllers extend this base class, which provides common functionality.

**Constructor:**
```php
function __construct()
{
    parent::__construct();

    $this->load->library('DX_Auth');
    if (getenv('TEST') != '1') {
        // Check login only when not in test mode
        $this->dx_auth->check_login();
    }

    // ... additional initialization
}
```

**Note:**
- `check_login()` only verifies the user is logged in
- It does NOT check URI permissions
- Controllers that need URI permission protection must explicitly call `$this->dx_auth->check_uri_permissions()` in their constructor

### 3. Controller-Level Protection

Controllers can protect themselves in two ways:

#### Option 1: Call check_uri_permissions() in constructor

Example from `backend.php`:

```php
class Backend extends GVV_Controller {
    function __construct() {
        parent::__construct();

        $this->load->library('DX_Auth');

        // Protect entire controller with URI permissions
        $this->dx_auth->check_uri_permissions();
    }
}
```

This protects ALL actions in the controller using URI permissions configured in the database.

#### Option 2: Manual role checking

Controllers can manually check roles or permissions:

```php
if (!$this->dx_auth->is_admin()) {
    $this->dx_auth->deny_access();
}

// Or check specific role
if (!$this->dx_auth->is_role(['editor', 'admin'])) {
    $this->dx_auth->deny_access();
}

// Or check custom permission
if (!$this->dx_auth->get_permission_value('can_edit_posts')) {
    $this->dx_auth->deny_access();
}
```

## Permission Flow Diagram

```
User Login
    ↓
DX_Auth::login()
    ↓
_set_session($user_data)
    ↓
_get_role_data($role_id)
    ↓
    ├─ Load role from database
    ├─ Recursively load parent roles
    └─ Load permissions (user + parents)
    ↓
Session variables set:
    - DX_role_id, DX_role_name
    - DX_parent_roles_id, DX_parent_roles_name
    - DX_permission, DX_parent_permissions
    ↓
User accesses /controller/action/
    ↓
Controller __construct()
    ↓
check_uri_permissions()
    ↓
    ├─ Is logged in? → No → Redirect to login
    ├─ Is admin? → Yes → Grant access
    └─ Check URI permissions:
        ↓
        get_permissions_value('uri')
        ↓
        Check user's permissions + all parent permissions
        ↓
        Does any permission match:
            - '/'
            - '/controller/'
            - '/controller/action/'
        ↓
        ├─ Match found → Grant access
        └─ No match → deny_access() → 403 Forbidden
```

## Hierarchical Role Inheritance

### How it Works

When a user has role_id = 5 (e.g., 'editor'), and the roles table shows:

```
id | parent_id | name
---|-----------|-------
1  | 0         | guest
2  | 1         | user
5  | 2         | editor
```

The role hierarchy is: **editor → user → guest**

### Permission Resolution

When checking permissions for 'editor':

1. Get 'editor' permissions from `permissions` table (role_id=5)
2. Get 'user' permissions from `permissions` table (role_id=2)
3. Get 'guest' permissions from `permissions` table (role_id=1)
4. Check access: if ANY of these three have matching URI → grant access

### Example

**Permissions in database:**
- guest (role_id=1): `['uri' => ['/welcome/']]`
- user (role_id=2): `['uri' => ['/membre/', '/membre/edit/']]`
- editor (role_id=5): `['uri' => ['/posts/', '/posts/create/']]`

**User with 'editor' role can access:**
- `/welcome/` (from guest)
- `/membre/` and `/membre/edit/` (from user)
- `/posts/` and `/posts/create/` (from editor)

**User with 'user' role can access:**
- `/welcome/` (from guest)
- `/membre/` and `/membre/edit/` (from user)
- ❌ NOT `/posts/` (no editor role)

## Special Considerations

### 1. Admin Role

The 'admin' role is **special** and **hardcoded**:
- Determined by role name, not role_id
- Bypasses ALL URI permission checks
- Has full access to the entire application
- Should be used sparingly for security

**Code check:**
```php
function is_admin() {
    return strtolower($this->ci->session->userdata('DX_role_name')) == 'admin';
}
```

### 2. Root URI Permission ('/')

The root URI `'/'` grants access to **all controllers and actions**.

This is typically assigned to authenticated users to provide basic access.

### 3. URI Format

URIs in the permissions system always have this format:
- End with trailing slash: `/controller/`, `/controller/action/`
- Start with leading slash
- Controller and action names from routing (not filesystem)

**Example:**
- `'/backend/'` - Access to entire backend controller
- `'/backend/users/'` - Access only to users action
- `'/'` - Access to everything

### 4. Permission Precedence

When checking permissions:
1. Admin role → Always grant access (bypass all checks)
2. Specific action permission (e.g., `/backend/users/`)
3. Controller-level permission (e.g., `/backend/`)
4. Root permission (`'/'`)

### 5. Denied Access Behavior

When `deny_access()` is called:
- If user not logged in → Redirect to login page
- If user logged in but unauthorized → Redirect to deny page (403 Forbidden)
- Uses configurable URIs from `dx_auth` config

## Security Considerations

### 1. Password Storage

Passwords are stored using MD5 hashing with a salt:
```php
function _encode($password) {
    $majorsalt = $this->ci->config->item('DX_salt');
    // ... MD5 encoding with per-character hashing
    return md5($majorsalt);
}
```

**⚠️ Note:** MD5 is considered weak by modern standards. Consider migrating to bcrypt/Argon2.

### 2. Session Security

- All permissions cached in session for performance
- Parent roles and permissions pre-loaded during login
- Session data includes role hierarchy to prevent privilege escalation

### 3. Ban Mechanism

Users can be banned:
- Sets `banned = 1` flag in users table
- Prevents login even with valid credentials
- Optional ban_reason for audit trail

### 4. Default Permissions

**Important:** New roles have NO permissions by default.
- Admins must explicitly grant URI permissions
- Prevents accidental over-permissioning

## Usage Examples

### Example 1: Protecting a Controller

```php
class My_Controller extends GVV_Controller {
    function __construct() {
        parent::__construct();

        // Protect entire controller with URI permissions
        $this->dx_auth->check_uri_permissions();
    }

    function index() {
        // Only users with permission to /my_controller/ or / can access
    }

    function edit($id) {
        // Only users with permission to /my_controller/edit/ or /my_controller/ or / can access
    }
}
```

### Example 2: Role-Based Protection

```php
class Admin_Controller extends GVV_Controller {
    function __construct() {
        parent::__construct();

        // Only admins can access
        if (!$this->dx_auth->is_admin()) {
            $this->dx_auth->deny_access();
        }
    }
}
```

### Example 3: Custom Permission Check

```php
function delete($id) {
    // Check custom 'delete' permission
    if (!$this->dx_auth->get_permission_value('delete')) {
        $this->dx_auth->deny_access();
    }

    // Proceed with deletion
}
```

### Example 4: Creating a New Role via Backend

1. Navigate to `/backend/roles`
2. Enter role name: "moderator"
3. Select parent role: "user" (id=2)
4. Click "Add"
5. Navigate to `/backend/uri_permissions`
6. Select role: "moderator"
7. Add URIs (one per line):
   ```
   /
   /posts/
   /posts/moderate/
   /posts/delete/
   ```
8. Click "Save"

Users with "moderator" role will now have:
- All permissions from "user" role (inherited)
- Access to posts moderation and deletion

## Configuration

### DX_Auth Configuration

Location: `application/config/dx_auth.php`

Key configuration options:
- `DX_salt` - Salt for password hashing
- `DX_login_uri` - URI for login page
- `DX_deny_uri` - URI for access denied page
- `DX_table_prefix` - Prefix for DX_Auth tables
- `DX_users_table` - Users table name
- `DX_roles_table` - Roles table name
- `DX_permissions_table` - Permissions table name

### Database Table Names

By default (with no prefix):
- `users` - User accounts
- `roles` - Role definitions
- `permissions` - Permission data

## Troubleshooting

### Issue: User can't access a page

**Check:**
1. Is user logged in? (`$this->dx_auth->is_logged_in()`)
2. What is user's role? (`$this->dx_auth->get_role_name()`)
3. What are user's URI permissions? (Check `permissions` table for role_id)
4. Does the controller call `check_uri_permissions()`?
5. Is the URI format correct? (trailing slashes)

### Issue: Admin can't access everything

**Check:**
1. Role name must be exactly 'admin' (case-insensitive)
2. Check session: `$this->session->userdata('DX_role_name')`

### Issue: Permission changes don't take effect

**Solution:**
- User must log out and log back in
- Permissions are cached in session during login
- Changing permissions in database doesn't affect active sessions

### Issue: Role hierarchy not working

**Check:**
1. Verify `parent_id` in roles table
2. Check for circular references (role A → parent B → parent A)
3. Ensure parent roles have their own permissions set

## Best Practices

1. **Principle of Least Privilege**
   - Grant minimum permissions necessary
   - Use role hierarchy to build up permissions incrementally
   - Don't over-use admin role

2. **URI Permission Patterns**
   - Grant `'/'` to all authenticated users for basic access
   - Use controller-level permissions for feature access
   - Use action-level permissions for sensitive operations

3. **Role Organization**
   - Create meaningful role hierarchy (guest → user → editor → admin)
   - Document role purposes and permissions
   - Avoid creating too many roles (maintenance burden)

4. **Security**
   - Regularly audit user roles and permissions
   - Monitor banned users
   - Review permission changes in version control

5. **Testing**
   - Test with different role levels
   - Verify inherited permissions work correctly
   - Test access denial flows

## Future Improvements

Potential enhancements to consider:

1. **Stronger Password Hashing**
   - Migrate from MD5 to bcrypt or Argon2
   - Add password complexity requirements

2. **Permission Caching**
   - Cache compiled permissions in Redis/Memcached
   - Invalidate cache on permission changes

3. **Audit Logging**
   - Log all permission checks
   - Track permission changes
   - Monitor failed access attempts

4. **Fine-grained Permissions**
   - Resource-level permissions (e.g., "edit own posts" vs "edit all posts")
   - Time-based permissions
   - IP-based restrictions

5. **UI Improvements**
   - Visual role hierarchy tree
   - Permission inheritance visualization
   - Batch permission editing

## Related Files

- Controllers:
  - `application/controllers/backend.php` - User/role/permission management

- Models:
  - `application/models/dx_auth/users.php` - User model
  - `application/models/dx_auth/roles.php` - Role model
  - `application/models/dx_auth/permissions.php` - Permission model

- Libraries:
  - `application/libraries/DX_Auth.php` - Core authorization library
  - `application/libraries/Gvv_Controller.php` - Base controller with auth integration

- Views:
  - `application/views/backend/users.php` - User management UI
  - `application/views/backend/roles.php` - Role management UI
  - `application/views/backend/uri_permissions.php` - Permission management UI

- Migrations:
  - `application/migrations/types_roles.sql` - Section-based role types
  - `application/migrations/user_roles_per_section.sql` - Section role assignments

## References

- DX Auth Library: Third-party authentication library for CodeIgniter
- CodeIgniter 2.x Documentation: https://codeigniter.com/userguide2/
- GVV Project README: See `README.md` for project overview
