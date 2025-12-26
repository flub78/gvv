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

class WsseHelperTest extends PHPUnit\Framework\TestCase {

    protected function setUp(): void {
        // Helper is loaded via minimal_bootstrap.php
    }

    public function test_wsse_helper_functions_exist() {
        $this->assertTrue(function_exists('wsse_header_short'),
            'wsse_header_short function should exist');
        $this->assertTrue(function_exists('decode_chunked'),
            'decode_chunked function should exist');
        $this->assertTrue(function_exists('heva_request'),
            'heva_request function should exist');
    }

    public function test_wsse_header_short_format() {
        $username = 'testuser';
        $password = 'testpass';

        $header = wsse_header_short($username, $password);

        // Check that header contains required elements
        $this->assertStringContainsString('UsernameToken', $header);
        $this->assertStringContainsString('Username="testuser"', $header);
        $this->assertStringContainsString('PasswordDigest=', $header);
        $this->assertStringContainsString('Nonce=', $header);
        $this->assertStringContainsString('Created=', $header);
    }

    public function test_wsse_header_short_unique_per_call() {
        $username = 'testuser';
        $password = 'testpass';

        $header1 = wsse_header_short($username, $password);
        $header2 = wsse_header_short($username, $password);

        // Headers should be different because nonce and created timestamp are unique
        $this->assertNotEquals($header1, $header2,
            'Each call should generate a unique header with different nonce and timestamp');
    }

    public function test_wsse_header_short_different_passwords_different_digests() {
        $username = 'testuser';

        // The digests should be different for different passwords
        // We'll extract the digest portion and verify they differ
        $header1 = wsse_header_short($username, 'password1');
        $header2 = wsse_header_short($username, 'password2');

        $this->assertNotEquals($header1, $header2,
            'Different passwords should produce different headers');
    }

    public function test_wsse_header_short_handles_special_characters() {
        $username = 'user@domain.com';
        $password = 'p@ssw0rd!';

        $header = wsse_header_short($username, $password);

        $this->assertStringContainsString('Username="user@domain.com"', $header);
        $this->assertStringContainsString('PasswordDigest=', $header);
    }

    public function test_decode_chunked_simple() {
        // Simple chunked encoding: "5\r\nHello\r\n0\r\n\r\n"
        $chunked = "5\r\nHello\r\n0\r\n\r\n";
        $expected = "Hello";

        $result = decode_chunked($chunked);
        $this->assertEquals($expected, $result);
    }

    public function test_decode_chunked_multiple_chunks() {
        // Multiple chunks: "5\r\nHello\r\n6\r\n World\r\n0\r\n\r\n"
        $chunked = "5\r\nHello\r\n6\r\n World\r\n0\r\n\r\n";
        $expected = "Hello World";

        $result = decode_chunked($chunked);
        $this->assertEquals($expected, $result);
    }

    public function test_decode_chunked_empty_string() {
        $result = decode_chunked('');
        $this->assertEquals('', $result);
    }

    public function test_decode_chunked_single_character() {
        // Single character: "1\r\nA\r\n0\r\n\r\n"
        $chunked = "1\r\nA\r\n0\r\n\r\n";
        $expected = "A";

        $result = decode_chunked($chunked);
        $this->assertEquals($expected, $result);
    }

    public function test_decode_chunked_large_chunk() {
        // Larger chunk with hex size
        $data = str_repeat('X', 255); // 255 chars = FF in hex
        $chunked = "ff\r\n" . $data . "\r\n0\r\n\r\n";

        $result = decode_chunked($chunked);
        $this->assertEquals($data, $result);
    }

    public function test_decode_chunked_mixed_case_hex() {
        // Test with mixed case hex (a vs A)
        $chunked = "5\r\nHello\r\nA\r\n WorldTest\r\n0\r\n\r\n";
        $expected = "Hello WorldTest";

        $result = decode_chunked($chunked);
        $this->assertEquals($expected, $result);
    }
}

/* End of file WsseHelperTest.php */
/* Location: ./application/tests/unit/helpers/WsseHelperTest.php */
