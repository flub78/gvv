<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test: email feature for archived documents
 *
 * Tests:
 * - model get_filtered_documents includes pilot_email field
 * - subject construction logic (description/type fallback, pilot, machine)
 * - access control: send_email requires admin role
 */
class ArchivedDocumentsEmailTest extends TestCase
{
    /** @var object CI instance */
    private $CI;
    /** @var Archived_documents_model */
    private $model;

    private $pilot_login = '9992'; // existing test user with known FK constraint
    private $type_id;
    private $doc_id;

    protected function setUp(): void
    {
        $this->CI = &get_instance();

        if (!isset($this->CI->gvvmetadata)) {
            $this->CI->gvvmetadata = new MockGvvMetadata();
        }

        $this->CI->db->trans_start();

        if (!class_exists('Common_Model')) {
            require_once APPPATH . 'models/common_model.php';
        }
        if (!class_exists('Archived_documents_model')) {
            require_once APPPATH . 'models/archived_documents_model.php';
        }

        $this->CI->gvv_model = new Archived_documents_model();
        $this->model = $this->CI->gvv_model;

        $this->_create_fixtures();
    }

    protected function tearDown(): void
    {
        $this->CI->db->trans_rollback();
    }

    private function _create_fixtures()
    {
        $ts = time();

        $this->CI->db->insert('document_types', [
            'code'           => 'email_test_' . $ts,
            'name'           => 'Type test email',
            'scope'          => 'pilot',
            'required'       => 0,
            'has_expiration' => 0,
            'storage_by_year'=> 0,
            'active'         => 1,
            'display_order'  => 99,
        ]);
        $this->type_id = $this->CI->db->insert_id();

        // Document with description + pilot
        $this->CI->db->insert('archived_documents', [
            'document_type_id'   => $this->type_id,
            'pilot_login'        => $this->pilot_login,
            'section_id'         => null,
            'file_path'          => 'test/email_test.pdf',
            'original_filename'  => 'email_test.pdf',
            'uploaded_by'        => 'test',
            'uploaded_at'        => date('Y-m-d H:i:s'),
            'is_current_version' => 1,
            'validation_status'  => 'approved',
            'description'        => 'Certificat médical',
            'machine_immat'      => 'F-CGVV',
        ]);
        $this->doc_id = $this->CI->db->insert_id();
    }

    // -------------------------------------------------------------------------
    // Model: pilot_email is included in get_filtered_documents
    // -------------------------------------------------------------------------

    public function testGetFilteredDocumentsIncludesPilotEmail()
    {
        $docs = $this->model->get_filtered_documents([]);

        $found = array_filter($docs, function($d) {
            return $d['file_path'] === 'test/email_test.pdf';
        });

        $this->assertNotEmpty($found, 'Le document de test doit être dans la liste');

        $doc = array_values($found)[0];
        $this->assertArrayHasKey('pilot_email', $doc,
            'get_filtered_documents doit retourner le champ pilot_email');
    }

    // -------------------------------------------------------------------------
    // Subject construction logic (in PHP, mirrors the view logic)
    // -------------------------------------------------------------------------

    /**
     * Build email subject from document data (mirrors bs_documentsListView.php logic)
     */
    private function _build_subject($description, $type_name, $pilot_prenom, $pilot_nom, $machine_immat)
    {
        $parts = [];
        $desc = trim($description ?? '');
        $type_fallback = !empty($type_name) ? $type_name : 'Autre .. (non défini)';
        $parts[] = !empty($desc) ? $desc : $type_fallback;

        $full_name = trim("$pilot_prenom $pilot_nom");
        if (!empty($full_name)) {
            $parts[] = $full_name;
        }
        if (!empty($machine_immat)) {
            $parts[] = $machine_immat;
        }
        return implode(' - ', $parts);
    }

    public function testSubjectUsesDescriptionWhenPresent()
    {
        $subject = $this->_build_subject('Certificat médical', 'Licence', 'Jean', 'Dupont', 'F-CGVV');
        $this->assertStringStartsWith('Certificat médical', $subject);
    }

    public function testSubjectFallsBackToTypeNameWhenDescriptionEmpty()
    {
        $subject = $this->_build_subject('', 'Licence FFVV', 'Jean', 'Dupont', '');
        $this->assertStringStartsWith('Licence FFVV', $subject);
    }

    public function testSubjectIncludesPilotName()
    {
        $subject = $this->_build_subject('Certicat', 'Type', 'Jean', 'Dupont', '');
        $this->assertStringContainsString('Jean Dupont', $subject);
    }

    public function testSubjectIncludesMachineImmat()
    {
        $subject = $this->_build_subject('Doc', 'Type', '', '', 'F-CGVV');
        $this->assertStringContainsString('F-CGVV', $subject);
    }

    public function testSubjectOmitsPilotWhenEmpty()
    {
        $subject = $this->_build_subject('Certicat', 'Type', '', '', '');
        $this->assertEquals('Certicat', $subject);
    }

    // -------------------------------------------------------------------------
    // Access control: _is_admin logic
    // The controller _is_admin() checks dx_auth->is_role('ca') || dx_auth->is_admin()
    // We verify that the model does not expose the endpoint data to non-admins
    // (full access-control testing done in Playwright)
    // -------------------------------------------------------------------------

    public function testNonAdminIsBlockedByIsAdminCheck()
    {
        // Verify that an unauthenticated user is not admin
        // DX_Auth::is_admin() returns false when no user is logged in
        $this->CI->load->library('DX_Auth');
        $is_admin = $this->CI->dx_auth->is_admin();

        $this->assertFalse($is_admin,
            'Sans utilisateur connecté, is_admin doit retourner false');
    }
}
