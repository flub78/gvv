<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Acceptance_signatures_model
 *
 * @package tests
 * @see application/models/acceptance_signatures_model.php
 */
class AcceptanceSignaturesModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $items_model;
    protected $records_model;
    protected $test_item_ids = array();
    protected $test_record_ids = array();
    protected $test_signature_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('acceptance_items_model');
        $this->CI->load->model('acceptance_records_model');
        $this->CI->load->model('acceptance_signatures_model');
        $this->model = $this->CI->acceptance_signatures_model;
        $this->items_model = $this->CI->acceptance_items_model;
        $this->records_model = $this->CI->acceptance_records_model;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->test_signature_ids) as $id) {
            $this->db->delete('acceptance_signatures', array('id' => $id));
        }
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

    protected function createTestItem()
    {
        $login = $this->getTestLogin();
        if (!$login) {
            $this->markTestSkipped('No member in database for testing');
        }

        $id = $this->items_model->create(array(
            'title' => 'Sig Test Item ' . uniqid(),
            'category' => 'autorisation',
            'target_type' => 'external',
            'mandatory' => 0,
            'dual_validation' => 0,
            'active' => 1,
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s')
        ));
        $this->test_item_ids[] = $id;
        return $id;
    }

    protected function createTestRecord($item_id)
    {
        $id = $this->records_model->create(array(
            'item_id' => $item_id,
            'external_name' => 'External Person',
            'status' => 'pending',
            'signature_mode' => 'direct',
            'created_at' => date('Y-m-d H:i:s')
        ));
        $this->test_record_ids[] = $id;
        return $id;
    }

    // ==================== create_tactile tests ====================

    public function testCreateTactile_ReturnsId()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $signer_data = array(
            'first_name' => 'Jean',
            'last_name' => 'Dupont'
        );
        $sig_id = $this->model->create_tactile($record_id, $signer_data, 'data:image/png;base64,abc123');
        $this->test_signature_ids[] = $sig_id;

        $this->assertNotFalse($sig_id);
        $this->assertGreaterThan(0, $sig_id);
    }

    public function testCreateTactile_StoresCorrectData()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $signer_data = array(
            'first_name' => 'Marie',
            'last_name' => 'Martin'
        );
        $sig_id = $this->model->create_tactile($record_id, $signer_data, 'base64data');
        $this->test_signature_ids[] = $sig_id;

        $sig = $this->model->get_by_id('id', $sig_id);
        $this->assertEquals('Marie', $sig['signer_first_name']);
        $this->assertEquals('Martin', $sig['signer_last_name']);
        $this->assertEquals('tactile', $sig['signature_type']);
        $this->assertEquals('base64data', $sig['signature_data']);
        $this->assertNotNull($sig['signed_at']);
    }

    public function testCreateTactile_WithParentalAuthorization()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $signer_data = array(
            'first_name' => 'Pierre',
            'last_name' => 'Dupont',
            'quality' => 'pere',
            'beneficiary_first_name' => 'Lucas',
            'beneficiary_last_name' => 'Dupont'
        );
        $sig_id = $this->model->create_tactile($record_id, $signer_data, 'sig_data');
        $this->test_signature_ids[] = $sig_id;

        $sig = $this->model->get_by_id('id', $sig_id);
        $this->assertEquals('pere', $sig['signer_quality']);
        $this->assertEquals('Lucas', $sig['beneficiary_first_name']);
        $this->assertEquals('Dupont', $sig['beneficiary_last_name']);
    }

    // ==================== create_upload tests ====================

    public function testCreateUpload_StoresFileInfo()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $signer_data = array(
            'first_name' => 'Anne',
            'last_name' => 'Bernard',
            'pilot_attestation' => true
        );
        $file_info = array(
            'path' => 'uploads/acceptances/signatures/test.pdf',
            'original_filename' => 'document_signe.pdf',
            'file_size' => 12345,
            'mime_type' => 'application/pdf'
        );
        $sig_id = $this->model->create_upload($record_id, $signer_data, $file_info);
        $this->test_signature_ids[] = $sig_id;

        $sig = $this->model->get_by_id('id', $sig_id);
        $this->assertEquals('upload', $sig['signature_type']);
        $this->assertEquals('uploads/acceptances/signatures/test.pdf', $sig['file_path']);
        $this->assertEquals('document_signe.pdf', $sig['original_filename']);
        $this->assertEquals(12345, $sig['file_size']);
        $this->assertEquals('application/pdf', $sig['mime_type']);
        $this->assertEquals(1, $sig['pilot_attestation']);
    }

    // ==================== get_by_record tests ====================

    public function testGetByRecord_ReturnsSignatures()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $sig_id1 = $this->model->create_tactile($record_id,
            array('first_name' => 'A', 'last_name' => 'B'), 'data1');
        $this->test_signature_ids[] = $sig_id1;

        $sig_id2 = $this->model->create_tactile($record_id,
            array('first_name' => 'C', 'last_name' => 'D'), 'data2');
        $this->test_signature_ids[] = $sig_id2;

        $results = $this->model->get_by_record($record_id);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    // ==================== select_page tests ====================

    public function testSelectPage_IncludesJoinedFields()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $sig_id = $this->model->create_tactile($record_id,
            array('first_name' => 'Test', 'last_name' => 'User'), 'sigdata');
        $this->test_signature_ids[] = $sig_id;

        $results = $this->model->select_page();
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $sig_id) {
                $this->assertArrayHasKey('item_title', $row);
                $this->assertArrayHasKey('record_status', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ==================== image tests ====================

    public function testImage_ReturnsSignerName()
    {
        $item_id = $this->createTestItem();
        $record_id = $this->createTestRecord($item_id);

        $sig_id = $this->model->create_tactile($record_id,
            array('first_name' => 'Jean', 'last_name' => 'Dupont'), 'data');
        $this->test_signature_ids[] = $sig_id;

        $this->assertEquals('Jean Dupont', $this->model->image($sig_id));
    }

    public function testImage_EmptyKey()
    {
        $this->assertEquals('', $this->model->image(''));
    }
}
