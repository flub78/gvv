-- Test migration 049 with rollback capability
-- This will run the migration SQL statements within a transaction
-- Note: Some DDL statements will cause implicit commits in MySQL

START TRANSACTION;

-- Check existing tables
SELECT 'Checking existing tables...' as status;
SELECT table_name
FROM information_schema.tables
WHERE table_schema = 'gvv2'
AND table_name IN ('email_lists', 'email_list_roles', 'email_list_members', 'email_list_external');

-- Note: The actual migration will be run via PHP since it contains complex logic
-- This is just to demonstrate the approach

ROLLBACK;
