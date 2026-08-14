<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 169 (Lot 4 — acceptance_items.archived_document_id,
 * referencing an already archived document instead of uploading a new PDF).
 */
class AcceptanceItemsArchivedDocumentMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/169_acceptance_items_archived_document.php';
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
        $migration = new Migration_Acceptance_items_archived_document();
        $this->assertTrue($migration->up(), 'Migration 169 up() should succeed');
    }

    public function testMigration169AddsColumnIndexAndForeignKey()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'archived_document_id'));
        $this->assertTrue($this->indexExists('acceptance_items', 'idx_archived_document_id'));
        $this->assertTrue($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_archived_document'));
    }

    public function testMigration169UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('acceptance_items', 'archived_document_id'));
        $this->assertTrue($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_archived_document'));
    }

    public function testMigration169DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        $login = $this->db->query("SELECT mlogin FROM membres LIMIT 1")->row_array();
        $this->assertNotNull($login, 'Au moins un membre est requis pour ce test');

        // acceptance_items sans document rattache : archived_document_id doit rester NULL.
        $this->db->insert('acceptance_items', array(
            'title'       => 'Migration 169 test ' . time(),
            'category'    => 'document',
            'target_type' => 'internal',
            'created_by'  => $login['mlogin'],
            'created_at'  => date('Y-m-d H:i:s'),
        ));
        $item_id = $this->db->insert_id();
        $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
        $this->assertNull($item['archived_document_id']);

        // La FK doit accepter un document archive existant.
        $doc = $this->db->query("SELECT id FROM archived_documents LIMIT 1")->row_array();
        if ($doc) {
            $this->db->where('id', $item_id)->update('acceptance_items', array(
                'archived_document_id' => $doc['id'],
            ));
            $item = $this->db->where('id', $item_id)->get('acceptance_items')->row_array();
            $this->assertEquals($doc['id'], $item['archived_document_id']);
        }

        $this->db->where('id', $item_id)->delete('acceptance_items');

        // down() doit retirer la FK, l'index et la colonne proprement.
        $migration = new Migration_Acceptance_items_archived_document();
        $this->assertTrue($migration->down(), 'Migration 169 down() should succeed');
        $this->assertFalse($this->columnExists('acceptance_items', 'archived_document_id'));
        $this->assertFalse($this->indexExists('acceptance_items', 'idx_archived_document_id'));
        $this->assertFalse($this->foreignKeyExists('acceptance_items', 'fk_acceptance_items_archived_document'));

        // Restaure l'etat attendu pour le reste de la suite / l'application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('acceptance_items', 'archived_document_id'));
    }
}
