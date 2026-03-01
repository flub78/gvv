<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Tests – Séances théoriques
 *
 * Tests the Formation_seance_participants_model and the théorique extensions
 * of Formation_seance_model (create_theorique, update_theorique, get_participants).
 *
 * Usage:
 *   phpunit --bootstrap application/tests/integration_bootstrap.php \
 *           application/tests/mysql/FormationSeanceTheoriqueModelTest.php
 */
class FormationSeanceTheoriqueModelTest extends TransactionalTestCase
{
    /** @var Formation_seance_model */
    private $seance_model;

    /** @var Formation_seance_participants_model */
    private $part_model;

    /** @var string  A valid member login existing in gvv2 */
    private $instructeur_id;

    /** @var string  A valid member login for participants */
    private $pilote1_id;

    /** @var string */
    private $pilote2_id;

    /** @var int  A type_seance_id of nature 'theorique' */
    private $type_id;

    public function setUp(): void
    {
        parent::setUp();

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        if (!$this->CI->db->table_exists('formation_seances_participants')) {
            $this->markTestSkipped('Table formation_seances_participants does not exist (run migration 079)');
        }

        $this->CI->load->model('formation_seance_model');
        $this->CI->load->model('formation_seance_participants_model');
        $this->CI->load->model('formation_type_seance_model');

        // Access models via CI after loading
        $this->seance_model = $this->CI->formation_seance_model;

        // Instantiate participants model directly (CI mock may not support lazy props)
        require_once APPPATH . 'models/formation_seance_participants_model.php';
        $this->part_model = new Formation_seance_participants_model();
        // Share the same DB connection
        $this->part_model->db = $this->CI->db;

        // Find a real instructeur to use in tests
        $row = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(1)
            ->get()->row_array();

        if (empty($row)) {
            $this->markTestSkipped('No active member found in the database');
        }
        $this->instructeur_id = $row['mlogin'];

        // Reuse same member as pilot1 and pilot2 (unique pilots not required for these tests)
        $rows = $this->CI->db
            ->select('mlogin')
            ->from('membres')
            ->where('actif', 1)
            ->limit(2)
            ->get()->result_array();

        $this->pilote1_id = $rows[0]['mlogin'];
        $this->pilote2_id = isset($rows[1]) ? $rows[1]['mlogin'] : $rows[0]['mlogin'];

        // Ensure a theorique type exists
        $type_row = $this->CI->db
            ->select('id')
            ->from('formation_types_seance')
            ->where('nature', 'theorique')
            ->where('actif', 1)
            ->limit(1)
            ->get()->row_array();

        if (empty($type_row)) {
            // Create one for the test
            $this->type_id = $this->CI->formation_type_seance_model->create(array(
                'nom'    => 'Type théorique test',
                'nature' => 'theorique',
                'actif'  => 1,
            ));
        } else {
            $this->type_id = $type_row['id'];
        }
    }

    // -----------------------------------------------------------------------
    // Formation_seance_participants_model
    // -----------------------------------------------------------------------

    public function testReplaceAndGetBySeance()
    {
        $seance_id = $this->_create_theorique_seance();

        $this->part_model->replace_participants($seance_id, array($this->pilote1_id, $this->pilote2_id));
        $rows = $this->part_model->get_by_seance($seance_id);

        $ids = array_column($rows, 'pilote_id');
        $this->assertContains($this->pilote1_id, $ids);
        $this->assertContains($this->pilote2_id, $ids);
    }

    public function testReplaceOverwritesPreviousList()
    {
        $seance_id = $this->_create_theorique_seance();

        $this->part_model->replace_participants($seance_id, array($this->pilote1_id, $this->pilote2_id));
        $this->part_model->replace_participants($seance_id, array($this->pilote1_id));

        $rows = $this->part_model->get_by_seance($seance_id);
        $this->assertCount(1, $rows);
        $this->assertEquals($this->pilote1_id, $rows[0]['pilote_id']);
    }

    public function testCountBySeance()
    {
        $seance_id = $this->_create_theorique_seance();
        $this->part_model->replace_participants($seance_id, array($this->pilote1_id, $this->pilote2_id));
        $this->assertEquals(count(array_unique(array($this->pilote1_id, $this->pilote2_id))),
                            $this->part_model->count_by_seance($seance_id));
    }

    public function testIsParticipant()
    {
        $seance_id = $this->_create_theorique_seance();
        $this->part_model->replace_participants($seance_id, array($this->pilote1_id));
        $this->assertTrue($this->part_model->is_participant($seance_id, $this->pilote1_id));
        $this->assertFalse($this->part_model->is_participant($seance_id, 'nobody_xyz'));
    }

    public function testDeleteBySeance()
    {
        $seance_id = $this->_create_theorique_seance();
        $this->part_model->replace_participants($seance_id, array($this->pilote1_id));
        $this->part_model->delete_by_seance($seance_id);
        $this->assertEmpty($this->part_model->get_by_seance($seance_id));
    }

    // -----------------------------------------------------------------------
    // Formation_seance_model – méthodes théoriques
    // -----------------------------------------------------------------------

    public function testCreateTheoriqueAndGetParticipants()
    {
        $seance_data = $this->_seance_data();
        $seance_id   = $this->seance_model->create_theorique($seance_data, array($this->pilote1_id, $this->pilote2_id));

        $this->assertNotFalse($seance_id, 'create_theorique doit retourner un ID valide');

        $participants = $this->seance_model->get_participants($seance_id);
        $ids = array_column($participants, 'pilote_id');

        $this->assertContains($this->pilote1_id, $ids);
        $this->assertContains($this->pilote2_id, $ids);
    }

