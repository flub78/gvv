<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 141 (Lot 6, étape 6.3 — handler post-soumission optionnel).
 */
class FormsHandlerClassMigrationTest extends TestCase
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
        require_once APPPATH . 'migrations/141_forms_handler_class.php';
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
        $migration = new Migration_Forms_handler_class();
        $this->assertTrue($migration->up(), 'Migration 141 up() should succeed');
    }

    public function testMigration141AddsHandlerClassColumn()
    {
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'handler_class'));
    }

    public function testMigration141UpIsIdempotent()
    {
        $this->runMigrationUp();
        $this->runMigrationUp();

        $this->assertTrue($this->columnExists('forms', 'handler_class'));
    }

    public function testMigration141DefaultsAndDownRoundtrip()
    {
        $this->runMigrationUp();

        // handler_class doit rester NULL par défaut pour un formulaire existant.
        $this->db->insert('forms', array(
            'code'        => 'mig141_test_' . time(),
            'title'       => 'Migration 141 test',
            'public_slug' => 'mig141-test-' . time(),
            'status'      => 'draft',
        ));
        $form_id = $this->db->insert_id();

        $form = $this->db->where('id', $form_id)->get('forms')->row_array();
        $this->assertNull($form['handler_class']);

        $this->db->where('id', $form_id)->delete('forms');

        // down() doit retirer la colonne proprement.
        $migration = new Migration_Forms_handler_class();
        $this->assertTrue($migration->down(), 'Migration 141 down() should succeed');
        $this->assertFalse($this->columnExists('forms', 'handler_class'));

        // Restaure l'état attendu pour le reste de la suite / l'application.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('forms', 'handler_class'));
    }
}
