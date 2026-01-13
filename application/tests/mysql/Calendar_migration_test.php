<?php

/**
 * PHPUnit Tests for Calendar Table Migration 060
 *
 * Tests the database migration that refactors the calendar table to support
 * FullCalendar v6 with datetime fields, full_day boolean, status, and audit fields.
 *
 * @covers Migration_Refactor_calendar_table
 */

use PHPUnit\Framework\TestCase;

class Calendar_migration_test extends TestCase {

    protected static $CI;
    protected static $migration_executed = false;

    /**
     * Set up test environment - initialize CodeIgniter
     */
    public static function setUpBeforeClass(): void {
        if (!isset(self::$CI)) {
            self::$CI = &get_instance();
            self::$CI->load->database();
        }
    }

    /**
     * Helper: Execute migration up
     */
    private function runMigrationUp() {
        require_once APPPATH . 'migrations/060_refactor_calendar_table.php';
        $migration = new Migration_Refactor_calendar_table();
        return $migration->up();
    }

    /**
     * Helper: Execute migration down
     */
    private function runMigrationDown() {
        require_once APPPATH . 'migrations/060_refactor_calendar_table.php';
        $migration = new Migration_Refactor_calendar_table();
        return $migration->down();
    }

    /**
     * Helper: Get table structure
     */
    private function getTableStructure($table) {
        $query = self::$CI->db->query("DESCRIBE $table");
        $structure = [];
        foreach ($query->result_array() as $row) {
            $structure[$row['Field']] = $row;
        }
        return $structure;
    }

    /**
     * Helper: Get table indexes
     */
    private function getTableIndexes($table) {
        $query = self::$CI->db->query("SHOW INDEX FROM $table");
        $indexes = [];
        foreach ($query->result_array() as $row) {
            $indexes[$row['Key_name']] = $row;
        }
        return $indexes;
    }

    /**
     * Helper: Check if column exists
     */
    private function columnExists($table, $column) {
        $structure = $this->getTableStructure($table);
        return isset($structure[$column]);
    }

    /**
     * Helper: Check if index exists
     */
    private function indexExists($table, $index) {
        $indexes = $this->getTableIndexes($table);
        return isset($indexes[$index]);
    }

    /**
     * Test: Migration up adds all required columns
     */
    public function test_migration_up_adds_columns(): void {
        // Execute migration up
        $result = $this->runMigrationUp();
        $this->assertTrue($result, "Migration up should succeed");
        self::$migration_executed = true;

        // Verify new columns exist
        $this->assertTrue($this->columnExists('calendar', 'start_datetime'), "start_datetime column should exist");
        $this->assertTrue($this->columnExists('calendar', 'end_datetime'), "end_datetime column should exist");
        $this->assertTrue($this->columnExists('calendar', 'full_day'), "full_day column should exist");
        $this->assertTrue($this->columnExists('calendar', 'status'), "status column should exist");
        $this->assertTrue($this->columnExists('calendar', 'created_by'), "created_by column should exist");
        $this->assertTrue($this->columnExists('calendar', 'updated_by'), "updated_by column should exist");
        $this->assertTrue($this->columnExists('calendar', 'created_at'), "created_at column should exist");
        $this->assertTrue($this->columnExists('calendar', 'updated_at'), "updated_at column should exist");

        // Verify original columns still exist
        $this->assertTrue($this->columnExists('calendar', 'id'), "id column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'date'), "date column should still exist (deprecated)");
        $this->assertTrue($this->columnExists('calendar', 'mlogin'), "mlogin column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'role'), "role column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'commentaire'), "commentaire column should still exist");
    }

    /**
     * Test: Migration up adds all required indexes
     */
    public function test_migration_up_adds_indexes(): void {
        // This test runs after the migration is executed in previous test
        if (!self::$migration_executed) {
            $this->runMigrationUp();
            self::$migration_executed = true;
        }

        // Verify indexes exist
        $this->assertTrue($this->indexExists('calendar', 'idx_date_range'), "idx_date_range index should exist");
        $this->assertTrue($this->indexExists('calendar', 'idx_mlogin'), "idx_mlogin index should exist");
        $this->assertTrue($this->indexExists('calendar', 'idx_status'), "idx_status index should exist");
        $this->assertTrue($this->indexExists('calendar', 'idx_full_day'), "idx_full_day index should exist");
    }

    /**
     * Test: full_day column has correct default value
     */
    public function test_full_day_default_value(): void {
        if (!self::$migration_executed) {
            $this->runMigrationUp();
            self::$migration_executed = true;
        }

        $structure = $this->getTableStructure('calendar');
        $full_day = $structure['full_day'];

        $this->assertEquals('1', $full_day['Default'], "full_day default should be 1");
        $this->assertEquals('tinyint(1)', $full_day['Type'], "full_day should be tinyint(1)");
        $this->assertEquals('NO', $full_day['Null'], "full_day should be NOT NULL");
    }

