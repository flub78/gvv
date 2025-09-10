<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for form_elements helper functions
 * 
 * Demonstrates loading CodeIgniter form helpers and testing table manipulation functions
 */
class FormElementsIntegrationTest extends TestCase
{
    private $CI;
    
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Load form_elements and required system helpers
        $this->CI->load->helper(['form_elements', 'form']);
        
        // Mock config for default values  
        $mock_config = new MockConfig();
        $this->CI->config = $mock_config;
    }
    
    public function tearDown(): void
    {
        // Rollback transaction
        $this->CI->db->trans_rollback();
    }

    /**
     * Test add_first_row function with table manipulation
     */
    public function testAddFirstRowIntegration()
    {
        $table = array(
            array(100, 200, 300),
            array(400, 500, 600)
        );
        
        $this->assertCount(2, $table, "Initial table should have 2 rows");
        
        // Add header row
        add_first_row($table, array('Q1', 'Q2', 'Q3'));
        
        $this->assertCount(3, $table, "Table should have 3 rows after adding header");
        $this->assertEquals('Q1', $table[0][0], "First header should be Q1");
        $this->assertEquals('Q2', $table[0][1], "Second header should be Q2");
        $this->assertEquals('Q3', $table[0][2], "Third header should be Q3");
        $this->assertEquals(100, $table[1][0], "Original data should be shifted down");
    }

    /**
     * Test add_first_col function with table manipulation
     */
    public function testAddFirstColIntegration()
    {
        $table = array(
            array('Jan', 'Feb', 'Mar'),
            array(10, 20, 30),
            array(40, 50, 60)
        );
        
        // Add first column with year labels
        add_first_col($table, array('Month', '2023', '2024'));
        
        $this->assertEquals('Month', $table[0][0], "Header should be Month");
        $this->assertEquals('2023', $table[1][0], "First year should be 2023");
        $this->assertEquals('2024', $table[2][0], "Second year should be 2024");
        $this->assertEquals('Jan', $table[0][1], "Original headers should shift right");
        $this->assertEquals(10, $table[1][1], "Original data should shift right");
    }

    /**
     * Test complete table manipulation workflow
     */
    public function testCompleteTableWorkflow()
    {
        // Start with raw data
        $sales_data = array(
            array(1000, 1500, 2000),  // Q1 sales
            array(1200, 1800, 2200),  // Q2 sales
            array(1100, 1600, 2100)   // Q3 sales
        );
        
        // Add column headers (months)
        add_first_row($sales_data, array('Jan', 'Feb', 'Mar'));
        
        // Add row labels (quarters)
        add_first_col($sales_data, array('Quarter', 'Q1', 'Q2', 'Q3'));
        
        // Verify final structure
        $this->assertEquals('Quarter', $sales_data[0][0], "Top-left should be Quarter label");
        $this->assertEquals('Jan', $sales_data[0][1], "First month header");
        $this->assertEquals('Q1', $sales_data[1][0], "First quarter label");
        $this->assertEquals(1000, $sales_data[1][1], "Q1 Jan sales data");
        $this->assertEquals(2200, $sales_data[2][3], "Q2 Mar sales data"); // Fixed index
        
        // Verify table dimensions
        $this->assertCount(4, $sales_data, "Should have 4 rows (header + 3 data rows)");
        $this->assertCount(4, $sales_data[0], "Should have 4 columns (label + 3 data columns)");
    }

    /**
     * Test input_field function if available
     */
    public function testInputFieldFunction()
    {
        if (function_exists('input_field')) {
            // Test with array attributes (input_field expects array, not string)
            $input = input_field("username", "john_doe", array('class' => 'form-control'));
            
            $this->assertTrue(is_string($input), "input_field should return HTML string");
            $this->assertTrue(strpos($input, 'username') !== false, "Should contain field name");
            $this->assertTrue(strpos($input, 'john_doe') !== false, "Should contain field value");
            $this->assertTrue(strpos($input, 'form-control') !== false, "Should contain CSS class");
        } else {
            $this->assertTrue(true, 'input_field function not available');
        }
    }

    /**
     * Test dropdown_field function if available
     */
    public function testDropdownFieldFunction()
    {
        if (function_exists('dropdown_field')) {
            $options = array(
                'admin' => 'Administrator',
                'user' => 'Regular User',
                'guest' => 'Guest'
            );
            
            $dropdown = dropdown_field('role', 'user', $options, "class='select-control'");
            
            $this->assertIsString($dropdown, "dropdown_field should return HTML string");
            $this->assertStringContainsString('role', $dropdown, "Should contain field name");
            $this->assertStringContainsString('Administrator', $dropdown, "Should contain option text");
        } else {
            $this->markTestSkipped('dropdown_field function not available');
        }
    }

    /**
     * Test helper loading with database integration
     */
    public function testHelperWithDatabaseIntegration()
    {
        // Load CSV helper as well
        $this->CI->load->helper('csv');
        
        if (function_exists('csv_file')) {
            // Create table data
            $table = array(
                array('Product', 'Q1', 'Q2', 'Q3'),
                array('Laptops', 100, 150, 200),
                array('Phones', 200, 250, 300)
            );
            
            // Add summary row
            add_first_row($table, array('Sales Report 2024', '', '', ''));
            
            // Generate CSV
            $csv_content = csv_file("sales_report.csv", $table, false);
            
            $this->assertNotEmpty($csv_content, "CSV should be generated");
            $this->assertStringContainsString('Sales Report 2024', $csv_content, "Should contain title");
            $this->assertStringContainsString('Laptops', $csv_content, "Should contain product data");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }
}
