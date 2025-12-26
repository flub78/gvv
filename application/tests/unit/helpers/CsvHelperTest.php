<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for CSV helper functions
 *
 * Tests the csv_file function that generates CSV files from arrays
 */
class CsvHelperTest extends TestCase
{
    protected $CI;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a mock CI instance
        $this->CI = new stdClass();
        $this->CI->load = new stdClass();

        // Mock the helper loading methods
        $this->CI->load->helper = function($helper) {
            // Do nothing - helpers are already loaded
        };

        // Store the mock instance
        $this->setMockCIInstance($this->CI);
    }

    /**
     * Helper to set mock CI instance for get_instance()
     */
    private function setMockCIInstance($instance)
    {
        // Load the helper file which will use get_instance()
        require_once APPPATH . 'helpers/csv_helper.php';
    }

    /**
     * Test CSV generation with basic data (no download)
     */
    public function testCsvFileBasicData()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];

        $result = csv_file('test', $data, false, false);

        // Check title is included
        $this->assertStringContainsString('test;', $result);

        // Check data is present
        $this->assertStringContainsString('John;', $result);
        $this->assertStringContainsString('Jane;', $result);
        $this->assertStringContainsString('30;', $result);
        $this->assertStringContainsString('25;', $result);
    }

    /**
     * Test CSV generation with header row
     */
    public function testCsvFileWithHeader()
    {
        $data = [
            ['name' => 'John', 'age' => 30],
            ['name' => 'Jane', 'age' => 25]
        ];

        $result = csv_file('test', $data, false, true);

        // Check header is included
        $this->assertStringContainsString('name;', $result);
        $this->assertStringContainsString('age;', $result);

        // Check data is present
        $this->assertStringContainsString('John;', $result);
        $this->assertStringContainsString('Jane;', $result);
    }

    /**
     * Test CSV generation with numeric values
     */
    public function testCsvFileNumericValues()
    {
        $data = [
            ['price' => 10.50, 'quantity' => 3],
            ['price' => 25.75, 'quantity' => 2]
        ];

        $result = csv_file('prices', $data, false, false);

        // Check that decimal points are converted to commas (trailing zeros are not preserved)
        $this->assertStringContainsString('10,5;', $result);
        $this->assertStringContainsString('25,75;', $result);

        // Check integer values
        $this->assertStringContainsString('3;', $result);
        $this->assertStringContainsString('2;', $result);
    }

    /**
     * Test CSV generation with empty data
     */
    public function testCsvFileEmptyData()
    {
        $data = [];

        $result = csv_file('empty', $data, false, false);

        // Should only contain the title
        $this->assertStringContainsString('empty;', $result);
    }

    /**
     * Test CSV generation without title
     */
    public function testCsvFileNoTitle()
    {
        $data = [
            ['name' => 'John', 'age' => 30]
        ];

        $result = csv_file('', $data, false, false);

        // Should contain data
        $this->assertStringContainsString('John;', $result);
        $this->assertStringContainsString('30;', $result);
    }

    /**
     * Test CSV generation with mixed data types
     */
    public function testCsvFileMixedDataTypes()
    {
        $data = [
            ['name' => 'Test', 'value' => 123, 'price' => 45.67, 'active' => true],
            ['name' => 'Test2', 'value' => 456, 'price' => 78.90, 'active' => false]
        ];

        $result = csv_file('mixed', $data, false, false);

        // Check string values
        $this->assertStringContainsString('Test;', $result);
        $this->assertStringContainsString('Test2;', $result);

        // Check numeric values
        $this->assertStringContainsString('123;', $result);
        $this->assertStringContainsString('456;', $result);

        // Check decimal values (with comma, trailing zeros not preserved)
        $this->assertStringContainsString('45,67;', $result);
        $this->assertStringContainsString('78,9;', $result);
    }

    /**
     * Test that lines end with newline characters
     */
    public function testCsvFileLineEndings()
    {
        $data = [
            ['name' => 'John'],
            ['name' => 'Jane']
        ];

        $result = csv_file('test', $data, false, false);

        // Check that lines end with \n
        $lines = explode("\n", $result);
        $this->assertGreaterThan(2, count($lines)); // Title + data rows
    }
}
