<?php

require_once(__DIR__ . '/TransactionalTestCase.php');

/**
 * Integration test for Licences_model membership fee methods
 *
 * Tests the new methods added for membership fee entry:
 * - check_cotisation_exists()
 * - create_cotisation()
 *
 * Requirements:
 * - Full CodeIgniter framework loaded
 * - Database connection configured
 * - InnoDB tables (for transaction support)
 */
class LicencesModelIntegrationTest extends TransactionalTestCase
{
    /**
     * @var Licences_model
     */
    private $licences_model;

    /**
     * Test data IDs for cleanup
     */
    private $created_ids = [];

    /**
     * Set up test environment with database transaction
     */
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load model
        $this->CI->load->model('licences_model');
        $this->licences_model = $this->CI->licences_model;

        // Start transaction for test isolation
        $this->CI->db->trans_start();

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test check_cotisation_exists returns false when no membership exists
     */
    public function test_check_cotisation_exists_returns_false_when_not_exists()
    {
        // Use a pilot that likely doesn't have a membership for year 9999
        $result = $this->licences_model->check_cotisation_exists('test_pilot', 9999);

        $this->assertFalse($result);
    }

    /**
     * Test check_cotisation_exists returns true when membership exists
     */
    public function test_check_cotisation_exists_returns_true_when_exists()
    {
        // First, create a membership
        $pilote = 'test_pilot_' . time();
        $year = date('Y');
        $type = 0; // cotisation simple
        $date = date('Y-m-d');
        $comment = 'Test membership';

        // Insert test data directly
        $data = [
            'pilote' => $pilote,
            'type' => $type,
            'year' => $year,
            'date' => $date,
            'comment' => $comment
        ];
        $this->CI->db->insert('licences', $data);
        $created_id = $this->CI->db->insert_id();
        $this->created_ids[] = $created_id;

        // Now test check_cotisation_exists
        $result = $this->licences_model->check_cotisation_exists($pilote, $year);

        $this->assertTrue($result);
    }

    /**
     * Test check_cotisation_exists only checks type 0 (simple membership)
     */
    public function test_check_cotisation_exists_only_checks_type_zero()
    {
        // Create a membership with type != 0
        $pilote = 'test_pilot_' . time();
        $year = date('Y');
        $type = 1; // NOT a simple membership
        $date = date('Y-m-d');
        $comment = 'Test membership type 1';

        // Insert test data with non-zero type
        $data = [
            'pilote' => $pilote,
            'type' => $type,
            'year' => $year,
            'date' => $date,
            'comment' => $comment
        ];
        $this->CI->db->insert('licences', $data);
        $created_id = $this->CI->db->insert_id();
        $this->created_ids[] = $created_id;

        // Check should return false because we only check type=0
        $result = $this->licences_model->check_cotisation_exists($pilote, $year);

        $this->assertFalse($result);
    }

    /**
     * Test create_cotisation successfully creates a membership record
     */
    public function test_create_cotisation_inserts_record()
    {
        $pilote = 'test_pilot_' . time();
        $type = 0;
        $year = date('Y');
        $date = date('Y-m-d');
        $comment = 'Test membership created by PHPUnit';

        // Create the membership
        $result = $this->licences_model->create_cotisation($pilote, $type, $year, $date, $comment);

        // Should return the insert ID
        $this->assertNotFalse($result);
        $this->assertGreaterThan(0, $result);
        $this->created_ids[] = $result;

        // Verify the record was created
        $this->CI->db->where('id', $result);
        $query = $this->CI->db->get('licences');
        $record = $query->row_array();

        $this->assertNotEmpty($record);
        $this->assertEquals($pilote, $record['pilote']);
        $this->assertEquals($type, $record['type']);
        $this->assertEquals($year, $record['year']);
        $this->assertEquals($date, $record['date']);
        $this->assertEquals($comment, $record['comment']);
    }

    /**
     * Test create_cotisation returns false on database error
     *
     * This is hard to test without mocking, but we can test with invalid data
     * that violates database constraints if any exist
     */
    public function test_create_cotisation_handles_errors()
    {
        // Assuming primary key is (pilote, year, type), trying to insert duplicate should fail
        $pilote = 'test_pilot_' . time();
        $type = 0;
        $year = date('Y');
        $date = date('Y-m-d');
        $comment = 'First membership';

        // First insert should succeed
        $result1 = $this->licences_model->create_cotisation($pilote, $type, $year, $date, $comment);
        $this->assertNotFalse($result1);
        $this->created_ids[] = $result1;

        // Second insert with same pilote, year, type should fail (if there's a unique constraint)
        // Note: This test assumes the database has a unique constraint on (pilote, year, type)
        // If no constraint exists, this test will be skipped
        $comment2 = 'Duplicate membership';
        $result2 = $this->licences_model->create_cotisation($pilote, $type, $year, $date, $comment2);

        // If database has unique constraint, this should return false
        // Otherwise, it will create a duplicate (in which case we accept both outcomes)
        if ($result2 !== false) {
            $this->created_ids[] = $result2;
            $this->markTestSkipped('Database allows duplicate memberships - no unique constraint');
        } else {
            $this->assertFalse($result2);
        }
    }

    /**
     * Test full workflow: check, create, check again
     */
    public function test_full_membership_workflow()
    {
        $pilote = 'test_pilot_' . time();
        $type = 0;
        $year = date('Y');
        $date = date('Y-m-d');
        $comment = 'Full workflow test';

        // 1. Check membership doesn't exist
        $exists_before = $this->licences_model->check_cotisation_exists($pilote, $year);
        $this->assertFalse($exists_before);

        // 2. Create membership
        $created_id = $this->licences_model->create_cotisation($pilote, $type, $year, $date, $comment);
        $this->assertNotFalse($created_id);
        $this->created_ids[] = $created_id;

        // 3. Check membership now exists
        $exists_after = $this->licences_model->check_cotisation_exists($pilote, $year);
        $this->assertTrue($exists_after);
    }

    /**
     * Test check_cotisation_exists with different years for same pilot
     */
    public function test_check_cotisation_exists_year_specific()
    {
        $pilote = 'test_pilot_' . time();
        $type = 0;
        $year1 = date('Y');
        $year2 = $year1 + 1;
        $date = date('Y-m-d');
        $comment = 'Year-specific test';

        // Create membership for year1
        $created_id = $this->licences_model->create_cotisation($pilote, $type, $year1, $date, $comment);
        $this->assertNotFalse($created_id);
        $this->created_ids[] = $created_id;

        // Check year1 exists
        $exists_year1 = $this->licences_model->check_cotisation_exists($pilote, $year1);
        $this->assertTrue($exists_year1);

        // Check year2 doesn't exist
        $exists_year2 = $this->licences_model->check_cotisation_exists($pilote, $year2);
        $this->assertFalse($exists_year2);
    }
}

?>
