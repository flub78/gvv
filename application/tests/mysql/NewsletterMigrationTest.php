<?php

use PHPUnit\Framework\TestCase;

/**
 * Test des migrations 181 (newsletter) et 182 (consignes, exécutions,
 * suggestions) — up() et down().
 *
 * Les tables sont celles de la base partagée : leur contenu est sauvegardé
 * avant le test et restauré à l'identique après, même en cas d'échec.
 * Les migrations sont rejouées dans l'ordre (182 down → 181 down → 181 up →
 * 182 up) pour que le schéma final soit celui de la version courante.
 */
class NewsletterMigrationTest extends TestCase
{
    private const TABLES = array(
        'newsletters', 'newsletter_subscribers', 'newsletter_contexts',
        'newsletter_reviews', 'newsletter_sendings', 'newsletter_runs',
    );

    private $db;
    private $m181;
    private $m182;
    private $snapshot = array();

    protected function setUp(): void
    {
        $CI       = &get_instance();
        $this->db = $CI->db;
        if (!class_exists('CI_Migration')) {
            require_once BASEPATH . 'libraries/Migration.php';
        }
        if (!class_exists('Migration_Newsletter')) {
            require_once APPPATH . 'migrations/181_newsletter.php';
        }
        if (!class_exists('Migration_Newsletter_runs')) {
            require_once APPPATH . 'migrations/182_newsletter_runs.php';
        }
        $this->m181 = new Migration_Newsletter();
        $this->m182 = new Migration_Newsletter_runs();

        foreach (self::TABLES as $t) {
            if ($this->db->table_exists($t)) {
                $this->snapshot[$t] = $this->db->query("SELECT * FROM `$t`")->result_array();
            }
        }
    }

    protected function tearDown(): void
    {
        // Schéma courant restauré quel que soit l'état laissé par le test
        if (!$this->db->table_exists('newsletters')) {
            $this->m181->up();
        }
        if (!$this->db->table_exists('newsletter_runs')) {
            $this->m182->up();
        }

        $this->db->query('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($this->snapshot as $t => $rows) {
            $this->db->query("DELETE FROM `$t`");
            foreach ($rows as $row) {
                $this->db->insert($t, $row);
            }
        }
        $this->db->query('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function columnExists(string $table, string $column): bool
    {
        return $this->db->table_exists($table) && $this->db->field_exists($column, $table);
    }

    public function test_down_then_up_recreates_full_schema(): void
    {
        $this->assertTrue($this->m182->down());
        $this->assertFalse($this->db->table_exists('newsletter_runs'));
        $this->assertFalse($this->db->table_exists('newsletter_contexts'));
        $this->assertFalse($this->columnExists('newsletter_reviews', 'decision'));
        $this->assertFalse($this->columnExists('newsletters', 'review_deadline'));

        $this->assertTrue($this->m181->down());
        foreach (array('newsletters', 'newsletter_reviews', 'newsletter_subscribers', 'newsletter_sendings') as $t) {
            $this->assertFalse($this->db->table_exists($t), "$t devrait être supprimée");
        }

        $this->assertTrue($this->m181->up());
        $this->assertTrue($this->columnExists('newsletters', 'status'));
        $this->assertTrue($this->columnExists('newsletters', 'review_iteration'));
        $this->assertTrue($this->columnExists('newsletters', 'pdf_path'));
        $this->assertTrue($this->columnExists('newsletter_subscribers', 'unsubscribe_token'));
        $this->assertTrue($this->columnExists('newsletter_subscribers', 'confirmation_token'));
        $this->assertTrue($this->columnExists('newsletter_sendings', 'sent_at'));

        $this->assertTrue($this->m182->up());
        $this->assertTrue($this->columnExists('newsletter_contexts', 'content'));
        $this->assertTrue($this->columnExists('newsletter_runs', 'run_id'));
        $this->assertTrue($this->columnExists('newsletter_runs', 'package_path'));
        $this->assertTrue($this->columnExists('newsletters', 'review_sent_at'));
        $this->assertTrue($this->columnExists('newsletters', 'review_deadline'));
        foreach (array('message_id', 'kind', 'decision', 'admin_text', 'decided_by', 'decided_at') as $c) {
            $this->assertTrue($this->columnExists('newsletter_reviews', $c), "newsletter_reviews.$c manquante");
        }
    }

    public function test_up_181_is_idempotent(): void
    {
        $this->assertTrue($this->m181->up());
    }

    public function test_182_down_keeps_reviews_of_editions(): void
    {
        $this->assertTrue($this->m182->down());
        $this->assertTrue($this->db->table_exists('newsletter_reviews'));
        $this->assertTrue($this->m182->up());
        $this->assertTrue($this->columnExists('newsletter_reviews', 'decision'));
    }
}
