<?php

use PHPUnit\Framework\TestCase;

/**
 * Enhanced CI tests for MailMetadata class
 *
 * Tests metadata configuration for mails table
 *
 * @package tests
 */
class MailMetadataTest extends TestCase
{
    private $CI;
    private $metadata;

    protected function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI = &get_instance();

        // Load required libraries
        $this->CI->load->library('MailMetadata');
        $this->metadata = $this->CI->mailmetadata;
    }

    /**
     * Test that MailMetadata can be instantiated
     */
    public function testMetadataInstantiation()
    {
        $this->assertNotNull($this->metadata, "MailMetadata should be instantiated");
        $this->assertInstanceOf('MailMetadata', $this->metadata);
        $this->assertInstanceOf('GVVMetadata', $this->metadata, "Should extend GVVMetadata");
    }

    /**
     * Test that mail table fields are configured
     */
    public function testMailTableFieldsAreDefined()
    {
        $this->assertArrayHasKey('mails', $this->metadata->field,
            "mails table should have field definitions");
    }

    /**
     * Test that id field has custom name
     */
    public function testIdFieldHasCustomName()
    {
        $this->assertArrayHasKey('id', $this->metadata->field['mails'],
            "id field should be defined");
        $this->assertEquals('Numéro', $this->metadata->field['mails']['id']['Name'],
            "id field should have custom name 'Numéro'");
    }

    /**
     * Test that date_envoie field is configured
     */
    public function testDateEnvoieFieldIsConfigured()
    {
        $this->assertArrayHasKey('date_envoie', $this->metadata->field['mails'],
            "date_envoie field should be defined");
        $this->assertEquals('Date', $this->metadata->field['mails']['date_envoie']['Name'],
            "date_envoie should have custom name 'Date'");
    }

    /**
     * Test that titre field is configured
     */
    public function testTitreFieldIsConfigured()
    {
        $this->assertArrayHasKey('titre', $this->metadata->field['mails'],
            "titre field should be defined");
        $this->assertEquals('Sujet', $this->metadata->field['mails']['titre']['Name'],
            "titre should have custom name 'Sujet'");
    }

    /**
     * Test that selection field is configured
     */
    public function testSelectionFieldIsConfigured()
    {
        $this->assertArrayHasKey('selection', $this->metadata->field['mails'],
            "selection field should be defined");
        $this->assertEquals('Selection', $this->metadata->field['mails']['selection']['Name'],
            "selection should have custom name 'Selection'");
    }

    /**
     * Test that individuel field is boolean
     */
    public function testIndividuelFieldIsBoolean()
    {
        $this->assertArrayHasKey('individuel', $this->metadata->field['mails'],
            "individuel field should be defined");
        $this->assertEquals('boolean', $this->metadata->field['mails']['individuel']['Subtype'],
            "individuel should be boolean subtype");
    }

    /**
     * Test that destinataires field has textarea attributes
     */
    public function testDestinatairesFieldHasTextareaAttrs()
    {
        $this->assertArrayHasKey('destinataires', $this->metadata->field['mails'],
            "destinataires field should be defined");
        $this->assertArrayHasKey('Attrs', $this->metadata->field['mails']['destinataires'],
            "destinataires should have Attrs");
        $this->assertEquals(96, $this->metadata->field['mails']['destinataires']['Attrs']['cols'],
            "destinataires should have 96 cols");
        $this->assertEquals(4, $this->metadata->field['mails']['destinataires']['Attrs']['rows'],
            "destinataires should have 4 rows");
    }

    /**
     * Test that texte field has textarea attributes
     */
    public function testTexteFieldHasTextareaAttrs()
    {
        $this->assertArrayHasKey('texte', $this->metadata->field['mails'],
            "texte field should be defined");
        $this->assertArrayHasKey('Attrs', $this->metadata->field['mails']['texte'],
            "texte should have Attrs");
        $this->assertEquals(96, $this->metadata->field['mails']['texte']['Attrs']['cols'],
            "texte should have 96 cols");
        $this->assertEquals(16, $this->metadata->field['mails']['texte']['Attrs']['rows'],
            "texte should have 16 rows");
    }

    /**
     * Test that selection field in mails is enumerate
     */
    public function testMailsSelectionIsEnumerate()
    {
        $this->assertArrayHasKey('selection', $this->metadata->field['mails'],
            "selection field should be defined in mails");
        $this->assertEquals('enumerate', $this->metadata->field['mails']['selection']['Subtype'],
            "selection in mails should be enumerate subtype");
    }

    /**
     * Test that vue_mails selection field is enumerate
     */
    public function testVueMailsSelectionIsEnumerate()
    {
        $this->assertArrayHasKey('vue_mails', $this->metadata->field,
            "vue_mails should be defined");
        $this->assertArrayHasKey('selection', $this->metadata->field['vue_mails'],
            "selection field should be defined in vue_mails");
        $this->assertEquals('enumerate', $this->metadata->field['vue_mails']['selection']['Subtype'],
            "selection in vue_mails should be enumerate subtype");
    }

    /**
     * Test that titre field has title attribute
     */
    public function testTitreFieldHasTitle()
    {
        $this->assertArrayHasKey('Title', $this->metadata->field['mails']['titre'],
            "titre field should have Title attribute");
        $this->assertEquals("Sujet du courriel", $this->metadata->field['mails']['titre']['Title'],
            "titre Title should be 'Sujet du courriel'");
    }

    /**
     * Test that destinataires field has title attribute
     */
    public function testDestinatairesFieldHasTitle()
    {
        $this->assertArrayHasKey('Title', $this->metadata->field['mails']['destinataires'],
            "destinataires field should have Title attribute");
        $this->assertStringContainsString("Destinataires",
            $this->metadata->field['mails']['destinataires']['Title'],
            "destinataires Title should contain 'Destinataires'");
    }

    /**
     * Test that individuel field has title attribute
     */
    public function testIndividuelFieldHasTitle()
    {
        $this->assertArrayHasKey('Title', $this->metadata->field['mails']['individuel'],
            "individuel field should have Title attribute");
        $this->assertStringContainsString("Envois individuels",
            $this->metadata->field['mails']['individuel']['Title'],
            "individuel Title should describe individual sending");
    }

    /**
     * Test that texte field has title with variable substitution info
     */
    public function testTexteFieldHasTitleWithVariables()
    {
        $this->assertArrayHasKey('Title', $this->metadata->field['mails']['texte'],
            "texte field should have Title attribute");
        $this->assertStringContainsString("$",
            $this->metadata->field['mails']['texte']['Title'],
            "texte Title should mention variable substitution");
    }

    /**
     * Test metadata has field array
     */
    public function testMetadataHasFieldArray()
    {
        $this->assertIsArray($this->metadata->field, "Metadata should have field array");
        $this->assertNotEmpty($this->metadata->field, "Field array should not be empty");
    }
}
