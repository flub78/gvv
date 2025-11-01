<?php

use PHPUnit\Framework\TestCase;

/**
 * Test class for email helper functions
 *
 * Tests validation, normalization, deduplication, chunking,
 * export generation, and mailto URL generation.
 *
 * @package tests
 * @see application/helpers/email_helper.php
 */
class EmailHelperTest extends TestCase
{
    // ========================================================================
    // Email Validation Tests
    // ========================================================================

    public function testValidateEmail_ValidEmails_ReturnsTrue()
    {
        $this->assertTrue(validate_email('test@example.com'));
        $this->assertTrue(validate_email('user.name@domain.co.uk'));
        $this->assertTrue(validate_email('firstname.lastname@example.com'));
        $this->assertTrue(validate_email('email@subdomain.example.com'));
        $this->assertTrue(validate_email('1234567890@example.com'));
    }

    public function testValidateEmail_InvalidEmails_ReturnsFalse()
    {
        $this->assertFalse(validate_email('plainaddress'));
        $this->assertFalse(validate_email('@missinglocal.com'));
        $this->assertFalse(validate_email('missing@domain'));
        $this->assertFalse(validate_email('missing.domain@.com'));
        $this->assertFalse(validate_email('two@@example.com'));
    }

    public function testValidateEmail_EmptyString_ReturnsFalse()
    {
        $this->assertFalse(validate_email(''));
        $this->assertFalse(validate_email(NULL));
    }

    // ========================================================================
    // Email Normalization Tests
    // ========================================================================

    public function testNormalizeEmail_LowercasesAndTrims()
    {
        $this->assertEquals('test@example.com', normalize_email('TEST@EXAMPLE.COM'));
        $this->assertEquals('test@example.com', normalize_email('Test@Example.Com'));
        $this->assertEquals('test@example.com', normalize_email('  test@example.com  '));
        $this->assertEquals('test@example.com', normalize_email('  TEST@EXAMPLE.COM  '));
    }

    public function testNormalizeEmail_EmptyString_ReturnsEmpty()
    {
        $this->assertEquals('', normalize_email(''));
        $this->assertEquals('', normalize_email(NULL));
    }

    // ========================================================================
    // Email Deduplication Tests
    // ========================================================================

    public function testDeduplicateEmails_CaseInsensitive()
    {
        $emails = array(
            array('email' => 'test@example.com'),
            array('email' => 'TEST@EXAMPLE.COM'),
            array('email' => 'other@example.com')
        );

        $result = deduplicate_emails($emails);

        $this->assertCount(2, $result);
        $this->assertEquals('test@example.com', $result[0]['email']);
        $this->assertEquals('other@example.com', $result[1]['email']);
    }

    public function testDeduplicateEmails_StringArray()
    {
        $emails = array(
            'test@example.com',
            'TEST@EXAMPLE.COM',
            'other@example.com'
        );

        $result = deduplicate_emails($emails);

        $this->assertCount(2, $result);
        $this->assertEquals('test@example.com', $result[0]);
        $this->assertEquals('other@example.com', $result[1]);
    }

    public function testDeduplicateEmails_EmptyArray_ReturnsEmpty()
    {
        $this->assertCount(0, deduplicate_emails(array()));
    }

    public function testDeduplicateEmails_SkipsEmptyEntries()
    {
        $emails = array(
            array('email' => 'test@example.com'),
            array('email' => ''),
            array('email' => 'other@example.com')
        );

        $result = deduplicate_emails($emails);

        $this->assertCount(2, $result);
    }

    // ========================================================================
    // Email Chunking Tests
    // ========================================================================

    public function testChunkEmails_Standard20PerChunk()
    {
        $emails = range(1, 87);
        $chunks = chunk_emails($emails, 20);

        $this->assertCount(5, $chunks);
        $this->assertCount(20, $chunks[0]);
        $this->assertCount(20, $chunks[1]);
        $this->assertCount(20, $chunks[2]);
        $this->assertCount(20, $chunks[3]);
        $this->assertCount(7, $chunks[4]);
    }

    public function testChunkEmails_CustomSize()
    {
        $emails = range(1, 100);
        $chunks = chunk_emails($emails, 30);

        $this->assertCount(4, $chunks);
        $this->assertCount(30, $chunks[0]);
        $this->assertCount(10, $chunks[3]);
    }

