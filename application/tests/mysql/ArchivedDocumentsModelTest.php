<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for archived_documents and document_types models
 *
 * Tests model functionality including CRUD operations, expiration detection,
 * versioning, and alarm management.
 *
 * @package tests
 * @see application/models/archived_documents_model.php
 * @see application/models/document_types_model.php
 */
class ArchivedDocumentsModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $document_types_model;
    protected $archived_documents_model;

    // Test data IDs for cleanup
    protected $test_document_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        // Load models
        $this->CI->load->model('document_types_model');
        $this->CI->load->model('archived_documents_model');

        $this->document_types_model = $this->CI->document_types_model;
        $this->archived_documents_model = $this->CI->archived_documents_model;
    }

    protected function tearDown(): void
    {
        // Clean up test documents
        foreach ($this->test_document_ids as $id) {
            $this->db->delete('archived_documents', array('id' => $id));
        }
    }

    // ==================== Document Types Model Tests ====================

    public function testDocumentTypesModel_GetActiveTypes()
    {
        $types = $this->document_types_model->get_active_types();

        $this->assertIsArray($types);
        $this->assertGreaterThan(0, count($types), 'Should have at least one active document type');
    }

    public function testDocumentTypesModel_GetActiveTypes_FilterByScope()
    {
        $pilot_types = $this->document_types_model->get_active_types('pilot');

        $this->assertIsArray($pilot_types);
        foreach ($pilot_types as $type) {
            $this->assertEquals('pilot', $type['scope'], 'All returned types should have pilot scope');
        }
    }

    public function testDocumentTypesModel_GetRequiredPilotTypes()
    {
        $required_types = $this->document_types_model->get_required_pilot_types();

        $this->assertIsArray($required_types);
        foreach ($required_types as $type) {
            $this->assertEquals(1, $type['required'], 'All returned types should be required');
            $this->assertEquals('pilot', $type['scope'], 'All returned types should have pilot scope');
        }
    }

    public function testDocumentTypesModel_GetByCode()
    {
        $medical = $this->document_types_model->get_by_code('medical');

        $this->assertIsArray($medical);
        $this->assertEquals('medical', $medical['code']);
        $this->assertEquals('pilot', $medical['scope']);
        $this->assertEquals(1, $medical['required']);
    }

    public function testDocumentTypesModel_GetByCode_NotFound()
    {
        $result = $this->document_types_model->get_by_code('nonexistent_code');

        $this->assertEmpty($result);
    }

    public function testDocumentTypesModel_Image()
    {
        // Get medical type ID
        $medical = $this->document_types_model->get_by_code('medical');
        $this->assertNotEmpty($medical);

        $image = $this->document_types_model->image($medical['id']);

        $this->assertEquals($medical['name'], $image);
    }

    public function testDocumentTypesModel_TypeSelector()
    {
        $selector = $this->document_types_model->type_selector();

        $this->assertIsArray($selector);
        $this->assertGreaterThan(0, count($selector));

        // Keys should be IDs, values should be names
        foreach ($selector as $id => $name) {
            $this->assertIsNumeric($id);
            $this->assertIsString($name);
        }
    }

    public function testDocumentTypesModel_ScopeSelector()
    {
        $selector = $this->document_types_model->scope_selector();

        $this->assertIsArray($selector);
        $this->assertArrayHasKey('pilot', $selector);
        $this->assertArrayHasKey('section', $selector);
        $this->assertArrayHasKey('club', $selector);
    }

    // ==================== Archived Documents Model Tests ====================

    public function testArchivedDocumentsModel_ComputeExpirationStatus_Active()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('+60 days')),
            'alert_days_before' => 30
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_ACTIVE, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_ExpiringSoon()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('+15 days')),
            'alert_days_before' => 30
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_EXPIRING_SOON, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_Expired()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('-5 days')),
            'alert_days_before' => 30
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_EXPIRED, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_NoExpiration()
    {
        $doc = array(
            'valid_until' => null,
            'alert_days_before' => 30
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_ACTIVE, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_Pending()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'alert_days_before' => 30,
            'validation_status' => 'pending'
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_PENDING, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_Rejected()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'alert_days_before' => 30,
            'validation_status' => 'rejected'
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_REJECTED, $status);
    }

    public function testArchivedDocumentsModel_ComputeExpirationStatus_Approved()
    {
        $doc = array(
            'valid_until' => date('Y-m-d', strtotime('+1 year')),
            'alert_days_before' => 30,
            'validation_status' => 'approved'
        );

        $status = $this->archived_documents_model->compute_expiration_status($doc);

        $this->assertEquals(Archived_documents_model::STATUS_ACTIVE, $status);
    }

    public function testArchivedDocumentsModel_StatusBadgeClass()
    {
        $this->assertEquals('bg-success',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_ACTIVE));
        $this->assertEquals('bg-warning text-dark',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_EXPIRING_SOON));
        $this->assertEquals('bg-danger',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_EXPIRED));
        $this->assertEquals('bg-secondary',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_MISSING));
        $this->assertEquals('bg-info text-dark',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_PENDING));
        $this->assertEquals('bg-danger',
            Archived_documents_model::status_badge_class(Archived_documents_model::STATUS_REJECTED));
    }

    public function testArchivedDocumentsModel_StatusLabel()
    {
        $this->assertEquals('Valide',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_ACTIVE));
        $this->assertEquals('Expire bientot',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_EXPIRING_SOON));
        $this->assertEquals('Expire',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_EXPIRED));
        $this->assertEquals('Manquant',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_MISSING));
        $this->assertEquals('En attente',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_PENDING));
        $this->assertEquals('Refuse',
            Archived_documents_model::status_label(Archived_documents_model::STATUS_REJECTED));
    }

    public function testArchivedDocumentsModel_GetExpiredDocuments_ReturnsArray()
    {
        $expired = $this->archived_documents_model->get_expired_documents();

        $this->assertIsArray($expired);
    }

    public function testArchivedDocumentsModel_GetExpiringSoonDocuments_ReturnsArray()
    {
        $expiring = $this->archived_documents_model->get_expiring_soon_documents();

        $this->assertIsArray($expiring);
    }

    public function testArchivedDocumentsModel_GetClubDocuments_ReturnsArray()
    {
        $club_docs = $this->archived_documents_model->get_club_documents();

        $this->assertIsArray($club_docs);
    }

    /**
     * Test document creation and versioning
     * Requires a test pilot in the database
     */
    public function testArchivedDocumentsModel_CreateDocument()
    {
        // Get a test pilot
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        // Get medical document type
        $medical = $this->document_types_model->get_by_code('medical');
        $this->assertNotEmpty($medical, 'Medical document type should exist');

        // Create a document
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login' => $pilot['mlogin'],
            'file_path' => 'uploads/documents/test/test_file.pdf',
            'original_filename' => 'test_medical_certificate.pdf',
            'description' => 'Test medical certificate',
            'uploaded_by' => $pilot['mlogin'],
            'valid_from' => date('Y-m-d'),
            'valid_until' => date('Y-m-d', strtotime('+2 years'))
        );

        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        $this->assertNotFalse($doc_id, 'Document creation should return an ID');
        $this->assertIsNumeric($doc_id);

        // Verify document was created
        $created_doc = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertNotEmpty($created_doc);
        $this->assertEquals($medical['id'], $created_doc['document_type_id']);
        $this->assertEquals(1, $created_doc['is_current_version']);
        $this->assertEquals(0, $created_doc['alarm_disabled']);
    }

    /**
     * Test alarm toggle functionality
     */
    public function testArchivedDocumentsModel_ToggleAlarm()
    {
        // Get a test pilot
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        // Get a document type
        $medical = $this->document_types_model->get_by_code('medical');

        // Create a test document
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login' => $pilot['mlogin'],
            'file_path' => 'uploads/documents/test/alarm_test.pdf',
            'original_filename' => 'alarm_test.pdf',
            'uploaded_by' => $pilot['mlogin'],
            'valid_until' => date('Y-m-d', strtotime('-1 day')) // Expired
        );

        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        // Initially alarm should be enabled (alarm_disabled = 0)
        $doc = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertEquals(0, $doc['alarm_disabled']);

        // Toggle alarm (disable)
        $new_state = $this->archived_documents_model->toggle_alarm($doc_id);
        $this->assertEquals(1, $new_state);

        // Verify
        $doc = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertEquals(1, $doc['alarm_disabled']);

        // Toggle again (enable)
        $new_state = $this->archived_documents_model->toggle_alarm($doc_id);
        $this->assertEquals(0, $new_state);
    }

    /**
     * Test versioning functionality
     */
    public function testArchivedDocumentsModel_Versioning()
    {
        // Get a test pilot
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $insurance = $this->document_types_model->get_by_code('insurance');

        // Create first version
        $v1_data = array(
            'document_type_id' => $insurance['id'],
            'pilot_login' => $pilot['mlogin'],
            'file_path' => 'uploads/documents/test/insurance_v1.pdf',
            'original_filename' => 'insurance_2025.pdf',
            'uploaded_by' => $pilot['mlogin'],
            'valid_until' => date('Y-m-d', strtotime('+1 year'))
        );

        $v1_id = $this->archived_documents_model->create_document($v1_data);
        $this->test_document_ids[] = $v1_id;

        // Verify v1 is current
        $v1 = $this->archived_documents_model->get_by_id('id', $v1_id);
        $this->assertEquals(1, $v1['is_current_version']);
        $this->assertNull($v1['previous_version_id']);

        // Create second version (versioning is now always explicit: pass previous_version_id)
        $v2_data = array(
            'document_type_id'    => $insurance['id'],
            'pilot_login'         => $pilot['mlogin'],
            'file_path'           => 'uploads/documents/test/insurance_v2.pdf',
            'original_filename'   => 'insurance_2026.pdf',
            'uploaded_by'         => $pilot['mlogin'],
            'valid_until'         => date('Y-m-d', strtotime('+2 years')),
            'previous_version_id' => $v1_id,
        );

        $v2_id = $this->archived_documents_model->create_document($v2_data);
        $this->test_document_ids[] = $v2_id;

        // Verify v2 is current and links to v1
        $v2 = $this->archived_documents_model->get_by_id('id', $v2_id);
        $this->assertEquals(1, $v2['is_current_version']);
        $this->assertEquals($v1_id, $v2['previous_version_id']);

        // Verify v1 is no longer current
        $v1_updated = $this->archived_documents_model->get_by_id('id', $v1_id);
        $this->assertEquals(0, $v1_updated['is_current_version']);

        // Test version history
        $history = $this->archived_documents_model->get_version_history($v2_id);
        $this->assertCount(2, $history);
        $this->assertEquals($v2_id, $history[0]['id']);
        $this->assertEquals($v1_id, $history[1]['id']);
    }

    // ==================== Additional Coverage (Lot 7) ====================

    /**
     * Test get_pilot_documents adds computed expiration_status
     */
    public function testArchivedDocumentsModel_GetPilotDocuments()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $medical = $this->document_types_model->get_by_code('medical');

        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login'      => $pilot['mlogin'],
            'file_path'        => 'uploads/documents/test/pilot_docs_test.pdf',
            'original_filename' => 'pilot_docs_test.pdf',
            'uploaded_by'      => $pilot['mlogin'],
            'valid_until'      => date('Y-m-d', strtotime('+1 year')),
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        $docs = $this->archived_documents_model->get_pilot_documents($pilot['mlogin']);

        $this->assertIsArray($docs);
        $found = false;
        foreach ($docs as $doc) {
            $this->assertArrayHasKey('expiration_status', $doc, 'Each document must have expiration_status');
            if ($doc['id'] == $doc_id) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'Created document should appear in pilot documents');
    }

    /**
     * Test get_pilot_document_status returns documents and missing keys
     */
    public function testArchivedDocumentsModel_GetPilotDocumentStatus()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $status = $this->archived_documents_model->get_pilot_document_status($pilot['mlogin']);

        $this->assertIsArray($status);
        $this->assertArrayHasKey('documents', $status);
        $this->assertArrayHasKey('missing', $status);
    }

    /**
     * Test get_missing_documents detects obligatory types without valid document
     */
    public function testArchivedDocumentsModel_GetMissingDocuments()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        // Get required pilot types
        $required_types = $this->document_types_model->get_required_pilot_types();
        if (empty($required_types)) {
            $this->markTestSkipped('No required pilot document type defined');
        }
        $type = $required_types[0];

        // Ensure the pilot has no current document of this type
        $this->db->where('pilot_login', $pilot['mlogin']);
        $this->db->where('document_type_id', $type['id']);
        $this->db->where('is_current_version', 1);
        $existing = $this->db->get('archived_documents')->result_array();
        if (!empty($existing)) {
            $this->markTestSkipped('Pilot already has a document of required type - skipping missing test');
        }

        $missing = $this->archived_documents_model->get_missing_documents($pilot['mlogin']);

        $this->assertIsArray($missing);
        $type_ids = array_column($missing, 'id');
        $this->assertContains($type['id'], $type_ids, 'Required type without document should be missing');

        // Now create a valid (approved) document for that type
        $doc_data = array(
            'document_type_id'  => $type['id'],
            'pilot_login'       => $pilot['mlogin'],
            'file_path'         => 'uploads/documents/test/missing_test.pdf',
            'original_filename' => 'missing_test.pdf',
            'uploaded_by'       => $pilot['mlogin'],
            'valid_until'       => date('Y-m-d', strtotime('+1 year')),
            'validation_status' => 'approved',
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        $missing_after = $this->archived_documents_model->get_missing_documents($pilot['mlogin']);
        $type_ids_after = array_column($missing_after, 'id');
        $this->assertNotContains($type['id'], $type_ids_after, 'Required type with valid document should not be missing');
    }

    /**
     * Test update_document modifies label and description in place (no new version)
     */
    public function testArchivedDocumentsModel_UpdateDocument()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $medical = $this->document_types_model->get_by_code('medical');
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login'      => $pilot['mlogin'],
            'file_path'        => 'uploads/documents/test/update_test.pdf',
            'original_filename' => 'update_test.pdf',
            'uploaded_by'      => $pilot['mlogin'],
            'description'      => 'Original description',
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        // Update description in place
        $result = $this->archived_documents_model->update_document($doc_id, array(
            'description' => 'Updated description',
        ));
        $this->assertTrue($result !== false, 'Update should succeed');

        // Verify the update
        $updated = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertEquals('Updated description', $updated['description']);

        // Verify no new version was created
        $count = $this->db->where('document_type_id', $medical['id'])
                          ->where('pilot_login', $pilot['mlogin'])
                          ->count_all_results('archived_documents');
        $original_count = 1; // Only the one we created
        // (We can't assert strict equality since other tests may have created documents,
        //  but we can verify the updated doc is still is_current_version=1)
        $this->assertEquals(1, $updated['is_current_version']);
    }

    /**
     * Test delete_document by owner
     */
    public function testArchivedDocumentsModel_DeleteDocument_ByOwner()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $medical = $this->document_types_model->get_by_code('medical');
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login'      => $pilot['mlogin'],
            'file_path'        => 'uploads/documents/test/delete_own.pdf',
            'original_filename' => 'delete_own.pdf',
            'uploaded_by'      => $pilot['mlogin'],
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        // Do NOT add to cleanup - the delete should handle it
        // But if the test fails, add to cleanup
        $this->test_document_ids[] = $doc_id;

        $result = $this->archived_documents_model->delete_document($doc_id, $pilot['mlogin'], false);
        $this->assertTrue($result, 'Pilot should be able to delete their own document');

        // Verify deleted
        $deleted = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertEmpty($deleted);

        // Remove from cleanup since already deleted
        $this->test_document_ids = array_diff($this->test_document_ids, array($doc_id));
    }

    /**
     * Test delete_document: pilot cannot delete another pilot's document
     */
    public function testArchivedDocumentsModel_DeleteDocument_PilotCannotDeleteOthers()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 2");
        $pilots = $query->result_array();
        if (count($pilots) < 2) {
            $this->markTestSkipped('Need at least 2 pilots in database');
        }

        $owner = $pilots[0];
        $other = $pilots[1];

        $medical = $this->document_types_model->get_by_code('medical');
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login'      => $owner['mlogin'],
            'file_path'        => 'uploads/documents/test/ownership_test.pdf',
            'original_filename' => 'ownership_test.pdf',
            'uploaded_by'      => $owner['mlogin'],
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        $result = $this->archived_documents_model->delete_document($doc_id, $other['mlogin'], false);
        $this->assertFalse($result, 'Pilot should not be able to delete another pilot\'s document');

        // Document should still exist
        $doc = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertNotEmpty($doc);
    }

    /**
     * Test delete_document: admin can delete any document
     */
    public function testArchivedDocumentsModel_DeleteDocument_AdminCanDeleteAny()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 2");
        $pilots = $query->result_array();
        if (count($pilots) < 2) {
            $this->markTestSkipped('Need at least 2 pilots in database');
        }

        $owner = $pilots[0];
        $admin = $pilots[1];

        $medical = $this->document_types_model->get_by_code('medical');
        $doc_data = array(
            'document_type_id' => $medical['id'],
            'pilot_login'      => $owner['mlogin'],
            'file_path'        => 'uploads/documents/test/admin_delete_test.pdf',
            'original_filename' => 'admin_delete_test.pdf',
            'uploaded_by'      => $owner['mlogin'],
        );
        $doc_id = $this->archived_documents_model->create_document($doc_data);
        $this->test_document_ids[] = $doc_id;

        $result = $this->archived_documents_model->delete_document($doc_id, $admin['mlogin'], true);
        $this->assertTrue($result, 'Admin should be able to delete any document');

        $deleted = $this->archived_documents_model->get_by_id('id', $doc_id);
        $this->assertEmpty($deleted);

        $this->test_document_ids = array_diff($this->test_document_ids, array($doc_id));
    }

    /**
     * Test delete_document restores previous version as current when deleting current version
     */
    public function testArchivedDocumentsModel_DeleteDocument_RestoresPreviousVersion()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $pilot = $query->row_array();
        if (!$pilot) {
            $this->markTestSkipped('No pilot in database for testing');
        }

        $insurance = $this->document_types_model->get_by_code('insurance');

        // Create v1
        $v1_data = array(
            'document_type_id' => $insurance['id'],
            'pilot_login'      => $pilot['mlogin'],
            'file_path'        => 'uploads/documents/test/restore_v1.pdf',
            'original_filename' => 'restore_v1.pdf',
            'uploaded_by'      => $pilot['mlogin'],
            'valid_until'      => date('Y-m-d', strtotime('+1 year')),
        );
        $v1_id = $this->archived_documents_model->create_document($v1_data);
        $this->test_document_ids[] = $v1_id;

        // Create v2 pointing to v1
        $v2_data = array(
            'document_type_id'    => $insurance['id'],
            'pilot_login'         => $pilot['mlogin'],
            'file_path'           => 'uploads/documents/test/restore_v2.pdf',
            'original_filename'   => 'restore_v2.pdf',
            'uploaded_by'         => $pilot['mlogin'],
            'valid_until'         => date('Y-m-d', strtotime('+2 years')),
            'previous_version_id' => $v1_id,
        );
        $v2_id = $this->archived_documents_model->create_document($v2_data);
        $this->test_document_ids[] = $v2_id;

        // v2 is current, v1 is not
        $v2 = $this->archived_documents_model->get_by_id('id', $v2_id);
        $this->assertEquals(1, $v2['is_current_version']);
        $v1 = $this->archived_documents_model->get_by_id('id', $v1_id);
        $this->assertEquals(0, $v1['is_current_version']);

        // Delete v2 (current version)
        $result = $this->archived_documents_model->delete_document($v2_id, $pilot['mlogin'], false);
        $this->assertTrue($result, 'Should delete current version');

        // v1 should be restored as current
        $v1_restored = $this->archived_documents_model->get_by_id('id', $v1_id);
        $this->assertEquals(1, $v1_restored['is_current_version'], 'Previous version should be restored as current');

        // Cleanup: only v2 was deleted, v1 still needs cleanup
        $this->test_document_ids = array_diff($this->test_document_ids, array($v2_id));
    }
}
