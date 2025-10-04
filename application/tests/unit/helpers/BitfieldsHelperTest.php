<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for bitfields helper functions
 *
 * Tests the array2int and int2array functions which convert between
 * bitfield arrays and integer representations.
 */
class BitfieldsHelperTest extends TestCase
{
    /**
     * Test array2int with nominal cases
     */
    public function testArray2IntNominalCases()
    {
        // Test empty array
        $this->assertEquals(0, array2int(array()));

        // Test single bit flags
        $this->assertEquals(1, array2int(array(1)));
        $this->assertEquals(2, array2int(array(2)));
        $this->assertEquals(4, array2int(array(4)));
        $this->assertEquals(8, array2int(array(8)));

        // Test multiple bit flags
        $this->assertEquals(3, array2int(array(1, 2))); // 1 | 2 = 3
        $this->assertEquals(7, array2int(array(1, 2, 4))); // 1 | 2 | 4 = 7
        $this->assertEquals(15, array2int(array(1, 2, 4, 8))); // 1 | 2 | 4 | 8 = 15
        $this->assertEquals(5, array2int(array(1, 4))); // 1 | 4 = 5
        $this->assertEquals(10, array2int(array(2, 8))); // 2 | 8 = 10

        // Test larger values
        $this->assertEquals(255, array2int(array(1, 2, 4, 8, 16, 32, 64, 128)));
        $this->assertEquals(1024, array2int(array(1024)));
        $this->assertEquals(1023, array2int(array(1, 2, 4, 8, 16, 32, 64, 128, 256, 512)));
    }

    /**
     * Test array2int with duplicate values
     */
    public function testArray2IntWithDuplicates()
    {
        // Duplicate values should give same result (OR is idempotent)
        $this->assertEquals(3, array2int(array(1, 2, 1, 2)));
        $this->assertEquals(7, array2int(array(1, 2, 4, 1)));
        $this->assertEquals(5, array2int(array(1, 4, 1, 4)));
    }

    /**
     * Test array2int with null/false/empty values
     */
    public function testArray2IntWithIncorrectData()
    {
        // Test with null - should return 0
        $this->assertEquals(0, array2int(null));

        // Test with false - should return 0
        $this->assertEquals(0, array2int(false));

        // Test with empty string - should return 0
        $this->assertEquals(0, array2int(''));

        // Test with 0 in array - should not affect result
        $this->assertEquals(3, array2int(array(1, 2, 0)));

        // Test with array containing only zeros
        $this->assertEquals(0, array2int(array(0, 0, 0)));
    }

    /**
     * Test array2int with mixed key types
     */
    public function testArray2IntWithMixedKeys()
    {
        // Test with associative array (values should be used, not keys)
        $this->assertEquals(3, array2int(array('a' => 1, 'b' => 2)));
        $this->assertEquals(7, array2int(array(0 => 1, 1 => 2, 2 => 4)));
    }

    /**
     * Test int2array with nominal cases
     */
    public function testInt2ArrayNominalCases()
    {
        // Test zero - should return empty array
        $this->assertEquals(array(), int2array(0));

        // Test single bit flags
        $this->assertEquals(array(1 => 1), int2array(1));
        $this->assertEquals(array(2 => 2), int2array(2));
        $this->assertEquals(array(4 => 4), int2array(4));
        $this->assertEquals(array(8 => 8), int2array(8));
        $this->assertEquals(array(16 => 16), int2array(16));

        // Test multiple bit flags
        $this->assertEquals(array(1 => 1, 2 => 2), int2array(3)); // 3 = 1 | 2
        $this->assertEquals(array(1 => 1, 4 => 4), int2array(5)); // 5 = 1 | 4
        $this->assertEquals(array(1 => 1, 2 => 2, 4 => 4), int2array(7)); // 7 = 1 | 2 | 4
        $this->assertEquals(array(1 => 1, 2 => 2, 4 => 4, 8 => 8), int2array(15)); // 15 = 1 | 2 | 4 | 8
        $this->assertEquals(array(2 => 2, 8 => 8), int2array(10)); // 10 = 2 | 8

        // Test larger values
        $expected = array(
            1 => 1,
            2 => 2,
            4 => 4,
            8 => 8,
            16 => 16,
            32 => 32,
            64 => 64,
            128 => 128
        );
        $this->assertEquals($expected, int2array(255));

        $this->assertEquals(array(1024 => 1024), int2array(1024));
    }

