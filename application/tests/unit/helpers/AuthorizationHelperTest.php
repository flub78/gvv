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

class AuthorizationHelperTest extends PHPUnit\Framework\TestCase {

    private $CI;

    protected function setUp(): void {
        $this->CI = &get_instance();
        // Helper is loaded via minimal_bootstrap.php
    }

    public function test_authorization_helper_functions_exist() {
        $this->assertTrue(function_exists('logged_username'),
            'logged_username function should exist');
    }

    public function test_logged_username_returns_username() {
        // Set a known username
        $this->CI->dx_auth->set_username('john_doe');

        $username = logged_username();

        $this->assertEquals('john_doe', $username,
            'logged_username should return the username from dx_auth');
    }

    public function test_logged_username_returns_different_usernames() {
        // Test with first username
        $this->CI->dx_auth->set_username('alice');
        $username1 = logged_username();
        $this->assertEquals('alice', $username1);

        // Test with second username
        $this->CI->dx_auth->set_username('bob');
        $username2 = logged_username();
        $this->assertEquals('bob', $username2);

        // Verify they're different
        $this->assertNotEquals($username1, $username2,
            'Different usernames should be returned');
    }

    public function test_logged_username_returns_empty_string() {
        // Test with empty username
        $this->CI->dx_auth->set_username('');

        $username = logged_username();

        $this->assertEquals('', $username,
            'logged_username should handle empty username');
    }

    public function test_logged_username_uses_dx_auth() {
        // Verify the function uses the dx_auth component
        $this->assertNotNull($this->CI->dx_auth,
            'CI instance should have dx_auth');

        $username = logged_username();

        $this->assertIsString($username,
            'logged_username should return a string');
    }

    public function test_logged_username_with_special_characters() {
        // Test with username containing special characters
        $special_username = 'user@example.com';
        $this->CI->dx_auth->set_username($special_username);

        $username = logged_username();

        $this->assertEquals($special_username, $username,
            'logged_username should handle usernames with special characters');
    }

    public function test_logged_username_with_unicode() {
        // Test with unicode characters
        $unicode_username = 'Müller';
        $this->CI->dx_auth->set_username($unicode_username);

        $username = logged_username();

        $this->assertEquals($unicode_username, $username,
            'logged_username should handle unicode usernames');
    }

    public function test_logged_username_with_spaces() {
        // Test with username containing spaces
        $spaced_username = 'John Doe';
        $this->CI->dx_auth->set_username($spaced_username);

        $username = logged_username();

        $this->assertEquals($spaced_username, $username,
            'logged_username should handle usernames with spaces');
    }
}

/* End of file AuthorizationHelperTest.php */
/* Location: ./application/tests/unit/helpers/AuthorizationHelperTest.php */
