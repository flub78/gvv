<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 108 (mnumero autoincrement on membres)
 *
 * @see application/migrations/108_mnumero_autoincrement.php
 */
class MnumeroAutoIncrementMigrationTest extends TestCase
{
    protected $db;

    protected function setUp(): void
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    private function keyExists($table, $key_name)
    {
        $t = $this->db->escape_str($table);
        $k = $this->db->escape_str($key_name);
        $query = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND INDEX_NAME = '$k'"
        );
        $row = $query ? $query->row_array() : null;
        return isset($row['cnt']) && (int)$row['cnt'] > 0;
    }

    /**
     * Test that migration 108 ensures no NULL mnumero values exist
     */
    public function testMigration108_NoNullMnumeroAfterMigration()
    {
        // After migration 108 is applied, no member should have NULL mnumero
        $query = $this->db->query("SELECT COUNT(*) as cnt FROM membres WHERE mnumero IS NULL");
        $row = $query->row_array();
        $this->assertEquals(0, (int)$row['cnt'], 'No member should have NULL mnumero after migration 108 applied');
    }

    /**
     * Test that migration 108 ensures UNIQUE constraint on mnumero
     */
    public function testMigration108_MnumeroHasUniqueConstraint()
    {
        // After migration 108, mnumero should have a UNIQUE constraint
        $query = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'membres'
             AND COLUMN_NAME = 'mnumero' AND SEQ_IN_INDEX = 1 AND NON_UNIQUE = 0"
        );
        $row = $query->row_array();
        $this->assertGreaterThan(0, (int)$row['cnt'], 'mnumero should have a UNIQUE constraint');
    }

    /**
     * Test that migration 108 sets up AUTO_INCREMENT
     */
    public function testMigration108_MnumeroHasAutoIncrement()
    {
        // After migration 108, mnumero should be NOT NULL and have AUTO_INCREMENT
        $query = $this->db->query(
            "SELECT COLUMN_KEY, EXTRA FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'membres'
             AND COLUMN_NAME = 'mnumero'"
        );
        $row = $query->row_array();
        
        $this->assertNotNull($row, 'Column mnumero should exist');
        $this->assertStringContainsString('auto_increment', strtolower($row['EXTRA']), 
            'mnumero should have AUTO_INCREMENT specified');
    }

    /**
     * Test that mjumero values are unique
     */
    public function testMigration108_MnumeroValuesAreUnique()
    {
        // Count unique mnumero values vs total
        $query = $this->db->query(
            "SELECT COUNT(*) as total, COUNT(DISTINCT mnumero) as unique_count FROM membres"
        );
        $row = $query->row_array();
        
        $this->assertEquals((int)$row['total'], (int)$row['unique_count'], 
            'All mnumero values should be unique across all members');
    }
}
