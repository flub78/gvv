<?php
/**
 * Tests unitaires — Maintenance_markdown_parser
 *
 * Utilise le fichier de test doc/test-data/maintenance_visite_100h.md.
 * Pas d'acces base de donnees necessaire (parsing pur), a l'image de
 * Formation_markdown_parser mais simplifie : pas de champ `numero` a
 * reconnaitre (ni sur les sections, ni sur les taches), pas de split
 * description/objectifs (une seule colonne `description`).
 *
 * @see doc/plans/maintenance_aeronefs_plan.md (Etape 4.1, 9.1)
 */

use PHPUnit\Framework\TestCase;

class MaintenanceMarkdownParserTest extends TestCase
{
    private $parser;
    private $test_markdown;

    protected function setUp(): void
    {
        parent::setUp();

        $CI = &get_instance();
        $CI->load->library('Maintenance_markdown_parser');
        $this->parser = $CI->maintenance_markdown_parser;

        $test_file = APPPATH . '../doc/test-data/maintenance_visite_100h.md';
        if (!file_exists($test_file)) {
            $this->markTestSkipped("Fichier de test introuvable : $test_file");
        }
        $this->test_markdown = file_get_contents($test_file);
    }

    public function testParseVisite100h()
    {
        $result = $this->parser->parse($this->test_markdown);

        $this->assertArrayHasKey('titre', $result);
        $this->assertSame('Visite 100 heures cellule', $result['titre']);

        $this->assertArrayHasKey('sections', $result);
        $this->assertCount(3, $result['sections']);
    }

    public function testSectionStructure()
    {
        $result = $this->parser->parse($this->test_markdown);

        $section1 = $result['sections'][0];
        $this->assertSame(1, $section1['ordre']);
        $this->assertSame('Moteur', $section1['titre']);
        $this->assertArrayHasKey('taches', $section1);
        $this->assertIsArray($section1['taches']);
        $this->assertCount(3, $section1['taches']);
    }

    public function testTacheStructure()
    {
        $result = $this->parser->parse($this->test_markdown);

        $tache = $result['sections'][0]['taches'][0];
        $this->assertSame('Vidange moteur', $tache['titre']);
        $this->assertSame(1, $tache['ordre']);
        $this->assertNotEmpty($tache['description']);
        $this->assertStringContainsString('huile moteur', $tache['description']);
    }

    public function testAllSectionsAreParsedInOrder()
    {
        $result = $this->parser->parse($this->test_markdown);

        $expected = array(
            1 => array('titre' => 'Moteur', 'nb_taches' => 3),
            2 => array('titre' => 'Cellule', 'nb_taches' => 2),
            3 => array('titre' => 'Equipements de securite', 'nb_taches' => 2),
        );

        foreach ($expected as $ordre => $attendu) {
            $section = $result['sections'][$ordre - 1];
            $this->assertSame($ordre, $section['ordre']);
            $this->assertSame($attendu['titre'], $section['titre']);
            $this->assertCount($attendu['nb_taches'], $section['taches']);
        }
    }

    public function testTachesOrdreIsSequentialWithinSection()
    {
        $result = $this->parser->parse($this->test_markdown);
        $taches = $result['sections'][0]['taches'];

        $this->assertSame(1, $taches[0]['ordre']);
        $this->assertSame(2, $taches[1]['ordre']);
        $this->assertSame(3, $taches[2]['ordre']);
    }

    public function testParseEmptyContentThrowsException()
    {
        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Contenu Markdown vide');
        $this->parser->parse('');
    }

    public function testParseWithoutTitleThrowsException()
    {
        $markdown = "## Section\n### Tache";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Titre du programme manquant');
        $this->parser->parse($markdown);
    }

    public function testParseWithoutSectionsThrowsException()
    {
        $markdown = "# Programme test\n\nDu texte";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Aucune section trouvée');
        $this->parser->parse($markdown);
    }

    public function testTacheBeforeSectionThrowsException()
    {
        $markdown = "# Programme test\n### Tache orpheline";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage('Tâche trouvée avant toute section');
        $this->parser->parse($markdown);
    }

    public function testSectionWithoutTacheThrowsException()
    {
        $markdown = "# Programme test\n\n## Section vide\n\n## Section suivante\n### Une tache\nContenu.";

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("ne contient aucune tâche");
        $this->parser->parse($markdown);
    }

    public function testContentBeforeFirstTacheIsSilentlyIgnored()
    {
        // maintenance_programme_sections n'a pas de colonne description : le texte
        // place directement sous un H2, avant la premiere H3, est ignore (comme
        // Formation_markdown_parser ignore le texte place avant la premiere leçon).
        $markdown = "# Programme test\n\n## Section\nCe texte n'a nulle part ou aller.\n\n### Tache\nDescription de la tache.";

        $result = $this->parser->parse($markdown);

        $this->assertCount(1, $result['sections']);
        $this->assertCount(1, $result['sections'][0]['taches']);
        $this->assertSame('Description de la tache.', $result['sections'][0]['taches'][0]['description']);
    }

    public function testValidateValidStructure()
    {
        $result = $this->parser->parse($this->test_markdown);
        $validation = $this->parser->validate($result);

        $this->assertTrue($validation, 'Une structure valide doit retourner TRUE');
    }

    public function testValidateDetectsMissingTitle()
    {
        $data = array('titre' => '', 'sections' => array());
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Titre du programme manquant', $validation);
    }

    public function testValidateDetectsMissingSections()
    {
        $data = array('titre' => 'Test', 'sections' => array());
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Aucune section trouvée', $validation);
    }

    public function testValidateDetectsSectionWithoutTaches()
    {
        $data = array(
            'titre' => 'Test',
            'sections' => array(
                array('ordre' => 1, 'titre' => 'Section 1', 'taches' => array()),
            ),
        );
        $validation = $this->parser->validate($data);

        $this->assertIsString($validation);
        $this->assertStringContainsString('Aucune tâche trouvée', $validation);
    }
}

/* End of file MaintenanceMarkdownParserTest.php */
/* Location: ./application/tests/unit/libraries/MaintenanceMarkdownParserTest.php */
