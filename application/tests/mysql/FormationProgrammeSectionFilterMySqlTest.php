<?php
/**
 * MySQL tests for Formation Programme section filtering
 * 
 * Tests that programmes are correctly filtered based on section_id:
 * - Programmes with section_id = NULL are visible to all sections
 * - Programmes with specific section_id are only visible to that section
 * - When "all sections" is selected, all programmes are visible
 *
 * @group mysql
 * @group formation
 */

class FormationProgrammeSectionFilterMySqlTest extends PHPUnit\Framework\TestCase
{
    private $CI;
    private $test_programme_ids = [];
    private $section_ids = [];

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->model('formation_programme_model');
        $this->CI->load->model('sections_model');
        
        // Get existing sections
        $sections = $this->CI->db->query("SELECT id FROM sections WHERE id > 0 LIMIT 2")->result_array();
        $this->assertGreaterThanOrEqual(2, count($sections), "Need at least 2 sections in database");
        
        $this->section_ids = array_column($sections, 'id');
        
        // Create test programmes with different section assignments
        // Programme 1: Global (section_id = NULL)
        $programme1_data = [
            'code' => 'TEST_GLOBAL_' . microtime(true),
            'titre' => 'Test Programme Global',
            'description' => 'Programme visible dans toutes les sections',
            'contenu_markdown' => '# Test Global',
            'section_id' => null,
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ];
        $this->test_programme_ids[] = $this->CI->formation_programme_model->create($programme1_data);
        
        // Programme 2: Section 1
        $programme2_data = [
            'code' => 'TEST_SEC1_' . microtime(true),
            'titre' => 'Test Programme Section 1',
            'description' => 'Programme uniquement pour section 1',
            'contenu_markdown' => '# Test Section 1',
            'section_id' => $this->section_ids[0],
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ];
        $this->test_programme_ids[] = $this->CI->formation_programme_model->create($programme2_data);
        
        // Programme 3: Section 2
        $programme3_data = [
            'code' => 'TEST_SEC2_' . microtime(true),
            'titre' => 'Test Programme Section 2',
            'description' => 'Programme uniquement pour section 2',
            'contenu_markdown' => '# Test Section 2',
            'section_id' => $this->section_ids[1],
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ];
        $this->test_programme_ids[] = $this->CI->formation_programme_model->create($programme3_data);
    }

    protected function tearDown(): void
    {
        // Clean up test programmes (with cascade for all related tables)
        foreach ($this->test_programme_ids as $id) {
            // Delete in order of dependencies
            $this->CI->db->delete('formation_evaluations', ['programme_id' => $id]);
            $this->CI->db->delete('formation_seances', ['programme_id' => $id]);
            $this->CI->db->delete('formation_inscriptions', ['programme_id' => $id]);
            $this->CI->db->delete('formation_sujets', ['programme_id' => $id]);
            $this->CI->db->delete('formation_lecons', ['programme_id' => $id]);
            $this->CI->db->delete('formation_programmes', ['id' => $id]);
        }
    }

    /**
     * Test that global programmes (section_id = NULL) are visible to all sections
     */
    public function test_global_programmes_visible_to_all_sections()
    {
        // Test with section 1
        $programmes = $this->CI->formation_programme_model->get_by_section($this->section_ids[0]);
        $this->assertContainsProgramme($programmes, 'Test Programme Global', 'Global programme should be visible to section 1');
        
        // Test with section 2
        $programmes = $this->CI->formation_programme_model->get_by_section($this->section_ids[1]);
        $this->assertContainsProgramme($programmes, 'Test Programme Global', 'Global programme should be visible to section 2');
    }

    /**
     * Test that section-specific programmes are only visible to their section
     */
    public function test_section_specific_programmes_filtered()
    {
        // From section 1, should see: Global + Section 1 (not Section 2)
        $programmes = $this->CI->formation_programme_model->get_by_section($this->section_ids[0]);
        $this->assertContainsProgramme($programmes, 'Test Programme Global');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 1');
        $this->assertNotContainsProgramme($programmes, 'Test Programme Section 2', 'Section 2 programme should NOT be visible to section 1');
        
        // From section 2, should see: Global + Section 2 (not Section 1)
        $programmes = $this->CI->formation_programme_model->get_by_section($this->section_ids[1]);
        $this->assertContainsProgramme($programmes, 'Test Programme Global');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 2');
        $this->assertNotContainsProgramme($programmes, 'Test Programme Section 1', 'Section 1 programme should NOT be visible to section 2');
    }

    /**
     * Test that when no section is specified (null), all programmes are visible
     */
    public function test_all_programmes_visible_when_section_null()
    {
        $programmes = $this->CI->formation_programme_model->get_by_section(null);
        $this->assertContainsProgramme($programmes, 'Test Programme Global');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 1');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 2');
    }

    /**
     * Test that when section is empty string, all programmes are visible
     */
    public function test_all_programmes_visible_when_section_empty()
    {
        $programmes = $this->CI->formation_programme_model->get_by_section('');
        $this->assertContainsProgramme($programmes, 'Test Programme Global');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 1');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 2');
    }

    /**
     * Test that when section is 'toutes', all programmes are visible
     */
    public function test_all_programmes_visible_when_section_toutes()
    {
        $programmes = $this->CI->formation_programme_model->get_by_section('toutes');
        $this->assertContainsProgramme($programmes, 'Test Programme Global');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 1');
        $this->assertContainsProgramme($programmes, 'Test Programme Section 2');
    }

    /**
     * Helper: Assert that programmes array contains a programme with given title
     */
    private function assertContainsProgramme($programmes, $titre, $message = '')
    {
        $found = false;
        foreach ($programmes as $prog) {
            if ($prog['titre'] === $titre) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, $message ?: "Programme '$titre' should be in the list");
    }

    /**
     * Helper: Assert that programmes array does NOT contain a programme with given title
     */
    private function assertNotContainsProgramme($programmes, $titre, $message = '')
    {
        $found = false;
        foreach ($programmes as $prog) {
            if ($prog['titre'] === $titre) {
                $found = true;
                break;
            }
        }
        $this->assertFalse($found, $message ?: "Programme '$titre' should NOT be in the list");
    }
}