    public function testChunkEmails_EmptyArray_ReturnsEmpty()
    {
        $this->assertCount(0, chunk_emails(array(), 20));
    }

    public function testChunkEmails_InvalidSize_ReturnsEmpty()
    {
        $emails = range(1, 50);
        $this->assertCount(0, chunk_emails($emails, 0));
        $this->assertCount(0, chunk_emails($emails, -1));
    }

    // ========================================================================
    // TXT Export Generation Tests
    // ========================================================================

    public function testGenerateTxtExport_CommaSeparated()
    {
        $emails = array(
            array('email' => 'test1@example.com'),
            array('email' => 'test2@example.com'),
            array('email' => 'test3@example.com')
        );

        $result = generate_txt_export($emails, ',');

        $this->assertEquals('test1@example.com, test2@example.com, test3@example.com', $result);
    }

    public function testGenerateTxtExport_SemicolonSeparated()
    {
        $emails = array(
            array('email' => 'test1@example.com'),
            array('email' => 'test2@example.com')
        );

        $result = generate_txt_export($emails, ';');

        $this->assertEquals('test1@example.com; test2@example.com', $result);
    }

    public function testGenerateTxtExport_StringArray()
    {
        $emails = array('test1@example.com', 'test2@example.com');

        $result = generate_txt_export($emails, ',');

        $this->assertEquals('test1@example.com, test2@example.com', $result);
    }

    public function testGenerateTxtExport_EmptyArray_ReturnsEmpty()
    {
        $this->assertEquals('', generate_txt_export(array(), ','));
    }

    // ========================================================================
    // Mailto URL Generation Tests
    // ========================================================================

    public function testGenerateMailto_ToField()
    {
        $emails = array('test1@example.com', 'test2@example.com');
        $params = array('field' => 'to');

        $result = generate_mailto($emails, $params);

        $this->assertEquals('mailto:test1@example.com, test2@example.com', $result);
    }

    public function testGenerateMailto_CcField()
    {
        $emails = array('test@example.com');
        $params = array('field' => 'cc');

        $result = generate_mailto($emails, $params);

        $this->assertEquals('mailto:?cc=test%40example.com', $result);
    }

    public function testGenerateMailto_BccField()
    {
        $emails = array('test@example.com');
        $params = array('field' => 'bcc');

        $result = generate_mailto($emails, $params);

        $this->assertEquals('mailto:?bcc=test%40example.com', $result);
    }

    public function testGenerateMailto_WithSubject()
    {
        $emails = array('test@example.com');
        $params = array(
            'field' => 'to',
            'subject' => 'Test Subject'
        );

        $result = generate_mailto($emails, $params);

        $this->assertStringContainsString('subject=Test+Subject', $result);
    }

    public function testGenerateMailto_WithReplyTo()
    {
        $emails = array('test@example.com');
        $params = array(
            'field' => 'to',
            'reply_to' => 'reply@example.com'
        );

        $result = generate_mailto($emails, $params);

        $this->assertStringContainsString('reply-to=reply%40example.com', $result);
    }

    public function testGenerateMailto_EmptyEmails_ReturnsBasicMailto()
    {
        $result = generate_mailto(array(), array());
        $this->assertEquals('mailto:', $result);
    }

    public function testGenerateMailto_TooLong_ReturnsFalse()
    {
        // Create a very long email list that exceeds 2000 characters
        $emails = array();
        for ($i = 0; $i < 100; $i++) {
            $emails[] = 'very.long.email.address.number.' . $i . '@example.com';
        }

        $result = generate_mailto($emails, array('field' => 'to'));

        $this->assertFalse($result);
    }

    // ========================================================================
    // Text Email Parsing Tests
    // ========================================================================

    public function testParseTextEmails_ValidEmails()
    {
        $content = "test1@example.com\ntest2@example.com\ntest3@example.com";

        $result = parse_text_emails($content);

        $this->assertCount(3, $result);
        $this->assertEquals('test1@example.com', $result[0]['email']);
        $this->assertTrue($result[0]['valid']);
        $this->assertEquals(1, $result[0]['line']);
    }

