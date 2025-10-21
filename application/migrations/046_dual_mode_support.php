<?php
defined('BASEPATH') OR exit('No direct script access allowed');

/**
 * Migration 046: Dual Mode Support for Progressive User Migration
 *
 * Creates authorization_comparison_log table to track authorization
 * discrepancies during progressive migration from DX_Auth to Gvv_Authorization.
 *
 * Part of Phase 6: Progressive Migration
 *
 * @author  GVV Development Team
 * @date    2025-10-21
 */
class Migration_Dual_mode_support extends CI_Migration
{
    /**
     * Execute migration: Create comparison log table
     *
     * @return void
     */
    public function up()
    {
        // Create authorization_comparison_log table
        $this->dbforge->add_field(array(
            'id' => array(
                'type'           => 'INT',
                'constraint'     => 11,
                'unsigned'       => TRUE,
                'auto_increment' => TRUE
            ),
            'user_id' => array(
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => FALSE,
                'comment'    => 'User ID being tested in dual mode'
            ),
            'controller' => array(
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => FALSE,
                'comment'    => 'Controller name accessed'
            ),
            'action' => array(
                'type'       => 'VARCHAR',
                'constraint' => 100,
                'null'       => FALSE,
                'comment'    => 'Action/method name accessed'
            ),
            'section_id' => array(
                'type'       => 'INT',
                'constraint' => 11,
                'null'       => TRUE,
                'comment'    => 'Section context during access check'
            ),
            'new_system_result' => array(
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => FALSE,
                'comment'    => '1 = access granted, 0 = denied (new system)'
            ),
            'legacy_system_result' => array(
                'type'       => 'TINYINT',
                'constraint' => 1,
                'null'       => FALSE,
                'comment'    => '1 = access granted, 0 = denied (legacy system)'
            ),
            'new_system_details' => array(
                'type'    => 'TEXT',
                'null'    => TRUE,
                'comment' => 'JSON: roles, permissions, rules used (new system)'
            ),
            'legacy_system_details' => array(
                'type'    => 'TEXT',
                'null'    => TRUE,
                'comment' => 'JSON: roles, permissions used (legacy system)'
            ),
            'created_at' => array(
                'type' => 'DATETIME',
                'null' => FALSE
            )
        ));

        $this->dbforge->add_key('id', TRUE);
        $this->dbforge->create_table('authorization_comparison_log');

        // Add indexes for performance
        $this->db->query('CREATE INDEX idx_user_id ON authorization_comparison_log(user_id)');
        $this->db->query('CREATE INDEX idx_created_at ON authorization_comparison_log(created_at)');
        $this->db->query('CREATE INDEX idx_controller_action ON authorization_comparison_log(controller, action)');
        $this->db->query('CREATE INDEX idx_mismatch ON authorization_comparison_log(new_system_result, legacy_system_result)');

        // Add foreign key constraint
        $this->db->query('
            ALTER TABLE authorization_comparison_log
            ADD CONSTRAINT fk_comparison_log_user
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        ');

        log_message('info', 'Migration 046: authorization_comparison_log table created successfully');
    }

    /**
     * Rollback migration: Drop comparison log table
     *
     * @return void
     */
    public function down()
    {
        // Drop foreign key constraint first
        $this->db->query('
            ALTER TABLE authorization_comparison_log
            DROP FOREIGN KEY fk_comparison_log_user
        ');

        // Drop table
        $this->dbforge->drop_table('authorization_comparison_log');

        log_message('info', 'Migration 046: authorization_comparison_log table dropped successfully');
    }
}
