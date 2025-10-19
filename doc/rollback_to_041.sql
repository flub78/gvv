-- =====================================================
-- Rollback Script: Reset Database to Migration 041
-- =====================================================
-- This script removes all changes from migrations 042 and 043
-- regardless of the current database state.
-- Safe to run multiple times (uses IF EXISTS).
--
-- IMPORTANT: Run this ONLY on development/test environments!
-- Take a backup before running: mysqldump -u user -p gvv2 > backup_before_rollback.sql
-- =====================================================

-- Disable foreign key checks to allow dropping in any order
SET FOREIGN_KEY_CHECKS = 0;

-- =====================================================
-- 1. Drop tables created by migration 042
-- =====================================================

-- Drop authorization_migration_status table
DROP TABLE IF EXISTS `authorization_migration_status`;

-- Drop authorization_audit_log table
DROP TABLE IF EXISTS `authorization_audit_log`;

-- Drop data_access_rules table
DROP TABLE IF EXISTS `data_access_rules`;

-- Drop role_permissions table
DROP TABLE IF EXISTS `role_permissions`;

-- =====================================================
-- 2. Remove columns added to user_roles_per_section
-- =====================================================

-- Check and drop foreign key if exists
SET @fk_exists = (SELECT COUNT(*)
    FROM information_schema.KEY_COLUMN_USAGE
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_roles_per_section'
    AND CONSTRAINT_NAME = 'fk_user_roles_granted_by');

SET @sql = IF(@fk_exists > 0,
    'ALTER TABLE `user_roles_per_section` DROP FOREIGN KEY `fk_user_roles_granted_by`',
    'SELECT "FK fk_user_roles_granted_by does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Drop columns if they exist
SET @col_granted_by = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_roles_per_section'
    AND COLUMN_NAME = 'granted_by');

SET @sql = IF(@col_granted_by > 0,
    'ALTER TABLE `user_roles_per_section` DROP COLUMN `granted_by`',
    'SELECT "Column granted_by does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_granted_at = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_roles_per_section'
    AND COLUMN_NAME = 'granted_at');

SET @sql = IF(@col_granted_at > 0,
    'ALTER TABLE `user_roles_per_section` DROP COLUMN `granted_at`',
    'SELECT "Column granted_at does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_revoked_at = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_roles_per_section'
    AND COLUMN_NAME = 'revoked_at');

SET @sql = IF(@col_revoked_at > 0,
    'ALTER TABLE `user_roles_per_section` DROP COLUMN `revoked_at`',
    'SELECT "Column revoked_at does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_notes = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'user_roles_per_section'
    AND COLUMN_NAME = 'notes');

SET @sql = IF(@col_notes > 0,
    'ALTER TABLE `user_roles_per_section` DROP COLUMN `notes`',
    'SELECT "Column notes does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 3. Remove columns added to types_roles
-- =====================================================

SET @col_scope = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'types_roles'
    AND COLUMN_NAME = 'scope');

SET @sql = IF(@col_scope > 0,
    'ALTER TABLE `types_roles` DROP COLUMN `scope`',
    'SELECT "Column scope does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_is_system = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'types_roles'
    AND COLUMN_NAME = 'is_system_role');

SET @sql = IF(@col_is_system > 0,
    'ALTER TABLE `types_roles` DROP COLUMN `is_system_role`',
    'SELECT "Column is_system_role does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_display = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'types_roles'
    AND COLUMN_NAME = 'display_order');

SET @sql = IF(@col_display > 0,
    'ALTER TABLE `types_roles` DROP COLUMN `display_order`',
    'SELECT "Column display_order does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_translation = (SELECT COUNT(*)
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'types_roles'
    AND COLUMN_NAME = 'translation_key');

SET @sql = IF(@col_translation > 0,
    'ALTER TABLE `types_roles` DROP COLUMN `translation_key`',
    'SELECT "Column translation_key does not exist"');
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- =====================================================
-- 4. Update migration version to 041
-- =====================================================

UPDATE `migrations` SET `version` = 41 WHERE 1=1;

-- Re-enable foreign key checks
SET FOREIGN_KEY_CHECKS = 1;

-- =====================================================
-- Verification Queries
-- =====================================================

SELECT 'Migration version after rollback:' AS info, version FROM migrations;

SELECT 'Remaining authorization tables (should be empty):' AS info;
SELECT TABLE_NAME
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME IN ('authorization_migration_status', 'authorization_audit_log', 'data_access_rules', 'role_permissions');

SELECT 'Columns in types_roles:' AS info;
SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'types_roles'
ORDER BY ORDINAL_POSITION;

SELECT 'Columns in user_roles_per_section:' AS info;
SELECT COLUMN_NAME
FROM information_schema.COLUMNS
WHERE TABLE_SCHEMA = DATABASE()
AND TABLE_NAME = 'user_roles_per_section'
ORDER BY ORDINAL_POSITION;

SELECT '=== ROLLBACK COMPLETE ===' AS status;
