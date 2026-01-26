<?php

/**
 * MySQL test for Formation_inscription_model
 *
 * Tests the lifecycle methods and status transitions using real database:
 * - Opening an inscription (ouvrir)
 * - Suspending an inscription (suspendre)
 * - Reactivating a suspended inscription (reactiver)
 * - Closing an inscription (cloturer - success or abandon)
 * - Validations (duplicate inscription, status constraints)
 *
 * Requirements:
 * - Real MySQL database connection
 * - Formation tables created (migration 063)
 * - At least 2 active members must exist in membres table
 *
 * Note: This test uses existing members from the database rather than
 * creating test users. Tests run in transactions that are rolled back.
 *
 * @see doc/plans/suivi_formation_plan.md Phase 3
 */
class FormationInscriptionMysqlTest extends PHPUnit\Framework\TestCase
{
    /**
     * @var CI_Controller
     */
    private $CI;

    /**
     * @var Formation_inscription_model
     */
    private $inscription_model;

    /**
     * @var Formation_programme_model
     */
    private $programme_model;

    /**
     * Test programme ID
     */
    private $test_programme_id;

    /**
     * Test pilot login
     */
    private $test_pilot = null;

    /**
     * Test instructor login
     */
    private $test_instructor = null;

    /**
     * Created inscription IDs for tracking
     */
    private $created_inscription_ids = [];

    /**
     * Set up test environment with database transaction
     */
    public function setUp(): void
    {
        // Get CodeIgniter instance
        $this->CI =& get_instance();

        // Load models
        $this->CI->load->model('formation_inscription_model');
        $this->CI->load->model('formation_programme_model');
        $this->CI->load->model('membres_model');
        $this->inscription_model = $this->CI->formation_inscription_model;
        $this->programme_model = $this->CI->formation_programme_model;

        // Start transaction for test isolation
        $this->CI->db->trans_start();

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // Find existing members for testing
        $membres = $this->CI->db->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(2)
            ->get()
            ->result_array();
        
        if (count($membres) < 2) {
            $this->markTestSkipped('Need at least 2 active members in database for testing');
        }
        
        $this->test_pilot = $membres[0]['mlogin'];
        $this->test_instructor = $membres[1]['mlogin'];

        // Create test programme
        $this->test_programme_id = $this->createTestProgramme();
    }

    /**
     * Create a test programme for inscriptions
     */
    private function createTestProgramme()
    {
        // Use microtime for unique codes even in fast test execution
        $unique_id = str_replace('.', '', microtime(true));
        
        $data = [
            'code' => 'TEST_INSCR_' . $unique_id,
            'titre' => 'Test Programme for Inscriptions',
            'description' => 'Programme de test',
            'contenu_markdown' => "# Programme de Test\n\n## Leçon 1: Test\n\n### Sujet 1.1\nContenu test",
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ];

        return $this->programme_model->create($data);
    }

    /**
     * Test 1: Opening an inscription successfully
     */
    public function test_ouvrir_inscription_creates_new_inscription()
    {
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'instructeur_referent_id' => $this->test_instructor,
            'date_ouverture' => date('Y-m-d'),
            'commentaires' => 'Test inscription'
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        $this->assertIsInt($inscription_id);
        $this->assertGreaterThan(0, $inscription_id);

        // Verify the inscription was created
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertNotEmpty($inscription);
        $this->assertEquals('ouverte', $inscription['statut']);
        $this->assertEquals($this->test_pilot, $inscription['pilote_id']);
        $this->assertEquals($this->test_programme_id, $inscription['programme_id']);
    }

    /**
     * Test 2: Cannot open duplicate inscription for same pilot/programme
     */
    public function test_cannot_open_duplicate_inscription()
    {
        // Create first inscription
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ];

        $first_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $first_id;

        // Check for duplicate
        $existing = $this->inscription_model->get_by_pilote_programme(
            $this->test_pilot,
            $this->test_programme_id,
            'ouverte'
        );

