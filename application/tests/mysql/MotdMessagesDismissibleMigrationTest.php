<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 172 (Lot 3d — motd_messages.dismissible).
 */
class MotdMessagesDismissibleMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/172_motd_messages_dismissible.php';
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
        $migration = new Migration_Motd_messages_dismissible();
        $this->assertTrue($migration->up(), 'Migration 172 up() should succeed');
    }

    public function testMigration172AddsDismissibleColumn()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('motd_messages', 'dismissible'));

        $info = $this->db->query(
            "SELECT COLUMN_DEFAULT FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'motd_messages' AND COLUMN_NAME = 'dismissible'"
        )->row_array();
        $this->assertEquals('1', $info['COLUMN_DEFAULT']);
    }

    public function testMigration172UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('motd_messages', 'dismissible'));
    }

    public function testMigration172DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $this->db->insert('motd_messages', array(
            'content'    => 'Migration 172 test ' . time(),
            'start_date' => date('Y-m-d H:i:s'),
            'end_date'   => date('Y-m-d H:i:s', strtotime('+1 day')),
            'origin'     => 'system',
        ));
        $message_id = $this->db->insert_id();
        $message = $this->db->where('id', $message_id)->get('motd_messages')->row_array();
        $this->assertEquals(1, (int) $message['dismissible']);

        $this->db->where('id', $message_id)->update('motd_messages', array('dismissible' => 0));
        $message = $this->db->where('id', $message_id)->get('motd_messages')->row_array();
        $this->assertEquals(0, (int) $message['dismissible']);

        $this->db->where('id', $message_id)->delete('motd_messages');

        $migration = new Migration_Motd_messages_dismissible();
        $this->assertTrue($migration->down(), 'Migration 172 down() should succeed');
        $this->assertFalse($this->columnExists('motd_messages', 'dismissible'));

        // Restore expected state for the rest of the suite / the application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('motd_messages', 'dismissible'));
    }
}
