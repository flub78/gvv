<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migrations 093 and 094 (Lot 2 audit fields on membres, fleet
 * and formation tables).
 *
 * Uses plain TestCase (DDL is not transactional).
 */
class AuditMembresFormationMigrationTest extends TestCase
{
    /** @var CI_DB_driver */
    private $db;

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists('Migration_Audit_membres_flotte')) {
            require_once APPPATH . 'migrations/093_audit_membres_flotte.php';
        }
        if (!class_exists('Migration_Audit_formation')) {
            require_once APPPATH . 'migrations/094_audit_formation.php';
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

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

    private function runUp093()
    {
        $migration = new Migration_Audit_membres_flotte();
        $migration->up();
    }

    private function runDown093()
    {
        $migration = new Migration_Audit_membres_flotte();
        $migration->down();
    }

    private function runUp094()
    {
        $migration = new Migration_Audit_formation();
        $migration->up();
    }

    private function runDown094()
    {
        $migration = new Migration_Audit_formation();
        $migration->down();
    }

    // -------------------------------------------------------------------------
    // Migration 093 — membres & fleet
    // -------------------------------------------------------------------------

    public function testMigration093AddsAuditColumnsToMembresAndFleet()
    {
        $this->runUp093();

        $tables = array('membres', 'licences', 'machinesa', 'machinesp', 'terrains', 'planc', 'pompes');
        $cols   = array('created_by', 'created_at', 'updated_by', 'updated_at');

        foreach ($tables as $table) {
            foreach ($cols as $col) {
                $this->assertTrue(
                    $this->columnExists($table, $col),
                    "Expected column $col to exist in $table after migration 093"
                );
            }
        }
    }

    public function testMigration093IsIdempotent()
    {
        $this->runUp093();
        // Running up() a second time must not throw or fail
        $this->runUp093();

        $this->assertTrue($this->columnExists('membres', 'created_by'), 'created_by still present after idempotent re-run');
    }

    public function testMigration093Rollback()
    {
        $this->runUp093();
        $this->runDown093();

        $tables = array('membres', 'licences', 'machinesa', 'machinesp', 'terrains', 'planc', 'pompes');
        foreach ($tables as $table) {
            $this->assertFalse(
                $this->columnExists($table, 'created_by'),
                "created_by should be removed from $table after migration 093 rollback"
            );
        }
    }

    // -------------------------------------------------------------------------
    // Migration 094 — formation
    // -------------------------------------------------------------------------

    public function testMigration094AddsAuditColumnsToFormationTables()
    {
        $this->runUp093();
        $this->runUp094();

        $tables = array(
            'formation_chapitres',
            'formation_evaluations',
            'formation_inscriptions',
            'formation_item',
            'formation_lecons',
            'formation_progres',
            'formation_seances',
            'formation_seances_participants',
            'formation_sujets',
            'formation_types_seance',
            'formation_autorisations_solo',
            'formation_programmes',
        );
        $cols = array('created_by', 'created_at', 'updated_by', 'updated_at');

        foreach ($tables as $table) {
            foreach ($cols as $col) {
                $this->assertTrue(
                    $this->columnExists($table, $col),
                    "Expected column $col to exist in $table after migration 094"
                );
            }
        }
    }

    public function testMigration094BackfillsFromDateCreation()
    {
        $this->runUp093();
        $this->runUp094();

        $row = $this->db->query(
            "SELECT date_creation, created_at FROM formation_programmes WHERE date_creation IS NOT NULL AND created_at IS NOT NULL LIMIT 1"
        )->row_array();

        if (empty($row)) {
            $this->markTestSkipped('No formation_programmes with date_creation for backfill assertion');
        }

        $this->assertEquals(
            $row['date_creation'],
            $row['created_at'],
            'created_at should match date_creation after backfill in formation_programmes'
        );
    }

    public function testMigration094Rollback()
    {
        $this->runUp093();
        $this->runUp094();
        $this->runDown094();
        $this->runDown093();

        $check_tables = array('formation_seances', 'membres', 'pompes');
        foreach ($check_tables as $table) {
            $this->assertFalse(
                $this->columnExists($table, 'created_by'),
                "created_by should not exist in $table after rollback"
            );
        }
    }
}

