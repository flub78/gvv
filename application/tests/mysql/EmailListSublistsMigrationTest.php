<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for email_list_sublists migration
 *
 * Tests that the migration properly creates the table with all constraints,
 * foreign keys, and indexes. Also tests the rollback functionality.
 *
 * @package tests
 * @see application/migrations/054_create_email_list_sublists.php
 */
class EmailListSublistsMigrationTest extends TestCase
{
    protected $CI;
    protected $db;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    /**
     * Helper method to check if email_list_sublists table exists
     */
    protected function tableExists()
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
        ");
        $result = $query->row_array();
        return $result['count'] > 0;
    }

    public function testTable_Exists()
    {
        // Check table existence via information_schema
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
        ");
        $result = $query->row_array();
        $this->assertEquals(1, $result['count'], 'email_list_sublists table should exist after migration');
    }

    public function testTable_HasCorrectColumns()
    {
        // Check table existence
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
        ");
        $result = $query->row_array();
        if ($result['count'] == 0) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Use information_schema instead of field_data()
        $query = $this->db->query("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
        ");

        $columns = array();
        foreach ($query->result_array() as $row) {
            $columns[] = $row['COLUMN_NAME'];
        }

        $expected_columns = ['id', 'parent_list_id', 'child_list_id', 'added_at'];

        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in email_list_sublists");
        }
    }

    public function testTable_IdIsPrimaryKey()
    {
        // Check table existence
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
        ");
        $result = $query->row_array();
        if ($result['count'] == 0) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Check primary key via information_schema
        $query = $this->db->query("
            SELECT COLUMN_NAME, COLUMN_KEY, DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND COLUMN_NAME = 'id'
        ");

        $this->assertEquals(1, $query->num_rows(), 'id column should exist');

        $result = $query->row_array();
        $this->assertEquals('PRI', $result['COLUMN_KEY'], 'id should be primary key');
        $this->assertEquals('int', $result['DATA_TYPE'], 'id should be INT type');
    }

    public function testForeignKey_ParentListId_Exists()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check for foreign key constraint
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND COLUMN_NAME = 'parent_list_id'
            AND REFERENCED_TABLE_NAME = 'email_lists'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on parent_list_id should exist');
    }

    public function testForeignKey_ChildListId_Exists()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check for foreign key constraint
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND COLUMN_NAME = 'child_list_id'
            AND REFERENCED_TABLE_NAME = 'email_lists'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Foreign key on child_list_id should exist');
    }

    public function testForeignKey_ParentListId_CascadeOnDelete()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check CASCADE behavior
        $query = $this->db->query("
            SELECT DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND CONSTRAINT_NAME = 'fk_email_list_sublists_parent'
        ");

        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $this->assertEquals('CASCADE', $row['DELETE_RULE'], 'parent_list_id FK should CASCADE on delete');
        }
    }

    public function testForeignKey_ChildListId_RestrictOnDelete()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check RESTRICT behavior
        $query = $this->db->query("
            SELECT DELETE_RULE
            FROM information_schema.REFERENTIAL_CONSTRAINTS
            WHERE CONSTRAINT_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND CONSTRAINT_NAME = 'fk_email_list_sublists_child'
        ");

        if ($query->num_rows() > 0) {
            $row = $query->row_array();
            $this->assertEquals('RESTRICT', $row['DELETE_RULE'], 'child_list_id FK should RESTRICT on delete');
        }
    }

    public function testUniqueConstraint_ParentChild_Exists()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check for unique constraint
        $query = $this->db->query("
            SELECT CONSTRAINT_NAME
            FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND CONSTRAINT_TYPE = 'UNIQUE'
            AND CONSTRAINT_NAME = 'unique_parent_child'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'UNIQUE constraint on (parent_list_id, child_list_id) should exist');
    }

    public function testIndex_Parent_Exists()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check for index
        $query = $this->db->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND COLUMN_NAME = 'parent_list_id'
            AND INDEX_NAME = 'idx_parent'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Index idx_parent should exist');
    }

    public function testIndex_Child_Exists()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Query to check for index
        $query = $this->db->query("
            SELECT INDEX_NAME
            FROM information_schema.STATISTICS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'email_list_sublists'
            AND COLUMN_NAME = 'child_list_id'
            AND INDEX_NAME = 'idx_child'
        ");

        $this->assertGreaterThan(0, $query->num_rows(), 'Index idx_child should exist');
    }

    /**
     * Test CASCADE behavior: deleting parent list should delete sublist references
     */
    public function testCascade_DeleteParent_RemovesSublistReferences()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Load email lists model
        $this->CI->load->model('email_lists_model');

        // Get a real user ID
        $user_query = $this->db->query("SELECT id FROM users LIMIT 1");
        $user = $user_query->row_array();
        $user_id = $user ? $user['id'] : 1;

        // Cleanup any leftover test data
        $this->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_CASCADE_%'");

        // Create two test lists
        $parent_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_CASCADE_PARENT',
            'created_by' => $user_id
        ]);

        $child_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_CASCADE_CHILD',
            'created_by' => $user_id
        ]);

        // Create a sublist relationship
        $this->db->insert('email_list_sublists', [
            'parent_list_id' => $parent_id,
            'child_list_id' => $child_id
        ]);

        // Verify the relationship was created
        $query_before = $this->db->query("SELECT COUNT(*) as count FROM email_list_sublists WHERE parent_list_id = $parent_id");
        $count_before = $query_before->row_array()['count'];
        $this->assertEquals(1, $count_before, 'Sublist relationship should be created');

        // Delete the parent list
        $this->CI->email_lists_model->delete_list($parent_id);

        // Verify the sublist reference was automatically deleted (CASCADE)
        $query_after = $this->db->query("SELECT COUNT(*) as count FROM email_list_sublists WHERE parent_list_id = $parent_id");
        $count_after = $query_after->row_array()['count'];
        $this->assertEquals(0, $count_after, 'Sublist reference should be deleted when parent is deleted (CASCADE)');

        // Cleanup: delete child list
        $this->CI->email_lists_model->delete_list($child_id);
    }

    /**
     * Test RESTRICT behavior: cannot delete a list used as sublist
     */
    public function testRestrict_DeleteChild_Blocked()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Load email lists model
        $this->CI->load->model('email_lists_model');

        // Get a real user ID
        $user_query = $this->db->query("SELECT id FROM users LIMIT 1");
        $user = $user_query->row_array();
        $user_id = $user ? $user['id'] : 1;

        // Create two test lists
        $parent_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_RESTRICT_PARENT',
            'created_by' => $user_id
        ]);

        $child_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_RESTRICT_CHILD',
            'created_by' => $user_id
        ]);

        // Create a sublist relationship
        $this->db->insert('email_list_sublists', [
            'parent_list_id' => $parent_id,
            'child_list_id' => $child_id
        ]);

        // Try to delete the child list (should fail due to RESTRICT FK)
        $this->expectException(Exception::class);

        try {
            $this->db->query("DELETE FROM email_lists WHERE id = ?", [$child_id]);
        } catch (Exception $e) {
            // Cleanup: remove sublist reference first, then delete both lists
            $this->db->delete('email_list_sublists', ['parent_list_id' => $parent_id, 'child_list_id' => $child_id]);
            $this->CI->email_lists_model->delete_list($child_id);
            $this->CI->email_lists_model->delete_list($parent_id);

            // Re-throw to satisfy expectException
            throw $e;
        }
    }

    /**
     * Test UNIQUE constraint: cannot add same (parent, child) pair twice
     */
    public function testUniqueConstraint_PreventsDuplicates()
    {
        if (!$this->tableExists()) {
            $this->markTestSkipped('Table email_list_sublists does not exist');
        }

        // Load email lists model
        $this->CI->load->model('email_lists_model');

        // Get a real user ID
        $user_query = $this->db->query("SELECT id FROM users LIMIT 1");
        $user = $user_query->row_array();
        $user_id = $user ? $user['id'] : 1;

        // Create two test lists
        $parent_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_UNIQUE_PARENT',
            'created_by' => $user_id
        ]);

        $child_id = $this->CI->email_lists_model->create_list([
            'name' => 'TEST_UNIQUE_CHILD',
            'created_by' => $user_id
        ]);

        // Insert first sublist relationship
        $this->db->insert('email_list_sublists', [
            'parent_list_id' => $parent_id,
            'child_list_id' => $child_id
        ]);

        // Try to insert duplicate (should fail)
        $this->expectException(Exception::class);

        try {
            $this->db->insert('email_list_sublists', [
                'parent_list_id' => $parent_id,
                'child_list_id' => $child_id
            ]);
        } catch (Exception $e) {
            // Cleanup
            $this->db->delete('email_list_sublists', ['parent_list_id' => $parent_id]);
            $this->CI->email_lists_model->delete_list($child_id);
            $this->CI->email_lists_model->delete_list($parent_id);

            // Re-throw
            throw $e;
        }
    }
}
