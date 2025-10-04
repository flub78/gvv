<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for validation helper functions
 * 
 * This test demonstrates how to test CodeIgniter helper functions
 * with PHPUnit 8.x using a minimal bootstrap approach.
 */
class ValidationHelperTest extends TestCase
{
    /**
     * Test date conversion from database format (YYYY-MM-DD) to human format (DD/MM/YYYY)
     */
    public function testDateDb2Ht()
    {
        // Test valid dates
        $this->assertEquals("01/01/2023", date_db2ht("2023-01-01"));
        $this->assertEquals("31/12/2023", date_db2ht("2023-12-31"));
        $this->assertEquals("15/06/2024", date_db2ht("2024-06-15"));
        
        // Test edge cases
        $this->assertEquals("", date_db2ht(""));
        $this->assertEquals("", date_db2ht("0000-00-00"));
        
        // Test invalid format (should return as-is)
        $this->assertEquals("invalid-date", date_db2ht("invalid-date"));
    }
    
    /**
     * Test date conversion from human format (DD/MM/YYYY) to database format (YYYY-MM-DD)
     */
    public function testDateHt2Db()
    {
        // Test valid dates
        $this->assertEquals("2023-01-01", date_ht2db("01/01/2023"));
        $this->assertEquals("2023-12-31", date_ht2db("31/12/2023"));
        $this->assertEquals("2024-06-15", date_ht2db("15/06/2024"));
        
        // Test single digit days and months
        $this->assertEquals("2023-01-01", date_ht2db("1/1/2023"));
        $this->assertEquals("2023-12-05", date_ht2db("5/12/2023"));
        
        // Test empty string
        $this->assertEquals("", date_ht2db(""));
        
        // Test invalid format (should return as-is)
        $this->assertEquals("invalid-date", date_ht2db("invalid-date"));
    }
    
    /**
     * Test French date comparison function
     */
    public function testFrenchDateCompare()
    {
        // Test equality
        $this->assertTrue(french_date_compare("01/01/2023", "01/01/2023", "=="));
        $this->assertTrue(french_date_compare("15/06/2024", "15/06/2024", "=="));
        
        // Test less than
        $this->assertTrue(french_date_compare("01/01/2023", "02/01/2023", "<"));
        $this->assertTrue(french_date_compare("31/12/2022", "01/01/2023", "<"));
        
        // Test less than or equal
        $this->assertTrue(french_date_compare("01/01/2023", "01/01/2023", "<="));
        $this->assertTrue(french_date_compare("01/01/2023", "02/01/2023", "<="));
        
        // Test greater than
        $this->assertTrue(french_date_compare("02/01/2023", "01/01/2023", ">"));
        $this->assertTrue(french_date_compare("01/01/2024", "31/12/2023", ">"));
        
        // Test greater than or equal
        $this->assertTrue(french_date_compare("01/01/2023", "01/01/2023", ">="));
        $this->assertTrue(french_date_compare("02/01/2023", "01/01/2023", ">="));
    }
    
    /**
     * Test minute to time conversion
     */
    public function testMinuteToTime()
    {
        // Test minutes conversion
        $this->assertEquals("00:00", minute_to_time(0));
        $this->assertEquals("00:30", minute_to_time(30));
        $this->assertEquals("01:00", minute_to_time(60));
        $this->assertEquals("02:35", minute_to_time(155));
        $this->assertEquals("10:45", minute_to_time(645));
        
        // Test already formatted times (should return as-is)
        $this->assertEquals("02:30", minute_to_time("02:30"));
        $this->assertEquals("10:15", minute_to_time("10:15"));
    }
    
    /**
     * Test decimal to time conversion
     */
    public function testDecimalToTime()
    {
        // Test decimal hours to HH:MM format
        $this->assertEquals("00:00", decimal_to_time(0));
        $this->assertEquals("01:30", decimal_to_time(1.30));
        $this->assertEquals("02:45", decimal_to_time(2.45));
        $this->assertEquals("10:15", decimal_to_time(10.15));
        $this->assertEquals("23:59", decimal_to_time(23.59));
    }
    
    /**
     * Test euro formatting function
     */
    public function testEuro()
    {
        // Test basic formatting with default HTML output
        $this->assertEquals("0,00&nbsp;€", euro(0));
        $this->assertEquals("10,50&nbsp;€", euro(10.5));
        $this->assertEquals("123,45&nbsp;€", euro(123.45));
        $this->assertEquals("1&nbsp;234,56&nbsp;€", euro(1234.56));
        
        // Test with string input
        $this->assertEquals("15,75&nbsp;€", euro("15.75"));
        $this->assertEquals("1&nbsp;000,00&nbsp;€", euro("1000"));
        
        // Test with different separators
        $this->assertEquals("123.45&nbsp;€", euro(123.45, '.'));
        
        // Test PDF target (no HTML entities)
        $this->assertEquals("123,45", euro(123.45, ',', 'pdf'));
        
        // Test CSV target (no HTML entities, no symbol)
        $this->assertEquals("123,45", euro(123.45, ',', 'csv'));
    }
    
    /**
     * Test email validation function (from minimal bootstrap)
     */
    public function testEmailValidation()
    {
        // Test valid emails
        $this->assertTrue(valid_email('test@example.com'));
        $this->assertTrue(valid_email('user.name@domain.co.uk'));
        $this->assertTrue(valid_email('first.last+tag@subdomain.example.com'));
        
        // Test invalid emails
        $this->assertFalse(valid_email('test#example.com'));
        $this->assertFalse(valid_email('invalid.email'));
        $this->assertFalse(valid_email('@domain.com'));
        $this->assertFalse(valid_email('user@'));
        $this->assertFalse(valid_email(''));
    }
}

?>