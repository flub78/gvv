<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Test for Formation_type_seance_model
 *
 * Tests CRUD operations, selector, activation/deactivation,
 * and the compliance query (get_eleves_non_conformes).
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php \
 *         application/tests/mysql/FormationTypeSeanceModelTest.php
 */
class FormationTypeSeanceModelTest extends TransactionalTestCase
{
    /** @var Formation_type_seance_model */
    private $model;

    public function setUp(): void
    {
        parent::setUp();

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // Verify migration 078 has run (table must exist)
        if (!$this->CI->db->table_exists('formation_types_seance')) {
            $this->markTestSkipped('Table formation_types_seance does not exist (run migration 078 first)');
        }

        $this->CI->load->model('formation_type_seance_model');
        $this->model = $this->CI->formation_type_seance_model;
    }

    // -----------------------------------------------------------------------
    // CRUD
    // -----------------------------------------------------------------------

    public function testCreateAndGet()
    {
        $id = $this->model->create(array(
            'nom'                   => 'Test type vol',
            'nature'                => 'vol',
            'description'           => 'Description test',
            'periodicite_max_jours' => null,
            'actif'                 => 1,
        ));

        $this->assertNotFalse($id, 'Create should return a valid id');

        $row = $this->model->get_by_id('id', $id);
        $this->assertEquals('Test type vol', $row['nom']);
        $this->assertEquals('vol', $row['nature']);
        $this->assertNull($row['periodicite_max_jours']);
        $this->assertEquals(1, (int)$row['actif']);
    }

    public function testCreateWithPeriodicite()
    {
        $id = $this->model->create(array(
            'nom'                   => 'Cours sol test',
            'nature'                => 'theorique',
            'description'           => '',
            'periodicite_max_jours' => 180,
            'actif'                 => 1,
        ));

        $row = $this->model->get_by_id('id', $id);
        $this->assertEquals(180, (int)$row['periodicite_max_jours']);
    }

    public function testUpdate()
    {
        $id = $this->model->create(array(
            'nom'                   => 'Avant modif',
            'nature'                => 'vol',
            'periodicite_max_jours' => null,
            'actif'                 => 1,
        ));

        $this->model->update('id', array(
            'id'                    => $id,
            'nom'                   => 'Après modif',
            'nature'                => 'theorique',
            'periodicite_max_jours' => 90,
            'actif'                 => 1,
        ));

        $row = $this->model->get_by_id('id', $id);
        $this->assertEquals('Après modif', $row['nom']);
        $this->assertEquals('theorique', $row['nature']);
        $this->assertEquals(90, (int)$row['periodicite_max_jours']);
    }

    public function testDelete()
    {
        $id = $this->model->create(array(
            'nom'    => 'À supprimer',
            'nature' => 'vol',
            'actif'  => 1,
        ));

        $this->model->delete(array('id' => $id));
        $row = $this->model->get_by_id('id', $id);
        $this->assertEmpty($row, 'Row should not exist after delete');
    }

    // -----------------------------------------------------------------------
    // Selectors
    // -----------------------------------------------------------------------

    public function testGetAll()
    {
        $before = count($this->model->get_all());
        $this->model->create(array('nom' => 'T1', 'nature' => 'vol',       'actif' => 1));
        $this->model->create(array('nom' => 'T2', 'nature' => 'theorique', 'actif' => 1));
        $after = count($this->model->get_all());
        $this->assertEquals($before + 2, $after);
    }

    public function testGetActiveFiltersByNature()
    {
        $this->model->create(array('nom' => 'vol-actif',  'nature' => 'vol',       'actif' => 1));
        $this->model->create(array('nom' => 'sol-actif',  'nature' => 'theorique', 'actif' => 1));
        $this->model->create(array('nom' => 'vol-inactif','nature' => 'vol',       'actif' => 0));

        $vols = $this->model->get_active('vol');
        foreach ($vols as $row) {
            $this->assertEquals('vol', $row['nature']);
            $this->assertEquals(1, (int)$row['actif']);
        }

        $sols = $this->model->get_active('theorique');
        foreach ($sols as $row) {
            $this->assertEquals('theorique', $row['nature']);
        }
    }

    public function testGetSelector()
    {
        $id = $this->model->create(array('nom' => 'SelectorTest', 'nature' => 'theorique', 'actif' => 1));
        $selector = $this->model->get_selector();
        $this->assertArrayHasKey($id, $selector);
        $this->assertEquals('SelectorTest', $selector[$id]);
    }

    // -----------------------------------------------------------------------
    // Deactivation / is_in_use
    // -----------------------------------------------------------------------

    public function testDeactivate()
    {
        $id = $this->model->create(array('nom' => 'Actif', 'nature' => 'vol', 'actif' => 1));
        $this->model->deactivate($id);
        $row = $this->model->get_by_id('id', $id);
        $this->assertEquals(0, (int)$row['actif']);
    }

    public function testIsInUseReturnsFalseForUnusedType()
    {
        $id = $this->model->create(array('nom' => 'Inutilisé', 'nature' => 'vol', 'actif' => 1));
        $this->assertFalse($this->model->is_in_use($id));
    }

    // -----------------------------------------------------------------------
    // Periodicite
    // -----------------------------------------------------------------------

    public function testGetWithPeriodicite()
    {
        $id1 = $this->model->create(array('nom' => 'Avec seuil', 'nature' => 'theorique', 'periodicite_max_jours' => 365, 'actif' => 1));
        $id2 = $this->model->create(array('nom' => 'Sans seuil', 'nature' => 'theorique', 'periodicite_max_jours' => null, 'actif' => 1));

        $with_seuil = $this->model->get_with_periodicite();
        $ids = array_column($with_seuil, 'id');

        $this->assertContains($id1, $ids);
        $this->assertNotContains($id2, $ids);
    }

    public function testGetElevesNonConformesReturnsEmptyForTypeWithoutSeuil()
    {
        $id = $this->model->create(array('nom' => 'Sans seuil', 'nature' => 'theorique', 'periodicite_max_jours' => null, 'actif' => 1));
        $result = $this->model->get_eleves_non_conformes($id);
        $this->assertEmpty($result, 'Should return empty array when no periodicite defined');
    }
}
