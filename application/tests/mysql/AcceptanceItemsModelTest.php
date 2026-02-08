<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Acceptance_items_model
 *
 * @package tests
 * @see application/models/acceptance_items_model.php
 */
class AcceptanceItemsModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $test_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('acceptance_items_model');
        $this->model = $this->CI->acceptance_items_model;
    }

    protected function tearDown(): void
    {
        // Clean up test data in reverse order
        foreach (array_reverse($this->test_ids) as $id) {
            $this->db->delete('acceptance_items', array('id' => $id));
        }
    }

    /**
     * Helper to get a valid member login from the database
     */
    protected function getTestLogin()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $row = $query->row_array();
        return $row ? $row['mlogin'] : null;
    }

    /**
     * Helper to create a test item
     */
    protected function createTestItem($overrides = array())
    {
        $login = $this->getTestLogin();
        if (!$login) {
            $this->markTestSkipped('No member in database for testing');
        }

        $data = array_merge(array(
            'title' => 'Test Item ' . uniqid(),
            'category' => 'document',
            'target_type' => 'internal',
            'mandatory' => 0,
            'dual_validation' => 0,
            'active' => 1,
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s')
        ), $overrides);

        $id = $this->model->create($data);
        $this->assertNotFalse($id, 'Failed to create test item');
        $this->test_ids[] = $id;
        return $id;
    }

    // ==================== CRUD tests ====================

    public function testCreate_ReturnsId()
    {
        $id = $this->createTestItem();
        $this->assertGreaterThan(0, $id);
    }

    public function testGetById_ReturnsCorrectItem()
    {
        $id = $this->createTestItem(array('title' => 'Test GetById'));
        $item = $this->model->get_by_id('id', $id);

        $this->assertNotEmpty($item);
        $this->assertEquals('Test GetById', $item['title']);
        $this->assertEquals('document', $item['category']);
    }

    public function testUpdate_ModifiesItem()
    {
        $id = $this->createTestItem();
        $this->model->update('id', array(
            'id' => $id,
            'title' => 'Updated Title',
            'updated_at' => date('Y-m-d H:i:s')
        ));

        $item = $this->model->get_by_id('id', $id);
        $this->assertEquals('Updated Title', $item['title']);
    }

    public function testDelete_RemovesItem()
    {
        $id = $this->createTestItem();
        $this->model->delete(array('id' => $id));

        $item = $this->model->get_by_id('id', $id);
        $this->assertEmpty($item);

        // Remove from cleanup list since already deleted
        $this->test_ids = array_diff($this->test_ids, array($id));
    }

    // ==================== select_page tests ====================

    public function testSelectPage_ReturnsArray()
    {
        $this->createTestItem();
        $results = $this->model->select_page();

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }

    public function testSelectPage_ContainsCreatorName()
    {
        $id = $this->createTestItem();
        $results = $this->model->select_page();

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) {
                $this->assertArrayHasKey('created_by_name', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Test item should appear in select_page results');
    }

    public function testSelectPage_WithFilter()
    {
        $id = $this->createTestItem(array('category' => 'briefing'));
        $results = $this->model->select_page(0, 0, array('acceptance_items.category' => 'briefing'));

        $this->assertIsArray($results);
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ==================== get_active_items tests ====================

    public function testGetActiveItems_ReturnsOnlyActive()
    {
        $active_id = $this->createTestItem(array('active' => 1));
        $inactive_id = $this->createTestItem(array('active' => 0));

        $results = $this->model->get_active_items();

        $active_found = false;
        $inactive_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $active_id) $active_found = true;
            if ($row['id'] == $inactive_id) $inactive_found = true;
        }

        $this->assertTrue($active_found, 'Active item should be returned');
        $this->assertFalse($inactive_found, 'Inactive item should not be returned');
    }

    public function testGetActiveItems_FilterByCategory()
    {
        $doc_id = $this->createTestItem(array('category' => 'document'));
        $formation_id = $this->createTestItem(array('category' => 'formation'));

        $results = $this->model->get_active_items('formation');

        $doc_found = false;
        $formation_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $doc_id) $doc_found = true;
            if ($row['id'] == $formation_id) $formation_found = true;
        }

        $this->assertTrue($formation_found, 'Formation item should be returned');
        $this->assertFalse($doc_found, 'Document item should not be returned when filtering by formation');
    }

    // ==================== get_overdue_items tests ====================

    public function testGetOverdueItems_ReturnsExpiredDeadlines()
    {
        $overdue_id = $this->createTestItem(array(
            'deadline' => '2020-01-01',
            'active' => 1
        ));
        $future_id = $this->createTestItem(array(
            'deadline' => '2030-12-31',
            'active' => 1
        ));

        $results = $this->model->get_overdue_items();

        $overdue_found = false;
        $future_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $overdue_id) $overdue_found = true;
            if ($row['id'] == $future_id) $future_found = true;
        }

        $this->assertTrue($overdue_found, 'Overdue item should be returned');
        $this->assertFalse($future_found, 'Future deadline item should not be returned');
    }

    // ==================== image & selector tests ====================

    public function testImage_ReturnsTitle()
    {
        $id = $this->createTestItem(array('title' => 'My Test Document'));
        $this->assertEquals('My Test Document', $this->model->image($id));
    }

    public function testImage_EmptyKey()
    {
        $this->assertEquals('', $this->model->image(''));
    }

    public function testSelector_ReturnsArray()
    {
        $id = $this->createTestItem(array('title' => 'Selector Test'));
        $result = $this->model->selector();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('', $result, 'Selector should have empty option');
        $this->assertArrayHasKey($id, $result);
        $this->assertEquals('Selector Test', $result[$id]);
    }

    // ==================== Category enum tests ====================

    public function testCreate_AllCategories()
    {
        $categories = array('document', 'formation', 'controle', 'briefing', 'autorisation');
        foreach ($categories as $cat) {
            $id = $this->createTestItem(array('category' => $cat));
            $item = $this->model->get_by_id('id', $id);
            $this->assertEquals($cat, $item['category']);
        }
    }

    public function testCreate_BothTargetTypes()
    {
        $internal_id = $this->createTestItem(array('target_type' => 'internal'));
        $external_id = $this->createTestItem(array('target_type' => 'external'));

        $internal = $this->model->get_by_id('id', $internal_id);
        $external = $this->model->get_by_id('id', $external_id);

        $this->assertEquals('internal', $internal['target_type']);
        $this->assertEquals('external', $external['target_type']);
    }
}
