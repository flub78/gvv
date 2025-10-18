<?php
// Simple migration runner script
define('BASEPATH', './system/');
define('APPPATH', './application/');
define('ENVIRONMENT', 'development');

// Load database config
require_once APPPATH . 'config/database.php';

// Create database connection
$mysqli = new mysqli(
    $db['default']['hostname'],
    $db['default']['username'],
    $db['default']['password'],
    $db['default']['database']
);

if ($mysqli->connect_error) {
    die("Connection failed: " . $mysqli->connect_error);
}

// Check current migration version
$result = $mysqli->query("SELECT version FROM migrations ORDER BY version DESC LIMIT 1");
$current_version = $result ? $result->fetch_assoc()['version'] : 0;

echo "Current migration version: $current_version\n";
echo "Target version: 43\n\n";

if ($current_version >= 43) {
    echo "Database is already at version 43 or higher.\n";
    exit(0);
}

// Load CodeIgniter core files needed for migrations
require_once BASEPATH . 'core/Common.php';
require_once BASEPATH . 'database/DB.php';

// Continue with manual SQL execution for now
echo "Running migrations manually...\n";

// Add columns to types_roles
if ($current_version < 42) {
    echo "Running migration 042...\n";
    
    // Check if scope column already exists
    $result = $mysqli->query("SHOW COLUMNS FROM types_roles LIKE 'scope'");
    if ($result->num_rows == 0) {
        echo "Adding columns to types_roles...\n";
        $mysqli->query("ALTER TABLE types_roles ADD COLUMN scope ENUM('global', 'section') NOT NULL DEFAULT 'section' AFTER description");
        $mysqli->query("ALTER TABLE types_roles ADD COLUMN is_system_role TINYINT(1) NOT NULL DEFAULT 0 COMMENT 'Cannot be deleted' AFTER scope");
        $mysqli->query("ALTER TABLE types_roles ADD COLUMN display_order INT(11) NOT NULL DEFAULT 100 AFTER is_system_role");
        $mysqli->query("ALTER TABLE types_roles ADD COLUMN translation_key VARCHAR(64) NULL COMMENT 'Language file key for role name' AFTER display_order");
    }
    
    // Update existing roles
    echo "Updating existing roles...\n";
    $mysqli->query("UPDATE types_roles SET scope='global', is_system_role=1, display_order=10, translation_key='role_club_admin' WHERE id=10");
    $mysqli->query("UPDATE types_roles SET scope='global', is_system_role=1, display_order=20, translation_key='role_super_tresorier' WHERE id=9");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=30, translation_key='role_bureau' WHERE id=7");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=40, translation_key='role_tresorier' WHERE id=8");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=50, translation_key='role_ca' WHERE id=6");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=60, translation_key='role_planchiste' WHERE id=5");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=70, translation_key='role_auto_planchiste' WHERE id=2");
    $mysqli->query("UPDATE types_roles SET scope='section', is_system_role=1, display_order=80, translation_key='role_user' WHERE id=1");
    
    // Create role_permissions table
    $result = $mysqli->query("SHOW TABLES LIKE 'role_permissions'");
    if ($result->num_rows == 0) {
        echo "Creating role_permissions table...\n";
        $mysqli->query("CREATE TABLE role_permissions (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            types_roles_id INT(11) NOT NULL COMMENT 'FK to types_roles',
            section_id INT(11) NULL COMMENT 'NULL for global roles, specific section for section roles',
            controller VARCHAR(64) NOT NULL COMMENT 'Controller name',
            action VARCHAR(64) NULL COMMENT 'Action name, NULL means all actions',
            permission_type ENUM('view', 'create', 'edit', 'delete', 'admin') NOT NULL DEFAULT 'view',
            created DATETIME NOT NULL,
            modified TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            KEY idx_role_section (types_roles_id, section_id),
            KEY idx_controller_action (controller, action),
            KEY idx_permission_lookup (types_roles_id, controller, action),
            CONSTRAINT fk_role_permissions_role FOREIGN KEY (types_roles_id) REFERENCES types_roles (id) ON DELETE CASCADE,
            CONSTRAINT fk_role_permissions_section FOREIGN KEY (section_id) REFERENCES sections (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='URI and action permissions per role'");
    }
    
    // Create data_access_rules table
    $result = $mysqli->query("SHOW TABLES LIKE 'data_access_rules'");
    if ($result->num_rows == 0) {
        echo "Creating data_access_rules table...\n";
        $mysqli->query("CREATE TABLE data_access_rules (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            types_roles_id INT(11) NOT NULL,
            table_name VARCHAR(64) NOT NULL COMMENT 'Table being accessed',
            access_scope ENUM('own', 'section', 'all') NOT NULL DEFAULT 'own',
            field_name VARCHAR(64) NULL COMMENT 'Field to check for ownership',
            section_field VARCHAR(64) NULL COMMENT 'Field containing section_id',
            description TEXT NULL,
            CONSTRAINT fk_data_access_rules_role FOREIGN KEY (types_roles_id) REFERENCES types_roles (id) ON DELETE CASCADE,
            UNIQUE KEY unique_rule (types_roles_id, table_name, access_scope)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Row-level data access rules'");
    }
    
    // Add columns to user_roles_per_section
    $result = $mysqli->query("SHOW COLUMNS FROM user_roles_per_section LIKE 'granted_at'");
    if ($result->num_rows == 0) {
        echo "Adding columns to user_roles_per_section...\n";
        $mysqli->query("ALTER TABLE user_roles_per_section ADD COLUMN granted_by INT(11) NULL COMMENT 'User who granted this role' AFTER section_id");
        $mysqli->query("ALTER TABLE user_roles_per_section ADD COLUMN granted_at DATETIME NOT NULL AFTER granted_by");
        $mysqli->query("ALTER TABLE user_roles_per_section ADD COLUMN revoked_at DATETIME NULL AFTER granted_at");
        $mysqli->query("ALTER TABLE user_roles_per_section ADD COLUMN notes TEXT NULL AFTER revoked_at");
        $mysqli->query("ALTER TABLE user_roles_per_section ADD CONSTRAINT fk_user_roles_granted_by FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL");
        $mysqli->query("ALTER TABLE user_roles_per_section ADD INDEX idx_user_section_active (user_id, section_id, revoked_at)");
        $mysqli->query("UPDATE user_roles_per_section SET granted_at = NOW() WHERE granted_at IS NULL OR granted_at = '0000-00-00 00:00:00'");
    }
    
    // Create authorization_audit_log table
    $result = $mysqli->query("SHOW TABLES LIKE 'authorization_audit_log'");
    if ($result->num_rows == 0) {
        echo "Creating authorization_audit_log table...\n";
        $mysqli->query("CREATE TABLE authorization_audit_log (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            action_type ENUM('grant_role', 'revoke_role', 'modify_permission', 'access_denied', 'access_granted') NOT NULL,
            actor_user_id INT(11) NULL COMMENT 'User who performed the action',
            target_user_id INT(11) NULL COMMENT 'User affected by action',
            types_roles_id INT(11) NULL,
            section_id INT(11) NULL,
            controller VARCHAR(64) NULL,
            action VARCHAR(64) NULL,
            ip_address VARCHAR(45) NULL,
            details TEXT NULL COMMENT 'JSON or text details',
            created_at DATETIME NOT NULL,
            KEY idx_actor (actor_user_id),
            KEY idx_target (target_user_id),
            KEY idx_timestamp (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Audit log for authorization changes'");
    }
    
    // Create authorization_migration_status table
    $result = $mysqli->query("SHOW TABLES LIKE 'authorization_migration_status'");
    if ($result->num_rows == 0) {
        echo "Creating authorization_migration_status table...\n";
        $mysqli->query("CREATE TABLE authorization_migration_status (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            user_id INT(11) NOT NULL COMMENT 'User being migrated',
            migration_status ENUM('pending', 'in_progress', 'completed', 'failed', 'rolled_back') NOT NULL DEFAULT 'pending',
            use_new_system TINYINT(1) NOT NULL DEFAULT 0 COMMENT '1 = use new authorization, 0 = use legacy DX_Auth',
            migrated_by INT(11) NULL COMMENT 'Admin who initiated migration',
            migrated_at DATETIME NULL,
            completed_at DATETIME NULL,
            error_message TEXT NULL,
            notes TEXT NULL,
            CONSTRAINT fk_auth_migration_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
            CONSTRAINT fk_auth_migration_migrator FOREIGN KEY (migrated_by) REFERENCES users (id) ON DELETE SET NULL,
            UNIQUE KEY unique_user (user_id),
            KEY idx_migration_status (migration_status, use_new_system)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COMMENT='Track progressive migration of users to new authorization system'");
    }
    
    // Update migration version
    $mysqli->query("UPDATE migrations SET version = 42");
    echo "Migration 042 complete.\n\n";
}

// Migration 043 will be run separately if needed

$mysqli->close();
echo "Done.\n";
