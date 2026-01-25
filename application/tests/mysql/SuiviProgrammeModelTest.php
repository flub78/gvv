<?php

require_once __DIR__ . '/../integration/TransactionalTestCase.php';

/**
 * MySQL Integration Test for Formation_programme_model
 *
 * Tests CRUD operations and business logic for training programs.
 *
 * Usage:
 * phpunit --bootstrap application/tests/integration_bootstrap.php application/tests/mysql/SuiviProgrammeModelTest.php
 */
class SuiviProgrammeModelTest extends TransactionalTestCase
{
    /**
     * @var Formation_programme_model
     */
    private $model;

    /**
     * Set up test environment
     */
    public function setUp(): void
    {
        parent::setUp();

        if (!$this->CI->db->conn_id) {
            $this->markTestSkipped('Database connection not available');
        }

        // Load the model
        $this->CI->load->model('formation_programme_model');
        $this->model = $this->CI->formation_programme_model;
    }

    /**
     * Test create programme
     */
    public function testCreateProgramme()
    {
        $data = [
            'code' => 'SPL_TEST_' . time(),
            'titre' => 'Licence de Pilote de Planeur - Test',
            'description' => 'Programme de test',
            'contenu_markdown' => '# SPL Test\n\n## Lecon 1\n\nContenu test',
            'section_id' => null,
            'statut' => 'actif'
        ];

        $id = $this->model->create_programme($data);

        $this->assertGreaterThan(0, $id, 'Programme should be created');

        // Verify programme exists
        $programme = $this->model->get($id);
        $this->assertEquals($data['code'], $programme['code']);
        $this->assertEquals($data['titre'], $programme['titre']);
        $this->assertEquals(1, $programme['version'], 'Initial version should be 1');
        $this->assertNotEmpty($programme['date_creation']);
    }

    /**
     * Test get_by_code
     */
    public function testGetByCode()
    {
        $code = 'UNIQUE_CODE_' . time();
        $data = [
            'code' => $code,
            'titre' => 'Programme par code',
            'description' => 'Test',
            'contenu_markdown' => '# Test'
        ];

        $this->model->create_programme($data);

        $programme = $this->model->get_by_code($code);
        $this->assertNotEmpty($programme);
        $this->assertEquals($code, $programme['code']);
    }

    /**
     * Test update programme
     */
    public function testUpdateProgramme()
    {
        $data = [
            'code' => 'UPDATE_TEST_' . time(),
            'titre' => 'Titre original',
            'description' => 'Description originale',
            'contenu_markdown' => '# Original'
        ];

        $id = $this->model->create_programme($data);

        // Update
        $this->model->update_programme($id, [
            'titre' => 'Titre modifie',
            'description' => 'Description modifiee'
        ]);

        $updated = $this->model->get($id);
        $this->assertEquals('Titre modifie', $updated['titre']);
        $this->assertEquals('Description modifiee', $updated['description']);
        $this->assertNotEmpty($updated['date_modification']);
    }

    /**
     * Test increment_version
     */
    public function testIncrementVersion()
    {
        $data = [
            'code' => 'VERSION_TEST_' . time(),
            'titre' => 'Test Version',
            'description' => 'Test',
            'contenu_markdown' => '# Version 1'
        ];

        $id = $this->model->create_programme($data);

        // Initial version is 1
        $programme = $this->model->get($id);
        $this->assertEquals(1, $programme['version']);

        // Increment
        $this->model->increment_version($id);

        $updated = $this->model->get($id);
        $this->assertEquals(2, $updated['version']);
    }

    /**
     * Test archive and reactivate
     */
    public function testArchiveAndReactivate()
    {
        $data = [
            'code' => 'ARCHIVE_TEST_' . time(),
            'titre' => 'Test Archive',
            'description' => 'Test',
            'contenu_markdown' => '# Archive'
        ];

        $id = $this->model->create_programme($data);

        // Archive
        $this->model->archive($id);
        $archived = $this->model->get($id);
        $this->assertEquals('archive', $archived['statut']);

        // Reactivate
        $this->model->reactivate($id);
        $reactivated = $this->model->get($id);
        $this->assertEquals('actif', $reactivated['statut']);
    }

    /**
     * Test is_code_unique
     */
    public function testIsCodeUnique()
    {
        $code = 'UNIQUE_' . time();
        $data = [
            'code' => $code,
            'titre' => 'Test Unique',
            'description' => 'Test',
            'contenu_markdown' => '# Unique'
        ];

        $id = $this->model->create_programme($data);

        // Code should not be unique anymore
        $this->assertFalse($this->model->is_code_unique($code));

        // But should be unique when excluding the same ID
        $this->assertTrue($this->model->is_code_unique($code, $id));

        // A different code should be unique
        $this->assertTrue($this->model->is_code_unique('DEFINITELY_UNIQUE_' . time()));
    }

    /**
     * Test get_visibles (section filtering)
     */
    public function testGetVisibles()
    {
        // Create a global programme (section_id = NULL)
        $global_data = [
            'code' => 'GLOBAL_' . time(),
            'titre' => 'Programme Global',
            'description' => 'Visible par tous',
            'contenu_markdown' => '# Global',
            'section_id' => null,
            'statut' => 'actif'
        ];
        $this->model->create_programme($global_data);

        // Get visible programmes
        $visibles = $this->model->get_visibles();

        // Should find at least the global one
        $found = false;
        foreach ($visibles as $prog) {
            if ($prog['code'] == $global_data['code']) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Global programme should be visible');
    }

    /**
     * Test image method
     */
    public function testImageMethod()
    {
        $data = [
            'code' => 'IMG_TEST',
            'titre' => 'Test Image Display',
            'description' => 'Test',
            'contenu_markdown' => '# Image'
        ];

        $id = $this->model->create_programme($data);

        $image = $this->model->image($id);
        $this->assertStringContainsString('IMG_TEST', $image);
        $this->assertStringContainsString('Test Image Display', $image);
    }

    /**
     * Test select_page with filters
     */
    public function testSelectPageWithFilters()
    {
        // Create active and archived programmes
        $active_data = [
            'code' => 'ACTIVE_PAGE_' . time(),
            'titre' => 'Active Programme',
            'description' => 'Test',
            'contenu_markdown' => '# Active',
            'statut' => 'actif'
        ];
        $this->model->create_programme($active_data);

        $archived_data = [
            'code' => 'ARCHIVED_PAGE_' . time(),
            'titre' => 'Archived Programme',
            'description' => 'Test',
            'contenu_markdown' => '# Archived',
            'statut' => 'archive'
        ];
        $this->model->create_programme($archived_data);

        // Filter by active status
        $active_results = $this->model->select_page(['statut' => 'actif']);
        foreach ($active_results as $prog) {
            $this->assertEquals('actif', $prog['statut']);
        }

        // Filter by archived status
        $archived_results = $this->model->select_page(['statut' => 'archive']);
        foreach ($archived_results as $prog) {
            $this->assertEquals('archive', $prog['statut']);
        }
    }
}

/* End of file SuiviProgrammeModelTest.php */
/* Location: ./application/tests/mysql/SuiviProgrammeModelTest.php */
