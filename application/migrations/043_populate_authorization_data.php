<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 043: Populate Authorization Data
 *
 * This migration populates the new authorization tables with data from the
 * existing permissions system. It converts PHP-serialized URI permissions
 * into structured role_permissions records and creates default data access rules.
 *
 * @see /doc/plans/2025_authorization_refactoring_plan.md
 */
class Migration_populate_authorization_data extends CI_Migration {

    public function up()
    {
        // 1. Migrate URI permissions from 'permissions' table to 'role_permissions'
        $this->_migrate_uri_permissions();

        // 2. Create default data access rules for each role
        $this->_create_default_data_access_rules();

        // 3. Log the initial migration in audit log
        $this->_log_initial_migration();
    }

    public function down()
    {
        // Clear data from new tables (schema remains intact)
        $this->db->truncate('authorization_audit_log');
        $this->db->truncate('data_access_rules');
        $this->db->truncate('role_permissions');
    }

    /**
     * Migrate URI permissions from serialized format to structured table
     */
    private function _migrate_uri_permissions()
    {
        // Get all permissions from the old table
        $query = $this->db->select('p.id, p.role_id, p.data, tr.nom as role_name, tr.scope')
            ->from('permissions p')
            ->join('types_roles tr', 'p.role_id = tr.id', 'left')
            ->get();

        $permissions_inserted = 0;
        $errors = array();

        foreach ($query->result() as $perm) {
            // Skip permissions for non-existent roles (orphaned data)
            if (is_null($perm->role_name)) {
                $errors[] = "Skipping permissions for non-existent role_id {$perm->role_id}";
                continue;
            }

            // Unserialize the PHP data
            $data = @unserialize($perm->data);

            if ($data === FALSE || !isset($data['uri'])) {
                $errors[] = "Could not unserialize permissions for role_id {$perm->role_id}";
                continue;
            }

            $uris = $data['uri'];

            foreach ($uris as $uri) {
                // Skip empty URIs
                if (empty($uri) || $uri == '/') {
                    continue;
                }

                // Parse URI to extract controller and action
                $parsed = $this->_parse_uri($uri);

                if (!$parsed) {
                    continue;
                }

                // Determine section_id (NULL for global roles, otherwise needs context)
                $section_id = ($perm->scope == 'global') ? NULL : NULL;

                // Insert into role_permissions
                $insert_data = array(
                    'types_roles_id' => $perm->role_id,
                    'section_id' => $section_id,
                    'controller' => $parsed['controller'],
                    'action' => $parsed['action'],
                    'permission_type' => 'view', // Default permission type
                    'created' => date('Y-m-d H:i:s'),
                    'modified' => date('Y-m-d H:i:s')
                );

                // Check if this permission already exists
                $exists = $this->db->where($insert_data)->get('role_permissions')->num_rows();

                if ($exists == 0) {
                    $this->db->insert('role_permissions', $insert_data);
                    $permissions_inserted++;
                }
            }
        }

        // Log results
        log_message('info', "Authorization Migration: Inserted {$permissions_inserted} permissions");
        if (!empty($errors)) {
            log_message('error', "Authorization Migration Errors: " . implode(', ', $errors));
        }
    }

    /**
     * Parse URI string to extract controller and action
     *
     * @param string $uri URI like "/membre/" or "/vols_planeur/page/"
     * @return array|false Array with 'controller' and 'action', or FALSE
     */
    private function _parse_uri($uri)
    {
        // Remove leading and trailing slashes
        $uri = trim($uri, '/');

        if (empty($uri)) {
            return FALSE;
        }

        // Split by slash
        $parts = explode('/', $uri);

        $controller = $parts[0];
        $action = isset($parts[1]) ? $parts[1] : NULL;

        // Skip if controller is empty
        if (empty($controller)) {
            return FALSE;
        }

        return array(
            'controller' => $controller,
            'action' => $action
        );
    }

