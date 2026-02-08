<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Acceptance_records_model
 *
 * @package tests
 * @see application/models/acceptance_records_model.php
 */
class AcceptanceRecordsModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $items_model;
    protected $test_item_ids = array();
    protected $test_record_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('acceptance_items_model');
        $this->CI->load->model('acceptance_records_model');
        $this->model = $this->CI->acceptance_records_model;
        $this->items_model = $this->CI->acceptance_items_model;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->test_record_ids) as $id) {
            $this->db->delete('acceptance_records', array('id' => $id));
        }
        foreach (array_reverse($this->test_item_ids) as $id) {
            $this->db->delete('acceptance_items', array('id' => $id));
        }
    }

    protected function getTestLogin()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $row = $query->row_array();
        return $row ? $row['mlogin'] : null;
    }

    protected function getSecondTestLogin()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1 OFFSET 1");
        $row = $query->row_array();
        return $row ? $row['mlogin'] : null;
    }

    protected function createTestItem()
    {
        $login = $this->getTestLogin();
        if (!$login) {
            $this->markTestSkipped('No member in database for testing');
        }

        $id = $this->items_model->create(array(
            'title' => 'Test Item ' . uniqid(),
            'category' => 'document',
            'target_type' => 'internal',
            'mandatory' => 0,
            'dual_validation' => 0,
            'active' => 1,
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s')
        ));
        $this->test_item_ids[] = $id;
        return $id;
    }

    protected function createTestRecord($item_id, $overrides = array())
    {
        $login = $this->getTestLogin();
        $data = array_merge(array(
            'item_id' => $item_id,
            'user_login' => $login,
            'status' => 'pending',
            'created_at' => date('Y-m-d H:i:s')
        ), $overrides);

        $id = $this->model->create($data);
        $this->assertNotFalse($id, 'Failed to create test record');
        $this->test_record_ids[] = $id;
        return $id;
    }

    // ==================== CRUD tests ====================

    public function testCreate_ReturnsId()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);
        $this->assertGreaterThan(0, $record_id);
    }

    public function testGetById_ReturnsCorrectRecord()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $record = $this->model->get_by_id('id', $record_id);
        $this->assertNotEmpty($record);
        $this->assertEquals($item_id, $record['item_id']);
        $this->assertEquals('pending', $record['status']);
    }

    // ==================== select_page tests ====================

    public function testSelectPage_ReturnsArrayWithJoins()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $results = $this->model->select_page();
        $this->assertIsArray($results);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $record_id) {
                $this->assertArrayHasKey('item_title', $row);
                $this->assertArrayHasKey('pilot_nom', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Test record should appear in select_page');
    }

    // ==================== get_by_user tests ====================

    public function testGetByUser_ReturnsUserRecords()
    {
        $login = $this->getTestLogin();
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $results = $this->model->get_by_user($login);
        $this->assertIsArray($results);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $record_id) {
                $this->assertArrayHasKey('item_title', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testGetByUser_FiltersByStatus()
    {
        $login = $this->getTestLogin();
        $item_id = $this->createTestItem();
        $pending_id = $this->createTestRecord($item_id, array('status' => 'pending'));
        $accepted_id = $this->createTestRecord($item_id, array(
            'status' => 'accepted',
            'acted_at' => date('Y-m-d H:i:s')
        ));

        $results = $this->model->get_by_user($login, 'pending');
        $pending_found = false;
        $accepted_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $pending_id) $pending_found = true;
            if ($row['id'] == $accepted_id) $accepted_found = true;
        }

        $this->assertTrue($pending_found);
        $this->assertFalse($accepted_found);
    }

    // ==================== get_by_item tests ====================

    public function testGetByItem_ReturnsItemRecords()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $results = $this->model->get_by_item($item_id);
        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $record_id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ==================== pending tests ====================

    public function testGetPendingForUser_ReturnsPendingOnly()
    {
        $login = $this->getTestLogin();
        $item_id = $this->createTestItem();
        $pending_id = $this->createTestRecord($item_id, array('status' => 'pending'));

        $results = $this->model->get_pending_for_user($login);
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $pending_id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    public function testCountPendingForUser()
    {
        $login = $this->getTestLogin();
        $item_id = $this->createTestItem();

        $count_before = $this->model->count_pending_for_user($login);

        $this->createTestRecord($item_id, array('status' => 'pending'));
        $this->createTestRecord($item_id, array('status' => 'pending'));

        $count_after = $this->model->count_pending_for_user($login);
        $this->assertEquals($count_before + 2, $count_after);
    }

    // ==================== accept/refuse tests ====================

    public function testAccept_UpdatesStatusAndTimestamp()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $formula = 'Je reconnais avoir lu et accepte le document';
        $result = $this->model->accept($record_id, $formula);
        $this->assertTrue($result);

        $record = $this->model->get_by_id('id', $record_id);
        $this->assertEquals('accepted', $record['status']);
        $this->assertEquals($formula, $record['formula_text']);
        $this->assertNotNull($record['acted_at']);
    }

    public function testRefuse_UpdatesStatus()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $result = $this->model->refuse($record_id);
        $this->assertTrue($result);

        $record = $this->model->get_by_id('id', $record_id);
        $this->assertEquals('refused', $record['status']);
        $this->assertNotNull($record['acted_at']);
    }

    // ==================== link_to_pilot tests ====================

    public function testLinkToPilot_SetsLinkedFields()
    {
        $login = $this->getTestLogin();
        $second_login = $this->getSecondTestLogin();
        if (!$second_login) {
            $this->markTestSkipped('Need at least 2 members for pilot linking test');
        }

        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id, array(
            'user_login' => null,
            'external_name' => 'External Person'
        ));

        $result = $this->model->link_to_pilot($record_id, $second_login, $login);
        $this->assertTrue($result);

        $record = $this->model->get_by_id('id', $record_id);
        $this->assertEquals($second_login, $record['linked_pilot_login']);
        $this->assertEquals($login, $record['linked_by']);
        $this->assertNotNull($record['linked_at']);
    }

    public function testGetLinkedRecords_ReturnsLinkedOnly()
    {
        $login = $this->getTestLogin();
        $second_login = $this->getSecondTestLogin();
        if (!$second_login) {
            $this->markTestSkipped('Need at least 2 members for linked records test');
        }

        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id, array(
            'user_login' => null,
            'external_name' => 'External Person'
        ));

        // Link to pilot
        $this->model->link_to_pilot($record_id, $second_login, $login);

        $results = $this->model->get_linked_records($second_login);
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $record_id) {
                $this->assertArrayHasKey('item_title', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ==================== image tests ====================

    public function testImage_ReturnsFormattedString()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $result = $this->model->image($record_id);
        $this->assertIsString($result);
        $this->assertStringContainsString('pending', $result);
    }

    public function testImage_EmptyKey()
    {
        $this->assertEquals('', $this->model->image(''));
    }

    // ==================== External record tests ====================

    public function testCreate_ExternalRecord()
    {
        $login = $this->getTestLogin();
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id, array(
            'user_login' => null,
            'external_name' => 'Jean Dupont',
            'initiated_by' => $login,
            'signature_mode' => 'link'
        ));

        $record = $this->model->get_by_id('id', $record_id);
        $this->assertNull($record['user_login']);
        $this->assertEquals('Jean Dupont', $record['external_name']);
        $this->assertEquals('link', $record['signature_mode']);
    }
}
