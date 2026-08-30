<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL tests for migration 141 (Lot 6, étape 6.3 — handler post-soumission optionnel).
 */
class FormsHandlerClassMigrationTest extends TestCase
{
    /** @var RealDatabase */
    private $db;

    /** @var array [id => handler_class] configured before the test — restored in tearDown */
    private $saved_handler_classes = array();

    protected function setUp(): void
    {
        $CI = &get_instance();
        $this->db = $CI->db;

        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        require_once APPPATH . 'migrations/141_forms_handler_class.php';

        // The DB is shared with the running app and the Playwright suite. These
        // tests drop/recreate forms.handler_class, which wipes every configured
        // handler (e.g. briefing-passager-ulm, set by migration 151). Snapshot
        // the current values so tearDown() can restore them even if a test fails
        // mid-roundtrip.
        $this->saved_handler_classes = array();
        if ($this->columnExists('forms', 'handler_class')) {
            $rows = $this->db
                ->select('id, handler_class')
                ->where('handler_class IS NOT NULL', null, false)
                ->get('forms')->result_array();
            foreach ($rows as $row) {
                $this->saved_handler_classes[(int) $row['id']] = $row['handler_class'];
            }
        }
    }

    protected function tearDown(): void
    {
        // Column must exist for the app / next test; recreate it if a test left
        // it dropped (assertion failure between down() and up()).
        if (!$this->columnExists('forms', 'handler_class')) {
            (new Migration_Forms_handler_class())->up();
        }

        // Restore the handler_class values the roundtrip erased.
        foreach ($this->saved_handler_classes as $id => $handler_class) {
            $this->db->where('id', (int) $id)
                ->update('forms', array('handler_class' => $handler_class));
        }

        // Drop any leftover fixture form (its INSERT/DELETE is not failure-safe).
        $this->db->query("DELETE FROM forms WHERE code LIKE 'mig141\\_test\\_%'");
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

        // down() retire la colonne (et efface donc les handler_class configurés,
        // ex. briefing-passager-ulm) ; setUp/tearDown les capture et les restaure.
        $migration = new Migration_Forms_handler_class();
        $this->assertTrue($migration->down(), 'Migration 141 down() should succeed');
        $this->assertFalse($this->columnExists('forms', 'handler_class'));

        // up() doit recréer la colonne proprement.
        $this->runMigrationUp();
        $this->assertTrue($this->columnExists('forms', 'handler_class'));
    }
}
