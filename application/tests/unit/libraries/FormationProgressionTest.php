<?php
/**
 * Tests for Formation_progression library
 * 
 * Tests the calculation engine that computes progression statistics
 * from evaluations recorded during training sessions.
 */

use PHPUnit\Framework\TestCase;

class FormationProgressionTest extends TestCase
{
    private $CI;
    private $formation_progression;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Load necessary models and libraries
        $this->CI->load->model('formation_inscription_model');
        $this->CI->load->model('formation_seance_model');
        $this->CI->load->model('formation_evaluation_model');
        $this->CI->load->model('formation_programme_model');
        $this->CI->load->model('membres_model');  // Use membres_model instead of pilote_model
        $this->CI->load->library('Formation_progression');
        
        $this->formation_progression = $this->CI->formation_progression;
    }
    
    /**
     * Test calculer() returns expected data structure
     * SKIP: Requires real database - move to integration tests
     */
    public function testCalculerReturnStructure()
    {
        $this->markTestSkipped('Requires real database - should be in integration tests');
    }
    
    /**
     * Test percentage calculation
     * SKIP: Requires real database - move to integration tests
     */
    public function testPourcentageAcquisCalculation()
    {
        $this->markTestSkipped('Requires real database - should be in integration tests');
    }
    
    /**
     * Test get_progress_bar_class() returns correct colors
     */
    public function testProgressBarClass()
    {
        // Test thresholds
        $this->assertEquals('bg-danger', $this->formation_progression->get_progress_bar_class(0));
        $this->assertEquals('bg-danger', $this->formation_progression->get_progress_bar_class(24));
        $this->assertEquals('bg-warning', $this->formation_progression->get_progress_bar_class(25));
        $this->assertEquals('bg-warning', $this->formation_progression->get_progress_bar_class(49));
        $this->assertEquals('bg-info', $this->formation_progression->get_progress_bar_class(50));
        $this->assertEquals('bg-info', $this->formation_progression->get_progress_bar_class(74));
        $this->assertEquals('bg-success', $this->formation_progression->get_progress_bar_class(75));
        $this->assertEquals('bg-success', $this->formation_progression->get_progress_bar_class(100));
    }
    
    /**
     * Test get_niveau_badge_class() returns correct CSS classes
     */
    public function testNiveauBadgeClass()
    {
        $this->assertEquals('bg-secondary', $this->formation_progression->get_niveau_badge_class('-'));
        $this->assertEquals('bg-info', $this->formation_progression->get_niveau_badge_class('A'));
        $this->assertEquals('bg-warning', $this->formation_progression->get_niveau_badge_class('R'));
        $this->assertEquals('bg-success', $this->formation_progression->get_niveau_badge_class('Q'));
        $this->assertEquals('bg-secondary', $this->formation_progression->get_niveau_badge_class('invalid'));
    }
    
    /**
     * Test get_niveau_label() returns correct keys (translations tested separately)
     */
    public function testNiveauLabel()
    {
        $this->CI->load->helper('language');
        $this->CI->lang->load('formation', 'french');
        
        // Test that method returns language keys (not empty)
        $label_non_aborde = $this->formation_progression->get_niveau_label('-');
        $label_aborde = $this->formation_progression->get_niveau_label('A');
        $label_a_revoir = $this->formation_progression->get_niveau_label('R');
        $label_acquis = $this->formation_progression->get_niveau_label('Q');
        
        // Verify non-empty strings are returned
        $this->assertNotEmpty($label_non_aborde);
        $this->assertNotEmpty($label_aborde);
        $this->assertNotEmpty($label_a_revoir);
        $this->assertNotEmpty($label_acquis);
        $this->assertEquals('', $this->formation_progression->get_niveau_label('invalid'));
    }
    
    /**
     * Test with no séances (empty progression)
     * SKIP: Requires real database - move to integration tests
     */
    public function testCalculerWithNoSeances()
    {
        $this->markTestSkipped('Requires real database - should be in integration tests');
    }
    
    /**
     * Test derniere_evaluation is correctly identified
     * SKIP: Requires real database - move to integration tests
     */
    public function testDerniereEvaluationSelection()
    {
        $this->markTestSkipped('Requires real database - should be in integration tests');
    }
    
    /**
     * Helper: Create minimal test inscription with optional séances
     */
    private function createTestInscription($with_seances = true)
    {
        // Find or create test pilot
        $pilote_id = $this->getOrCreateTestPilot();
        if (!$pilote_id) {
            return null;
        }
        
        // Find or create test programme
        $programme_id = $this->getOrCreateTestProgramme();
        if (!$programme_id) {
            return null;
        }
        
        // Create inscription
        $inscription_data = [
            'pilote_id' => $pilote_id,
            'programme_id' => $programme_id,
            'date_ouverture' => date('Y-m-d'),
            'statut' => 'EN_COURS'
        ];
        
        $inscription_id = $this->CI->formation_inscription_model->insert($inscription_data);
        
        if (!$inscription_id) {
            return null;
        }
        
        $result = [
            'inscription_id' => $inscription_id,
            'pilote_id' => $pilote_id,
            'programme_id' => $programme_id,
            'seance_ids' => []
        ];
        
        if ($with_seances) {
            // Create a test séance
            $seance_data = [
                'inscription_id' => $inscription_id,
                'date_seance' => date('Y-m-d'),
                'duree' => '01:00:00',
                'atterrissages' => 3,
                'remarques' => 'Test seance'
            ];
            
            $seance_id = $this->CI->formation_seance_model->insert($seance_data);
            if ($seance_id) {
                $result['seance_ids'][] = $seance_id;
            }
        }
        
        return $result;
    }
    
    /**
     * Helper: Create inscription with specific evaluations
     */
    private function createTestInscriptionWithEvaluations($evaluations)
    {
        $test_data = $this->createTestInscription(false);
        if (!$test_data) {
            return null;
        }
        
        // Get programme sujets
        $programme = $this->CI->formation_programme_model->get($test_data['programme_id']);
        $lecons = json_decode($programme->contenu, true);
        
        if (empty($lecons)) {
            return null;
        }
        
        $sujet_index = 0;
        foreach ($lecons as $lecon) {
            if (!isset($lecon['sujets'])) continue;
            
            foreach ($lecon['sujets'] as $sujet) {
                if ($sujet_index >= count($evaluations)) {
                    break 2;
                }
                
                // Create séance for this evaluation
                $seance_data = [
                    'inscription_id' => $test_data['inscription_id'],
                    'date_seance' => date('Y-m-d', strtotime("+{$sujet_index} days")),
                    'duree' => '01:00:00',
                    'atterrissages' => 2
                ];
                
                $seance_id = $this->CI->formation_seance_model->insert($seance_data);
                $test_data['seance_ids'][] = $seance_id;
                
                // Create evaluation
                $eval_data = [
                    'seance_id' => $seance_id,
                    'sujet_numero' => $sujet['numero'],
                    'niveau' => $evaluations[$sujet_index]['niveau']
                ];
                
                $this->CI->formation_evaluation_model->insert($eval_data);
                
                $sujet_index++;
            }
        }
        
        return $test_data;
    }
    
    /**
     * Helper: Create inscription with multiple evaluations on same subject
     */
    private function createTestInscriptionWithMultipleEvaluations()
    {
        $test_data = $this->createTestInscription(false);
        if (!$test_data) {
            return null;
        }
        
        // Get first sujet from programme
        $programme = $this->CI->formation_programme_model->get($test_data['programme_id']);
        $lecons = json_decode($programme->contenu, true);
        
        if (empty($lecons) || empty($lecons[0]['sujets'])) {
            return null;
        }
        
        $sujet_numero = $lecons[0]['sujets'][0]['numero'];
        
        // Create 3 séances with progressive evaluations
        $niveaux = ['A', 'R', 'Q'];
        foreach ($niveaux as $index => $niveau) {
            $seance_data = [
                'inscription_id' => $test_data['inscription_id'],
                'date_seance' => date('Y-m-d', strtotime("+{$index} days")),
                'duree' => '01:00:00',
                'atterrissages' => 2
            ];
            
            $seance_id = $this->CI->formation_seance_model->insert($seance_data);
            $test_data['seance_ids'][] = $seance_id;
            
            // Create evaluation
            $eval_data = [
                'seance_id' => $seance_id,
                'sujet_numero' => $sujet_numero,
                'niveau' => $niveau
            ];
            
            $this->CI->formation_evaluation_model->insert($eval_data);
        }
        
        return $test_data;
    }
    
    /**
     * Helper: Get or create test pilot
     */
    private function getOrCreateTestPilot()
    {
        // Try to find existing test pilot
        $this->CI->db->where('nom', 'TEST_PROGRESSION');
        $query = $this->CI->db->get('membres');
        
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }
        
        // Create new test pilot directly via database
        $pilote_data = [
            'nom' => 'TEST_PROGRESSION',
            'prenom' => 'Test',
            'actif' => 1
        ];
        
        $this->CI->db->insert('membres', $pilote_data);
        return $this->CI->db->insert_id();
    }
    
    /**
     * Helper: Get or create test programme
     */
    private function getOrCreateTestProgramme()
    {
        // Try to find existing test programme
        $this->CI->db->where('titre', 'Programme Test Progression');
        $query = $this->CI->db->get('formation_programmes');
        
        if ($query->num_rows() > 0) {
            return $query->row()->id;
        }
        
        // Create minimal test programme with 2 lessons, 4 subjects
        $contenu = [
            [
                'numero' => '1',
                'titre' => 'Leçon 1',
                'sujets' => [
                    ['numero' => '1.1', 'titre' => 'Sujet 1.1'],
                    ['numero' => '1.2', 'titre' => 'Sujet 1.2']
                ]
            ],
            [
                'numero' => '2',
                'titre' => 'Leçon 2',
                'sujets' => [
                    ['numero' => '2.1', 'titre' => 'Sujet 2.1'],
                    ['numero' => '2.2', 'titre' => 'Sujet 2.2']
                ]
            ]
        ];
        
        $programme_data = [
            'titre' => 'Programme Test Progression',
            'description' => 'Programme for unit testing',
            'contenu' => json_encode($contenu),
            'version' => 1,
            'actif' => 1
        ];
        
        return $this->CI->formation_programme_model->insert($programme_data);
    }
    
    /**
     * Helper: Cleanup test data
     */
    private function cleanupTestData($test_data)
    {
        if (!$test_data) {
            return;
        }
        
        // Delete evaluations (cascade from séances)
        if (!empty($test_data['seance_ids'])) {
            foreach ($test_data['seance_ids'] as $seance_id) {
                $this->CI->db->where('seance_id', $seance_id);
                $this->CI->db->delete('formation_evaluations');
                
                $this->CI->formation_seance_model->delete($seance_id);
            }
        }
        
        // Delete inscription
        if (!empty($test_data['inscription_id'])) {
            $this->CI->formation_inscription_model->delete($test_data['inscription_id']);
        }
        
        // Note: We keep test pilot and programme for reuse in other tests
    }
}
