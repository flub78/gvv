<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for the Deadlines Calendar feature.
 *
 * Tests the get_documents_for_calendar() model method and the colour-mapping
 * logic used by the deadlines_calendar controller.
 *
 * @package tests
 * @see application/controllers/deadlines_calendar.php
 * @see application/models/archived_documents_model.php
 */
class DeadlinesCalendarEventsTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $test_ids = array();

    // ---- colour constants (same as controller) ----
    const COLOR_EXPIRED        = '#dc3545';
    const COLOR_EXPIRING_SOON  = '#fd7e14';
    const COLOR_ACTIVE         = '#198754';

    protected function setUp(): void
    {
        $this->CI  =& get_instance();
        $this->db  = $this->CI->db;
        $this->CI->load->model('archived_documents_model');
        $this->model = $this->CI->archived_documents_model;
    }

    protected function tearDown(): void
    {
        foreach ($this->test_ids as $id) {
            $this->db->delete('archived_documents', array('id' => $id));
        }
    }

    // ------------------------------------------------------------------ helpers

    private function get_test_type_id()
    {
        $row = $this->db->query("SELECT id FROM document_types LIMIT 1")->row_array();
        if (!$row) {
            $this->markTestSkipped('No document type in database');
        }
        return $row['id'];
    }

    private function insert_test_doc($valid_until, $validation_status = 'approved', $is_current = 1)
    {
        $type_id = $this->get_test_type_id();
        $data = array(
            'document_type_id'  => $type_id,
            'file_path'         => 'uploads/test/deadlines_test.pdf',
            'original_filename' => 'deadlines_test.pdf',
            'description'       => 'Test doc for DeadlinesCalendarEventsTest',
            'uploaded_by'       => 'testadmin',
            'uploaded_at'       => date('Y-m-d H:i:s'),
            'valid_until'       => $valid_until,
            'is_current_version'=> $is_current,
            'validation_status' => $validation_status,
        );
        $this->db->insert('archived_documents', $data);
        $id = $this->db->insert_id();
        $this->test_ids[] = $id;
        return $id;
    }

    // ------------------------------------------------------------------ tests

    public function testGetDocumentsForCalendar_ReturnsArray()
    {
        $result = $this->model->get_documents_for_calendar();
        $this->assertIsArray($result);
    }

    public function testGetDocumentsForCalendar_IncludesApprovedDocWithExpiration()
    {
        $valid_until = date('Y-m-d', strtotime('+60 days'));
        $id = $this->insert_test_doc($valid_until, 'approved');

        $docs = $this->model->get_documents_for_calendar();
        $ids  = array_column($docs, 'id');
        $this->assertContains((string)$id, array_map('strval', $ids),
            'Approved document with valid_until should appear in calendar');
    }

    public function testGetDocumentsForCalendar_ExcludesNullValidUntil()
    {
        $id = $this->insert_test_doc(null, 'approved');

        $docs = $this->model->get_documents_for_calendar();
        $ids  = array_column($docs, 'id');
        $this->assertNotContains((string)$id, array_map('strval', $ids),
            'Document without valid_until must not appear in calendar');
    }

    public function testGetDocumentsForCalendar_ExcludesPendingDocument()
    {
        $valid_until = date('Y-m-d', strtotime('+60 days'));
        $id = $this->insert_test_doc($valid_until, 'pending');

        $docs = $this->model->get_documents_for_calendar();
        $ids  = array_column($docs, 'id');
        $this->assertNotContains((string)$id, array_map('strval', $ids),
            'Pending document must not appear in calendar');
    }

    public function testGetDocumentsForCalendar_ExcludesRejectedDocument()
    {
        $valid_until = date('Y-m-d', strtotime('+60 days'));
        $id = $this->insert_test_doc($valid_until, 'rejected');

        $docs = $this->model->get_documents_for_calendar();
        $ids  = array_column($docs, 'id');
        $this->assertNotContains((string)$id, array_map('strval', $ids),
            'Rejected document must not appear in calendar');
    }

    public function testGetDocumentsForCalendar_DateRangeFilter_Start()
    {
        $far_past   = date('Y-m-d', strtotime('-2 years'));
        $near_future = date('Y-m-d', strtotime('+30 days'));
        $id_old  = $this->insert_test_doc($far_past,    'approved');
        $id_new  = $this->insert_test_doc($near_future, 'approved');

        $today = date('Y-m-d');
        $docs = $this->model->get_documents_for_calendar($today, null);
        $ids  = array_column($docs, 'id');
        $this->assertNotContains((string)$id_old, array_map('strval', $ids),
            'Old document must be filtered out by start date');
        $this->assertContains((string)$id_new, array_map('strval', $ids),
            'Future document must be included when start date is today');
    }

    public function testGetDocumentsForCalendar_DateRangeFilter_End()
    {
        $near_future = date('Y-m-d', strtotime('+10 days'));
        $far_future  = date('Y-m-d', strtotime('+2 years'));
        $id_near = $this->insert_test_doc($near_future, 'approved');
        $id_far  = $this->insert_test_doc($far_future,  'approved');

        $end = date('Y-m-d', strtotime('+30 days'));
        $docs = $this->model->get_documents_for_calendar(null, $end);
        $ids  = array_column($docs, 'id');
        $this->assertContains((string)$id_near, array_map('strval', $ids),
            'Near document must be included before end date');
        $this->assertNotContains((string)$id_far, array_map('strval', $ids),
            'Far-future document must be filtered out by end date');
    }

    // ------------------------------------------------------------------ colour mapping

    private function status_to_color($status)
    {
        if ($status === Archived_documents_model::STATUS_EXPIRED)       return self::COLOR_EXPIRED;
        if ($status === Archived_documents_model::STATUS_EXPIRING_SOON) return self::COLOR_EXPIRING_SOON;
        return self::COLOR_ACTIVE;
    }

    public function testColorMapping_Expired()
    {
        $doc    = array('valid_until' => date('Y-m-d', strtotime('-1 day')), 'alert_days_before' => 30);
        $status = $this->model->compute_expiration_status($doc);
        $this->assertEquals(self::COLOR_EXPIRED, $this->status_to_color($status));
    }

    public function testColorMapping_ExpiringSoon()
    {
        $doc    = array('valid_until' => date('Y-m-d', strtotime('+15 days')), 'alert_days_before' => 30);
        $status = $this->model->compute_expiration_status($doc);
        $this->assertEquals(self::COLOR_EXPIRING_SOON, $this->status_to_color($status));
    }

    public function testColorMapping_Active()
    {
        $doc    = array('valid_until' => date('Y-m-d', strtotime('+90 days')), 'alert_days_before' => 30);
        $status = $this->model->compute_expiration_status($doc);
        $this->assertEquals(self::COLOR_ACTIVE, $this->status_to_color($status));
    }
}
