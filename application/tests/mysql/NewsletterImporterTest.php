<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests du stockage des fichiers de la veille (Newsletter_files) et de
 * l'import d'un brouillon de l'agent de rédaction (Newsletter_importer) :
 * lecture du titre, de la période et de la version, fichier .md ou archive
 * .zip avec images, versions successives d'une édition, refus.
 *
 * Les fichiers sont écrits dans un répertoire temporaire propre au test,
 * supprimé à la fin ; les éditions créées (période en 1990) sont supprimées.
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
        if (!$this->CI->db->table_exists('newsletter_annotations')) {
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
        if ($this->CI->db->table_exists('newsletter_annotations')) {
            $this->CI->db->query("DELETE FROM newsletters WHERE title LIKE 'ZZTest import%'");
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

    private function draft(int $version, string $extra = ''): string
    {
        return "# ZZTest import – janvier 1990\n\n**Aéroclub**\nPériode : du 2 janvier au 30 janvier 1990.\n\n"
            . "<!-- BROUILLON -->\n> **BROUILLON v$version – NON VALIDÉ.** À relire.\n<!-- /BROUILLON -->\n\n## 1. À retenir\n\nArticle\n" . $extra;
    }

    private function zip(array $entries): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'zzt');
        $zip = new ZipArchive();
        $zip->open($tmp, ZipArchive::OVERWRITE);
        foreach ($entries as $name => $content) $zip->addFromString($name, $content);
        $zip->close();
        $data = file_get_contents($tmp);
        unlink($tmp);
        return $data;
    }

    private function countNewsletters(): int
    {
        return (int) $this->CI->db->query('SELECT COUNT(*) AS n FROM newsletters')->row_array()['n'];
    }

    // ── Lecture des métadonnées ───────────────────────────────────────────

    public function test_parse_meta_reads_title_period_and_version(): void
    {
        $meta = Newsletter_importer::parse_meta($this->draft(3));
        $this->assertSame(array('title' => 'ZZTest import – janvier 1990', 'period_start' => '1990-01-02',
                                'period_end' => '1990-01-30', 'version' => 3), $meta);
    }

    public function test_parse_meta_period_formats(): void
    {
        $cases = array(
            'Période : du 21 septembre au 21 octobre 2026 (avec un rattrapage)' => array('2026-09-21', '2026-10-21'),
            '**Période :** du 1er décembre au 15 janvier 2027'                  => array('2026-12-01', '2027-01-15'),
            'Période : du 21/09/2026 au 21/10/2026.'                            => array('2026-09-21', '2026-10-21'),
            'Période couverte : du 2026-09-01 au 2026-09-30'                    => array('2026-09-01', '2026-09-30'),
        );
        foreach ($cases as $line => $expected) {
            $m = Newsletter_importer::parse_meta("# T\n$line\n");
            $this->assertSame($expected, array($m['period_start'], $m['period_end']), $line);
        }
        $none = Newsletter_importer::parse_meta("Texte sans repère");
        $this->assertSame(array(NULL, NULL, NULL, NULL), array_values($none));
    }

    // ── Newsletter_files ──────────────────────────────────────────────────

    public function test_versions_are_stored_with_images(): void
    {
        $p3 = $this->files->save_version(42, 3, 'trois', array('logo.png' => 'PNG'));
        $p5 = $this->files->save_version(42, 5, 'cinq');
        $this->assertFalse($this->files->save_version(42, 5, 'encore'), 'une version existante ne doit pas être écrasée');
        $this->assertSame(array(3, 5), array_column($this->files->list_versions(42), 'version'));
        $this->assertSame(5, Newsletter_files::version_of($p5));
        $this->assertSame('trois', $this->files->read($p3));
        $this->assertSame('PNG', $this->files->read($this->files->image_path(42, 3, 'logo.png')));
        $this->assertNull($this->files->image_path(42, 3, '../../v5/brouillon.md'));
    }

    public function test_read_refuses_paths_outside_storage(): void
    {
        $this->assertFalse($this->files->read(APPPATH . 'config/config.php'));
        $this->assertFalse($this->files->read($this->dir . '/../../etc/passwd'));
    }

    public function test_remove_internal_notes(): void
    {
        $md = "# Veille\n\n<!-- BROUILLON -->\n> **BROUILLON v9 – NON VALIDÉ.** À relire.\n>\n> suite de l'avertissement\n<!-- /BROUILLON -->\n\nIntro\n\n"
            . "## 1. Article\n\nTexte\n\n{{ note de rédaction\nsur deux lignes [date] }}\n\n<!-- INTERNE -->\nnote balisée\n<!-- /INTERNE -->\n\n---\n\n## SECTION INTERNE – à retirer\n\nNote CPS\n\n### Détail\n\nautre note\n\n---\n---\n";
        $clean = Newsletter_files::remove_internal_notes($md);
        foreach (array('BROUILLON', 'avertissement', 'note balisée', 'note de rédaction', '{{', 'SECTION INTERNE', 'Note CPS', 'autre note', '<!--') as $gone) {
            $this->assertStringNotContainsString($gone, $clean);
        }
        $this->assertStringContainsString("## 1. Article\n\nTexte", $clean);
        $this->assertStringEndsWith("Texte\n", $clean);
    }

    public function test_append_validation(): void
    {
        $this->assertSame("Texte\n\n*Validé le 25/10/2026 par Jean Dupont*\n",
                          Newsletter_files::append_validation("Texte\n\n", 'Validé le 25/10/2026 par Jean Dupont'));
    }

    public function test_internal_section_stops_at_next_section_of_same_level(): void
    {
        $md = "## SECTION INTERNE\n\nnote\n\n## Parutions\n\nPlaneur Info\n";
        $this->assertSame("## Parutions\n\nPlaneur Info\n", Newsletter_files::remove_internal_notes($md));
    }

    public function test_find_open_mentions_ignores_links_images_and_comments(): void
    {
        $md = "Voir [la fiche](https://bea.aero/x) ![Planeur](img/p.svg) [À compléter : date] et [ ]. <!-- [caché] --> {{ note [date] }}";
        $this->assertSame(array('[À compléter : date]', '[ ]'), Newsletter_files::find_open_mentions($md));
    }

    // ── Newsletter_importer ───────────────────────────────────────────────

    public function test_import_markdown_creates_edition(): void
    {
        $r = $this->importer->import('brouillon.md', $this->draft(2));
        $this->assertTrue($r['ok'], implode(' ', $r['errors']));
        $this->assertTrue($r['created']);
        $this->assertSame(2, $r['version']);

        $nl = $this->CI->newsletter_model->get($r['newsletter_id']);
        $this->assertSame('ZZTest import – janvier 1990', $nl['title']);
        $this->assertSame('1990-01-02', $nl['period_start']);
        $this->assertSame('draft', $nl['status']);
        $this->assertSame(2, Newsletter_files::version_of($nl['draft_path']));
        $this->assertSame($this->draft(2), $this->files->read($nl['draft_path']));
    }

    public function test_import_zip_resolves_images_and_rewrites_references(): void
    {
        $md = $this->draft(1, "\n![Planeur](../../assets/img/planeur.svg) REX\n\n![Photo](photos/piste.png)\n\n![Absente](img/absente.png)\n");
        $zip = $this->zip(array(
            'lettre/brouillon_v1.md' => $md,
            'assets/img/planeur.svg' => '<svg xmlns="http://www.w3.org/2000/svg"/>',
            'lettre/photos/piste.png' => 'PNGDATA',
            'lettre/notes.txt'        => 'ignoré',
        ));
        $r = $this->importer->import('lettre.zip', $zip);
        $this->assertTrue($r['ok'], implode(' ', $r['errors']));
        $this->assertCount(1, $r['warnings'], 'image absente signalée');

        $nl = $this->CI->newsletter_model->get($r['newsletter_id']);
        $stored = $this->files->read($nl['draft_path']);
        $this->assertStringContainsString('![Planeur](img/planeur.svg)', $stored);
        $this->assertStringContainsString('![Photo](img/piste.png)', $stored);
        $this->assertStringContainsString('![Absente](img/absente.png)', $stored);
        $this->assertSame(array('piste.png', 'planeur.svg'), array_keys($this->files->list_images($nl['id'], 1)));
    }

    public function test_next_version_joins_edition_and_returns_to_draft(): void
    {
        $r1 = $this->importer->import('v1.md', $this->draft(1));
        $this->CI->newsletter_model->transition($r1['newsletter_id'], 'reviewing');

        $r2 = $this->importer->import('v3.md', $this->draft(3));
        $this->assertTrue($r2['ok'], implode(' ', $r2['errors']));
        $this->assertFalse($r2['created']);
        $this->assertSame($r1['newsletter_id'], $r2['newsletter_id']);
        $nl = $this->CI->newsletter_model->get($r1['newsletter_id']);
        $this->assertSame('draft', $nl['status']);
        $this->assertSame(3, Newsletter_files::version_of($nl['draft_path']));
        $this->assertSame(array(1, 3), array_column($this->files->list_versions($nl['id']), 'version'));

        $again = $this->importer->import('v3.md', $this->draft(3));
        $this->assertFalse($again['ok']);
        $this->assertCount(1, $again['errors']);
        $this->assertSame(array(1, 3), array_column($this->files->list_versions($nl['id']), 'version'));
    }

    public function test_import_refused_for_validated_edition(): void
    {
        $r1 = $this->importer->import('v1.md', $this->draft(1));
        $this->CI->newsletter_model->transition($r1['newsletter_id'], 'approved');
        $r2 = $this->importer->import('v2.md', $this->draft(2));
        $this->assertFalse($r2['ok']);
        $this->assertSame(array(1), array_column($this->files->list_versions($r1['newsletter_id']), 'version'));
    }

    public function test_import_rejects_invalid_input_without_side_effects(): void
    {
        $before = $this->countNewsletters();

        $missing = $this->importer->import('x.md', "Pas de titre ni de période");
        $this->assertFalse($missing['ok']);
        $this->assertCount(3, $missing['errors']);

        $this->assertFalse($this->importer->import('x.pdf', 'PDF')['ok']);
        $this->assertFalse($this->importer->import('x.zip', 'pas une archive')['ok']);
        $two = $this->importer->import('x.zip', $this->zip(array('a.md' => $this->draft(1), 'b.md' => $this->draft(1))));
        $this->assertFalse($two['ok']);
        $evil = $this->importer->import('x.zip', $this->zip(array('../evil.md' => $this->draft(1))));
        $this->assertFalse($evil['ok']);

        $this->assertSame($before, $this->countNewsletters());
        $this->assertFalse(is_dir($this->dir . '/editions'));
    }

    public function test_import_rejects_oversized_or_crowded_archives(): void
    {
        $before = $this->countNewsletters();

        $big_md = $this->importer->import('x.zip', $this->zip(array(
            'lettre.md' => $this->draft(1) . str_repeat("x", Newsletter_importer::MAX_MARKDOWN_SIZE))));
        $this->assertFalse($big_md['ok']);
        $this->assertCount(1, $big_md['errors']);

        $big_img = $this->importer->import('x.zip', $this->zip(array(
            'lettre.md' => $this->draft(1), 'img/a.png' => str_repeat("\0", Newsletter_importer::MAX_IMAGE_SIZE + 1))));
        $this->assertFalse($big_img['ok']);
        $this->assertCount(1, $big_img['errors']);

        $entries = array('lettre.md' => $this->draft(1));
        for ($i = 0; $i < Newsletter_importer::MAX_ZIP_FILES; $i++) $entries["img/$i.png"] = 'png';
        $crowded = $this->importer->import('x.zip', $this->zip($entries));
        $this->assertFalse($crowded['ok']);

        $this->assertSame($before, $this->countNewsletters());
        $this->assertFalse(is_dir($this->dir . '/editions'));
    }
}