    public function testUpdateTheoriqueReplacesParticipants()
    {
        $seance_data = $this->_seance_data();
        $seance_id   = $this->seance_model->create_theorique($seance_data, array($this->pilote1_id, $this->pilote2_id));

        $ok = $this->seance_model->update_theorique(
            $seance_id,
            array_merge($seance_data, array('id' => $seance_id, 'lieu' => 'Salle B')),
            array($this->pilote1_id)
        );

        $this->assertTrue($ok);
        $participants = $this->seance_model->get_participants($seance_id);
        $this->assertCount(1, $participants);
        $this->assertEquals($this->pilote1_id, $participants[0]['pilote_id']);
    }

    public function testIsTheoriqueReturnsTrueForTheoriqueType()
    {
        $seance_data = $this->_seance_data();
        $seance_id   = $this->seance_model->create_theorique($seance_data, array($this->pilote1_id));
        $this->assertTrue($this->seance_model->is_theorique($seance_id));
    }

    public function testSelectPageIncludesNatureAndParticipantCount()
    {
        $seance_data = $this->_seance_data();
        $seance_id   = $this->seance_model->create_theorique(
            $seance_data,
            array($this->pilote1_id, $this->pilote2_id)
        );

        $rows = $this->seance_model->select_page(array('nature' => 'theorique'), 200);
        $found = null;
        foreach ($rows as $r) {
            if ((int)$r['id'] === (int)$seance_id) {
                $found = $r;
                break;
            }
        }

        $this->assertNotNull($found, 'La séance théorique doit apparaître dans select_page');
        $this->assertEquals('theorique', $found['nature_seance']);
        $this->assertGreaterThanOrEqual(
            count(array_unique(array($this->pilote1_id, $this->pilote2_id))),
            (int)$found['nb_participants']
        );
    }

    // -----------------------------------------------------------------------
    // Migration check
    // -----------------------------------------------------------------------

    public function testMigration079TableExists()
    {
        $this->assertTrue(
            $this->CI->db->table_exists('formation_seances_participants'),
            'Table formation_seances_participants must exist after migration 079'
        );
    }

    public function testMigration079ColumnsNullable()
    {
        // Verify the migration made pilote_id, machine_id, duree, nb_atterrissages nullable
        // by inserting a row with all those fields set to NULL
        $id = $this->seance_model->create(array(
            'date_seance'      => date('Y-m-d'),
            'type_seance_id'   => $this->type_id,
            'instructeur_id'   => $this->instructeur_id,
            'pilote_id'        => null,
            'machine_id'       => null,
            'duree'            => null,
            'nb_atterrissages' => null,
        ));
        $this->assertNotFalse($id, 'Should be able to create a seance with NULL pilote_id, machine_id, duree, nb_atterrissages');
    }

    // -----------------------------------------------------------------------
    // Phase 3: Annual stats methods
    // -----------------------------------------------------------------------

    public function testGetStatsAnnuelsParInstructeurReturnsArray()
    {
        // Create a theorique session in the current year so stats are non-empty
        $seance_id = $this->seance_model->create_theorique(
            $this->_seance_data(),
            array($this->pilote1_id)
        );
        $this->assertNotFalse($seance_id);

        $stats = $this->seance_model->get_stats_annuels_par_instructeur((int)date('Y'));
        $this->assertIsArray($stats);

        // At least one row must exist for the instructeur used in the test
        $ids = array_column($stats, 'id');
        $this->assertContains($this->instructeur_id, $ids);

        // Verify expected keys
        $row = $stats[array_search($this->instructeur_id, $ids)];
        $this->assertArrayHasKey('nb_seances_vol', $row);
        $this->assertArrayHasKey('nb_seances_sol', $row);
        $this->assertArrayHasKey('heures_vol', $row);
        $this->assertArrayHasKey('heures_sol', $row);
    }

    public function testGetStatsAnnuelsParProgrammeReturnsArray()
    {
        // Create a session for the current year
        $this->seance_model->create_theorique(
            $this->_seance_data(),
            array($this->pilote1_id)
        );

        $stats = $this->seance_model->get_stats_annuels_par_programme((int)date('Y'));
        $this->assertIsArray($stats);

        // Each row must have the expected keys
        if (!empty($stats)) {
            $row = $stats[0];
            $this->assertArrayHasKey('programme_id', $row);
            $this->assertArrayHasKey('programme_titre', $row);
            $this->assertArrayHasKey('nb_seances_vol', $row);
            $this->assertArrayHasKey('nb_seances_sol', $row);
        }
    }

    public function testCountTotalParticipantsYear()
    {
        $seance_id = $this->seance_model->create_theorique(
            $this->_seance_data(),
            array($this->pilote1_id, $this->pilote2_id)
        );
        $this->assertNotFalse($seance_id);

        $count = $this->part_model->count_total_participants_year((int)date('Y'));
        $this->assertGreaterThanOrEqual(
            count(array_unique(array($this->pilote1_id, $this->pilote2_id))),
            $count
        );
    }

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    private function _seance_data()
    {
        return array(
            'date_seance'    => date('Y-m-d'),
            'type_seance_id' => $this->type_id,
            'instructeur_id' => $this->instructeur_id,
            'pilote_id'      => null,
            'machine_id'     => null,
            'duree'          => null,
            'nb_atterrissages' => null,
            'lieu'           => 'Salle de test',
        );
    }

    private function _create_theorique_seance()
    {
        $id = $this->seance_model->create($this->_seance_data());
        $this->assertNotFalse($id);
        return $id;
    }
}
