<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 144 (Lot 11 — sous-formulaires, link_token) and the
 * associated Form_submissions_model methods (get_by_link_token,
 * backfill_subject_from_link_token, get_submission_summary).
 */
class FormsSubformMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/144_forms_subform.php';
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function indexExists($table, $index)
    {
        $t = $this->db->escape_str($table);
        $i = $this->db->escape_str($index);
        $row = $this->db->query(
            "SELECT COUNT(*) AS cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$i'"
        )->row_array();

        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Forms_subform();
        $this->assertTrue($migration->up(), 'Migration 144 up() should succeed');
    }

    public function testMigration144AddsExpectedColumnAndIndex()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('form_submissions', 'link_token'));
        $this->assertTrue($this->indexExists('form_submissions', 'idx_link_token'));
    }

    public function testMigration144UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('form_submissions', 'link_token'));
        $this->assertTrue($this->indexExists('form_submissions', 'idx_link_token'));
    }

    public function testMigration144DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $this->db->insert('forms', array(
            'code'        => 'mig144_test_' . time(),
            'title'       => 'Migration 144 test',
            'public_slug' => 'mig144-test-' . time(),
            'status'      => 'draft',
        ));
        $form_id = $this->db->insert_id();

        $this->db->insert('form_submissions', array(
            'form_id'         => $form_id,
            'submission_uuid' => 'mig144-uuid-' . time(),
            'status'          => 'submitted',
        ));
        $submission_id = $this->db->insert_id();
        $submission = $this->db->where('id', $submission_id)->get('form_submissions')->row_array();
        $this->assertNull($submission['link_token']);

        $this->db->where('id', $submission_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');

        $migration = new Migration_Forms_subform();
        $this->assertTrue($migration->down(), 'Migration 144 down() should succeed');
        $this->assertFalse($this->columnExists('form_submissions', 'link_token'));
        $this->assertFalse($this->indexExists('form_submissions', 'idx_link_token'));

        // Restore expected state for the rest of the suite / the application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('form_submissions', 'link_token'));
    }

    public function testGetByLinkTokenReturnsLatestSubmittedSubmission()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');

        $this->db->insert('forms', array(
            'code'        => 'mig144_link_test_' . time(),
            'title'       => 'Migration 144 link test',
            'public_slug' => 'mig144-link-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        $token = 'tok_' . uniqid();

        $submission_id = $CI->form_submissions_model->create_submission(array(
            'form_id'    => $form_id,
            'status'     => 'submitted',
            'link_token' => $token,
        ));
        $this->assertNotFalse($submission_id);

        $result = $CI->form_submissions_model->get_by_link_token($token);
        $this->assertNotNull($result);
        $this->assertSame((int) $submission_id, (int) $result['id']);

        $no_match = $CI->form_submissions_model->get_by_link_token('unknown-token');
        $this->assertNull($no_match);

        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');
    }

    public function testBackfillSubjectFromLinkTokenSkipsWhenAlreadySet()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');

        $this->db->insert('forms', array(
            'code'        => 'mig144_backfill_test_' . time(),
            'title'       => 'Migration 144 backfill test',
            'public_slug' => 'mig144-backfill-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        // Case 1: sub-form submission with no subject reference yet — backfill applies.
        $token_free = 'tok_free_' . uniqid();
        $sub_id_free = $CI->form_submissions_model->create_submission(array(
            'form_id'    => $form_id,
            'status'     => 'submitted',
            'link_token' => $token_free,
        ));

        $updated = $CI->form_submissions_model->backfill_subject_from_link_token($token_free, 'form_submission', 999);
        $this->assertTrue($updated);

        $row_free = $CI->form_submissions_model->get_by_id($sub_id_free);
        $this->assertSame('form_submission', $row_free['subject_type']);
        $this->assertSame(999, (int) $row_free['subject_id']);

        // Case 2: sub-form submission that is itself a category-3 form, already
        // carrying its own subject_type/subject_id — backfill must be a no-op.
        $token_used = 'tok_used_' . uniqid();
        $sub_id_used = $CI->form_submissions_model->create_submission(array(
            'form_id'      => $form_id,
            'status'       => 'submitted',
            'link_token'   => $token_used,
            'subject_type' => 'vols_decouverte',
            'subject_id'   => 12345,
        ));

        $updated_used = $CI->form_submissions_model->backfill_subject_from_link_token($token_used, 'form_submission', 999);
        $this->assertFalse($updated_used);

        $row_used = $CI->form_submissions_model->get_by_id($sub_id_used);
        $this->assertSame('vols_decouverte', $row_used['subject_type']);
        $this->assertSame(12345, (int) $row_used['subject_id']);

        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');
    }

    public function testGetSubmissionSummaryExcludesFileSignatureAndSubformFields()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');
        $CI->load->model('forms_model');
        $CI->load->model('form_pages_model');

        $this->db->insert('forms', array(
            'code'        => 'mig144_summary_test_' . time(),
            'title'       => 'Migration 144 summary test',
            'public_slug' => 'mig144-summary-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        $this->db->insert('form_pages', array(
            'form_id'     => $form_id,
            'page_number' => 1,
        ));
        $page_id = $this->db->insert_id();

        // Field structure is no longer persisted (migration 166) — parsed on demand
        // from HTML in production; here we build the descriptor array directly,
        // matching Forms_field_parser::parse_fields()'s output shape.
        $fields = array(
            array('name' => 'nom', 'label' => 'Nom', 'field_type' => 'text', 'sort_order' => 1),
            array('name' => 'piece_jointe', 'label' => 'Piece jointe', 'field_type' => 'file', 'sort_order' => 2),
        );

        $submission_id = $CI->form_submissions_model->create_submission(array(
            'form_id' => $form_id,
            'status'  => 'submitted',
            'values'  => array(
                'nom'          => 'Dupont',
                'piece_jointe' => 'scan.pdf',
            ),
        ));

        $summary = $CI->form_submissions_model->get_submission_summary($submission_id, $fields);
        $labels = array_column($summary, 'label');
        $this->assertContains('Nom', $labels);
        $this->assertNotContains('Piece jointe', $labels);

        $this->db->where('submission_id', $submission_id)->delete('form_submission_values');
        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('id', $page_id)->delete('form_pages');
        $this->db->where('id', $form_id)->delete('forms');
    }
}