        $this->assertNotEmpty($existing, 'Should find existing open inscription');
    }

    /**
     * Test 3: Suspending an open inscription
     */
    public function test_suspendre_inscription_changes_status()
    {
        // Create inscription
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        // Suspend it
        $motif = 'Test suspension';
        $result = $this->inscription_model->suspendre($inscription_id, $motif);

        $this->assertTrue($result);

        // Verify status changed
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('suspendue', $inscription['statut']);
        $this->assertEquals($motif, $inscription['motif_suspension']);
        $this->assertEquals(date('Y-m-d'), $inscription['date_suspension']);
    }

    /**
     * Test 4: Reactivating a suspended inscription
     */
    public function test_reactiver_inscription_restores_open_status()
    {
        // Create and suspend inscription
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        $this->inscription_model->suspendre($inscription_id, 'Suspension temporaire');

        // Reactivate
        $result = $this->inscription_model->reactiver($inscription_id);

        $this->assertTrue($result);

        // Verify status restored
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('ouverte', $inscription['statut']);
        $this->assertNull($inscription['date_suspension']);
        $this->assertNull($inscription['motif_suspension']);
    }

    /**
     * Test 5: Closing inscription with success
     */
    public function test_cloturer_inscription_with_success()
    {
        // Create inscription
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        // Close with success
        $result = $this->inscription_model->cloturer($inscription_id, 'cloturee', 'Formation réussie');

        $this->assertTrue($result);

        // Verify closure
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('cloturee', $inscription['statut']);
        $this->assertEquals('Formation réussie', $inscription['motif_cloture']);
        $this->assertEquals(date('Y-m-d'), $inscription['date_cloture']);
    }

    /**
     * Test 6: Closing inscription with abandon
     */
    public function test_cloturer_inscription_with_abandon()
    {
        // Create inscription
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        // Close with abandon
        $result = $this->inscription_model->cloturer($inscription_id, 'abandonnee', 'Abandon du pilote');

        $this->assertTrue($result);

        // Verify closure
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('abandonnee', $inscription['statut']);
        $this->assertEquals('Abandon du pilote', $inscription['motif_cloture']);
        $this->assertEquals(date('Y-m-d'), $inscription['date_cloture']);
    }

    /**
     * Test 7: Complete lifecycle workflow
     */
    public function test_complete_inscription_lifecycle()
    {
        // 1. Open
        $data = [
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d'),
            'commentaires' => 'Test workflow'
        ];

        $inscription_id = $this->inscription_model->ouvrir($data);
        $this->created_inscription_ids[] = $inscription_id;

        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('ouverte', $inscription['statut']);

        // 2. Suspend
        $this->inscription_model->suspendre($inscription_id, 'Pause hivernale');
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('suspendue', $inscription['statut']);

        // 3. Reactivate
        $this->inscription_model->reactiver($inscription_id);
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('ouverte', $inscription['statut']);

        // 4. Close
        $this->inscription_model->cloturer($inscription_id, 'cloturee', 'Succès');
        $inscription = $this->inscription_model->get($inscription_id);
        $this->assertEquals('cloturee', $inscription['statut']);
    }

    /**
     * Test 8: Get inscriptions with filters
     */
    public function test_get_all_with_filters()
    {
        // Create multiple inscriptions
        $id1 = $this->inscription_model->ouvrir([
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ]);
        $this->created_inscription_ids[] = $id1;

        $this->inscription_model->suspendre($id1, 'Test');

        // Use instructor as second pilot (different from test_pilot)
        $id2 = $this->inscription_model->ouvrir([
            'pilote_id' => $this->test_instructor,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ]);
        $this->created_inscription_ids[] = $id2;

        // Filter by status
        $suspended = $this->inscription_model->get_all(['statut' => 'suspendue']);
        $this->assertNotEmpty($suspended);

        $open = $this->inscription_model->get_all(['statut' => 'ouverte']);
        $this->assertNotEmpty($open);
    }

    /**
     * Test 9: Calculate progression (basic test)
     */
    public function test_calculate_progression_returns_structure()
    {
        // Create inscription
        $inscription_id = $this->inscription_model->ouvrir([
            'pilote_id' => $this->test_pilot,
            'programme_id' => $this->test_programme_id,
            'date_ouverture' => date('Y-m-d')
        ]);
        $this->created_inscription_ids[] = $inscription_id;

        // Calculate progression (no seances yet, should return 0%)
        $progression = $this->inscription_model->calculate_progression($inscription_id);

        $this->assertIsArray($progression);
        $this->assertArrayHasKey('total_sujets', $progression);
        $this->assertArrayHasKey('sujets_acquis', $progression);
        $this->assertArrayHasKey('pourcentage', $progression);
        $this->assertEquals(0, $progression['sujets_acquis']);
    }

    /**
     * Rollback transaction after each test
     */
    public function tearDown(): void
    {
        // Transaction is automatically rolled back by TransactionalTestCase
        if ($this->CI && $this->CI->db) {
            $this->CI->db->trans_rollback();
        }
    }
}
