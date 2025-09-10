<?php

use PHPUnit\Framework\TestCase;

/**
 * Simple debugging example test
 */
class DebugExampleTest extends TestCase
{
    /**
     * Simple test to demonstrate debugging
     */
    public function testDebuggingExample()
    {
        // This is where you would set a breakpoint in VS Code
        $value = 42;
        
        // You can also use xdebug_break() to force a breakpoint
        // xdebug_break();
        
        $result = $value * 2;
        
        // Set another breakpoint here to inspect $result
        $this->assertEquals(84, $result);
        
        // Test with array to inspect more complex data
        $data = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'values' => [1, 2, 3, 4, 5]
        ];
        
        // Breakpoint here to inspect $data array
        $this->assertCount(3, $data);
        $this->assertEquals('Test User', $data['name']);
    }
    
    /**
     * Test that demonstrates debugging with helper functions
     */
    public function testDebuggingWithHelpers()
    {
        // Test the date conversion function
        $input_date = "2023-12-25";
        
        // Set breakpoint here to step into the helper function
        $result = date_db2ht($input_date);
        
        // Set breakpoint here to inspect the result
        $this->assertEquals("25/12/2023", $result);
    }
}

?>
