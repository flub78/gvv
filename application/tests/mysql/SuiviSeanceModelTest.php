<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Test for Formation_seance_model and Formation_evaluation_model
 *
 * Tests session creation (with/without inscription), evaluation management,
 * and session lifecycle operations.
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php application/tests/mysql/SuiviSeanceModelTest.php
 */
class SuiviSeanceModelTest extends TransactionalTestCase
{
    private $seance_model;
    private $eval_model;
    private $programme_model;
    private $lecon_model;
    private $sujet_model;
    private $inscription_model;

    // Test data IDs
    private $programme_id;
    private $lecon_id;
    private $sujet_ids = array();
    private $inscription_id;

    // Test pilot/instructor IDs (from membres table)
    private $pilote_id;
    private $instructeur_id;
    private $machine_id;

    public function setUp(): void
    {
        parent::setUp();

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // Load models
        $this->CI->load->model('formation_seance_model');
        $this->CI->load->model('formation_evaluation_model');
        $this->CI->load->model('formation_programme_model');
        $this->CI->load->model('formation_lecon_model');
        $this->CI->load->model('formation_sujet_model');
        $this->CI->load->model('formation_inscription_model');

        $this->seance_model = $this->CI->formation_seance_model;
        $this->eval_model = $this->CI->formation_evaluation_model;
        $this->programme_model = $this->CI->formation_programme_model;
        $this->lecon_model = $this->CI->formation_lecon_model;
        $this->sujet_model = $this->CI->formation_sujet_model;
        $this->inscription_model = $this->CI->formation_inscription_model;

        // Setup test data
        $this->_createTestData();
    }

    /**
     * Create test programme, lessons, topics, and inscription
     */
    private function _createTestData()
    {
        // Find a valid pilot and instructor from membres
        $pilot = $this->CI->db->select('mlogin')->from('membres')->limit(1)->get()->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No members in database for testing');
        }
        $this->pilote_id = $pilot['mlogin'];

        // Use same member as instructor (or find another)
        $instructor = $this->CI->db->select('mlogin')->from('membres')
            ->where('mlogin !=', $this->pilote_id)->limit(1)->get()->row_array();
        $this->instructeur_id = $instructor ? $instructor['mlogin'] : $this->pilote_id;

        // Find a valid machine
        $machine = $this->CI->db->select('mpimmat')->from('machinesp')->limit(1)->get()->row_array();
        if (!$machine) {
            $this->markTestSkipped('No machines in database for testing');
        }
        $this->machine_id = $machine['mpimmat'];

        // Create programme
        $this->programme_id = $this->programme_model->create_programme(array(
            'code' => 'SEANCE_TEST_' . time(),
            'titre' => 'Test Programme Seances',
            'description' => 'Programme pour tests de seances',
            'contenu_markdown' => '# Test',
            'statut' => 'actif'
        ));

        // Create lesson
        $this->lecon_id = $this->lecon_model->create(array(
            'programme_id' => $this->programme_id,
            'numero' => 1,
            'titre' => 'Lecon 1 Test',
            'description' => 'Premiere lecon',
            'ordre' => 1
        ));

        // Create topics
        for ($i = 1; $i <= 3; $i++) {
            $this->sujet_ids[] = $this->sujet_model->create(array(
                'lecon_id' => $this->lecon_id,
                'numero' => '1.' . $i,
                'titre' => 'Sujet 1.' . $i . ' Test',
                'description' => 'Description sujet ' . $i,
                'ordre' => $i
            ));
        }

