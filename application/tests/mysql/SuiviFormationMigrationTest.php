<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Test for Suivi Formation Migration
 *
 * This test verifies that the formation tracking tables were created correctly
 * by the migration 063_add_formation_tables.php.
 *
 * Requirements:
 * - MySQL database connection (configured in integration_bootstrap.php)
 * - Migration 063 must have been executed
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php application/tests/mysql/SuiviFormationMigrationTest.php
 */
class SuiviFormationMigrationTest extends TransactionalTestCase
{
    /**
     * List of expected formation tables
     */
    private $expected_tables = [
        'formation_programmes',
        'formation_lecons',
        'formation_sujets',
        'formation_inscriptions',
        'formation_seances',
        'formation_evaluations'
    ];

    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();

        // Verify database connection
        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }
    }

    /**
     * Test that all formation tables exist
     */
    public function testAllFormationTablesExist()
    {
        foreach ($this->expected_tables as $table) {
            $result = $this->CI->db->query("SHOW TABLES LIKE '$table'");
            $this->assertGreaterThan(
                0,
                $result->num_rows(),
                "Table '$table' should exist"
            );
        }
    }

    /**
     * Test formation_programmes table structure
     */
    public function testSuiviProgrammesTableStructure()
    {
        $expected_columns = [
            'id', 'code', 'titre', 'description', 'contenu_markdown',
            'section_id', 'version', 'statut', 'date_creation', 'date_modification'
        ];

        $this->assertTableHasColumns('formation_programmes', $expected_columns);
    }

    /**
     * Test formation_lecons table structure
     */
    public function testSuiviLeconsTableStructure()
    {
        $expected_columns = ['id', 'programme_id', 'numero', 'titre', 'description', 'ordre'];
        $this->assertTableHasColumns('formation_lecons', $expected_columns);
    }

    /**
     * Test formation_sujets table structure
     */
    public function testSuiviSujetsTableStructure()
    {
        $expected_columns = ['id', 'lecon_id', 'numero', 'titre', 'description', 'objectifs', 'ordre'];
        $this->assertTableHasColumns('formation_sujets', $expected_columns);
    }

    /**
     * Test formation_inscriptions table structure
     */
    public function testSuiviInscriptionsTableStructure()
    {
        $expected_columns = [
            'id', 'pilote_id', 'programme_id', 'version_programme',
            'instructeur_referent_id', 'statut', 'date_ouverture',
            'date_suspension', 'motif_suspension', 'date_cloture',
            'motif_cloture', 'commentaires'
        ];
        $this->assertTableHasColumns('formation_inscriptions', $expected_columns);
    }

    /**
     * Test formation_seances table structure
     */
    public function testSuiviSeancesTableStructure()
    {
        $expected_columns = [
            'id', 'inscription_id', 'pilote_id', 'programme_id',
            'date_seance', 'instructeur_id', 'machine_id', 'duree',
            'nb_atterrissages', 'meteo', 'commentaires', 'prochaines_lecons'
        ];
        $this->assertTableHasColumns('formation_seances', $expected_columns);
    }

    /**
     * Test formation_evaluations table structure
     */
    public function testSuiviEvaluationsTableStructure()
    {
        $expected_columns = ['id', 'seance_id', 'sujet_id', 'niveau', 'commentaire'];
        $this->assertTableHasColumns('formation_evaluations', $expected_columns);
    }

    /**
     * Test foreign key on formation_lecons.programme_id
     */
    public function testForeignKeyLeconsToProgammes()
    {
        // Create a programme
        $programme_id = $this->createTestProgramme();

        // Create a lecon linked to the programme
        $this->CI->db->insert('formation_lecons', [
            'programme_id' => $programme_id,
            'numero' => 1,
            'titre' => 'Test Lecon FK',
            'description' => 'Test description',
            'ordre' => 1
        ]);
        $lecon_id = $this->CI->db->insert_id();

        $this->assertGreaterThan(0, $lecon_id, 'Lecon should be created');

        // Verify cascade delete: when programme is deleted, lecons should be deleted too
        $this->CI->db->delete('formation_programmes', ['id' => $programme_id]);

        $result = $this->CI->db->get_where('formation_lecons', ['id' => $lecon_id])->row_array();
        $this->assertEmpty($result, 'Lecon should be deleted when programme is deleted (CASCADE)');
    }

    /**
     * Test that inscription_id can be NULL in formation_seances (free sessions)
     */
    public function testSeanceAllowsNullInscription()
    {
        // Get a valid member and machine for foreign keys
        $membre = $this->CI->db->get_where('membres', ['actif' => 1])->row_array();
        $machine = $this->CI->db->get_where('machinesp', ['actif' => 1])->row_array();

        if (empty($membre) || empty($machine)) {
            $this->markTestSkipped('Need at least one active member and one active glider');
        }

        // Create a programme
        $programme_id = $this->createTestProgramme();

        // Create a seance without inscription (free session)
        $seance_data = [
            'inscription_id' => NULL,
            'pilote_id' => $membre['mlogin'],
            'programme_id' => $programme_id,
            'date_seance' => date('Y-m-d'),
            'instructeur_id' => $membre['mlogin'],
            'machine_id' => $machine['mpimmat'],
            'duree' => '00:45:00',
            'nb_atterrissages' => 3
        ];

        $this->CI->db->insert('formation_seances', $seance_data);
        $seance_id = $this->CI->db->insert_id();

        $this->assertGreaterThan(0, $seance_id, 'Free session (NULL inscription_id) should be created');

        // Verify the seance was created with NULL inscription_id
        $seance = $this->CI->db->get_where('formation_seances', ['id' => $seance_id])->row_array();
        $this->assertNull($seance['inscription_id'], 'inscription_id should be NULL for free session');
    }

    /**
     * Test evaluation niveau enum values
     */
    public function testEvaluationNiveauEnum()
    {
        // Get field info for niveau column
        $result = $this->CI->db->query("SHOW COLUMNS FROM formation_evaluations WHERE Field = 'niveau'");
        $column = $result->row_array();

        $this->assertNotEmpty($column, 'niveau column should exist');
        $this->assertStringContainsString("enum", $column['Type'], 'niveau should be an ENUM type');
        $this->assertStringContainsString("'-'", $column['Type'], 'niveau should include - value');
        $this->assertStringContainsString("'A'", $column['Type'], 'niveau should include A value');
        $this->assertStringContainsString("'R'", $column['Type'], 'niveau should include R value');
        $this->assertStringContainsString("'Q'", $column['Type'], 'niveau should include Q value');
    }

    /**
     * Test inscription statut enum values
     */
    public function testInscriptionStatutEnum()
    {
        $result = $this->CI->db->query("SHOW COLUMNS FROM formation_inscriptions WHERE Field = 'statut'");
        $column = $result->row_array();

        $this->assertNotEmpty($column, 'statut column should exist');
        $this->assertStringContainsString("enum", $column['Type'], 'statut should be an ENUM type');
        $this->assertStringContainsString("'ouverte'", $column['Type']);
        $this->assertStringContainsString("'suspendue'", $column['Type']);
        $this->assertStringContainsString("'cloturee'", $column['Type']);
        $this->assertStringContainsString("'abandonnee'", $column['Type']);
    }

    /**
     * Helper: Create a test programme
     */
    private function createTestProgramme()
    {
        $this->CI->db->insert('formation_programmes', [
            'code' => 'TEST' . time(),
            'titre' => 'Test Programme',
            'description' => 'Test description',
            'contenu_markdown' => '# Test Programme\n\n## Lecon 1\n\n### Sujet 1.1',
            'section_id' => NULL,
            'version' => 1,
            'statut' => 'actif',
            'date_creation' => date('Y-m-d H:i:s')
        ]);
        return $this->CI->db->insert_id();
    }

    /**
     * Helper: Assert that a table has specific columns
     */
    private function assertTableHasColumns($table, $expected_columns)
    {
        $result = $this->CI->db->query("SHOW COLUMNS FROM $table");
        $rows = $result->result_array();
        $fields = array_column($rows, 'Field');

        foreach ($expected_columns as $column) {
            $this->assertContains(
                $column,
                $fields,
                "Table '$table' should have column '$column'"
            );
        }
    }
}

/* End of file SuiviFormationMigrationTest.php */
/* Location: ./application/tests/mysql/SuiviFormationMigrationTest.php */
