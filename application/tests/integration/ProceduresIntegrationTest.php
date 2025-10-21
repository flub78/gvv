<?php
/**
 * Integration Tests for Procedures_model
 *
 * Tests CRUD operations with real database using transactions for isolation.
 *
 * Run with: ./run-tests.sh integration/ProceduresIntegrationTest
 */

require_once APPPATH . 'tests/integration_bootstrap.php';

class ProceduresIntegrationTest extends PHPUnit\Framework\TestCase
{
    protected $CI;
    protected $model;
    protected $test_section_id;
    protected $test_user_id;
    protected $test_procedures = array();
    protected $uploads_dir;

    protected function setUp(): void
    {
        $this->CI =& get_instance();

        // Load required models and libraries
        $this->CI->load->model('procedures_model');
        $this->model = $this->CI->procedures_model;

        // Start database transaction for isolation
        $this->CI->db->trans_start();

        // Create uploads directory structure for tests
        $this->uploads_dir = FCPATH . 'uploads/procedures/';
        if (!is_dir($this->uploads_dir)) {
            mkdir($this->uploads_dir, 0755, true);
        }

        // Get a test section ID from the database
        $section = $this->CI->db->select('id')->from('sections')->limit(1)->get()->row_array();
        $this->test_section_id = $section ? $section['id'] : null;

        // Get a test user ID
        $user = $this->CI->db->select('id')->from('users')->limit(1)->get()->row_array();
        $this->test_user_id = $user ? $user['id'] : null;
    }

    protected function tearDown(): void
    {
        // Clean up any test procedure directories
        foreach ($this->test_procedures as $procedure_name) {
            $proc_dir = $this->uploads_dir . $procedure_name;
            if (is_dir($proc_dir)) {
                $this->deleteDirectoryRecursive($proc_dir);
            }
        }

        // Rollback database transaction
        $this->CI->db->trans_rollback();
    }

    /**
     * Helper to recursively delete a directory
     */
    private function deleteDirectoryRecursive($dir_path)
    {
        if (!is_dir($dir_path)) {
            return false;
        }
        $files = array_diff(scandir($dir_path), array('.', '..'));
        foreach ($files as $file) {
            $path = $dir_path . DIRECTORY_SEPARATOR . $file;
            if (is_dir($path)) {
                $this->deleteDirectoryRecursive($path);
            } else {
                @unlink($path);
            }
        }
        return @rmdir($dir_path);
    }