        // Create inscription
        $this->inscription_id = $this->inscription_model->create(array(
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'version_programme' => 1,
            'statut' => 'ouverte',
            'date_ouverture' => date('Y-m-d')
        ));
    }

    /**
     * Test creating a session with inscription
     */
    public function testCreateSeanceAvecInscription()
    {
        $seance_data = array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:45:00',
            'nb_atterrissages' => 2,
            'meteo' => json_encode(array('cavok', 'vent_faible')),
            'commentaires' => 'Bon vol de formation',
            'prochaines_lecons' => 'Lecon 2'
        );

        $evaluations = array(
            array('sujet_id' => $this->sujet_ids[0], 'niveau' => 'A', 'commentaire' => 'Bien commence'),
            array('sujet_id' => $this->sujet_ids[1], 'niveau' => 'Q', 'commentaire' => 'Acquis')
        );

        $seance_id = $this->seance_model->create_with_evaluations($seance_data, $evaluations);

        $this->assertNotFalse($seance_id, 'Seance should be created');
        $this->assertGreaterThan(0, $seance_id);

        // Verify seance data
        $seance = $this->seance_model->get($seance_id);
        $this->assertEquals($this->inscription_id, $seance['inscription_id']);
        $this->assertEquals($this->pilote_id, $seance['pilote_id']);
        $this->assertEquals(2, $seance['nb_atterrissages']);

        // Verify it's NOT a free session
        $this->assertFalse($this->seance_model->is_seance_libre($seance_id));

        // Verify evaluations
        $evals = $this->eval_model->get_by_seance($seance_id);
        $this->assertCount(2, $evals);
    }

    /**
     * Test creating a free session (without inscription)
     */
    public function testCreateSeanceLibre()
    {
        $seance_data = array(
            'inscription_id' => null,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:30:00',
            'nb_atterrissages' => 1,
            'meteo' => json_encode(array('thermiques')),
            'commentaires' => 'Vol de perfectionnement'
        );

        $seance_id = $this->seance_model->create_with_evaluations($seance_data);

        $this->assertNotFalse($seance_id);

        // Verify it IS a free session
        $this->assertTrue($this->seance_model->is_seance_libre($seance_id));

        // Verify inscription_id is null
        $seance = $this->seance_model->get($seance_id);
        $this->assertNull($seance['inscription_id']);
    }

    /**
     * Test get_full returns joined data
     */
    public function testGetFullReturnsJoinedData()
    {
        $seance_id = $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:50:00',
            'nb_atterrissages' => 3
        ));

        $seance = $this->seance_model->get_full($seance_id);

        $this->assertNotEmpty($seance);
        $this->assertArrayHasKey('programme_code', $seance);
        $this->assertArrayHasKey('programme_titre', $seance);
        $this->assertArrayHasKey('pilote_nom', $seance);
        $this->assertArrayHasKey('instructeur_nom', $seance);
        $this->assertArrayHasKey('machine_modele', $seance);
    }

    /**
     * Test get_by_inscription
     */
    public function testGetByInscription()
    {
        // Create 2 sessions for the inscription
        for ($i = 0; $i < 2; $i++) {
            $this->seance_model->create_with_evaluations(array(
                'inscription_id' => $this->inscription_id,
                'pilote_id' => $this->pilote_id,
                'programme_id' => $this->programme_id,
                'date_seance' => date('Y-m-d', strtotime("-{$i} days")),
                'instructeur_id' => $this->instructeur_id,
                'machine_id' => $this->machine_id,
                'duree' => '00:40:00',
                'nb_atterrissages' => 1
            ));
        }

        $seances = $this->seance_model->get_by_inscription($this->inscription_id);
        $this->assertCount(2, $seances);
    }

    /**
     * Test get_by_pilote returns both types
     */
    public function testGetByPiloteReturnsBothTypes()
    {
        // Create one inscription session
        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:40:00',
            'nb_atterrissages' => 1
        ));

        // Create one free session
        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => null,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:30:00',
            'nb_atterrissages' => 1
        ));

        // Get all
        $all = $this->seance_model->get_by_pilote($this->pilote_id);
        $this->assertCount(2, $all);

        // Get inscription only
        $insc_only = $this->seance_model->get_by_pilote($this->pilote_id, array('inscription_only' => true));
        $this->assertCount(1, $insc_only);

        // Get libre only
        $libre_only = $this->seance_model->get_by_pilote($this->pilote_id, array('libre_only' => true));
        $this->assertCount(1, $libre_only);
    }

    /**
     * Test update_with_evaluations
     */
    public function testUpdateWithEvaluations()
    {
        $evaluations = array(
            array('sujet_id' => $this->sujet_ids[0], 'niveau' => 'A', 'commentaire' => 'Initial')
        );

        $seance_id = $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:40:00',
            'nb_atterrissages' => 1
        ), $evaluations);

        // Update with different evaluations
        $new_evaluations = array(
            array('sujet_id' => $this->sujet_ids[0], 'niveau' => 'Q', 'commentaire' => 'Updated'),
            array('sujet_id' => $this->sujet_ids[1], 'niveau' => 'R', 'commentaire' => 'New')
        );

        $success = $this->seance_model->update_with_evaluations($seance_id, array(
            'commentaires' => 'Updated comment'
        ), $new_evaluations);

        $this->assertTrue($success);

        // Verify evaluations replaced
        $evals = $this->eval_model->get_by_seance($seance_id);
        $this->assertCount(2, $evals);

        // Verify seance data updated
        $seance = $this->seance_model->get($seance_id);
        $this->assertEquals('Updated comment', $seance['commentaires']);
    }

    /**
     * Test evaluation levels and progression
     */
    public function testEvaluationProgression()
    {
        // Create session with evaluations
        $evaluations = array(
            array('sujet_id' => $this->sujet_ids[0], 'niveau' => 'Q', 'commentaire' => null),
            array('sujet_id' => $this->sujet_ids[1], 'niveau' => 'A', 'commentaire' => null),
            array('sujet_id' => $this->sujet_ids[2], 'niveau' => 'R', 'commentaire' => null)
        );

        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:45:00',
            'nb_atterrissages' => 2
        ), $evaluations);

        // Test count_acquis
        $acquis = $this->eval_model->count_acquis($this->inscription_id);
        $this->assertEquals(1, $acquis);

        // Test get_dernier_niveau_par_sujet
        $derniers = $this->eval_model->get_dernier_niveau_par_sujet($this->inscription_id);
        $this->assertCount(3, $derniers);
        $this->assertEquals('Q', $derniers[$this->sujet_ids[0]]['niveau']);
        $this->assertEquals('A', $derniers[$this->sujet_ids[1]]['niveau']);
        $this->assertEquals('R', $derniers[$this->sujet_ids[2]]['niveau']);
    }

    /**
     * Test stats for inscription
     */
    public function testStatsInscription()
    {
        // Create 2 sessions
        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:45:00',
            'nb_atterrissages' => 2
        ));

        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d', strtotime('-1 day')),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:30:00',
            'nb_atterrissages' => 1
        ));

        $stats = $this->seance_model->get_stats_inscription($this->inscription_id);
        $this->assertEquals(2, $stats['nb_seances']);
        $this->assertEquals(3, $stats['atterrissages_totaux']);
    }

    /**
     * Test select_page with type filter
     */
    public function testSelectPageWithTypeFilter()
    {
        // Create inscription session
        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:40:00',
            'nb_atterrissages' => 1
        ));

        // Create free session
        $this->seance_model->create_with_evaluations(array(
            'inscription_id' => null,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:30:00',
            'nb_atterrissages' => 1
        ));

        // Filter formation only
        $formation = $this->seance_model->select_page(array(
            'type' => 'formation',
            'pilote_id' => $this->pilote_id
        ));
        foreach ($formation as $s) {
            $this->assertEquals('Formation', $s['type_seance']);
        }

        // Filter libre only
        $libre = $this->seance_model->select_page(array(
            'type' => 'libre',
            'pilote_id' => $this->pilote_id
        ));
        foreach ($libre as $s) {
            $this->assertEquals('Libre', $s['type_seance']);
        }
    }

    /**
     * Test get_niveaux returns all levels
     */
    public function testGetNiveaux()
    {
        $niveaux = Formation_evaluation_model::get_niveaux();
        $this->assertCount(4, $niveaux);
        $this->assertArrayHasKey('-', $niveaux);
        $this->assertArrayHasKey('A', $niveaux);
        $this->assertArrayHasKey('R', $niveaux);
        $this->assertArrayHasKey('Q', $niveaux);
    }

    /**
     * Test seance image display string
     */
    public function testSeanceImage()
    {
        $seance_id = $this->seance_model->create_with_evaluations(array(
            'inscription_id' => $this->inscription_id,
            'pilote_id' => $this->pilote_id,
            'programme_id' => $this->programme_id,
            'date_seance' => '2026-01-26',
            'instructeur_id' => $this->instructeur_id,
            'machine_id' => $this->machine_id,
            'duree' => '00:45:00',
            'nb_atterrissages' => 1
        ));

        $image = $this->seance_model->image($seance_id);
        $this->assertNotEmpty($image);
        $this->assertStringContainsString('2026-01-26', $image);
    }
}

/* End of file SuiviSeanceModelTest.php */
/* Location: ./application/tests/mysql/SuiviSeanceModelTest.php */
