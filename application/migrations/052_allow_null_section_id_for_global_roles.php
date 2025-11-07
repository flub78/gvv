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
        // First, drop the foreign key constraint
        $this->db->query('ALTER TABLE user_roles_per_section DROP FOREIGN KEY section_id');

        // Modify the column to allow NULL
        $this->db->query('ALTER TABLE user_roles_per_section MODIFY COLUMN section_id INT(11) NULL');

        // Re-add the foreign key constraint allowing NULL
        $this->db->query('ALTER TABLE user_roles_per_section ADD CONSTRAINT section_id
            FOREIGN KEY (section_id) REFERENCES sections(id)
            ON DELETE CASCADE ON UPDATE CASCADE');

        echo "Migration 052: section_id column now allows NULL for global roles\n";
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

        echo "Migration 052 rolled back: section_id column is NOT NULL again\n";
    }
}
