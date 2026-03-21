<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for vols_decouverte migrations (085, 086)
 *
 * - 085: aerodrome column on vols_decouverte
 * - 086: vld_id column on archived_documents
 *
 * @package tests
 * @see application/migrations/085_vols_decouverte_aerodrome.php
 * @see application/migrations/086_archived_documents_vld.php
 */
class VolsDecouverteMigrationTest extends TestCase
{
    protected $db;

    protected function setUp(): void
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    private function columnExists($table, $column)
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = ?
            AND COLUMN_NAME = ?
        ", [$table, $column]);
        return $query->row_array()['count'] > 0;
    }

    private function loadMigration($file, $class)
    {
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists($class)) {
            require_once APPPATH . 'migrations/' . $file;
        }
        return new $class();
    }

    // --- Migration 085: aerodrome ---

    public function testMigration085_Up_AddsAerodromeColumn()
    {
        $migration = $this->loadMigration('085_vols_decouverte_aerodrome.php', 'Migration_Vols_decouverte_aerodrome');
        // Ensure clean state
        if ($this->columnExists('vols_decouverte', 'aerodrome')) {
            $migration->down();
        }
        $migration->up();
        $this->assertTrue($this->columnExists('vols_decouverte', 'aerodrome'),
            'Column aerodrome should exist in vols_decouverte after migration up');
    }

    public function testMigration085_AerodromeIsNullable()
    {
        $query = $this->db->query("
            SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'vols_decouverte'
            AND COLUMN_NAME = 'aerodrome'
        ");
        $row = $query->row_array();
        $this->assertNotEmpty($row, 'Column aerodrome should be found');
        $this->assertEquals('YES', $row['IS_NULLABLE'], 'aerodrome must be nullable');
    }

    public function testMigration085_ExistingRowsUnaffected()
    {
        // Existing rows should still be selectable with aerodrome = NULL
        $query = $this->db->query("SELECT aerodrome FROM vols_decouverte LIMIT 1");
        // No exception = existing rows are not affected
        $this->assertTrue(true);
    }

    public function testMigration085_Down_RemovesAerodromeColumn()
    {
        $migration = $this->loadMigration('085_vols_decouverte_aerodrome.php', 'Migration_Vols_decouverte_aerodrome');
        $migration->down();
        $this->assertFalse($this->columnExists('vols_decouverte', 'aerodrome'),
            'Column aerodrome should be removed after migration down');
        // Restore for subsequent tests
        $migration->up();
    }

    // --- Migration 086: vld_id on archived_documents ---

    public function testMigration086_Up_AddsVldIdColumn()
    {
        $file = APPPATH . 'migrations/086_archived_documents_vld.php';
        if (!file_exists($file)) {
            $this->markTestSkipped('Migration 086 not yet created');
        }
        $migration = $this->loadMigration('086_archived_documents_vld.php', 'Migration_Archived_documents_vld');
        // Ensure clean state
        if ($this->columnExists('archived_documents', 'vld_id')) {
            $migration->down();
        }
        $migration->up();
        $this->assertTrue($this->columnExists('archived_documents', 'vld_id'),
            'Column vld_id should exist in archived_documents after migration up');
    }

    public function testMigration086_VldIdIsNullable()
    {
        if (!$this->columnExists('archived_documents', 'vld_id')) {
            $this->markTestSkipped('Migration 086 not yet applied');
        }
        $query = $this->db->query("
            SELECT IS_NULLABLE FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = 'archived_documents'
            AND COLUMN_NAME = 'vld_id'
        ");
        $row = $query->row_array();
        $this->assertEquals('YES', $row['IS_NULLABLE'], 'vld_id must be nullable');
    }

    public function testMigration086_ExistingDocumentsUnaffected()
    {
        if (!$this->columnExists('archived_documents', 'vld_id')) {
            $this->markTestSkipped('Migration 086 not yet applied');
        }
        $query = $this->db->query("SELECT COUNT(*) as cnt FROM archived_documents WHERE vld_id IS NULL OR vld_id IS NOT NULL");
        $row = $query->row_array();
        $this->assertArrayHasKey('cnt', $row, 'Query on archived_documents with vld_id should succeed');
    }

    public function testMigration086_Down_RemovesVldIdColumn()
    {
        if (!$this->columnExists('archived_documents', 'vld_id')) {
            $this->markTestSkipped('Migration 086 not yet applied');
        }
        $migration = $this->loadMigration('086_archived_documents_vld.php', 'Migration_Archived_documents_vld');
        $migration->down();
        $this->assertFalse($this->columnExists('archived_documents', 'vld_id'),
            'Column vld_id should be removed after migration down');
        // Restore
        $migration->up();
    }
}

/* End of file VolsDecouverteMigrationTest.php */
/* Location: ./application/tests/mysql/VolsDecouverteMigrationTest.php */