    /**
     * Test: status column has correct enum values
     */
    public function test_status_enum_values(): void {
        if (!self::$migration_executed) {
            $this->runMigrationUp();
            self::$migration_executed = true;
        }

        $structure = $this->getTableStructure('calendar');
        $status = $structure['status'];

        $this->assertStringContainsString("enum", $status['Type'], "status should be enum type");
        $this->assertStringContainsString("confirmed", $status['Type'], "status should contain 'confirmed'");
        $this->assertStringContainsString("pending", $status['Type'], "status should contain 'pending'");
        $this->assertStringContainsString("completed", $status['Type'], "status should contain 'completed'");
        $this->assertStringContainsString("cancelled", $status['Type'], "status should contain 'cancelled'");
        $this->assertEquals('confirmed', $status['Default'], "status default should be 'confirmed'");
    }

    /**
     * Test: datetime columns are NOT NULL
     */
    public function test_datetime_columns_not_null(): void {
        if (!self::$migration_executed) {
            $this->runMigrationUp();
            self::$migration_executed = true;
        }

        $structure = $this->getTableStructure('calendar');

        $this->assertEquals('NO', $structure['start_datetime']['Null'], "start_datetime should be NOT NULL");
        $this->assertEquals('NO', $structure['end_datetime']['Null'], "end_datetime should be NOT NULL");
    }

    /**
     * Test: Timestamps have correct defaults
     */
    public function test_timestamp_defaults(): void {
        if (!self::$migration_executed) {
            $this->runMigrationUp();
            self::$migration_executed = true;
        }

        $structure = $this->getTableStructure('calendar');

        $this->assertStringContainsString('CURRENT_TIMESTAMP', $structure['created_at']['Default'],
            "created_at should default to CURRENT_TIMESTAMP");
        $this->assertStringContainsString('on update CURRENT_TIMESTAMP', strtolower($structure['updated_at']['Extra']),
            "updated_at should update on CURRENT_TIMESTAMP");
    }

    /**
     * Test: Migration down removes added columns
     */
    public function test_migration_down_removes_columns(): void {
        // First ensure migration is up
        $this->runMigrationUp();

        // Execute migration down
        $result = $this->runMigrationDown();
        $this->assertTrue($result, "Migration down should succeed");

        // Verify new columns are removed
        $this->assertFalse($this->columnExists('calendar', 'start_datetime'), "start_datetime should be removed");
        $this->assertFalse($this->columnExists('calendar', 'end_datetime'), "end_datetime should be removed");
        $this->assertFalse($this->columnExists('calendar', 'full_day'), "full_day should be removed");
        $this->assertFalse($this->columnExists('calendar', 'status'), "status should be removed");
        $this->assertFalse($this->columnExists('calendar', 'created_by'), "created_by should be removed");
        $this->assertFalse($this->columnExists('calendar', 'updated_by'), "updated_by should be removed");
        $this->assertFalse($this->columnExists('calendar', 'created_at'), "created_at should be removed");
        $this->assertFalse($this->columnExists('calendar', 'updated_at'), "updated_at should be removed");

        // Verify original columns still exist
        $this->assertTrue($this->columnExists('calendar', 'id'), "id column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'date'), "date column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'mlogin'), "mlogin column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'role'), "role column should still exist");
        $this->assertTrue($this->columnExists('calendar', 'commentaire'), "commentaire column should still exist");

        // Verify date column is NOT NULL again
        $structure = $this->getTableStructure('calendar');
        $this->assertEquals('NO', $structure['date']['Null'], "date should be NOT NULL after rollback");
    }

    /**
     * Test: Migration down removes added indexes
     */
    public function test_migration_down_removes_indexes(): void {
        // Ensure we're at version 59 (after down migration)
        // Run up first, then down
        $this->runMigrationUp();
        $this->runMigrationDown();

        // Verify indexes are removed
        $this->assertFalse($this->indexExists('calendar', 'idx_date_range'), "idx_date_range should be removed");
        $this->assertFalse($this->indexExists('calendar', 'idx_mlogin'), "idx_mlogin should be removed");
        $this->assertFalse($this->indexExists('calendar', 'idx_status'), "idx_status should be removed");
        $this->assertFalse($this->indexExists('calendar', 'idx_full_day'), "idx_full_day should be removed");
    }

    /**
     * Clean up: Restore to version 60 for subsequent tests
     */
    public static function tearDownAfterClass(): void {
        // Restore to version 60 for other tests that may depend on it
        require_once APPPATH . 'migrations/060_refactor_calendar_table.php';
        $migration = new Migration_Refactor_calendar_table();
        $migration->up();
    }
}

/* End of file Calendar_migration_test.php */
/* Location: ./application/tests/mysql/Calendar_migration_test.php */