    /**
     * Test int2array with edge cases
     */
    public function testInt2ArrayEdgeCases()
    {
        // Test consecutive bits
        $this->assertEquals(array(1 => 1, 2 => 2, 4 => 4), int2array(7));
        $this->assertEquals(array(1 => 1, 2 => 2, 4 => 4, 8 => 8, 16 => 16, 32 => 32, 64 => 64), int2array(127));

        // Test non-consecutive bits
        $this->assertEquals(array(1 => 1, 4 => 4, 16 => 16), int2array(21)); // 21 = 1 | 4 | 16
        $this->assertEquals(array(2 => 2, 8 => 8, 32 => 32), int2array(42)); // 42 = 2 | 8 | 32
    }

    /**
     * Test int2array with incorrect data
     */
    public function testInt2ArrayWithIncorrectData()
    {
        // Note: Negative numbers cause infinite loop in int2array due to bitwise operations
        // This is a limitation of the function design

        // Test with null - should be treated as 0
        $this->assertEquals(array(), int2array(null));

        // Test with string that can be converted to int
        $this->assertEquals(array(1 => 1, 2 => 2), int2array("3"));

        // Test with zero
        $this->assertEquals(array(), int2array(0));

        // Test with false - should be treated as 0
        $this->assertEquals(array(), int2array(false));

        // Note: Non-numeric strings will throw TypeError in PHP 8+ due to strict typing
        // in bitwise operations. This is expected behavior.
    }

    /**
     * Test roundtrip conversion (array -> int -> array)
     */
    public function testRoundtripArrayToIntToArray()
    {
        $testCases = array(
            array(1, 2, 4),
            array(1, 8, 32),
            array(2, 4, 16, 128),
            array(1, 2, 4, 8, 16, 32, 64, 128)
        );

        foreach ($testCases as $original) {
            $encoded = array2int($original);
            $decoded = int2array($encoded);

            // Convert both to indexed arrays for comparison
            $originalValues = array_values($original);
            sort($originalValues);

            $decodedValues = array_values($decoded);
            sort($decodedValues);

            $this->assertEquals($originalValues, $decodedValues,
                "Roundtrip failed for array: " . implode(', ', $original));
        }
    }

    /**
     * Test roundtrip conversion (int -> array -> int)
     */
    public function testRoundtripIntToArrayToInt()
    {
        $testCases = array(1, 2, 3, 5, 7, 10, 15, 31, 63, 127, 255, 1023);

        foreach ($testCases as $original) {
            $decoded = int2array($original);
            $encoded = array2int($decoded);

            $this->assertEquals($original, $encoded,
                "Roundtrip failed for int: " . $original);
        }
    }

    /**
     * Test that array keys in int2array result match values
     */
    public function testInt2ArrayKeysMatchValues()
    {
        $result = int2array(15); // 15 = 1 | 2 | 4 | 8

        foreach ($result as $key => $value) {
            $this->assertEquals($key, $value,
                "Key should match value in int2array result");
        }
    }

    /**
     * Test power of two values (common use case for bitfields)
     */
    public function testPowersOfTwo()
    {
        for ($i = 0; $i < 10; $i++) {
            $pow = pow(2, $i);

            // Test array2int
            $this->assertEquals($pow, array2int(array($pow)));

            // Test int2array
            $result = int2array($pow);
            $this->assertCount(1, $result);
            $this->assertArrayHasKey($pow, $result);
            $this->assertEquals($pow, $result[$pow]);
        }
    }
}

?>
