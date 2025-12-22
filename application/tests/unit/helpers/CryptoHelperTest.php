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

    /**
     * Test is_encrypted_backup with encrypted filenames
     */
    public function testIsEncryptedBackupWithEncryptedFilenames()
    {
        // Test various encrypted backup extensions
        $this->assertTrue(is_encrypted_backup('backup.enc.zip'));
        $this->assertTrue(is_encrypted_backup('backup.enc.tar.gz'));
        $this->assertTrue(is_encrypted_backup('backup.enc.tgz'));
        $this->assertTrue(is_encrypted_backup('backup.enc.gz'));

        // Test case insensitivity
        $this->assertTrue(is_encrypted_backup('backup.ENC.ZIP'));
        $this->assertTrue(is_encrypted_backup('backup.Enc.Tar.Gz'));

        // Test with path
        $this->assertTrue(is_encrypted_backup('/path/to/backup.enc.zip'));
    }

    /**
     * Test is_encrypted_backup with non-encrypted filenames
     */
    public function testIsEncryptedBackupWithNonEncryptedFilenames()
    {
        // Test regular archive files
        $this->assertFalse(is_encrypted_backup('backup.zip'));
        $this->assertFalse(is_encrypted_backup('backup.tar.gz'));
        $this->assertFalse(is_encrypted_backup('backup.tgz'));
        $this->assertFalse(is_encrypted_backup('backup.gz'));

        // Test files with .enc in the middle but wrong pattern
        $this->assertFalse(is_encrypted_backup('backup.enc.txt'));
        $this->assertFalse(is_encrypted_backup('backup.enc'));

        // Test regular files
        $this->assertFalse(is_encrypted_backup('file.txt'));
        $this->assertFalse(is_encrypted_backup('document.pdf'));
    }

    /**
     * Test get_decrypted_filename with various encrypted filenames
     */
    public function testGetDecryptedFilename()
    {
        // Test basic cases
        $this->assertEquals('backup.zip', get_decrypted_filename('backup.enc.zip'));
        $this->assertEquals('backup.tar.gz', get_decrypted_filename('backup.enc.tar.gz'));
        $this->assertEquals('backup.tgz', get_decrypted_filename('backup.enc.tgz'));
        $this->assertEquals('backup.gz', get_decrypted_filename('backup.enc.gz'));

        // Test case insensitivity
        $this->assertEquals('backup.ZIP', get_decrypted_filename('backup.enc.ZIP'));
        $this->assertEquals('backup.Tar.Gz', get_decrypted_filename('backup.enc.Tar.Gz'));

        // Test with path
        $this->assertEquals('/path/to/backup.zip',
            get_decrypted_filename('/path/to/backup.enc.zip'));

        // Test with complex filenames
        $this->assertEquals('my-backup-2024-01-15.zip',
            get_decrypted_filename('my-backup-2024-01-15.enc.zip'));
    }

    /**
     * Test get_decrypted_filename with non-encrypted filenames (should not change)
     */
    public function testGetDecryptedFilenameWithNonEncryptedFiles()
    {
        // Files that don't match the pattern should remain unchanged
        $this->assertEquals('backup.zip', get_decrypted_filename('backup.zip'));
        $this->assertEquals('file.txt', get_decrypted_filename('file.txt'));
        $this->assertEquals('backup.enc.txt', get_decrypted_filename('backup.enc.txt'));
    }

    /**
     * Test get_encrypted_filename with various filenames
     */
    public function testGetEncryptedFilename()
    {
        // Test basic cases
        $this->assertEquals('backup.enc.zip', get_encrypted_filename('backup.zip'));
        $this->assertEquals('backup.enc.tar.gz', get_encrypted_filename('backup.tar.gz'));
        $this->assertEquals('backup.enc.tgz', get_encrypted_filename('backup.tgz'));
        $this->assertEquals('backup.enc.gz', get_encrypted_filename('backup.gz'));

        // Test case insensitivity
        $this->assertEquals('backup.enc.ZIP', get_encrypted_filename('backup.ZIP'));
        $this->assertEquals('backup.enc.Tar.Gz', get_encrypted_filename('backup.Tar.Gz'));

        // Test with path
        $this->assertEquals('/path/to/backup.enc.zip',
            get_encrypted_filename('/path/to/backup.zip'));

        // Test with complex filenames
        $this->assertEquals('my-backup-2024-01-15.enc.zip',
            get_encrypted_filename('my-backup-2024-01-15.zip'));
    }

    /**
     * Test get_encrypted_filename with non-archive files (should not change)
     */
    public function testGetEncryptedFilenameWithNonArchiveFiles()
    {
        // Files that don't match archive patterns should remain unchanged
        $this->assertEquals('file.txt', get_encrypted_filename('file.txt'));
        $this->assertEquals('document.pdf', get_encrypted_filename('document.pdf'));
    }

    /**
     * Test encrypt_file with valid input
     */
    public function testEncryptFileWithValidInput()
    {
        // Create a temporary file to encrypt
        $tempFile = tempnam(sys_get_temp_dir(), 'test_encrypt_');
        $testContent = 'This is a test file for encryption';
        file_put_contents($tempFile, $testContent);

        $encryptedFile = $tempFile . '.enc';
        $passphrase = 'test_passphrase_123';

        try {
            // Test encryption
            $result = encrypt_file($tempFile, $encryptedFile, $passphrase);

            $this->assertTrue($result, 'Encryption should succeed');
            $this->assertFileExists($encryptedFile, 'Encrypted file should exist');

            // Verify the encrypted content is different from original
            $encryptedContent = file_get_contents($encryptedFile);
            $this->assertNotEquals($testContent, $encryptedContent,
                'Encrypted content should be different from original');

        } finally {
            // Cleanup
            if (file_exists($tempFile)) unlink($tempFile);
            if (file_exists($encryptedFile)) unlink($encryptedFile);
        }
    }

    /**
     * Test encrypt_file with default output filename
     */
    public function testEncryptFileWithDefaultOutputFilename()
    {
        // Create a temporary file
        $tempFile = tempnam(sys_get_temp_dir(), 'test_default_');
        file_put_contents($tempFile, 'Test content');

        $expectedEncryptedFile = $tempFile . '.enc';
        $passphrase = 'test_pass';

        try {
            // Test encryption with null output file (should default to input + .enc)
            $result = encrypt_file($tempFile, null, $passphrase);

            $this->assertTrue($result);
            $this->assertFileExists($expectedEncryptedFile);

        } finally {
            if (file_exists($tempFile)) unlink($tempFile);
            if (file_exists($expectedEncryptedFile)) unlink($expectedEncryptedFile);
        }
    }

    /**
     * Test encrypt_file with non-existent input file
     */
    public function testEncryptFileWithNonExistentFile()
    {
        $nonExistentFile = '/tmp/nonexistent_file_' . uniqid() . '.txt';
        $outputFile = '/tmp/output.enc';
        $passphrase = 'test_pass';

        $result = encrypt_file($nonExistentFile, $outputFile, $passphrase);

        $this->assertFalse($result, 'Should return false for non-existent input file');
    }

    /**
     * Test decrypt_file with valid encrypted file
     */
    public function testDecryptFileWithValidInput()
    {
        // Create and encrypt a file first
        $originalFile = tempnam(sys_get_temp_dir(), 'test_original_');
        $testContent = 'Secret content to be encrypted and decrypted';
        file_put_contents($originalFile, $testContent);

        $encryptedFile = $originalFile . '.enc';
        $decryptedFile = $originalFile . '.dec';
        $passphrase = 'secure_passphrase_456';

        try {
            // First encrypt
            $encryptResult = encrypt_file($originalFile, $encryptedFile, $passphrase);
            $this->assertTrue($encryptResult, 'Encryption should succeed');

            // Then decrypt
            $decryptResult = decrypt_file($encryptedFile, $decryptedFile, $passphrase);
            $this->assertTrue($decryptResult, 'Decryption should succeed');

            // Verify decrypted content matches original
            $this->assertFileExists($decryptedFile);
            $decryptedContent = file_get_contents($decryptedFile);
            $this->assertEquals($testContent, $decryptedContent,
                'Decrypted content should match original');

        } finally {
            // Cleanup
            if (file_exists($originalFile)) unlink($originalFile);
            if (file_exists($encryptedFile)) unlink($encryptedFile);
            if (file_exists($decryptedFile)) unlink($decryptedFile);
        }
    }

    /**
     * Test decrypt_file with wrong passphrase
     */
    public function testDecryptFileWithWrongPassphrase()
    {
        // Create and encrypt a file
        $originalFile = tempnam(sys_get_temp_dir(), 'test_wrong_pass_');
        file_put_contents($originalFile, 'Content');

        $encryptedFile = $originalFile . '.enc';
        $decryptedFile = $originalFile . '.dec';
        $correctPass = 'correct_password';
        $wrongPass = 'wrong_password';

        try {
            // Encrypt with correct password
            encrypt_file($originalFile, $encryptedFile, $correctPass);

            // Try to decrypt with wrong password
            $result = decrypt_file($encryptedFile, $decryptedFile, $wrongPass);

            // Should fail with wrong password
            $this->assertFalse($result, 'Decryption should fail with wrong password');

        } finally {
            if (file_exists($originalFile)) unlink($originalFile);
            if (file_exists($encryptedFile)) unlink($encryptedFile);
            if (file_exists($decryptedFile)) unlink($decryptedFile);
        }
    }

    /**
     * Test decrypt_file with non-existent input file
     */
    public function testDecryptFileWithNonExistentFile()
    {
        $nonExistentFile = '/tmp/nonexistent_encrypted_' . uniqid() . '.enc';
        $outputFile = '/tmp/decrypted.txt';
        $passphrase = 'test_pass';

        $result = decrypt_file($nonExistentFile, $outputFile, $passphrase);

        $this->assertFalse($result, 'Should return false for non-existent input file');
    }

    /**
     * Test encrypt/decrypt round-trip with different file sizes
     */
    public function testEncryptDecryptRoundTripWithDifferentSizes()
    {
        $passphrase = 'test_round_trip_pass';

        // Test with different content sizes
        $testCases = [
            'Small' => 'Small content',
            'Medium' => str_repeat('Medium size content. ', 100),
            'Large' => str_repeat('Large content block. ', 1000),
            'Binary' => random_bytes(1024), // Binary data
        ];

        foreach ($testCases as $label => $content) {
            $originalFile = tempnam(sys_get_temp_dir(), "test_${label}_");
            $encryptedFile = $originalFile . '.enc';
            $decryptedFile = $originalFile . '.dec';

            try {
                file_put_contents($originalFile, $content);

                // Encrypt and decrypt
                $this->assertTrue(encrypt_file($originalFile, $encryptedFile, $passphrase),
                    "Encryption should succeed for $label");
                $this->assertTrue(decrypt_file($encryptedFile, $decryptedFile, $passphrase),
                    "Decryption should succeed for $label");

                // Verify content
                $this->assertEquals($content, file_get_contents($decryptedFile),
                    "Content should match after round-trip for $label");

            } finally {
                if (file_exists($originalFile)) unlink($originalFile);
                if (file_exists($encryptedFile)) unlink($encryptedFile);
                if (file_exists($decryptedFile)) unlink($decryptedFile);
            }
        }
    }
}

?>