    /**
     * Test: Create a new procedure
     */
    public function testCreateProcedure()
    {
        $procedure_data = array(
            'name' => 'test_procedure_' . time(),
            'title' => 'Test Procedure',
            'description' => 'This is a test procedure for integration testing',
            'section_id' => $this->test_section_id,
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $procedure_data['name'];

        $procedure_id = $this->model->create_procedure($procedure_data);

        $this->assertIsNumeric($procedure_id, 'create_procedure should return numeric ID');
        $this->assertGreaterThan(0, $procedure_id, 'Procedure ID should be positive');

        // Verify the procedure was created in database
        $created = $this->model->get_by_id('id', $procedure_id);
        $this->assertNotEmpty($created, 'Procedure should exist in database');
        $this->assertEquals($procedure_data['name'], $created['name']);
        $this->assertEquals($procedure_data['title'], $created['title']);
        $this->assertEquals('draft', $created['status']);

        // Verify directory was created
        $proc_dir = $this->uploads_dir . $procedure_data['name'];
        $this->assertDirectoryExists($proc_dir, 'Procedure directory should be created');

        // Verify markdown file was created
        $md_file = $proc_dir . '/procedure_' . $procedure_data['name'] . '.md';
        $this->assertFileExists($md_file, 'Initial markdown file should be created');
    }

    /**
     * Test: Create procedure with invalid name fails
     */
    public function testCreateProcedureInvalidName()
    {
        $invalid_data = array(
            'name' => 'invalid name with spaces!',
            'title' => 'Invalid Test',
            'description' => 'Should fail',
            'status' => 'draft'
        );

        $result = $this->model->create_procedure($invalid_data);

        $this->assertFalse($result, 'create_procedure should fail with invalid name');
        $this->assertNotEmpty($this->model->error, 'Error message should be set');
    }

    /**
     * Test: Create procedure with duplicate name fails
     */
    public function testCreateProcedureDuplicateName()
    {
        $procedure_data = array(
            'name' => 'test_unique_' . time(),
            'title' => 'First Procedure',
            'description' => 'First one',
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $procedure_data['name'];

        // Create first procedure
        $first_id = $this->model->create_procedure($procedure_data);
        $this->assertIsNumeric($first_id);

        // Try to create duplicate
        $duplicate_data = $procedure_data;
        $duplicate_data['title'] = 'Duplicate Attempt';

        $duplicate_id = $this->model->create_procedure($duplicate_data);

        $this->assertFalse($duplicate_id, 'Duplicate procedure name should fail');
        $this->assertNotEmpty($this->model->error, 'Error message should be set for duplicate');
    }

    /**
     * Test: Update procedure
     */
    public function testUpdateProcedure()
    {
        // Create test procedure
        $procedure_data = array(
            'name' => 'test_update_' . time(),
            'title' => 'Original Title',
            'description' => 'Original description',
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $procedure_data['name'];
        $procedure_id = $this->model->create_procedure($procedure_data);
        $this->assertIsNumeric($procedure_id);

        // Update procedure
        $update_data = array(
            'id' => $procedure_id,
            'title' => 'Updated Title',
            'description' => 'Updated description',
            'status' => 'published',
            'version' => '2.0'
        );

        $result = $this->model->update_procedure($procedure_id, $update_data);

        $this->assertTrue($result, 'update_procedure should return true');

        // Verify updates
        $updated = $this->model->get_by_id('id', $procedure_id);
        $this->assertEquals('Updated Title', $updated['title']);
        $this->assertEquals('Updated description', $updated['description']);
        $this->assertEquals('published', $updated['status']);
        $this->assertEquals('2.0', $updated['version']);
    }

    /**
     * Test: Delete procedure removes database record and files
     */
    public function testDeleteProcedure()
    {
        // Create test procedure
        $procedure_data = array(
            'name' => 'test_delete_' . time(),
            'title' => 'To Be Deleted',
            'description' => 'This will be deleted',
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $procedure_data['name'];
        $procedure_id = $this->model->create_procedure($procedure_data);
        $this->assertIsNumeric($procedure_id);

        $proc_dir = $this->uploads_dir . $procedure_data['name'];
        $this->assertDirectoryExists($proc_dir, 'Directory should exist before deletion');

        // Delete procedure
        $result = $this->model->delete_procedure($procedure_id);

        $this->assertTrue($result, 'delete_procedure should return true');

        // Verify database deletion
        $deleted = $this->model->get_by_id('id', $procedure_id);
        $this->assertEmpty($deleted, 'Procedure should be deleted from database');

        // Verify directory deletion
        $this->assertDirectoryDoesNotExist($proc_dir, 'Procedure directory should be deleted');
    }

    /**
     * Test: select_page returns procedures with section information
     */
    public function testSelectPageWithSections()
    {
        // Create test procedures
        $proc1_data = array(
            'name' => 'test_list_1_' . time(),
            'title' => 'First Procedure',
            'description' => 'Test 1',
            'section_id' => $this->test_section_id,
            'status' => 'published',
            'version' => '1.0'
        );

        $proc2_data = array(
            'name' => 'test_list_2_' . time(),
            'title' => 'Second Procedure',
            'description' => 'Test 2',
            'section_id' => null, // Global procedure
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $proc1_data['name'];
        $this->test_procedures[] = $proc2_data['name'];

        $id1 = $this->model->create_procedure($proc1_data);
        $id2 = $this->model->create_procedure($proc2_data);

        $this->assertIsNumeric($id1);
        $this->assertIsNumeric($id2);

        // Get all procedures
        $procedures = $this->model->select_page();

        $this->assertIsArray($procedures, 'select_page should return array');
        $this->assertGreaterThanOrEqual(2, count($procedures), 'Should return at least our 2 test procedures');

        // Verify structure includes section data
        $found_proc1 = false;
        foreach ($procedures as $proc) {
            if ($proc['name'] === $proc1_data['name']) {
                $found_proc1 = true;
                $this->assertArrayHasKey('section_name', $proc, 'Should include section_name');
                $this->assertArrayHasKey('has_markdown', $proc, 'Should include has_markdown flag');
                $this->assertArrayHasKey('attachments_count', $proc, 'Should include attachments_count');
                $this->assertEquals(0, $proc['attachments_count'], 'New procedure should have 0 attachments');
                break;
            }
        }

        $this->assertTrue($found_proc1, 'Should find our test procedure in results');
    }

    /**
     * Test: get_procedures_by_status filters correctly
     */
    public function testGetProceduresByStatus()
    {
        // Create procedures with different statuses
        $draft_data = array(
            'name' => 'test_draft_' . time(),
            'title' => 'Draft Procedure',
            'status' => 'draft',
            'version' => '1.0'
        );

        $published_data = array(
            'name' => 'test_published_' . time(),
            'title' => 'Published Procedure',
            'status' => 'published',
            'version' => '1.0'
        );

        $this->test_procedures[] = $draft_data['name'];
        $this->test_procedures[] = $published_data['name'];

        $draft_id = $this->model->create_procedure($draft_data);
        $published_id = $this->model->create_procedure($published_data);

        $this->assertIsNumeric($draft_id);
        $this->assertIsNumeric($published_id);

        // Get draft procedures
        $drafts = $this->model->get_procedures_by_status('draft');

        $this->assertIsArray($drafts);

        $found_draft = false;
        $found_published = false;
        foreach ($drafts as $proc) {
            if ($proc['name'] === $draft_data['name']) {
                $found_draft = true;
            }
            if ($proc['name'] === $published_data['name']) {
                $found_published = true;
            }
        }

        $this->assertTrue($found_draft, 'Should find draft procedure in drafts');
        $this->assertFalse($found_published, 'Should NOT find published procedure in drafts');
    }

    /**
     * Test: get_procedures_by_section filters by section
     */
    public function testGetProceduresBySection()
    {
        // Create section-specific and global procedures
        $section_proc_data = array(
            'name' => 'test_section_' . time(),
            'title' => 'Section Procedure',
            'section_id' => $this->test_section_id,
            'status' => 'published',
            'version' => '1.0'
        );

        $global_proc_data = array(
            'name' => 'test_global_' . time(),
            'title' => 'Global Procedure',
            'section_id' => null,
            'status' => 'published',
            'version' => '1.0'
        );

        $this->test_procedures[] = $section_proc_data['name'];
        $this->test_procedures[] = $global_proc_data['name'];

        $section_id = $this->model->create_procedure($section_proc_data);
        $global_id = $this->model->create_procedure($global_proc_data);

        $this->assertIsNumeric($section_id);
        $this->assertIsNumeric($global_id);

        // Get procedures for specific section
        $section_procedures = $this->model->get_procedures_by_section($this->test_section_id);

        $this->assertIsArray($section_procedures);

        $found_section = false;
        foreach ($section_procedures as $proc) {
            if ($proc['name'] === $section_proc_data['name']) {
                $found_section = true;
                break;
            }
        }

        $this->assertTrue($found_section, 'Should find section-specific procedure');

        // Get global procedures (section_id = null)
        $global_procedures = $this->model->get_procedures_by_section(null);

        $found_global = false;
        foreach ($global_procedures as $proc) {
            if ($proc['name'] === $global_proc_data['name']) {
                $found_global = true;
                break;
            }
        }

        $this->assertTrue($found_global, 'Should find global procedure');
    }

    /**
     * Test: Markdown content get/save operations
     */
    public function testMarkdownContentGetSave()
    {
        // Create test procedure
        $procedure_data = array(
            'name' => 'test_markdown_' . time(),
            'title' => 'Markdown Test',
            'description' => 'Testing markdown operations',
            'status' => 'draft',
            'version' => '1.0'
        );

        $this->test_procedures[] = $procedure_data['name'];
        $procedure_id = $this->model->create_procedure($procedure_data);
        $this->assertIsNumeric($procedure_id);

        // Get initial content
        $initial_content = $this->model->get_markdown_content($procedure_data['name']);

        $this->assertIsString($initial_content, 'get_markdown_content should return string');
        $this->assertStringContainsString('# Markdown Test', $initial_content, 'Should contain procedure title');

        // Save new content
        $new_content = "# Updated Procedure\n\nThis is new content.\n\n## Step 1\n\nDo something.";
        $save_result = $this->model->save_markdown_content($procedure_data['name'], $new_content);

        $this->assertTrue($save_result, 'save_markdown_content should return true');

        // Verify content was saved
        $saved_content = $this->model->get_markdown_content($procedure_data['name']);

        $this->assertEquals($new_content, $saved_content, 'Saved content should match');
    }

    /**
     * Test: Pagination works correctly
     */
    public function testSelectPagePagination()
    {
        // Create multiple test procedures
        for ($i = 1; $i <= 5; $i++) {
            $proc_data = array(
                'name' => 'test_pagination_' . $i . '_' . time(),
                'title' => "Pagination Test $i",
                'status' => 'draft',
                'version' => '1.0'
            );

            $this->test_procedures[] = $proc_data['name'];
            $id = $this->model->create_procedure($proc_data);
            $this->assertIsNumeric($id);
        }

        // Test pagination: 2 per page, starting at offset 0
        $page1 = $this->model->select_page(2, 0);

        $this->assertIsArray($page1);
        $this->assertLessThanOrEqual(2, count($page1), 'First page should have max 2 results');

        // Test second page
        $page2 = $this->model->select_page(2, 2);

        $this->assertIsArray($page2);
        $this->assertLessThanOrEqual(2, count($page2), 'Second page should have max 2 results');
    }

    /**
     * Test: Select with filters
     */
    public function testSelectPageWithFilters()
    {
        // Create test procedure
        $procedure_data = array(
            'name' => 'test_filter_' . time(),
            'title' => 'Filtered Procedure',
            'status' => 'archived',
            'version' => '3.0'
        );

        $this->test_procedures[] = $procedure_data['name'];
        $procedure_id = $this->model->create_procedure($procedure_data);
        $this->assertIsNumeric($procedure_id);

        // Filter by status
        $filtered = $this->model->select_page(0, 0, array('procedures.status' => 'archived'));

        $this->assertIsArray($filtered);

        $found = false;
        foreach ($filtered as $proc) {
            if ($proc['name'] === $procedure_data['name']) {
                $found = true;
                $this->assertEquals('archived', $proc['status']);
                break;
            }
        }

        $this->assertTrue($found, 'Should find filtered procedure');
    }
}