    /**
     * Create default data access rules for each role
     */
    private function _create_default_data_access_rules()
    {
        $rules = array();

        // Rule 1: User role - can only access own data
        $rules[] = array(
            'types_roles_id' => 1, // user
            'table_name' => 'membres',
            'access_scope' => 'own',
            'field_name' => 'user_id',
            'section_field' => 'club',
            'description' => 'Users can only view their own member record'
        );

        $rules[] = array(
            'types_roles_id' => 1, // user
            'table_name' => 'volsp',
            'access_scope' => 'own',
            'field_name' => 'pilote',
            'section_field' => 'club',
            'description' => 'Users can view flights where they are pilot'
        );

        // Rule 2: Auto-planchiste - can access own data
        $rules[] = array(
            'types_roles_id' => 2, // auto_planchiste
            'table_name' => 'volsp',
            'access_scope' => 'own',
            'field_name' => 'pilote',
            'section_field' => 'club',
            'description' => 'Auto-planchistes can edit their own flights'
        );

        // Rule 3: Planchiste - can access all data in their section
        $rules[] = array(
            'types_roles_id' => 5, // planchiste
            'table_name' => 'volsp',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Planchistes can edit all flights in their section'
        );

        $rules[] = array(
            'types_roles_id' => 5, // planchiste
            'table_name' => 'membres',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Planchistes can view all members in their section'
        );

        // Rule 4: CA - can access all data in their section
        $rules[] = array(
            'types_roles_id' => 6, // ca
            'table_name' => 'membres',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'CA members can access all member data in their section'
        );

        $rules[] = array(
            'types_roles_id' => 6, // ca
            'table_name' => 'volsp',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'CA members can access all flights in their section'
        );

        $rules[] = array(
            'types_roles_id' => 6, // ca
            'table_name' => 'ecritures',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'CA members can view financial data (non-personal) in their section'
        );

        // Rule 5: Bureau - can access all data including personal financial
        $rules[] = array(
            'types_roles_id' => 7, // bureau
            'table_name' => 'membres',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Bureau members can access all member data including personal info'
        );

        $rules[] = array(
            'types_roles_id' => 7, // bureau
            'table_name' => 'ecritures',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Bureau members can access all financial data in their section'
        );

        // Rule 6: Trésorier - can edit financial data in their section
        $rules[] = array(
            'types_roles_id' => 8, // tresorier
            'table_name' => 'ecritures',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Treasurers can edit all financial data in their section'
        );

        $rules[] = array(
            'types_roles_id' => 8, // tresorier
            'table_name' => 'comptes',
            'access_scope' => 'section',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Treasurers can manage accounts in their section'
        );

        // Rule 7: Super-trésorier - can access financial data in all sections
        $rules[] = array(
            'types_roles_id' => 9, // super-tresorier
            'table_name' => 'ecritures',
            'access_scope' => 'all',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Super-treasurers can access all financial data across all sections'
        );

        $rules[] = array(
            'types_roles_id' => 9, // super-tresorier
            'table_name' => 'comptes',
            'access_scope' => 'all',
            'field_name' => NULL,
            'section_field' => 'club',
            'description' => 'Super-treasurers can manage accounts in all sections'
        );

        // Rule 8: Club-admin - can access everything
        $rules[] = array(
            'types_roles_id' => 10, // club-admin
            'table_name' => '*',
            'access_scope' => 'all',
            'field_name' => NULL,
            'section_field' => NULL,
            'description' => 'Club administrators have full access to all data'
        );

        // Insert all rules
        foreach ($rules as $rule) {
            // Check if rule already exists
            $exists = $this->db
                ->where('types_roles_id', $rule['types_roles_id'])
                ->where('table_name', $rule['table_name'])
                ->where('access_scope', $rule['access_scope'])
                ->get('data_access_rules')
                ->num_rows();

            if ($exists == 0) {
                $this->db->insert('data_access_rules', $rule);
            }
        }

        log_message('info', "Authorization Migration: Inserted " . count($rules) . " data access rules");
    }

    /**
     * Log the initial migration in audit log
     */
    private function _log_initial_migration()
    {
        $log_entry = array(
            'action_type' => 'modify_permission',
            'actor_user_id' => NULL,
            'target_user_id' => NULL,
            'types_roles_id' => NULL,
            'section_id' => NULL,
            'controller' => NULL,
            'action' => NULL,
            'ip_address' => $_SERVER['REMOTE_ADDR'] ?? 'migration',
            'details' => json_encode(array(
                'event' => 'authorization_system_migration',
                'description' => 'Migrated from DX_Auth serialized permissions to structured authorization system',
                'migration_date' => date('Y-m-d H:i:s'),
                'migration_file' => '043_populate_authorization_data.php'
            )),
            'created_at' => date('Y-m-d H:i:s')
        );

        $this->db->insert('authorization_audit_log', $log_entry);
    }
}
