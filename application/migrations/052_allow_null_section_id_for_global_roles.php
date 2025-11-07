<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 052: Allow NULL in section_id for global roles
 *
 * Global roles (like club-admin, super-tresorier) should have section_id = NULL
 * to indicate they apply to all sections.
 */
class Migration_Allow_null_section_id_for_global_roles extends CI_Migration {

    public function up()
    {
        // Check if table exists before trying to alter it
        if (!$this->db->table_exists('user_roles_per_section')) {
            log_message('info', 'Migration 052: user_roles_per_section table does not exist, skipping');
            return;
        }

        // Check if column already allows NULL
        $fields = $this->db->field_data('user_roles_per_section');
        $section_id_nullable = false;
        foreach ($fields as $field) {
            if ($field->name === 'section_id' && $field->null == 1) {
                $section_id_nullable = true;
                break;
            }
        }

        if ($section_id_nullable) {
            log_message('info', 'Migration 052: section_id column already allows NULL, skipping');
            return;
        }

        // Drop the foreign key constraint
        $this->db->query('ALTER TABLE user_roles_per_section DROP FOREIGN KEY section_id');

        // Modify the column to allow NULL
        $this->db->query('ALTER TABLE user_roles_per_section MODIFY COLUMN section_id INT(11) NULL');

        // Re-add the foreign key constraint allowing NULL
        $this->db->query('ALTER TABLE user_roles_per_section ADD CONSTRAINT section_id
            FOREIGN KEY (section_id) REFERENCES sections(id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        log_message('info', 'Migration 052: section_id column now allows NULL for global roles');
    }

    public function down()
    {
        // Drop the foreign key constraint
        $this->db->query('ALTER TABLE user_roles_per_section DROP FOREIGN KEY section_id');

        // Delete any rows with NULL section_id before setting NOT NULL
        $this->db->query('DELETE FROM user_roles_per_section WHERE section_id IS NULL');

        // Modify the column back to NOT NULL
        $this->db->query('ALTER TABLE user_roles_per_section MODIFY COLUMN section_id INT(11) NOT NULL');

        // Re-add the foreign key constraint
        $this->db->query('ALTER TABLE user_roles_per_section ADD CONSTRAINT section_id
            FOREIGN KEY (section_id) REFERENCES sections(id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        log_message('info', 'Migration 052: Rolled back - section_id column is NOT NULL again');
    }
}
