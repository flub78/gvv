<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for extract_email() and normalize_email() helpers.
 * Verifies that "Name <email>" format is correctly parsed at input points.
 */
class EmailExtractTest extends TestCase {

    protected function setUp(): void {
        if (!function_exists('extract_email')) {
            require_once APPPATH . 'helpers/email_helper.php';
        }
    }

    public function testExtractEmailFromNameFormat() {
        $this->assertEquals('louis.adams@orange.fr', extract_email('Louis ADAMS <louis.adams@orange.fr>'));
    }

    public function testExtractEmailFromNameFormatWithSpaces() {
        $this->assertEquals('a@b.com', extract_email('  Jean Dupont  <  a@b.com  >  '));
    }

    public function testExtractEmailPlain() {
        $this->assertEquals('user@example.com', extract_email('user@example.com'));
    }

    public function testExtractEmailTrimOnly() {
        $this->assertEquals('user@example.com', extract_email('  user@example.com  '));
    }

    public function testExtractEmailEmpty() {
        $this->assertEquals('', extract_email(''));
    }

    public function testNormalizeEmailStripsName() {
        $this->assertEquals('louis.adams@orange.fr', normalize_email('Louis ADAMS <louis.adams@orange.fr>'));
    }

    public function testNormalizeEmailLowercases() {
        $this->assertEquals('user@example.com', normalize_email('User@Example.COM'));
    }

    public function testNormalizeEmailStripsNameAndLowercases() {
        $this->assertEquals('user@example.com', normalize_email('John DOE <User@Example.COM>'));
    }

    public function testNormalizeEmailEmpty() {
        $this->assertEquals('', normalize_email(''));
    }

    public function testValidateEmailAcceptsNameFormat() {
        $this->assertTrue(validate_email('Louis ADAMS <louis.adams@orange.fr>'));
    }

    public function testValidateEmailAcceptsPlain() {
        $this->assertTrue(validate_email('user@example.com'));
    }

    public function testValidateEmailRejectsInvalid() {
        $this->assertFalse(validate_email('not-an-email'));
    }

    public function testValidateEmailRejectsEmpty() {
        $this->assertFalse(validate_email(''));
    }
}
