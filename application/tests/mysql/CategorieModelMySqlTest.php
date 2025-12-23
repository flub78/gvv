<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Categorie_model
 * 
 * Tests CRUD operations on the categorie (accounting categories) table
 * 
 * @package tests
 */
class CategorieModelMySqlTest extends TestCase
{
    private $CI;
    private $model;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load database and model
        $this->CI->load->database();
        $this->CI->load->model('categorie_model');
        $this->model = $this->CI->categorie_model;
    }

    /**
     * Test that model exists and can be instantiated
     */
    public function testModelInstantiation()
    {
        $this->assertNotNull($this->model, "Categorie_model should be instantiated");
        $this->assertEquals('categorie', $this->model->table, "Model should reference 'categorie' table");
        $this->assertEquals('id', $this->model->primary_key(), "Primary key should be 'id'");
    }

    /**
     * Test select_page() returns array with proper structure including parent info
     */
    public function testSelectPageReturnsArrayWithParent()
    {
        $result = $this->model->select_page(10, 0);

        $this->assertIsArray($result, "select_page should return an array");
        
        if (!empty($result)) {
            $this->assertIsArray($result[0], "Each row should be an array");
            $this->assertArrayHasKey('id', $result[0], "Should have 'id' field");
            $this->assertArrayHasKey('nom', $result[0], "Should have 'nom' field");
            $this->assertArrayHasKey('description', $result[0], "Should have 'description' field");
            $this->assertArrayHasKey('parent', $result[0], "Should have 'parent' field");
            $this->assertArrayHasKey('type', $result[0], "Should have 'type' field");
            $this->assertArrayHasKey('nom_parent', $result[0], "Should have 'nom_parent' field from join");
            $this->assertArrayHasKey('image', $result[0], "Should have 'image' field added by select_page");
        }
    }

    /**
     * Test select_page() adds image field to results
     */
    public function testSelectPageAddsImageField()
    {
        $result = $this->model->select_page(5, 0);

        if (!empty($result)) {
            foreach ($result as $row) {
                $this->assertArrayHasKey('image', $row, "Each row should have 'image' field");
                $this->assertEquals($row['nom'], $row['image'], "Image field should equal nom field");
            }
        }
    }

    /**
     * Test image() method returns category name
     */
    public function testImageMethodReturnsName()
    {
        // Get first category
        $categories = $this->model->select_page(1, 0);
        
        if (!empty($categories)) {
            $id = $categories[0]['id'];
            $expected_name = $categories[0]['nom'];
            $image = $this->model->image($id);

            $this->assertIsString($image, "image() should return a string");
            $this->assertEquals($expected_name, $image, "image() should return the category name");
        }
    }

    /**
     * Test image() method with empty ID
     */
    public function testImageMethodEmpty()
    {
        $image = $this->model->image('');

        $this->assertEquals('', $image, "image() should return empty string for empty ID");
    }

    /**
     * Test image() method with non-existent ID
     * Note: The model has a bug where get_by_id returns null instead of empty array
     * when category not found, causing array_key_exists to fail
     */
    public function testImageMethodNonExistent()
    {
        // The model's image() method will throw an error for non-existent ID
        // This test documents the current behavior
        $this->expectError();
        $image = $this->model->image(999999);
    }

    /**
     * Test count() returns total number of categories
     */
    public function testCountAllCategories()
    {
        $count = $this->model->count();

        $this->assertIsInt($count, "count() should return an integer");
        $this->assertGreaterThan(0, $count, "Should have at least one category in database");
    }

    /**
     * Test get_by_id() retrieves category by ID
     */
    public function testGetByIdRetrievesCategory()
    {
        // Get first category
        $categories = $this->model->select_page(1, 0);
        
        if (!empty($categories)) {
            $id = $categories[0]['id'];
            $result = $this->model->get_by_id('id', $id);

            $this->assertIsArray($result, "get_by_id should return an array");
            $this->assertArrayHasKey('id', $result, "Result should contain id field");
            $this->assertArrayHasKey('nom', $result, "Result should contain nom field");
            $this->assertEquals($id, $result['id'], "Retrieved ID should match requested");
        }
    }

    /**
     * Test select_all() returns all categories
     */
    public function testSelectAllReturnsCategories()
    {
        $all = $this->model->select_all();

        $this->assertIsArray($all, "select_all should return an array");
        $this->assertGreaterThan(0, count($all), "Should return at least one category");
    }

    /**
     * Test get_first() returns first category
     */
    public function testGetFirstReturnsFirstCategory()
    {
        $first = $this->model->get_first();

        $this->assertIsArray($first, "get_first should return an array");
        $this->assertArrayHasKey('id', $first, "Result should contain id field");
        $this->assertArrayHasKey('nom', $first, "Result should contain nom field");
    }

    /**
     * Test table() method returns correct table name
     */
    public function testTableMethod()
    {
        $table = $this->model->table();

        $this->assertEquals('categorie', $table, "table() method should return 'categorie'");
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
        if (!empty($selector)) {
            foreach ($selector as $key => $value) {
                $this->assertIsString($value, "Selector values should be strings");
            }
        }
    }

    /**
     * Test that select_page includes parent-child relationship
     */
    public function testSelectPageJoinsParentCategory()
    {
        $result = $this->model->select_page(10, 0);

        if (!empty($result)) {
            // At least verify the join fields are present
            $this->assertArrayHasKey('nom_parent', $result[0], 
                "Should include parent category name from join");
            $this->assertArrayHasKey('parent', $result[0], 
                "Should include parent ID");
        }
    }

    /**
     * Test model properties are correctly set
     */
    public function testModelProperties()
    {
        $this->assertEquals('categorie', $this->model->table, 
            "Table property should be 'categorie'");
        $this->assertEquals('id', $this->model->primary_key(), 
            "Primary key method should return 'id'");
    }
}
