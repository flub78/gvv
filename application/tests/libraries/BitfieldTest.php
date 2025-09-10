<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for Bitfield library
 * 
 * This test demonstrates how to test CodeIgniter library classes
 * with PHPUnit 8.x using a minimal bootstrap approach.
 */
class BitfieldTest extends TestCase
{
    /**
     * Test Bitfield constructor with different input types
     */
    public function testConstructor()
    {
        // Test default constructor
        $bitfield = new Bitfield();
        $this->assertEquals(0, $bitfield->toNumber());
        
        // Test constructor with integer
        $bitfield = new Bitfield(15); // 0x0F = 1111
        $this->assertEquals(15, $bitfield->toNumber());
        $this->assertEquals('1111', $bitfield->toString());
        
        // Test constructor with binary string
        $bitfield = new Bitfield('1010');
        $this->assertEquals(10, $bitfield->toNumber()); // 1010 binary = 10 decimal
        $this->assertEquals('1010', $bitfield->toString());
        
        // Test constructor with complex binary string
        $bitfield = new Bitfield('11110000');
        $this->assertEquals(240, $bitfield->toNumber()); // 11110000 binary = 240 decimal
        $this->assertEquals('11110000', $bitfield->toString());
    }
    
    /**
     * Test string representation (__toString method)
     */
    public function testToString()
    {
        $bitfield = new Bitfield(5); // 101 in binary
        $this->assertEquals('101', (string)$bitfield);
        
        $bitfield = new Bitfield(255); // 11111111 in binary
        $this->assertEquals('11111111', (string)$bitfield);
        
        $bitfield = new Bitfield(0);
        $this->assertEquals('0', (string)$bitfield);
    }
    
    /**
     * Test bit getting and setting operations
     */
    public function testBitOperations()
    {
        $bitfield = new Bitfield(0);
        
        // Test setting bits
        $bitfield->set(0); // Set bit 0 (rightmost)
        $this->assertTrue($bitfield->get(0));
        $this->assertEquals(1, $bitfield->toNumber());
        
        $bitfield->set(2); // Set bit 2
        $this->assertTrue($bitfield->get(2));
        $this->assertEquals(5, $bitfield->toNumber()); // 101 = 5
        
        $bitfield->set(3); // Set bit 3
        $this->assertTrue($bitfield->get(3));
        $this->assertEquals(13, $bitfield->toNumber()); // 1101 = 13
        
        // Test getting unset bits
        $this->assertFalse($bitfield->get(1));
        $this->assertFalse($bitfield->get(4));
        
        // Test resetting bits
        $bitfield->reset(0);
        $this->assertFalse($bitfield->get(0));
        $this->assertEquals(12, $bitfield->toNumber()); // 1100 = 12
        
        // Test toggling bits
        $bitfield->toggle(1); // Toggle bit 1 (was 0, now 1)
        $this->assertTrue($bitfield->get(1));
        $this->assertEquals(14, $bitfield->toNumber()); // 1110 = 14
        
        $bitfield->toggle(3); // Toggle bit 3 (was 1, now 0)
        $this->assertFalse($bitfield->get(3));
        $this->assertEquals(6, $bitfield->toNumber()); // 0110 = 6
    }
    
    /**
     * Test conversion from different number formats
     */
    public function testFromConversions()
    {
        $bitfield = new Bitfield();
        
        // Test fromNumber
        $bitfield->fromNumber(156);
        $this->assertEquals(156, $bitfield->toNumber());
        $this->assertEquals('10011100', $bitfield->toString());
        
        // Test fromString
        $bitfield->fromString('11001010');
        $this->assertEquals(202, $bitfield->toNumber());
        $this->assertEquals('11001010', $bitfield->toString());
        
        // Test fromBase with different bases
        $bitfield->fromBase('1F', 16); // Hex 1F = 31 decimal
        $this->assertEquals(31, $bitfield->toNumber());
        
        $bitfield->fromBase('77', 8); // Octal 77 = 63 decimal
        $this->assertEquals(63, $bitfield->toNumber());
        
        $bitfield->fromBase('1010', 2); // Binary 1010 = 10 decimal
        $this->assertEquals(10, $bitfield->toNumber());
        
        // Test fromHex
        $bitfield->fromHex('FF');
        $this->assertEquals(255, $bitfield->toNumber());
        
        // Test fromOct  
        $bitfield->fromOct('377');
        $this->assertEquals(255, $bitfield->toNumber());
        
        // Test fromBin
        $bitfield->fromBin('10101010');
        $this->assertEquals(170, $bitfield->toNumber());
    }
    
