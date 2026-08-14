<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 171 (Lot 3d — acceptance_items.mandatory_level,
 * replacing the boolean `mandatory` with a 3-level enum).
 */
class AcceptanceItemsMandatoryLevelMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/171_acceptance_items_mandatory_level.php';
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
        $migration = new Migration_Acceptance_items_mandatory_level();
        $this->assertTrue($migration->up(), 'Migration 171 up() should succeed');
    }

    public function testMigration171AddsMandatoryLevelAndDropsMandatory()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'mandatory_level'));
        $this->assertFalse($this->columnExists('acceptance_items', 'mandatory'));

        $info = $this->db->query(
            "SELECT COLUMN_TYPE, COLUMN_DEFAULT FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'acceptance_items' AND COLUMN_NAME = 'mandatory_level'"
        )->row_array();
        $this->assertStringContainsString("'optional'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'mandatory_soft'", $info['COLUMN_TYPE']);
        $this->assertStringContainsString("'mandatory_hard'", $info['COLUMN_TYPE']);
        $this->assertEquals('optional', trim($info['COLUMN_DEFAULT'], "'"));
    }

    public function testMigration171UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'mandatory_level'));
        $this->assertFalse($this->columnExists('acceptance_items', 'mandatory'));
    }

    public function testMigration171DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $login = $this->db->query("SELECT mlogin FROM membres LIMIT 1")->row_array();
        $this->assertNotNull($login, 'Au moins un membre est requis pour ce test');

        $this->db->insert('acceptance_items', array(
            'title'       => 'Migration 171 test ' . time(),
            'category'    => 'document',
            'target_type' => 'internal',
            'created_by'  => $login['mlogin'],
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        $item_id = $this->db->insert_id();
        $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
        $this->assertEquals('optional', $item['mandatory_level']);

        $this->db->where('id', $item_id)->update('acceptance_items', array('mandatory_level' => 'mandatory_hard'));
        $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
        $this->assertEquals('mandatory_hard', $item['mandatory_level']);

        $this->db->where('id', $item_id)->delete('acceptance_items');

        // down() must restore the boolean column, backfilling mandatory=1 for
        // any non-optional level.
        $migration = new Migration_Acceptance_items_mandatory_level();
        $this->assertTrue($migration->down(), 'Migration 171 down() should succeed');
        $this->assertTrue($this->columnExists('acceptance_items', 'mandatory'));
        $this->assertFalse($this->columnExists('acceptance_items', 'mandatory_level'));

        // Restore expected state for the rest of the suite / the application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('acceptance_items', 'mandatory_level'));
    }
}
