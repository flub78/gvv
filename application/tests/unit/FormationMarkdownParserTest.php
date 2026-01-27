<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * Unit Test for Formation_markdown_parser
 *
 * Tests the Markdown parser for training programs.
 * Uses the real formation_spl.md test data.
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php application/tests/unit/FormationMarkdownParserTest.php
 */
class FormationMarkdownParserTest extends TransactionalTestCase
{
    private $parser;
    private $test_markdown;

    public function setUp(): void
    {
        parent::setUp();

        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load parser library
        $this->CI->load->library('Formation_markdown_parser');
        $this->parser = $this->CI->formation_markdown_parser;

        // Load test data
        $test_file = APPPATH . '../doc/test-data/formation_spl.md';
        if (!file_exists($test_file)) {
            $this->markTestSkipped("Test data file not found: $test_file");
        }
        $this->test_markdown = file_get_contents($test_file);
    }

    /**
     * Test parsing the SPL formation file
     */
    public function testParseSPLFormation()
    {
        $result = $this->parser->parse($this->test_markdown);

        // Check program title
        $this->assertArrayHasKey('titre', $result);
        $this->assertEquals('Formation Initiale Planeur', $result['titre']);

        // Check lecons structure
        $this->assertArrayHasKey('lecons', $result);
        $this->assertIsArray($result['lecons']);
        $this->assertCount(5, $result['lecons'], 'Should have 5 lecons');
    }

    /**
     * Test lesson structure
     */
    public function testLeconStructure()
    {
        $result = $this->parser->parse($this->test_markdown);

        // Check first lecon
        $lecon1 = $result['lecons'][0];
        $this->assertEquals(1, $lecon1['numero']);
        $this->assertEquals('Découverte du planeur', $lecon1['titre']);
        $this->assertArrayHasKey('description', $lecon1);
        $this->assertArrayHasKey('ordre', $lecon1);
        $this->assertArrayHasKey('sujets', $lecon1);
        $this->assertIsArray($lecon1['sujets']);
    }

    /**
     * Test sujet structure
     */
    public function testSujetStructure()
    {
        $result = $this->parser->parse($this->test_markdown);

        // Check first sujet of first lecon
        $sujet = $result['lecons'][0]['sujets'][0];
        $this->assertEquals('1.1', $sujet['numero']);
        $this->assertEquals('Présentation de l\'aéronef', $sujet['titre']);
        $this->assertNotEmpty($sujet['description']);
        $this->assertNotEmpty($sujet['objectifs']);
        $this->assertEquals(1, $sujet['ordre']);
    }

    /**
     * Test that all 5 lessons are parsed correctly
     */
    public function testAllLessonsAreParsed()
    {
        $result = $this->parser->parse($this->test_markdown);

        $expected_lessons = [
            1 => 'Découverte du planeur',
            2 => 'Le vol rectiligne',
            3 => 'Les virages',
            4 => 'Le décollage',
            5 => 'L\'atterrissage'
        ];

        foreach ($expected_lessons as $numero => $titre) {
            $lecon = $result['lecons'][$numero - 1];
            $this->assertEquals($numero, $lecon['numero'], "Lecon $numero should have correct numero");
            $this->assertEquals($titre, $lecon['titre'], "Lecon $numero should have correct title");
            $this->assertNotEmpty($lecon['sujets'], "Lecon $numero should have sujets");
        }
    }

    /**
     * Test lesson 1 sujets count
     */
    public function testLecon1Sujets()
    {
        $result = $this->parser->parse($this->test_markdown);
        $lecon1 = $result['lecons'][0];

        $this->assertCount(2, $lecon1['sujets'], 'Leçon 1 should have 2 sujets');
        $this->assertEquals('1.1', $lecon1['sujets'][0]['numero']);
        $this->assertEquals('1.2', $lecon1['sujets'][1]['numero']);
    }

    /**
     * Test lesson 5 (atterrissage) has 3 sujets
     */
    public function testLecon5Sujets()
    {
        $result = $this->parser->parse($this->test_markdown);
        $lecon5 = $result['lecons'][4]; // Index 4 = Leçon 5

        $this->assertCount(3, $lecon5['sujets'], 'Leçon 5 should have 3 sujets');
        $this->assertEquals('5.1', $lecon5['sujets'][0]['numero']);
        $this->assertEquals('Circuit et intégration', $lecon5['sujets'][0]['titre']);
        $this->assertEquals('5.2', $lecon5['sujets'][1]['numero']);
        $this->assertEquals('5.3', $lecon5['sujets'][2]['numero']);
    }

