<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 170 (Lot 4 — acceptance_item_roles, role x section
 * targeting for acceptance items, mirroring email_list_roles).
 */
class AcceptanceItemRolesMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/170_acceptance_item_roles.php';
    }

    private function foreignKeyExists($table, $constraint)
    {
        $row = $this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = '" . $this->db->escape_str($table) . "'
               AND CONSTRAINT_NAME = '" . $this->db->escape_str($constraint) . "'"
        );
        return $row->num_rows() > 0;
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Acceptance_item_roles();
        $this->assertTrue($migration->up(), 'Migration 170 up() should succeed');
    }

    public function testMigration170CreatesTableWithForeignKeys()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->db->table_exists('acceptance_item_roles'));
        $this->assertTrue($this->foreignKeyExists('acceptance_item_roles', 'fk_acceptance_item_roles_item'));
        $this->assertTrue($this->foreignKeyExists('acceptance_item_roles', 'fk_acceptance_item_roles_role'));
        $this->assertTrue($this->foreignKeyExists('acceptance_item_roles', 'fk_acceptance_item_roles_section'));
    }

    public function testMigration170UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->db->table_exists('acceptance_item_roles'));
    }

    public function testMigration170DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $login = $this->db->query("SELECT mlogin FROM membres LIMIT 1")->row_array();
        $role = $this->db->query("SELECT id FROM types_roles LIMIT 1")->row_array();
        $section = $this->db->query("SELECT id FROM sections LIMIT 1")->row_array();
        $this->assertNotNull($login, 'Au moins un membre est requis pour ce test');
        $this->assertNotNull($role, 'Au moins un role est requis pour ce test');

        $this->db->insert('acceptance_items', array(
            'title'       => 'Migration 170 test ' . time(),
            'category'    => 'document',
            'target_type' => 'internal',
            'created_by'  => $login['mlogin'],
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        $item_id = $this->db->insert_id();

        // section_id NULL = "all sections" for that role.
        $this->db->insert('acceptance_item_roles', array(
            'item_id'        => $item_id,
            'types_roles_id' => $role['id'],
            'section_id'     => null,
            'created_at'     => date('Y-m-d H:i:s'),
            'created_by'     => $login['mlogin'],
        ));
        $row = $this->db->where('item_id', $item_id)->get('acceptance_item_roles')->row_array();
        $this->assertEquals($role['id'], $row['types_roles_id']);
        $this->assertNull($row['section_id']);

        if ($section) {
            $this->db->insert('acceptance_item_roles', array(
                'item_id'        => $item_id,
                'types_roles_id' => $role['id'],
                'section_id'     => $section['id'],
                'created_at'     => date('Y-m-d H:i:s'),
                'created_by'     => $login['mlogin'],
            ));
            $this->assertEquals(2, $this->db->where('item_id', $item_id)->count_all_results('acceptance_item_roles'));
        }

        // Deleting the acceptance item must cascade-delete its role targeting rows.
        $this->db->where('id', $item_id)->delete('acceptance_items');
        $this->assertEquals(0, $this->db->where('item_id', $item_id)->count_all_results('acceptance_item_roles'));

        // down() must drop the table cleanly.
        $migration = new Migration_Acceptance_item_roles();
        $this->assertTrue($migration->down(), 'Migration 170 down() should succeed');
        $this->assertFalse($this->db->table_exists('acceptance_item_roles'));

        // Restore expected state for the rest of the suite / the application.
        $this->runMigrationUp();
        $this->assertTrue($this->db->table_exists('acceptance_item_roles'));
    }
}
