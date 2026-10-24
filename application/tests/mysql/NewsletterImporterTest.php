<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests du stockage des fichiers de la veille (Newsletter_files) et de
 * l'import d'un paquet d'exécution (Newsletter_importer).
 *
 * Les fichiers sont écrits dans un répertoire temporaire propre au test,
 * supprimé à la fin ; les lignes créées en base sont supprimées.
 */
class NewsletterImporterTest extends TestCase
{
    private $CI;
    private $dir;
    private $files;
    private $importer;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        if (!$this->CI->db->table_exists('newsletter_runs')) {
            $this->markTestSkipped('Migration 182 non appliquée');
        }
        require_once APPPATH . 'libraries/Newsletter_files.php';
        require_once APPPATH . 'libraries/Newsletter_importer.php';
        $this->dir = sys_get_temp_dir() . '/gvv_nl_test_' . uniqid();
        $this->files = new Newsletter_files(array('base_path' => $this->dir));
        $this->importer = new Newsletter_importer(array('files' => $this->files));
    }

    protected function tearDown(): void
    {
        if ($this->CI->db->table_exists('newsletter_runs')) {
            $ids = $this->CI->db->query("SELECT id FROM newsletters WHERE title LIKE 'ZZTest import%'")->result_array();
            $this->CI->db->query("DELETE FROM newsletter_runs WHERE model = 'zztest-model'");
            foreach ($ids as $r) {
                $this->CI->db->query('DELETE FROM newsletters WHERE id = ' . (int) $r['id']);
            }
        }
        $this->rrmdir($this->dir);
    }

    private function rrmdir($d): void
    {
        if (!$d || !is_dir($d)) return;
        foreach (array_diff(scandir($d), array('.', '..')) as $f) {
            is_dir("$d/$f") ? $this->rrmdir("$d/$f") : unlink("$d/$f");
        }
        rmdir($d);
    }

    private function params(array $over = array()): array
    {
        return array_merge(array(
            'title' => 'ZZTest import', 'period_start' => '2026-09-21', 'period_end' => '2026-10-21',
            'draft' => "# Veille\n\nArticle", 'report' => "# Rapport\n", 'inventory' => "source_id;doc_id\n",
            'origin' => 'desktop', 'model' => 'zztest-model', 'cost_usd' => '2.50',
        ), $over);
    }

    // ── Newsletter_files ──────────────────────────────────────────────────

    public function test_versions_are_numbered_and_listed(): void
    {
        $v1 = $this->files->save_version(42, 'un');
        $v2 = $this->files->save_version(42, 'deux');
        $this->assertSame(1, $v1['version']);
        $this->assertSame(2, $v2['version']);
        $this->assertSame(array(1, 2), array_column($this->files->list_versions(42), 'version'));
        $this->assertSame('deux', $this->files->read($v2['path']));
        $this->assertSame(2, Newsletter_files::version_of($v2['path']));
    }

    public function test_read_refuses_paths_outside_storage(): void
    {
        $this->assertFalse($this->files->read(APPPATH . 'config/config.php'));
        $this->assertFalse($this->files->read($this->dir . '/../../etc/passwd'));
    }

    public function test_remove_internal_notes(): void
    {
        $md = "# Veille\n\n> **BROUILLON v9 – NON VALIDÉ.** À relire.\n> suite de l'avertissement\n\nIntro\n\n"
            . "## 1. Article\n\nTexte\n\n---\n\n## SECTION INTERNE – à retirer\n\nNote CPS\n\n### Détail\n\nautre note\n\n---\n---\n";
        $clean = Newsletter_files::remove_internal_notes($md);
        $this->assertStringNotContainsString('BROUILLON', $clean);
        $this->assertStringNotContainsString('avertissement', $clean);
        $this->assertStringNotContainsString('SECTION INTERNE', $clean);
        $this->assertStringNotContainsString('Note CPS', $clean);
        $this->assertStringNotContainsString('autre note', $clean);
        $this->assertStringContainsString("## 1. Article\n\nTexte", $clean);
        $this->assertStringEndsWith("Texte\n", $clean);
    }

    public function test_internal_section_stops_at_next_section_of_same_level(): void
    {
        $md = "## SECTION INTERNE\n\nnote\n\n## Parutions\n\nPlaneur Info\n";
        $clean = Newsletter_files::remove_internal_notes($md);
        $this->assertSame("## Parutions\n\nPlaneur Info\n", $clean);
    }

    public function test_find_open_mentions_ignores_links_and_images(): void
    {
        $md = "Voir [la fiche](https://bea.aero/x) ![Planeur](img/p.svg) [À compléter : date] et [ ].";
        $this->assertSame(array('[À compléter : date]', '[ ]'), Newsletter_files::find_open_mentions($md));
    }

    // ── Newsletter_importer ───────────────────────────────────────────────

    public function test_import_creates_edition_version_and_run(): void
    {
        $r = $this->importer->import($this->params());
        $this->assertTrue($r['ok'], implode(' ', $r['errors']));
        $this->assertSame(array(), array_values(array_filter($r['warnings'], function ($w) {
            return strpos($w, 'consignes') === FALSE; // dépend de l'état partagé des consignes
        })));

        $nl = $this->CI->newsletter_model->get($r['newsletter_id']);
        $this->assertSame('draft', $nl['status']);
        $this->assertSame(1, Newsletter_files::version_of($nl['draft_path']));
        $this->assertSame("# Veille\n\nArticle", $this->files->read($nl['draft_path']));

        $run = $this->CI->newsletter_runs_model->get_by_run_id($r['run_id']);
        $this->assertSame('succeeded', $run['status']);
        $this->assertSame('desktop', $run['origin']);
        $this->assertSame('2.50', $run['cost_usd']);
        $this->assertNotNull($this->files->run_file($r['run_id'], Newsletter_files::REPORT_FILE));

        $inv = $this->importer->current_inventory();
        $this->assertSame($r['run_id'], $inv['run_id']);
        $this->assertSame("source_id;doc_id\n", $inv['content']);
    }

    public function test_import_without_report_and_inventory_warns(): void
    {
        $r = $this->importer->import($this->params(array('report' => '', 'inventory' => '')));
        $this->assertTrue($r['ok']);
        $all = implode(' ', $r['warnings']);
        $this->assertStringContainsString("rapport d'activité", $all);
        $this->assertStringContainsString('inventaire', $all);
        $this->assertNull($this->files->run_file($r['run_id'], Newsletter_files::INVENTORY_FILE));
    }

    public function test_import_rejects_invalid_input_without_side_effects(): void
    {
        $before = (int) $this->CI->db->query('SELECT COUNT(*) AS n FROM newsletters')->row_array()['n'];
        $r = $this->importer->import($this->params(array('draft' => '  ', 'period_end' => '2026-01-01', 'title' => '')));
        $this->assertFalse($r['ok']);
        $this->assertCount(3, $r['errors']);
        $this->assertSame($before, (int) $this->CI->db->query('SELECT COUNT(*) AS n FROM newsletters')->row_array()['n']);
        $this->assertFalse(is_dir($this->dir . "/runs"));
    }
}
