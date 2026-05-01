<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 105 (mnumero column on membres)
 *
 * @see application/migrations/105_mnumero_membre.php
 */
class CartesMembreMigrationTest extends TestCase
{
    protected $db;

    protected function setUp(): void
    {
        $CI =& get_instance();
        $this->db = $CI->db;
    }

    private function columnExists($table, $column)
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $query = $this->db->query(
            "SELECT COUNT(*) as `count` FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        );
        $row = $query ? $query->row_array() : null;
        return isset($row['count']) && (int)$row['count'] > 0;
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

    public function testMigration105_Up_AddsMnumeroColumn()
    {
        $migration = $this->loadMigration('105_mnumero_membre.php', 'Migration_Mnumero_membre');

        if ($this->columnExists('membres', 'mnumero')) {
            $migration->down();
        }
        $migration->up();

        $this->assertTrue(
            $this->columnExists('membres', 'mnumero'),
            'Column mnumero should exist in membres after migration up'
        );
    }

    public function testMigration105_MnumeroIsNullable()
    {
        $this->assertTrue(
            $this->columnExists('membres', 'mnumero'),
            'Column mnumero must exist to test nullability'
        );

        $query = $this->db->query("
            SELECT IS_NULLABLE, DATA_TYPE
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'membres'
              AND COLUMN_NAME = 'mnumero'
        ");
        $row = $query->row_array();

        $this->assertNotEmpty($row, 'Column mnumero should be found in information_schema');
        $this->assertEquals('YES', $row['IS_NULLABLE'], 'mnumero must be nullable');
        $this->assertEquals('int', strtolower($row['DATA_TYPE']), 'mnumero must be of type INT');
    }

    public function testMigration105_ExistingMembresUnaffected()
    {
        $this->assertTrue(
            $this->columnExists('membres', 'mnumero'),
            'Column mnumero must exist'
        );
        $query = $this->db->query("SELECT COUNT(*) as cnt FROM membres WHERE mnumero IS NULL OR mnumero IS NOT NULL");
        $row = $query->row_array();
        $this->assertArrayHasKey('cnt', $row, 'SELECT on membres with mnumero should succeed');
    }

    public function testMigration105_Down_RemovesMnumeroColumn()
    {
        $migration = $this->loadMigration('105_mnumero_membre.php', 'Migration_Mnumero_membre');

        if (!$this->columnExists('membres', 'mnumero')) {
            $migration->up();
        }
        $migration->down();

        $this->assertFalse(
            $this->columnExists('membres', 'mnumero'),
            'Column mnumero should be removed after migration down'
        );

        // Restore for subsequent tests
        $migration->up();
    }
}