    public function testParseTextEmails_MixedValidAndInvalid()
    {
        $content = "test1@example.com\ninvalid-email\ntest2@example.com";

        $result = parse_text_emails($content);

        $this->assertCount(3, $result);
        $this->assertTrue($result[0]['valid']);
        $this->assertFalse($result[1]['valid']);
        $this->assertEquals('Invalid email format', $result[1]['error']);
        $this->assertTrue($result[2]['valid']);
    }

    public function testParseTextEmails_SkipsEmptyLines()
    {
        $content = "test1@example.com\n\n\ntest2@example.com\n";

        $result = parse_text_emails($content);

        $this->assertCount(2, $result);
    }

    public function testParseTextEmails_EmptyContent_ReturnsEmpty()
    {
        $this->assertCount(0, parse_text_emails(''));
    }

    // ========================================================================
    // CSV Email Parsing Tests
    // ========================================================================

    public function testParseCsvEmails_BasicParsing()
    {
        $content = "Name,Email\nJohn Doe,john@example.com\nJane Smith,jane@example.com";
        $config = array(
            'email_col' => 1,
            'has_header' => TRUE,
            'delimiter' => ','
        );

        $result = parse_csv_emails($content, $config);

        $this->assertCount(2, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
        $this->assertTrue($result[0]['valid']);
    }

    public function testParseCsvEmails_WithNameColumns()
    {
        $content = "Firstname,Lastname,Email\nJohn,Doe,john@example.com";
        $config = array(
            'email_col' => 2,
            'firstname_col' => 0,
            'name_col' => 1,
            'has_header' => TRUE,
            'delimiter' => ','
        );

        $result = parse_csv_emails($content, $config);

        $this->assertEquals('John', $result[0]['firstname']);
        $this->assertEquals('Doe', $result[0]['name']);
        $this->assertEquals('John Doe', $result[0]['display_name']);
    }

    public function testParseCsvEmails_SemicolonDelimiter()
    {
        $content = "Name;Email\nJohn;john@example.com";
        $config = array(
            'email_col' => 1,
            'has_header' => TRUE,
            'delimiter' => ';'
        );

        $result = parse_csv_emails($content, $config);

        $this->assertCount(1, $result);
        $this->assertEquals('john@example.com', $result[0]['email']);
    }

    public function testParseCsvEmails_NoHeader()
    {
        $content = "john@example.com\njane@example.com";
        $config = array(
            'email_col' => 0,
            'has_header' => FALSE,
            'delimiter' => ','
        );

        $result = parse_csv_emails($content, $config);

        $this->assertCount(2, $result);
    }

    public function testParseCsvEmails_EmptyContent_ReturnsEmpty()
    {
        $this->assertCount(0, parse_csv_emails('', array()));
    }

    // ========================================================================
    // Duplicate Detection Tests
    // ========================================================================

    public function testDetectDuplicates_FindsDuplicates()
    {
        $new_emails = array(
            'test1@example.com',
            'TEST2@EXAMPLE.COM',
            'new@example.com'
        );

        $existing_emails = array(
            'test1@example.com',
            'test2@example.com',
            'old@example.com'
        );

        $result = detect_duplicates($new_emails, $existing_emails);

        $this->assertCount(2, $result);
        $this->assertEquals('test1@example.com', $result[0]['new_email']);
        $this->assertEquals('TEST2@EXAMPLE.COM', $result[1]['new_email']);
    }

    public function testDetectDuplicates_NoDuplicates_ReturnsEmpty()
    {
        $new_emails = array('new1@example.com', 'new2@example.com');
        $existing_emails = array('old1@example.com', 'old2@example.com');

        $result = detect_duplicates($new_emails, $existing_emails);

        $this->assertCount(0, $result);
    }

    public function testDetectDuplicates_CaseInsensitive()
    {
        $new_emails = array('TEST@EXAMPLE.COM');
        $existing_emails = array('test@example.com');

        $result = detect_duplicates($new_emails, $existing_emails);

        $this->assertCount(1, $result);
        $this->assertEquals('test@example.com', $result[0]['normalized']);
    }

    public function testDetectDuplicates_AssociativeArrays()
    {
        $new_emails = array(
            array('email' => 'test@example.com')
        );

        $existing_emails = array(
            array('email' => 'test@example.com')
        );

        $result = detect_duplicates($new_emails, $existing_emails);

        $this->assertCount(1, $result);
    }
}