    /**
     * Test conversion to different number formats
     */
    public function testToConversions()
    {
        $bitfield = new Bitfield(255); // 11111111 in binary
        
        // Test toNumber
        $this->assertEquals(255, $bitfield->toNumber());
        
        // Test toString with padding
        $this->assertEquals('11111111', $bitfield->toString());
        $this->assertEquals('0011111111', $bitfield->toString(10)); // Padded to 10 bits
        
        // Test toArray
        $expected = ['1', '1', '1', '1', '1', '1', '1', '1']; // Reversed order (rightmost bit first)
        $this->assertEquals($expected, $bitfield->toArray());
        
        // Test base conversions
        $this->assertEquals('ff', $bitfield->toHex());
        $this->assertEquals('377', $bitfield->toOct());
        $this->assertEquals('11111111', $bitfield->toBin());
        
        // Test with smaller number
        $bitfield = new Bitfield(10); // 1010 in binary
        $this->assertEquals('a', $bitfield->toHex());
        $this->assertEquals('12', $bitfield->toOct());
        $this->assertEquals('1010', $bitfield->toBin());
    }
    
    /**
     * Test serialization functionality
     */
    public function testSerialization()
    {
        $bitfield = new Bitfield(42);
        
        // Test serialize
        $serialized = $bitfield->serialize();
        $this->assertEquals('42', $serialized);
        
        // Test unserialize
        $newBitfield = new Bitfield();
        $newBitfield->unserialize('123');
        $this->assertEquals(123, $newBitfield->toNumber());
        
        // Test full serialize/unserialize cycle
        $original = new Bitfield(199);
        $serialized = serialize($original);
        $restored = unserialize($serialized);
        $this->assertEquals($original->toNumber(), $restored->toNumber());
        $this->assertEquals($original->toString(), $restored->toString());
    }
    
    /**
     * Test iterator functionality
     */
    public function testIterator()
    {
        $bitfield = new Bitfield(5); // 101 in binary
        
        // Test that we can iterate over the bitfield
        $bits = [];
        foreach ($bitfield as $bit) {
            $bits[] = $bit;
        }
        
        // Should return array like toArray() - bits in reverse order (rightmost first)
        $expected = ['1', '0', '1']; // 101 reversed
        $this->assertEquals($expected, $bits);
        
        // Test with a larger number
        $bitfield = new Bitfield(15); // 1111 in binary
        $bits = [];
        foreach ($bitfield as $bit) {
            $bits[] = $bit;
        }
        
        $expected = ['1', '1', '1', '1']; // 1111 reversed (but it's symmetric)
        $this->assertEquals($expected, $bits);
    }
    
    /**
     * Test edge cases and error conditions
     */
    public function testEdgeCases()
    {
        // Test with zero
        $bitfield = new Bitfield(0);
        $this->assertEquals(0, $bitfield->toNumber());
        $this->assertEquals('0', $bitfield->toString());
        $this->assertFalse($bitfield->get(0));
        $this->assertFalse($bitfield->get(10));
        
        // Test with large numbers
        $bitfield = new Bitfield(1023); // 1111111111 in binary (10 bits)
        $this->assertEquals(1023, $bitfield->toNumber());
        $this->assertEquals('1111111111', $bitfield->toString());
        
        // Test getting/setting high bit positions
        $bitfield = new Bitfield(0);
        $bitfield->set(10);
        $this->assertTrue($bitfield->get(10));
        $this->assertEquals(1024, $bitfield->toNumber()); // 2^10
        
        // Test empty string input
        $bitfield = new Bitfield('');
        $this->assertEquals(0, $bitfield->toNumber());
        
        // Test single bit operations
        $bitfield = new Bitfield(1);
        $this->assertTrue($bitfield->get(0));
        $bitfield->reset(0);
        $this->assertEquals(0, $bitfield->toNumber());
        $bitfield->toggle(0);
        $this->assertEquals(1, $bitfield->toNumber());
    }
    
    /**
     * Test complex bit manipulation scenarios
     */
    /**
     * Skipped: Test complex bit manipulation scenarios
     */
    public function testComplexScenarios()
    {
        // $this->markTestSkipped('Complex scenarios test is skipped.');
        
        // Test creating a bitmask for permissions or flags
        $permissions = new Bitfield(0);
        
        // Set some permission flags
        $permissions->set(0); // READ permission
        $permissions->set(1); // WRITE permission
        $permissions->set(4); // EXECUTE permission
        
        // Check permissions
        $this->assertTrue($permissions->get(0)); // Has READ
        $this->assertTrue($permissions->get(1)); // Has WRITE
        $this->assertFalse($permissions->get(2)); // No CREATE
        $this->assertFalse($permissions->get(3)); // No DELETE
        $this->assertTrue($permissions->get(4)); // Has EXECUTE
        
        // Verify the numeric value
        $this->assertEquals(19, $permissions->toNumber()); // 10011 = 19
        
        // Test converting back and forth between formats
        $hexValue = $permissions->toHex();
        $newPermissions = new Bitfield();
        $newPermissions->fromHex($hexValue);
        $this->assertEquals($permissions->toNumber(), $newPermissions->toNumber());
        
        // Test toggle to remove permission
        $permissions->toggle(1); // Remove WRITE permission
        $this->assertFalse($permissions->get(1));
        $this->assertEquals(17, $permissions->toNumber()); // 10001 = 17
    }
}

?>