    /**
     * Test parsing empty content throws exception
     */
    public function testParseEmptyContentThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Contenu Markdown vide');
        $this->parser->parse('');
    }

    /**
     * Test parsing content without title throws exception
     */
    public function testParseWithoutTitleThrowsException()
    {
        $markdown = "## Leçon 1 : Test\n### Sujet 1.1 : Test";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Titre du programme manquant');
        $this->parser->parse($markdown);
    }

    /**
     * Test parsing content without lessons throws exception
     */
    public function testParseWithoutLessonsThrowsException()
    {
        $markdown = "# Test Programme\n\nSome content";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Aucune leçon trouvée');
        $this->parser->parse($markdown);
    }

    /**
     * Test sujet without lecon throws exception
     */
    public function testSujetBeforeLeconThrowsException()
    {
        $markdown = "# Test Programme\n### Sujet 1.1 : Test";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Sujet trouvé avant toute leçon');
        $this->parser->parse($markdown);
    }

    /**
     * Test validation of valid structure
     */
    public function testValidateValidStructure()
    {
        $result = $this->parser->parse($this->test_markdown);
        $validation = $this->parser->validate($result);

        $this->assertTrue($validation, 'Valid structure should return TRUE');
    }

    /**
     * Test validation detects missing title
     */
    public function testValidateDetectsMissingTitle()
    {
        $data = ['titre' => '', 'lecons' => []];
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Titre du programme manquant', $validation);
    }

    /**
     * Test validation detects missing lessons
     */
    public function testValidateDetectsMissingLessons()
    {
        $data = ['titre' => 'Test', 'lecons' => []];
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Aucune leçon trouvée', $validation);
    }

    /**
     * Test validation detects lesson without sujets
     */
    public function testValidateDetectsLeconWithoutSujets()
    {
        $data = [
            'titre' => 'Test',
            'lecons' => [
                ['numero' => 1, 'titre' => 'Lecon 1', 'sujets' => []]
            ]
        ];
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Aucun sujet trouvé', $validation);
    }

    /**
     * Test export to Markdown
     */
    public function testExportToMarkdown()
    {
        // Parse original
        $result = $this->parser->parse($this->test_markdown);

        // Export back to Markdown
        $exported = $this->parser->export($result['titre'], $result['lecons']);

        // Should contain key elements
        $this->assertStringContainsString('# Formation Initiale Planeur', $exported);
        $this->assertStringContainsString('## Leçon 1 : Découverte du planeur', $exported);
        $this->assertStringContainsString('### Sujet 1.1 : Présentation de l\'aéronef', $exported);
        $this->assertStringContainsString('## Leçon 5 : L\'atterrissage', $exported);
    }

    /**
     * Test round-trip: parse -> export -> parse should yield same structure
     */
    public function testRoundTripParsing()
    {
        // Parse original
        $result1 = $this->parser->parse($this->test_markdown);

        // Export to markdown
        $exported = $this->parser->export($result1['titre'], $result1['lecons']);

        // Parse again
        $result2 = $this->parser->parse($exported);

        // Compare key elements
        $this->assertEquals($result1['titre'], $result2['titre']);
        $this->assertCount(count($result1['lecons']), $result2['lecons']);

        // Compare first lecon
        $this->assertEquals($result1['lecons'][0]['numero'], $result2['lecons'][0]['numero']);
        $this->assertEquals($result1['lecons'][0]['titre'], $result2['lecons'][0]['titre']);
        $this->assertCount(count($result1['lecons'][0]['sujets']), $result2['lecons'][0]['sujets']);
    }

    /**
     * Test parsing with description between lecon and sujets
     */
    public function testLeconDescription()
    {
        $markdown = <<<MD
# Test Programme

## Leçon 1 : Test Leçon

This is the lesson description.
It can span multiple lines.

### Sujet 1.1 : Test Sujet
Sujet description here.
MD;

        $result = $this->parser->parse($markdown);
        $lecon = $result['lecons'][0];

        $this->assertNotEmpty($lecon['description']);
        $this->assertStringContainsString('lesson description', $lecon['description']);
    }

    /**
     * Test renumbering: lessons and subjects without numbers are auto-numbered
     */
    public function testRenumberUnnumberedContent()
    {
        $markdown = <<<MD
# Programme concaténé

## Découverte du planeur

### Présentation de l'aéronef
Description du sujet.

### Effets primaires des commandes
Description du sujet.

## Le vol rectiligne

### Assiette et trajectoire
Description du sujet.
MD;

        $result = $this->parser->parse($markdown);

        // Lessons renumbered sequentially
        $this->assertCount(2, $result['lecons']);
        $this->assertEquals(1, $result['lecons'][0]['numero']);
        $this->assertEquals('Découverte du planeur', $result['lecons'][0]['titre']);
        $this->assertEquals(2, $result['lecons'][1]['numero']);
        $this->assertEquals('Le vol rectiligne', $result['lecons'][1]['titre']);

        // Subjects renumbered sequentially within each lesson
        $this->assertCount(2, $result['lecons'][0]['sujets']);
        $this->assertEquals('1.1', $result['lecons'][0]['sujets'][0]['numero']);
        $this->assertEquals('Présentation de l\'aéronef', $result['lecons'][0]['sujets'][0]['titre']);
        $this->assertEquals('1.2', $result['lecons'][0]['sujets'][1]['numero']);

        $this->assertCount(1, $result['lecons'][1]['sujets']);
        $this->assertEquals('2.1', $result['lecons'][1]['sujets'][0]['numero']);
    }

    /**
     * Test renumbering: concatenated programs with conflicting numbers are renumbered
     */
    public function testRenumberConcatenatedPrograms()
    {
        $markdown = <<<MD
# Programme fusionné

## Leçon 1 : Première partie

### Sujet 1.1 : Sujet A
Contenu A.

### Sujet 1.2 : Sujet B
Contenu B.

## Leçon 1 : Deuxième partie

### Sujet 1.1 : Sujet C
Contenu C.

## Leçon 3 : Troisième partie

### Sujet 3.1 : Sujet D
Contenu D.
MD;

        $result = $this->parser->parse($markdown);

        // All lessons renumbered sequentially regardless of original numbers
        $this->assertCount(3, $result['lecons']);
        $this->assertEquals(1, $result['lecons'][0]['numero']);
        $this->assertEquals('Première partie', $result['lecons'][0]['titre']);
        $this->assertEquals(2, $result['lecons'][1]['numero']);
        $this->assertEquals('Deuxième partie', $result['lecons'][1]['titre']);
        $this->assertEquals(3, $result['lecons'][2]['numero']);
        $this->assertEquals('Troisième partie', $result['lecons'][2]['titre']);

        // Subjects renumbered within their new lesson numbers
        $this->assertEquals('1.1', $result['lecons'][0]['sujets'][0]['numero']);
        $this->assertEquals('1.2', $result['lecons'][0]['sujets'][1]['numero']);
        $this->assertEquals('2.1', $result['lecons'][1]['sujets'][0]['numero']);
        $this->assertEquals('3.1', $result['lecons'][2]['sujets'][0]['numero']);
    }

    /**
     * Test renumbering: mixed numbered and unnumbered content
     */
    public function testRenumberMixedContent()
    {
        $markdown = <<<MD
# Programme mixte

## Leçon 5 : Avec numéro

### Sujet 5.1 : Sujet numéroté
Contenu.

## Sans numéro

### Aussi sans numéro
Contenu.
MD;

        $result = $this->parser->parse($markdown);

        // Both renumbered sequentially
        $this->assertEquals(1, $result['lecons'][0]['numero']);
        $this->assertEquals('Avec numéro', $result['lecons'][0]['titre']);
        $this->assertEquals(2, $result['lecons'][1]['numero']);
        $this->assertEquals('Sans numéro', $result['lecons'][1]['titre']);

        $this->assertEquals('1.1', $result['lecons'][0]['sujets'][0]['numero']);
        $this->assertEquals('2.1', $result['lecons'][1]['sujets'][0]['numero']);
    }
}

/* End of file FormationMarkdownParserTest.php */
/* Location: ./application/tests/unit/FormationMarkdownParserTest.php */
