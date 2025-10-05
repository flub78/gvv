<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for crypto helper functions
 *
 * This test validates cryptographic helper functions for integer transformation
 * and reversible obfuscation with PHPUnit 8.x using a minimal bootstrap approach.
 */
class CryptoHelperTest extends TestCase
{
    /**
     * Test transformInteger with default keys on various inputs
     */
    public function testTransformIntegerWithDefaultKeys()
    {
        // Test zero
        $transformed = transformInteger(0);
        $this->assertIsInt($transformed);
        $this->assertGreaterThanOrEqual(0, $transformed);

        // Test small positive integers
        $transformed1 = transformInteger(1);
        $transformed10 = transformInteger(10);
        $transformed100 = transformInteger(100);

        $this->assertIsInt($transformed1);
        $this->assertIsInt($transformed10);
        $this->assertIsInt($transformed100);

        // Verify different inputs produce different outputs
        $this->assertNotEquals($transformed1, $transformed10);
        $this->assertNotEquals($transformed10, $transformed100);

        // Test larger integers
        $transformed1000 = transformInteger(1000);
        $transformed10000 = transformInteger(10000);

        $this->assertIsInt($transformed1000);
        $this->assertIsInt($transformed10000);
        $this->assertNotEquals($transformed1000, $transformed10000);
    }

    /**
     * Test transformInteger with custom keys
     */
    public function testTransformIntegerWithCustomKeys()
    {
        // Test with different custom keys
        $input = 42;
        $result1 = transformInteger($input, 11111, 22222);
        $result2 = transformInteger($input, 33333, 44444);
        $result3 = transformInteger($input, 11111, 22222); // Same keys as result1

        // Same input with different keys should produce different outputs
        $this->assertNotEquals($result1, $result2);

        // Same input with same keys should produce same output (deterministic)
        $this->assertEquals($result1, $result3);
    }

    /**
     * Test transformInteger with string input (numeric strings)
     */
    public function testTransformIntegerWithStringInput()
    {
        // Test numeric string conversion
        $resultFromString = transformInteger("123");
        $resultFromInt = transformInteger(123);

        // Should produce same result whether input is string or int
        $this->assertEquals($resultFromInt, $resultFromString);

        // Test with larger numeric string
        $this->assertIsInt(transformInteger("9999"));
    }

    /**
     * Test transformInteger with invalid inputs (should throw exceptions)
     */
    public function testTransformIntegerWithInvalidInputs()
    {
        // Test negative integer
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'entrée doit être un entier positif");
        transformInteger(-1);
    }

    /**
     * Test transformInteger with non-numeric string
     */
    public function testTransformIntegerWithNonNumericString()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'entrée doit être un entier positif");
        transformInteger("abc");
    }

    /**
     * Test transformInteger with float (should throw exception)
     */
    public function testTransformIntegerWithFloat()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'entrée doit être un entier positif");
        transformInteger(3.14);
    }

    /**
     * Test reverseTransform can decode encoded values with default keys
     */
    public function testReverseTransformWithDefaultKeys()
    {
        // Test reversibility with various inputs
        $testValues = [0, 1, 10, 42, 100, 999, 1234, 9999, 12345, 99999];

        foreach ($testValues as $original) {
            $encoded = transformInteger($original);
            $decoded = reverseTransform($encoded);

            $this->assertEquals($original, $decoded,
                "Failed to reverse transform for value: $original");
        }
    }

    /**
     * Test reverseTransform with custom keys
     */
    public function testReverseTransformWithCustomKeys()
    {
        // Test reversibility with custom keys
        $key1 = 54321;
        $key2 = 98765;
        $testValues = [0, 5, 50, 500, 5000, 50000];

        foreach ($testValues as $original) {
            $encoded = transformInteger($original, $key1, $key2);
            $decoded = reverseTransform($encoded, $key1, $key2);

            $this->assertEquals($original, $decoded,
                "Failed to reverse transform for value: $original with custom keys");
        }
    }

    /**
     * Test reverseTransform with wrong keys (should NOT recover original)
     */
    public function testReverseTransformWithWrongKeys()
    {
        $original = 123;
        $encoded = transformInteger($original, 11111, 22222);

        // Try to decode with different keys
        $wrongDecoded = reverseTransform($encoded, 33333, 44444);

        // Should not recover the original value
        $this->assertNotEquals($original, $wrongDecoded,
            "Should not decode correctly with wrong keys");
    }

    /**
     * Test modInverse function with valid inputs
     */
    public function testModInverseWithValidInputs()
    {
        // Test basic modular inverse
        // 3 * modInverse(3, 7) ≡ 1 (mod 7)
        $inv = modInverse(3, 7);
        $this->assertEquals(1, (3 * $inv) % 7);

        // Test another case: 5 * modInverse(5, 11) ≡ 1 (mod 11)
        $inv = modInverse(5, 11);
        $this->assertEquals(1, (5 * $inv) % 11);

        // Test with larger numbers
        $inv = modInverse(17, 43);
        $this->assertEquals(1, (17 * $inv) % 43);
    }

    /**
     * Test modInverse with zero (should throw exception)
     */
    public function testModInverseWithZero()
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'inverse modulaire de 0 n'existe pas");
        modInverse(0, 7);
    }

    /**
     * Test modInverse with non-coprime numbers (should throw exception)
     */
    public function testModInverseWithNonCoprimeNumbers()
    {
        // 6 and 9 are not coprime (GCD = 3), so inverse doesn't exist
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage("L'inverse modulaire n'existe pas");
        modInverse(6, 9);
    }

    /**
     * Test modInverse with negative input
     */
    public function testModInverseWithNegativeInput()
    {
        // modInverse should handle negative inputs by normalizing them
        $inv = modInverse(-3, 7);
        // -3 mod 7 = 4, so this should be the same as modInverse(4, 7)
        $invPositive = modInverse(4, 7);
        $this->assertEquals($invPositive, $inv);
    }

    /**
     * Test complete encode-decode cycle with edge cases
     */
    public function testCompleteEncodeDeCodeCycle()
    {
        // Test boundary values
        $edgeCases = [
            0,      // Minimum value
            1,      // Smallest positive
            255,    // Byte boundary
            256,    // Above byte boundary
            1000,   // Thousand
            10000,  // Ten thousand
            65535,  // 16-bit max
            100000, // Large value
        ];

        foreach ($edgeCases as $value) {
            $encoded = transformInteger($value);
            $decoded = reverseTransform($encoded);

            $this->assertEquals($value, $decoded,
                "Encode-decode cycle failed for edge case: $value");
        }
    }

    /**
     * Test that same input always produces same output (determinism)
     */
    public function testDeterministicBehavior()
    {
        $input = 12345;
        $key1 = 11111;
        $key2 = 22222;

        // Multiple transformations with same parameters should yield same result
        $result1 = transformInteger($input, $key1, $key2);
        $result2 = transformInteger($input, $key1, $key2);
        $result3 = transformInteger($input, $key1, $key2);

        $this->assertEquals($result1, $result2);
        $this->assertEquals($result2, $result3);
    }

    /**
     * Test that output is within expected modulo range
     */
    public function testOutputWithinModuloRange()
    {
        $prime = 1000000007; // The prime used in the implementation
        $testValues = [0, 1, 100, 1000, 10000, 100000];

        foreach ($testValues as $value) {
            $transformed = transformInteger($value);

            $this->assertLessThan($prime, $transformed,
                "Transformed value should be less than prime modulo");
            $this->assertGreaterThanOrEqual(0, $transformed,
                "Transformed value should be non-negative");
        }
    }
}

?>
