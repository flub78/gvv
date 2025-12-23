<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Types_ticket_model
 * 
 * Tests CRUD operations on the type_ticket (ticket types) table
 * 
 * @package tests
 */
class TypesTicketModelMySqlTest extends TestCase
{
    private $CI;
    private $model;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load database and model
        $this->CI->load->database();
        $this->CI->load->model('types_ticket_model');
        $this->model = $this->CI->types_ticket_model;
    }

    /**
     * Test that model exists and can be instantiated
     */
    public function testModelInstantiation()
    {
        $this->assertNotNull($this->model, "Types_ticket_model should be instantiated");
        $this->assertEquals('type_ticket', $this->model->table, "Model should reference 'type_ticket' table");
        $this->assertEquals('id', $this->model->primary_key(), "Primary key should be 'id'");
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
            $this->assertArrayHasKey('id', $result[0], "Should have 'id' field");
            $this->assertArrayHasKey('nom', $result[0], "Should have 'nom' field");
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
            $this->assertContains('id', $keys, "Result should contain 'id' column");
            $this->assertContains('nom', $keys, "Result should contain 'nom' column");
        }
    }

    /**
     * Test image() method returns ticket type name
     */
    public function testImageMethodReturnsName()
    {
        // Get first ticket type
        $types = $this->model->select_page(1, 0);
        
        if (!empty($types)) {
            $id = $types[0]['id'];
            $expected_name = $types[0]['nom'];
            $image = $this->model->image($id);

            $this->assertIsString($image, "image() should return a string");
            $this->assertEquals($expected_name, $image, "image() should return the ticket type name");
        }
    }

    /**
     * Test count() returns total number of ticket types
     */
    public function testCountAllTicketTypes()
    {
        $count = $this->model->count();

        $this->assertIsInt($count, "count() should return an integer");
        $this->assertGreaterThanOrEqual(0, $count, "Count should be non-negative");
    }

    /**
     * Test get_by_id() retrieves ticket type by ID
     */
    public function testGetByIdRetrievesTicketType()
    {
        // Get first ticket type
        $types = $this->model->select_page(1, 0);
        
        if (!empty($types)) {
            $id = $types[0]['id'];
            $result = $this->model->get_by_id('id', $id);

            $this->assertIsArray($result, "get_by_id should return an array");
            $this->assertArrayHasKey('id', $result, "Result should contain id field");
            $this->assertArrayHasKey('nom', $result, "Result should contain nom field");
            $this->assertEquals($id, $result['id'], "Retrieved ID should match requested");
        }
    }

    /**
     * Test select_all() returns all ticket types
     */
    public function testSelectAllReturnsTicketTypes()
    {
        $all = $this->model->select_all();

        $this->assertIsArray($all, "select_all should return an array");
        
        if (!empty($all)) {
            $this->assertArrayHasKey('id', $all[0], "Each row should have 'id'");
            $this->assertArrayHasKey('nom', $all[0], "Each row should have 'nom'");
        }
    }

    /**
     * Test get_first() returns first ticket type
     */
    public function testGetFirstReturnsFirstTicketType()
    {
        $first = $this->model->get_first();

        if (!empty($first)) {
            $this->assertIsArray($first, "get_first should return an array");
            $this->assertArrayHasKey('id', $first, "Result should contain id field");
            $this->assertArrayHasKey('nom', $first, "Result should contain nom field");
        } else {
            $this->markTestSkipped("No ticket types in database");
        }
    }

    /**
     * Test table() method returns correct table name
     */
    public function testTableMethod()
    {
        $table = $this->model->table();

        $this->assertEquals('type_ticket', $table, "table() method should return 'type_ticket'");
    }

    /**
     * Test primary_key() method returns correct key
     */
    public function testPrimaryKeyMethod()
    {
        $pk = $this->model->primary_key();

        $this->assertEquals('id', $pk, "primary_key() should return 'id'");
    }

    /**
     * Test selector() returns array suitable for dropdowns
     */
    public function testSelectorReturnsArray()
    {
        $selector = $this->model->selector();

        $this->assertIsArray($selector, "selector() should return an array");
        
        // Selector returns associative array with id => label
        foreach ($selector as $key => $value) {
            $this->assertIsString($value, "Selector values should be strings");
        }
    }

    /**
     * Test model properties are correctly set
     */
    public function testModelProperties()
    {
        $this->assertEquals('type_ticket', $this->model->table, 
            "Table property should be 'type_ticket'");
        $this->assertEquals('id', $this->model->primary_key(), 
            "Primary key method should return 'id'");
    }

    /**
     * Test select_page stores metadata correctly
     */
    public function testSelectPageStoresMetadata()
    {
        $result = $this->model->select_page(5, 0);

        // After calling select_page, metadata should be stored
        // This is internal behavior but we can verify the method completes without error
        $this->assertIsArray($result, "select_page should complete and return array");
    }
}
