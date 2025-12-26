<?php

use PHPUnit\Framework\TestCase;

/**
 * Enhanced CI tests for EventsTypesMetadata class
 *
 * Tests metadata configuration for events_types table
 *
 * @package tests
 */
class EventsTypesMetadataTest extends TestCase
{
    private $CI;
    private $metadata;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load required libraries
        $this->CI->load->library('EventsTypesMetadata');
        $this->metadata = $this->CI->eventstypesmetadata;
    }

    /**
     * Test that EventsTypesMetadata can be instantiated
     */
    public function testMetadataInstantiation()
    {
        $this->assertNotNull($this->metadata, "EventsTypesMetadata should be instantiated");
        $this->assertInstanceOf('EventsTypesMetadata', $this->metadata);
        $this->assertInstanceOf('GVVMetadata', $this->metadata, "Should extend GVVMetadata");
    }

    /**
     * Test that primary key configuration exists (via reflection)
     * Note: keys and alias_table are protected, but we can verify functionality through field definitions
     */
    public function testMetadataHasKeyConfiguration()
    {
        // We can verify the id field is configured as a key subtype
        $this->assertArrayHasKey('id', $this->metadata->field['events_types'],
            "ID field should be defined");
        $this->assertEquals('key', $this->metadata->field['events_types']['id']['Subtype'],
            "ID should be configured as key type");
    }

    /**
     * Test that id field is configured as key
     */
    public function testIdFieldIsKey()
    {
        $this->assertArrayHasKey('events_types', $this->metadata->field,
            "events_types table should have field definitions");
        $this->assertArrayHasKey('id', $this->metadata->field['events_types'],
            "id field should be defined");
        $this->assertEquals('key', $this->metadata->field['events_types']['id']['Subtype'],
            "id should have 'key' subtype");
    }

    /**
     * Test that activite field is configured as enumerate
     */
    public function testActiviteFieldIsEnumerate()
    {
        $this->assertArrayHasKey('activite', $this->metadata->field['events_types'],
            "activite field should be defined");
        $this->assertEquals('enumerate', $this->metadata->field['events_types']['activite']['Subtype'],
            "activite should have 'enumerate' subtype");
        $this->assertArrayHasKey('Enumerate', $this->metadata->field['events_types']['activite'],
            "activite should have Enumerate values");
    }

    /**
     * Test that boolean fields are configured correctly
     */
    public function testBooleanFieldsAreConfigured()
    {
        $boolean_fields = ['en_vol', 'multiple', 'expirable', 'annual'];

        foreach ($boolean_fields as $field) {
            $this->assertArrayHasKey($field, $this->metadata->field['events_types'],
                "Field '$field' should be defined");
            $this->assertEquals('boolean', $this->metadata->field['events_types'][$field]['Subtype'],
                "Field '$field' should have 'boolean' subtype");
        }
    }

    /**
     * Test that en_vol field is boolean
     */
    public function testEnVolFieldIsBoolean()
    {
        $this->assertEquals('boolean', $this->metadata->field['events_types']['en_vol']['Subtype'],
            "en_vol should be boolean");
    }

    /**
     * Test that multiple field is boolean
     */
    public function testMultipleFieldIsBoolean()
    {
        $this->assertEquals('boolean', $this->metadata->field['events_types']['multiple']['Subtype'],
            "multiple should be boolean");
    }

    /**
     * Test that expirable field is boolean
     */
    public function testExpirableFieldIsBoolean()
    {
        $this->assertEquals('boolean', $this->metadata->field['events_types']['expirable']['Subtype'],
            "expirable should be boolean");
    }

    /**
     * Test that annual field is boolean
     */
    public function testAnnualFieldIsBoolean()
    {
        $this->assertEquals('boolean', $this->metadata->field['events_types']['annual']['Subtype'],
            "annual should be boolean");
    }

    /**
     * Test that metadata has field array
     */
    public function testMetadataHasFieldArray()
    {
        $this->assertIsArray($this->metadata->field, "Metadata should have field array");
        $this->assertNotEmpty($this->metadata->field, "Field array should not be empty");
    }

    /**
     * Test that metadata inherits from parent class correctly
     */
    public function testMetadataInheritance()
    {
        // Verify that the metadata class has the expected structure
        $this->assertObjectHasAttribute('field', $this->metadata, "Metadata should have field property");
        $this->assertIsArray($this->metadata->field, "Field property should be an array");
    }

    /**
     * Test that all expected fields are defined
     */
    public function testAllExpectedFieldsAreDefined()
    {
        $expected_fields = ['id', 'activite', 'en_vol', 'multiple', 'expirable', 'annual'];

        foreach ($expected_fields as $field) {
            $this->assertArrayHasKey($field, $this->metadata->field['events_types'],
                "Field '$field' should be defined in events_types metadata");
        }
    }
}
