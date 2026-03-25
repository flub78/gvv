<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 095 (Lot 3 audit fields on documents/email tables).
 */
class AuditDocumentsEmailMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/095_audit_documents_email.php';
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
        $migration = new Migration_Audit_documents_email();
        $this->assertTrue($migration->up(), 'Migration 095 up() should succeed');
    }

    public function testMigration095AddsExpectedColumns()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('archived_documents', 'updated_by'));
        $this->assertTrue($this->columnExists('email_lists', 'updated_by'));

        foreach (array('document_types', 'attachments', 'mails') as $table) {
            $this->assertTrue($this->columnExists($table, 'created_by'));
            $this->assertTrue($this->columnExists($table, 'created_at'));
            $this->assertTrue($this->columnExists($table, 'updated_by'));
            $this->assertTrue($this->columnExists($table, 'updated_at'));
        }
    }

    public function testMigration095BackfillsLegacyFields()
    {
        $this->runMigrationUp();

        $suffix = (string) time();

        // Insert a mails row with explicit NULL audit fields to validate backfill
        $this->db->query(
            "INSERT INTO mails (titre, destinataires, copie_a, selection, individuel, date_envoie, texte, debut_facturation, fin_facturation, created_by, created_at, updated_by, updated_at)
             VALUES ('lot3 migration test $suffix', 'test@example.com', '', 0, 1, '2026-03-25 10:00:00', 'body', NULL, NULL, NULL, NULL, NULL, NULL)"
        );

        $migration = new Migration_Audit_documents_email();
        $this->assertTrue($migration->up(), 'Migration 095 should be idempotent and succeed when rerun');

        $mail = $this->db->query(
            "SELECT created_at, updated_at
             FROM mails
             WHERE titre = 'lot3 migration test $suffix'
             ORDER BY id DESC LIMIT 1"
        )->row_array();

        $this->assertStringStartsWith('2026-03-25 10:00:00', $mail['created_at']);
        $this->assertStringStartsWith('2026-03-25 10:00:00', $mail['updated_at']);

        // Existing email_lists rows should have updated_by backfilled from created_by
        $counts = $this->db->query(
            "SELECT
                SUM(CASE WHEN created_by IS NOT NULL AND updated_by IS NULL THEN 1 ELSE 0 END) AS missing_updated_by,
                COUNT(*) AS total
             FROM email_lists"
        )->row_array();

        if ((int) $counts['total'] > 0) {
            $this->assertEquals(0, (int) $counts['missing_updated_by']);
        }

        $this->db->query("DELETE FROM mails WHERE titre = 'lot3 migration test $suffix'");
    }
}
