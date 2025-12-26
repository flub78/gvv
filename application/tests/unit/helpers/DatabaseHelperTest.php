<?php
/**
 * GVV Gestion vol à voile
 * Copyright (C) 2011 Philippe Boissel & Frédéric Peignot
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 */

class DatabaseHelperTest extends PHPUnit\Framework\TestCase {

    private $CI;

    protected function setUp(): void {
        $this->CI = &get_instance();
        $this->CI->load->helper('database');
    }

    public function test_database_helper_loads_successfully() {
        $this->assertTrue(function_exists('mysql_real_escape_string'),
            'mysql_real_escape_string function should exist');
    }

    public function test_mysql_real_escape_string_escapes_single_quotes() {
        $input = "O'Reilly";
        $output = mysql_real_escape_string($input);
        $this->assertStringContainsString("\\'", $output,
            'Should escape single quotes');
    }

    public function test_mysql_real_escape_string_escapes_double_quotes() {
        $input = 'He said "Hello"';
        $output = mysql_real_escape_string($input);
        $this->assertStringContainsString('\\"', $output,
            'Should escape double quotes');
    }

    public function test_mysql_real_escape_string_escapes_backslashes() {
        $input = 'Path\\to\\file';
        $output = mysql_real_escape_string($input);
        $this->assertStringContainsString('\\\\', $output,
            'Should escape backslashes');
    }

    public function test_mysql_real_escape_string_handles_empty_string() {
        $output = mysql_real_escape_string('');
        $this->assertEquals('', $output,
            'Empty string should remain empty');
    }

    public function test_mysql_real_escape_string_handles_plain_text() {
        $input = 'Simple text without special characters';
        $output = mysql_real_escape_string($input);
        $this->assertEquals($input, $output,
            'Plain text should not be modified');
    }

    public function test_mysql_real_escape_string_handles_null_bytes() {
        $input = "text\0with\0nulls";
        $output = mysql_real_escape_string($input);
        // The function should escape or handle null bytes safely
        $this->assertNotEmpty($output);
    }

    public function test_mysql_real_escape_string_handles_sql_injection_attempt() {
        $input = "' OR '1'='1";
        $output = mysql_real_escape_string($input);
        // Should escape the quotes to prevent injection
        $this->assertStringContainsString("\\'", $output);
        $this->assertStringNotContainsString("' OR '1'='1", $output);
    }

    public function test_mysql_real_escape_string_handles_multiple_special_chars() {
        $input = "Test's \"quoted\" text\\with\\backslashes";
        $output = mysql_real_escape_string($input);
        // All special characters should be escaped
        $this->assertStringContainsString("\\'", $output);
        $this->assertStringContainsString('\\"', $output);
        $this->assertStringContainsString('\\\\', $output);
    }
}

/* End of file DatabaseHelperTest.php */
/* Location: ./application/tests/unit/helpers/DatabaseHelperTest.php */
