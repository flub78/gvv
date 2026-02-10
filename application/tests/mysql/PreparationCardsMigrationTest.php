<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for preparation_cards migration (069)
 *
 * Tests that the migration properly creates the table and supports rollback.
 *
 * @package tests
 * @see application/migrations/069_create_preparation_cards.php
 */
class PreparationCardsMigrationTest extends TestCase
{
    protected $CI;
    protected $db;

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;
    }

    protected function tableExists($table_name)
    {
        $query = $this->db->query("
            SELECT COUNT(*) as count
            FROM information_schema.TABLES
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
        ");
        $result = $query->row_array();
        return $result['count'] > 0;
    }

    protected function getTableColumns($table_name)
    {
        $query = $this->db->query("
            SELECT COLUMN_NAME
            FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE()
            AND TABLE_NAME = '" . $this->db->escape_str($table_name) . "'
        ");

        $columns = [];
        foreach ($query->result_array() as $row) {
            $columns[] = $row['COLUMN_NAME'];
        }
        return $columns;
    }

    private function loadMigrationClass()
    {
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists('Migration_Create_preparation_cards')) {
            require_once APPPATH . 'migrations/069_create_preparation_cards.php';
        }
    }

    private function runMigrationUp()
    {
        $this->loadMigrationClass();
        $migration = new Migration_Create_preparation_cards();
        return $migration->up();
    }

    private function runMigrationDown()
    {
        $this->loadMigrationClass();
        $migration = new Migration_Create_preparation_cards();
        return $migration->down();
    }

    public function testMigrationUp_CreatesTable()
    {
        $this->assertTrue($this->runMigrationUp(), 'Migration up should succeed');
        $this->assertTrue($this->tableExists('preparation_cards'), 'preparation_cards table should exist');

        $expected_columns = [
            'id', 'title', 'type', 'html_fragment', 'image_url', 'link_url',
            'category', 'display_order', 'visible', 'created_at', 'updated_at'
        ];

        $columns = $this->getTableColumns('preparation_cards');
        foreach ($expected_columns as $column) {
            $this->assertContains($column, $columns, "Column '$column' should exist in preparation_cards");
        }
    }

    public function testMigrationDown_DropsTable()
    {
        $this->runMigrationUp();
        $this->assertTrue($this->runMigrationDown(), 'Migration down should succeed');
        $this->assertFalse($this->tableExists('preparation_cards'), 'preparation_cards table should be dropped');

        // Restore for subsequent tests
        $this->runMigrationUp();
    }
}

/* End of file PreparationCardsMigrationTest.php */
/* Location: ./application/tests/mysql/PreparationCardsMigrationTest.php */
