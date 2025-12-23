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

    /**
     * Test csv_file() with numeric values uses comma as decimal separator
     */
    public function testCsvFileNumericConversion()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Prix", "Montant"),
                array(10.5, 100.25),
                array(0.99, 1000)
            );
            
            $csv_content = csv_file("prices.csv", $table, false);
            
            // Numeric values should have comma instead of period
            $this->assertStringContainsString("10,5", $csv_content, "Numeric value should use comma separator");
            $this->assertStringContainsString("100,25", $csv_content, "Decimal should convert . to ,");
            $this->assertStringContainsString("0,99", $csv_content, "Small decimal should use comma");
            $this->assertStringContainsString("1000", $csv_content, "Large number should be in output");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with header flag
     */
    public function testCsvFileWithHeaderFlag()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Nom" => "Jean", "Age" => 25),
                array("Nom" => "Marie", "Age" => 30)
            );
            
            $csv_content = csv_file("with_header.csv", $table, false, true);
            
            $this->assertIsString($csv_content, "CSV with header flag should return a string");
            $this->assertStringContainsString("Jean", $csv_content, "Should contain data");
            $this->assertStringContainsString("Marie", $csv_content, "Should contain second row data");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with special characters
     */
    public function testCsvFileWithSpecialCharacters()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Nom", "Commune"),
                array("François", "Île-de-France"),
                array("José", "España"),
                array("Müller", "Deutschland")
            );
            
            $csv_content = csv_file("special.csv", $table, false);
            
            $this->assertStringContainsString("François", $csv_content, "Should preserve French accent");
            $this->assertStringContainsString("Île-de-France", $csv_content, "Should preserve French special characters");
            $this->assertStringContainsString("José", $csv_content, "Should preserve Spanish accent");
            $this->assertStringContainsString("Müller", $csv_content, "Should preserve German umlaut");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with multiple rows and complex structure
     */
    public function testCsvFileMultipleRowsComplex()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("ID", "Nom", "Valeur1", "Valeur2"),
                array(1, "Item1", 100.5, 50),
                array(2, "Item2", 200.75, 75),
                array(3, "Item3", 300, 100),
                array(4, "Item4", 400.25, 125)
            );
            
            $csv_content = csv_file("complex.csv", $table, false);
            
            // Check title is included
            $this->assertStringContainsString("complex.csv", $csv_content, "CSV title should be in content");
            
            // Check all data is present
            $this->assertStringContainsString("Item1", $csv_content);
            $this->assertStringContainsString("Item2", $csv_content);
            $this->assertStringContainsString("Item3", $csv_content);
            $this->assertStringContainsString("Item4", $csv_content);
            
            // Check numeric conversions (commas for decimals)
            $this->assertStringContainsString("100,5", $csv_content);
            $this->assertStringContainsString("200,75", $csv_content);
            $this->assertStringContainsString("300", $csv_content);
            $this->assertStringContainsString("400,25", $csv_content);
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() produces valid CSV format (semicolon-delimited)
     */
    public function testCsvFileFormatValidation()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Col1", "Col2", "Col3"),
                array("Val1", "Val2", "Val3")
            );
            
            $csv_content = csv_file("format.csv", $table, false);
            
            // CSV should use semicolons as delimiters
            $this->assertStringContainsString(";", $csv_content, "CSV should contain semicolon delimiters");
            
            // Each line should end with newline
            $this->assertStringContainsString("\n", $csv_content, "CSV should contain newlines");
            
            // Should not use commas as delimiters (for numbers they use commas for decimals)
            $lines = explode("\n", trim($csv_content));
            foreach ($lines as $line) {
                if (!empty($line)) {
                    // Each data line should have semicolons
                    $this->assertStringContainsString(";", $line, "Each CSV line should have semicolons");
                }
            }
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }

    /**
     * Test csv_file() with empty strings and null values
     */
    public function testCsvFileWithEmptyAndNullValues()
    {
        if (function_exists('csv_file')) {
            $table = array(
                array("Name", "Value", "Description"),
                array("Item1", "", "No value"),
                array("Item2", null, "Null value"),
                array("", "Value", "No name")
            );
            
            $csv_content = csv_file("empty_values.csv", $table, false);
            
            $this->assertIsString($csv_content, "CSV with empty values should return a string");
            $this->assertNotEmpty($csv_content, "CSV should not be completely empty");
            $this->assertStringContainsString("Item1", $csv_content, "Should contain Item1");
            $this->assertStringContainsString("Item2", $csv_content, "Should contain Item2");
        } else {
            $this->markTestSkipped('csv_file function not available');
        }
    }
}
