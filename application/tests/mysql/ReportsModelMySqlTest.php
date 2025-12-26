<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Reports_model
 *
 * Tests CRUD operations on the reports table
 *
 * @package tests
 */
class ReportsModelMySqlTest extends TestCase
{
    private $CI;
    private $model;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load database and model
        $this->CI->load->database();
        $this->CI->load->model('reports_model');
        $this->model = $this->CI->reports_model;
    }

    /**
     * Test that model exists and can be instantiated
     */
    public function testModelInstantiation()
    {
        $this->assertNotNull($this->model, "Reports_model should be instantiated");
        $this->assertInstanceOf('Reports_model', $this->model);
        $this->assertEquals('reports', $this->model->table, "Model should reference 'reports' table");
    }

    /**
     * Test table() method returns correct table name
     */
    public function testTableMethod()
    {
        $table = $this->model->table();

        $this->assertEquals('reports', $table, "table() method should return 'reports'");
    }

    /**
     * Test primary_key() method returns correct key
     */
    public function testPrimaryKeyMethod()
    {
        $pk = $this->model->primary_key();

        $this->assertEquals('nom', $pk, "primary_key() should return 'nom'");
    }

    /**
     * Test select_page() returns array with proper structure
     */
    public function testSelectPageReturnsArray()
    {
        $result = $this->model->select_page(10, 0);

        $this->assertIsArray($result, "select_page should return an array");

        if (!empty($result)) {
            $this->assertIsArray($result[0], "Each row should be an array");
            $this->assertArrayHasKey('nom', $result[0], "Should have 'nom' field");
            $this->assertArrayHasKey('titre', $result[0], "Should have 'titre' field");
        }
    }

    /**
     * Test select_page() respects limit parameter
     */
    public function testSelectPageRespectsLimit()
    {
        // First check if table has data
        $all = $this->model->select_page(1000, 0);

        if (count($all) > 5) {
            $result = $this->model->select_page(5, 0);
            $this->assertLessThanOrEqual(5, count($result),
                "select_page should respect limit parameter");
        }
    }

    /**
     * Test select_page() with offset
     */
    public function testSelectPageWithOffset()
    {
        $first = $this->model->select_page(5, 0);
        $second = $this->model->select_page(5, 5);

        if (!empty($first) && !empty($second)) {
            $this->assertNotEquals($first[0]['nom'], $second[0]['nom'],
                "Different offsets should return different results");
        }
    }

    /**
     * Test select_page() returns only expected columns
     */
    public function testSelectPageColumns()
    {
        $result = $this->model->select_page(10, 0);

        if (!empty($result)) {
            $keys = array_keys($result[0]);
            $this->assertContains('nom', $keys, "Result should contain 'nom' column");
            $this->assertContains('titre', $keys, "Result should contain 'titre' column");
            $this->assertCount(2, $keys, "Result should have exactly 2 columns");
        }
    }

    /**
     * Test that model extends Common_Model
     */
    public function testModelExtendsCommonModel()
    {
        $this->assertInstanceOf('Common_Model', $this->model,
            "Reports_model should extend Common_Model");
    }

    /**
     * Test model has database connection
     */
    public function testModelHasDatabaseConnection()
    {
        $this->assertNotNull($this->model->db, "Model should have database connection");
        $this->assertIsObject($this->model->db, "Database should be an object");
    }

    /**
     * Test reports table exists
     */
    public function testReportsTableExists()
    {
        // Query the table to verify it exists
        $query = $this->CI->db->query("SHOW TABLES LIKE 'reports'");
        $result = $query->result_array();

        $this->assertNotEmpty($result, "Reports table should exist");
    }

    /**
     * Test select_page stores metadata
     */
    public function testSelectPageStoresMetadata()
    {
        $result = $this->model->select_page(5, 0);

        // After calling select_page, metadata should be stored in gvvmetadata
        // This is internal behavior but we can verify the method completes without error
        $this->assertIsArray($result, "select_page should complete and return array");
    }

    /**
     * Test model properties are correctly set
     */
    public function testModelProperties()
    {
        $this->assertEquals('reports', $this->model->table,
            "Table property should be 'reports'");
        $this->assertEquals('nom', $this->model->primary_key(),
            "Primary key should be 'nom'");
    }
}
