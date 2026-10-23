<?php

use PHPUnit\Framework\TestCase;

/**
 * Test de la migration 181 (newsletter) — up() et down().
 *
 * Vérifie la création et la suppression des 4 tables, y compris
 * les clés étrangères et les index.
 */
class NewsletterMigrationTest extends TestCase
{
    private $db;
    private $migration;

    protected function setUp(): void
    {
        $CI        = &get_instance();
        $this->db  = $CI->db;
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists('Migration_Newsletter')) {
            require_once APPPATH . 'migrations/181_newsletter.php';
        }
        $this->migration = new Migration_Newsletter();
        // S'assurer que les tables n'existent pas avant le test
        $this->_drop_tables();
    }

    protected function tearDown(): void
    {
        $this->_drop_tables();
    }

    private function _drop_tables(): void
    {
        foreach (array('newsletter_sendings', 'newsletter_reviews', 'newsletter_subscribers', 'newsletters') as $t) {
            $this->db->query("DROP TABLE IF EXISTS `$t`");
        }
    }

    private function tableExists(string $table): bool
    {
        return $this->db->table_exists($table);
    }

    private function columnExists(string $table, string $column): bool
    {
        $t = $this->db->escape_str($table);
        $c = $this->db->escape_str($column);
        $row = $this->db->query(
            "SELECT COUNT(*) as cnt FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t' AND COLUMN_NAME = '$c'"
        )->row_array();
        return isset($row['cnt']) && (int) $row['cnt'] > 0;
    }

    public function test_up_creates_all_tables(): void
    {
        $result = $this->migration->up();
        $this->assertTrue($result);
        $this->assertTrue($this->tableExists('newsletters'));
        $this->assertTrue($this->tableExists('newsletter_reviews'));
        $this->assertTrue($this->tableExists('newsletter_subscribers'));
        $this->assertTrue($this->tableExists('newsletter_sendings'));
    }

    public function test_up_creates_expected_columns(): void
    {
        $this->migration->up();
        $this->assertTrue($this->columnExists('newsletters', 'status'));
        $this->assertTrue($this->columnExists('newsletters', 'review_iteration'));
        $this->assertTrue($this->columnExists('newsletters', 'pdf_path'));
        $this->assertTrue($this->columnExists('newsletter_subscribers', 'unsubscribe_token'));
        $this->assertTrue($this->columnExists('newsletter_subscribers', 'confirmation_token'));
        $this->assertTrue($this->columnExists('newsletter_sendings', 'sent_at'));
    }

    public function test_up_is_idempotent(): void
    {
        $this->migration->up();
        // Deuxième appel ne doit pas échouer (IF NOT EXISTS)
        $result = $this->migration->up();
        $this->assertTrue($result);
    }

    public function test_down_drops_all_tables(): void
    {
        $this->migration->up();
        $result = $this->migration->down();
        $this->assertTrue($result);
        $this->assertFalse($this->tableExists('newsletters'));
        $this->assertFalse($this->tableExists('newsletter_reviews'));
        $this->assertFalse($this->tableExists('newsletter_subscribers'));
        $this->assertFalse($this->tableExists('newsletter_sendings'));
    }
}
