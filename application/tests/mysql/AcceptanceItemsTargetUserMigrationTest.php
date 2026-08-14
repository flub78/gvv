<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 168 (Lot 3c — ciblage d'un utilisateur individuel
 * sur acceptance_items).
 */
class AcceptanceItemsTargetUserMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/168_acceptance_items_target_user.php';
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

    private function indexExists($table, $index)
    {
        $row = $this->db->query(
            "SHOW INDEX FROM `$table` WHERE Key_name = '" . $this->db->escape_str($index) . "'"
        );
        return $row->num_rows() > 0;
    }

    private function foreignKeyExists($table, $constraint)
    {
        $row = $this->db->query(
            "SELECT 1 FROM INFORMATION_SCHEMA.TABLE_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND TABLE_NAME = '" . $this->db->escape_str($table) . "'
               AND CONSTRAINT_NAME = '" . $this->db->escape_str($constraint) . "'"
        );
        return $row->num_rows() > 0;
    }

    private function runMigrationUp()
    {
        $migration = new Migration_Acceptance_items_target_user();
        $this->assertTrue($migration->up(), 'Migration 168 up() should succeed');
    }

    public function testMigration168AddsColumnIndexAndForeignKey()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'target_user_login'));
        $this->assertTrue($this->indexExists('acceptance_items', 'idx_target_user_login'));
        $this->assertTrue($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_target_user'));
    }

    public function testMigration168UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'target_user_login'));
        $this->assertTrue($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_target_user'));
    }

    public function testMigration168DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        // target_user_login doit rester NULL pour un element cible par categorie.
        $login = $this->db->query("SELECT mlogin FROM membres LIMIT 1")->row_array();
        $this->assertNotNull($login, 'Au moins un membre est requis pour ce test');

        $this->db->insert('acceptance_items', array(
            'title'       => 'Migration 168 test ' . time(),
            'category'    => 'document',
            'target_type' => 'internal',
            'created_by'  => $login['mlogin'],
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        $item_id = $this->db->insert_id();
        $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
        $this->assertNull($item['target_user_login']);

        // La FK doit accepter un login existant.
        $this->db->where('id', $item_id)->update('acceptance_items', array(
            'target_user_login' => $login['mlogin'],
        ));
        $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
        $this->assertSame($login['mlogin'], $item['target_user_login']);

        $this->db->where('id', $item_id)->delete('acceptance_items');

        // down() doit retirer la FK, l'index et la colonne proprement.
        $migration = new Migration_Acceptance_items_target_user();
        $this->assertTrue($migration->down(), 'Migration 168 down() should succeed');
        $this->assertFalse($this->columnExists('acceptance_items', 'target_user_login'));
        $this->assertFalse($this->indexExists('acceptance_items', 'idx_target_user_login'));
        $this->assertFalse($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_target_user'));

        // Restaure l'etat attendu pour le reste de la suite / l'application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('acceptance_items', 'target_user_login'));
    }
}
