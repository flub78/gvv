<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 139 (Lot 9 — soumission par téléchargement).
 */
class FormsUploadResponseMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/139_forms_upload_response.php';
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

    private function runMigrationUp()
    {
        $migration = new Migration_Forms_upload_response();
        $this->assertTrue($migration->up(), 'Migration 139 up() should succeed');
    }

    public function testMigration139AddsExpectedColumns()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'allow_upload_response'));
        $this->assertTrue($this->columnExists('form_submissions', 'submission_method'));
        $this->assertTrue($this->columnExists('form_submissions', 'upload_comment'));
    }

    public function testMigration139UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'allow_upload_response'));
    }

    public function testMigration139DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        // Nouveau formulaire : allow_upload_response doit valoir 0 par défaut.
        $this->db->insert('forms', array(
            'code'        => 'mig139_test_' . time(),
            'title'       => 'Migration 139 test',
            'public_slug' => 'mig139-test-' . time(),
            'status'      => 'draft',
        ));
        $form_id = $this->db->insert_id();
        $form = $this->db->where('id', $form_id)->get('forms')->row_array();
        $this->assertSame('0', (string) $form['allow_upload_response']);
        $this->db->where('id', $form_id)->delete('forms');

        // down() doit retirer les colonnes proprement.
        $migration = new Migration_Forms_upload_response();
        $this->assertTrue($migration->down(), 'Migration 139 down() should succeed');
        $this->assertFalse($this->columnExists('forms', 'allow_upload_response'));
        $this->assertFalse($this->columnExists('form_submissions', 'submission_method'));
        $this->assertFalse($this->columnExists('form_submissions', 'upload_comment'));

        // Restaure l'état attendu pour le reste de la suite / l'application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('forms', 'allow_upload_response'));
    }
}
