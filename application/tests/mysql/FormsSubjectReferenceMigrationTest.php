<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 140 (Lot 6, étape 6.2 — référence générique subject_type/subject_id).
 */
class FormsSubjectReferenceMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/140_forms_subject_reference.php';
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
        $migration = new Migration_Forms_subject_reference();
        $this->assertTrue($migration->up(), 'Migration 140 up() should succeed');
    }

    public function testMigration140AddsExpectedColumnsAndIndex()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('form_submissions', 'subject_type'));
        $this->assertTrue($this->columnExists('form_submissions', 'subject_id'));
        $this->assertTrue($this->indexExists('form_submissions', 'idx_subject'));
    }

    public function testMigration140UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('form_submissions', 'subject_type'));
        $this->assertTrue($this->indexExists('form_submissions', 'idx_subject'));
    }

    public function testMigration140DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        // subject_type/subject_id doivent rester NULL pour une soumission sans sujet.
        $this->db->insert('forms', array(
            'code'        => 'mig140_test_' . time(),
            'title'       => 'Migration 140 test',
            'public_slug' => 'mig140-test-' . time(),
            'status'      => 'draft',
        ));
        $form_id = $this->db->insert_id();

        $this->db->insert('form_submissions', array(
            'form_id'         => $form_id,
            'submission_uuid' => 'mig140-uuid-' . time(),
            'status'          => 'submitted',
        ));
        $submission_id = $this->db->insert_id();
        $submission = $this->db->where('id', $submission_id)->get('form_submissions')->row_array();
        $this->assertNull($submission['subject_type']);
        $this->assertNull($submission['subject_id']);

        $this->db->where('id', $submission_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');

        // down() doit retirer l'index et les colonnes proprement.
        $migration = new Migration_Forms_subject_reference();
        $this->assertTrue($migration->down(), 'Migration 140 down() should succeed');
        $this->assertFalse($this->columnExists('form_submissions', 'subject_type'));
        $this->assertFalse($this->columnExists('form_submissions', 'subject_id'));
        $this->assertFalse($this->indexExists('form_submissions', 'idx_subject'));

        // Restaure l'état attendu pour le reste de la suite / l'application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('form_submissions', 'subject_type'));
    }

    public function testGetCurrentForSubjectReturnsLatestSubmittedSubmission()
    {
        $this->runMigrationUp();

        $CI = &get_instance();
        $CI->load->model('form_submissions_model');

        $this->db->insert('forms', array(
            'code'        => 'mig140_subject_test_' . time(),
            'title'       => 'Migration 140 subject test',
            'public_slug' => 'mig140-subject-test-' . time(),
            'status'      => 'published',
        ));
        $form_id = $this->db->insert_id();

        $subject_type = 'vols_decouverte';
        $subject_id = 999001;

        $older = $CI->form_submissions_model->create_submission(array(
            'form_id'      => $form_id,
            'status'       => 'submitted',
            'subject_type' => $subject_type,
            'subject_id'   => $subject_id,
        ));
        sleep(1);
        $newer = $CI->form_submissions_model->create_submission(array(
            'form_id'      => $form_id,
            'status'       => 'submitted',
            'subject_type' => $subject_type,
            'subject_id'   => $subject_id,
        ));

        $result = $CI->form_submissions_model->get_current_for_subject($subject_type, $subject_id);
        $this->assertNotNull($result);
        $this->assertSame((int) $newer, (int) $result['id']);

        $result_form_scoped = $CI->form_submissions_model->get_current_for_subject($subject_type, $subject_id, $form_id);
        $this->assertSame((int) $newer, (int) $result_form_scoped['id']);

        $no_match = $CI->form_submissions_model->get_current_for_subject('other_type', $subject_id);
        $this->assertNull($no_match);

        $this->db->where('form_id', $form_id)->delete('form_submissions');
        $this->db->where('id', $form_id)->delete('forms');
    }
}
