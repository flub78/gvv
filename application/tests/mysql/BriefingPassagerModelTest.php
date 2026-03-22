<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for briefing passager model methods
 *
 * Tests get_briefing_by_vld(), get_briefings_recent(), get_consignes_by_section()
 *
 * @package tests
 * @see application/models/archived_documents_model.php
 */
class BriefingPassagerModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $test_doc_ids = array();
    protected $briefing_type_id;
    protected $vld_id;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
        $this->CI->load->model('archived_documents_model');
        $this->model = $this->CI->archived_documents_model;

        // Get briefing_passager document type
        $row = $this->db->query(
            "SELECT id FROM document_types WHERE code='briefing_passager' AND section_id IS NULL LIMIT 1"
        )->row_array();
        if (!$row) {
            $this->markTestSkipped('briefing_passager document type not found — run migration 087');
        }
        $this->briefing_type_id = $row['id'];

        // Get an existing VLD to attach briefings to
        $vld = $this->db->query("SELECT id FROM vols_decouverte LIMIT 1")->row_array();
        if (!$vld) {
            $this->markTestSkipped('No vols_decouverte records found');
        }
        $this->vld_id = $vld['id'];
    }

    protected function tearDown(): void
    {
        foreach ($this->test_doc_ids as $id) {
            $this->db->delete('archived_documents', array('id' => $id));
        }
    }

    private function createBriefingDoc($vld_id, $uploaded_at = null)
    {
        $data = array(
            'document_type_id'  => $this->briefing_type_id,
            'vld_id'            => $vld_id,
            'file_path'         => 'uploads/documents/test/briefing_test.pdf',
            'original_filename' => 'briefing_test.pdf',
            'description'       => 'Briefing test',
            'uploaded_by'       => 'test_user',
            'is_current_version' => 1,
            'validation_status' => 'pending',
            'alarm_disabled'    => 0,
            'uploaded_at'       => $uploaded_at ?: date('Y-m-d H:i:s'),
        );
        $id = $this->model->create_document($data);
        $this->test_doc_ids[] = $id;
        return $id;
    }

    // --- get_briefing_by_vld ---

    public function testGetBriefingByVld_ReturnsNullWhenNone()
    {
        // Use a VLD ID that certainly has no briefing
        $result = $this->model->get_briefing_by_vld(0);
        $this->assertNull($result);
    }

    public function testGetBriefingByVld_ReturnsDocumentAfterInsert()
    {
        $doc_id = $this->createBriefingDoc($this->vld_id);
        $this->assertNotFalse($doc_id);

        $result = $this->model->get_briefing_by_vld($this->vld_id);
        $this->assertNotNull($result);
        $this->assertEquals($doc_id, $result['id']);
        $this->assertEquals($this->vld_id, $result['vld_id']);
        $this->assertEquals('briefing_passager', $result['type_code']);
    }

    public function testGetBriefingByVld_ReturnsCurrentVersionOnly()
    {
        // Find and temporarily mark any existing current briefing as non-current
        $displaced = $this->db->query(
            "SELECT id FROM archived_documents WHERE vld_id = ? AND is_current_version = 1",
            array($this->vld_id)
        )->result_array();
        $displaced_ids = array_column($displaced, 'id');
        if (!empty($displaced_ids)) {
            $this->db->where_in('id', $displaced_ids)
                     ->update('archived_documents', array('is_current_version' => 0));
        }

        // Create an old non-current version
        $data_old = array(
            'document_type_id'  => $this->briefing_type_id,
            'vld_id'            => $this->vld_id,
            'file_path'         => 'uploads/documents/test/old.pdf',
            'original_filename' => 'old.pdf',
            'uploaded_by'       => 'test_user',
            'is_current_version' => 0,
            'validation_status' => 'pending',
            'alarm_disabled'    => 0,
            'uploaded_at'       => date('Y-m-d H:i:s'),
        );
        $this->db->insert('archived_documents', $data_old);
        $old_id = $this->db->insert_id();
        $this->test_doc_ids[] = $old_id;

        // No current version → should return null
        $result = $this->model->get_briefing_by_vld($this->vld_id);

        // Restore displaced docs
        if (!empty($displaced_ids)) {
            $this->db->where_in('id', $displaced_ids)
                     ->update('archived_documents', array('is_current_version' => 1));
        }

        $this->assertNull($result);
    }

    // --- get_briefings_recent ---

    public function testGetBriefingsRecent_ReturnsRecentBriefings()
    {
        $doc_id = $this->createBriefingDoc($this->vld_id);

        $results = $this->model->get_briefings_recent(90);
        $this->assertIsArray($results);

        $ids = array_column($results, 'id');
        $this->assertContains($doc_id, $ids, 'Recently created briefing should appear in results');
    }

    public function testGetBriefingsRecent_ExcludesOldBriefings()
    {
        // Create a briefing with a date 100 days ago
        $old_date = date('Y-m-d H:i:s', strtotime('-100 days'));
        $doc_id   = $this->createBriefingDoc($this->vld_id, $old_date);
        // Force the uploaded_at to the old date (create_document overwrites it)
        $this->db->where('id', $doc_id)->update('archived_documents', array('uploaded_at' => $old_date));

        $results = $this->model->get_briefings_recent(90);
        $ids = array_column($results, 'id');
        $this->assertNotContains($doc_id, $ids, 'Briefing older than 90 days should not appear');
    }

    public function testGetBriefingsRecent_ReturnsFlightContext()
    {
        $doc_id = $this->createBriefingDoc($this->vld_id);

        $results = $this->model->get_briefings_recent(90);
        $found = null;
        foreach ($results as $r) {
            if ($r['id'] == $doc_id) {
                $found = $r;
                break;
            }
        }
        $this->assertNotNull($found, 'Created briefing should be in results');
        $this->assertArrayHasKey('date_vol', $found);
        $this->assertArrayHasKey('airplane_immat', $found);
        $this->assertArrayHasKey('beneficiaire', $found);
    }
}

/* End of file BriefingPassagerModelTest.php */
/* Location: ./application/tests/mysql/BriefingPassagerModelTest.php */
