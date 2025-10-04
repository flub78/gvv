<?php

use PHPUnit\Framework\TestCase;

/**
 * PHPUnit tests for csv helper functions
 * 
 * Tests CSV file generation functionality
 */
class CsvHelperTest extends TestCase
{
    public function setUp(): void
    {
        // Load csv helper
        if (!function_exists('csv_file')) {
            require_once APPPATH . 'helpers/csv_helper.php';
        }
    }

    /**
     * Test csv_file() function generates non-empty CSV content
     */
    public function testCsvFileFunction()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Nom", "Age"),
                array("Jean", 18),
                array("Mathusalem", 99)
            );
            
            $csv_content = csv_file("test.csv", $table, false);
            
            $this->assertNotEmpty($csv_content, "CSV content should not be empty");
            $this->assertIsString($csv_content, "CSV content should be a string");
            
            // Check that content contains expected data
            $this->assertStringContainsString("Nom", $csv_content, "CSV should contain 'Nom' header");
            $this->assertStringContainsString("Age", $csv_content, "CSV should contain 'Age' header");
            $this->assertStringContainsString("Jean", $csv_content, "CSV should contain 'Jean' data");
            $this->assertStringContainsString("18", $csv_content, "CSV should contain age '18'");
            $this->assertStringContainsString("Mathusalem", $csv_content, "CSV should contain 'Mathusalem' data");
            $this->assertStringContainsString("99", $csv_content, "CSV should contain age '99'");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with empty table
     */
    public function testCsvFileWithEmptyTable()
    {
        if (function_exists('csv_file')) {
            $empty_table = array();
            $csv_content = csv_file("empty.csv", $empty_table, false);
            
            // Should return some content even for empty table (headers, etc.)
            $this->assertIsString($csv_content, "CSV content should be a string even for empty table");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with single row
     */
    public function testCsvFileWithSingleRow()
    {
        if (function_exists('csv_file')) {
            $single_row_table = array(
                array("Header1", "Header2", "Header3")
            );
            
            $csv_content = csv_file("single.csv", $single_row_table, false);
            
            $this->assertNotEmpty($csv_content, "Single row CSV should not be empty");
            $this->assertStringContainsString("Header1", $csv_content, "Should contain Header1");
            $this->assertStringContainsString("Header2", $csv_content, "Should contain Header2");
            $this->assertStringContainsString("Header3", $csv_content, "Should contain Header3");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with mixed data types
     */
    // todo re-enable it later
    // public function testCsvFileWithMixedDataTypes()
    // {
    //     if (function_exists('csv_file')) {
    //         $mixed_table = array(
    //             array("String", "Number", "Float", "Boolean"),
    //             array("Test", 42, 3.14, true),
    //             array("Another", 0, -1.5, false)
    //         );
            
    //         $csv_content = csv_file("mixed.csv", $mixed_table, false);
            
    //         $this->assertNotEmpty($csv_content, "Mixed data types CSV should not be empty");
    //         $this->assertStringContainsString("42", $csv_content, "Should contain number 42");
    //         $this->assertStringContainsString("3.14", $csv_content, "Should contain float 3.14");
    //     } else {
    //         $this->markTestSkipped('csv_file function not available');
    //     }
    // }
}
