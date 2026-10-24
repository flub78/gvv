<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests du cycle de relecture de la veille (version 1) : analyse des réponses
 * email, tri et enregistrement des suggestions, email de transmission,
 * préparation de la demande de nouvelle version, différences entre versions.
 *
 * Les lignes créées et les pièces jointes conservées sont supprimées.
 */
class NewsletterReviewCycleTest extends TestCase
{
    private $CI;
    private $dir;
    private $files;
    private $cycle;
    private $nl;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        if (!$this->CI->db->table_exists('newsletter_runs')) {
            $this->markTestSkipped('Migration 182 non appliquée');
        }
        require_once APPPATH . 'libraries/Newsletter_files.php';
        require_once APPPATH . 'libraries/Newsletter_review_cycle.php';
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_reviews_model');
        $this->dir   = sys_get_temp_dir() . '/gvv_nl_cycle_' . uniqid();
        $this->files = new Newsletter_files(array('base_path' => $this->dir));
        $this->cycle = new Newsletter_review_cycle();

        $id = $this->CI->newsletter_model->create(array(
            'title' => 'ZZTest cycle relecture', 'period_start' => '2026-09-21', 'period_end' => '2026-10-21',
        ));
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $this->nl = $this->CI->newsletter_model->get($id);
    }

    protected function tearDown(): void
    {
        if ($this->nl) {
            $this->CI->db->query("DELETE FROM newsletter_reviews WHERE reviewer_email LIKE 'zztest_%@example.invalid'");
            $this->CI->db->query('DELETE FROM newsletters WHERE id = ' . (int) $this->nl['id']);
        }
        if ($this->dir && is_dir($this->dir)) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($this->dir, FilesystemIterator::SKIP_DOTS),
                                                RecursiveIteratorIterator::CHILD_FIRST);
            foreach ($it as $f) { $f->isDir() ? rmdir($f->getPathname()) : unlink($f->getPathname()); }
            rmdir($this->dir);
        }
    }

    private function reply($from, $subject, $body, $message_id, $extra_parts = ''): string
    {
        $boundary = 'zzb' . md5($message_id);
        return "Message-ID: $message_id\r\nFrom: \"Relecteur Test\" <$from>\r\nSubject: $subject\r\n"
            . "MIME-Version: 1.0\r\nContent-Type: multipart/mixed;\r\n boundary=\"$boundary\"\r\n\r\n"
            . "--$boundary\r\nContent-Type: text/plain; charset=UTF-8\r\nContent-Transfer-Encoding: quoted-printable\r\n\r\n"
            . quoted_printable_encode($body) . "\r\n"
            . $extra_parts
            . "--$boundary--\r\n";
    }

    private function tag(): string
    {
        return Newsletter_review_cycle::reference_tag($this->nl['id'], 1);
    }

    // ── Références et adresses ────────────────────────────────────────────

    public function test_reference_round_trip(): void
    {
        $subject = 'RE: [Veille] Brouillon (v3) ' . Newsletter_review_cycle::reference_tag(42, 3);
        $this->assertSame(array('newsletter_id' => 42, 'version' => 3), Newsletter_review_cycle::parse_reference($subject));
        $this->assertNull(Newsletter_review_cycle::parse_reference('RE: autre sujet'));
    }

    public function test_parse_addresses(): void
    {
        $this->assertSame(array('a@example.com', 'b@example.com'),
            Newsletter_review_cycle::parse_addresses("A@example.com; b@example.com,\n pas-une-adresse, a@example.com"));
    }

    public function test_review_email_uses_template_and_reference(): void
    {
        $cfg = array('newsletter_review_subject' => 'Brouillon {period} v{version}',
                     'newsletter_review_body' => 'Réponse avant le {deadline}\nMerci', 'newsletter_smtp_from_name' => 'Veille');
        $mail = Newsletter_review_cycle::build_review_email($cfg, $this->nl, 2, '2026-10-26');
        $this->assertSame('Brouillon 21/09/2026 – 21/10/2026 v2 ' . Newsletter_review_cycle::reference_tag($this->nl['id'], 2), $mail['subject']);
        $this->assertSame("Réponse avant le 26/10/2026\nMerci", $mail['body']);
        $this->assertSame(date('Y-m-d', strtotime('+2 days', mktime(12, 0, 0, 10, 24, 2026))),
                          Newsletter_review_cycle::deadline(2, mktime(12, 0, 0, 10, 24, 2026)));
    }

    // ── Analyse des emails ────────────────────────────────────────────────

    public function test_mail_parser_decodes_and_strips_quote(): void
    {
        $raw = $this->reply('ZZTest_A@Example.invalid', 'RE: x', "Corriger la date de l'AIC.\n\nLe 24/10/2026, Veille a écrit :\n> texte cité", '<p1@example.invalid>');
        $msg = Newsletter_mail_parser::parse($raw);
        $this->assertSame('zztest_a@example.invalid', $msg['from_email']);
        $this->assertSame('Relecteur Test', $msg['from_name']);
        $this->assertSame('<p1@example.invalid>', $msg['message_id']);
        $this->assertSame("Corriger la date de l'AIC.", Newsletter_mail_parser::strip_quoted($msg['text']));
    }

    public function test_mail_parser_reads_html_only_and_encoded_subject(): void
    {
        $raw = "Message-ID: <h1@example.invalid>\nFrom: x@example.invalid\nSubject: =?UTF-8?B?" . base64_encode('Réponse ✓') . "?=\n"
            . "Content-Type: text/html; charset=UTF-8\n\n<p>Bonjour<br>Ajouter le REX</p><blockquote>cité</blockquote>";
        $msg = Newsletter_mail_parser::parse($raw);
        $this->assertSame('Réponse ✓', $msg['subject']);
        $this->assertStringContainsString('Ajouter le REX', $msg['text']);
        $this->assertStringNotContainsString('cité', $msg['text']);
    }

    // ── Tri des réponses ──────────────────────────────────────────────────

    public function test_retained_reply_becomes_pending_suggestion_with_attachment(): void
    {
        $att = "--zzb" . md5('<r1@example.invalid>') . "\r\nContent-Type: image/png; name=\"photo piste.png\"\r\n"
             . "Content-Disposition: attachment; filename=\"photo piste.png\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . base64_encode('PNGDATA') . "\r\n";
        $raw = $this->reply('zztest_a@example.invalid', 'RE: Brouillon ' . $this->tag(), 'Ajouter la photo', '<r1@example.invalid>', $att);

        $r = $this->cycle->process_message($this->nl, $raw, array('zztest_a@example.invalid'), $this->files);
        $this->assertSame('retained', $r['status']);

        $s = $this->CI->newsletter_reviews_model->get($r['review_id']);
        $this->assertSame('en_attente', $s['decision']);
        $this->assertSame('Ajouter la photo', $s['content']);
        $this->assertSame((int) $this->nl['review_iteration'], (int) $s['iteration']);
        $this->assertSame(1, (int) $s['has_attachments']);
        $this->assertSame(array('photo_piste.png'), $this->files->list_reply_files($r['review_id']));
        $this->assertSame('PNGDATA', $this->files->read($this->files->reply_dir($r['review_id']) . 'photo_piste.png'));

        // Même message relu : ignoré comme déjà récupéré
        $again = $this->cycle->process_message($this->nl, $raw, array('zztest_a@example.invalid'), $this->files);
        $this->assertSame('ignored', $again['status']);
        $this->assertSame('déjà récupérée', $again['reason']);
    }

    public function test_unauthorized_unrelated_and_empty_replies_are_not_saved(): void
    {
        $ok = array('zztest_a@example.invalid');
        $stranger = $this->cycle->process_message($this->nl,
            $this->reply('zztest_x@example.invalid', 'RE: ' . $this->tag(), 'spam', '<x1@example.invalid>'), $ok, $this->files);
        $this->assertSame('rejected', $stranger['status']);
        $this->assertSame('expéditeur non autorisé', $stranger['reason']);

        $other = $this->cycle->process_message($this->nl,
            $this->reply('zztest_a@example.invalid', 'Autre sujet', 'x', '<x2@example.invalid>'), $ok, $this->files);
        $this->assertSame('ignored', $other['status']);

        $empty = $this->cycle->process_message($this->nl,
            $this->reply('zztest_a@example.invalid', 'RE: ' . $this->tag(), "> seulement la citation", '<x3@example.invalid>'), $ok, $this->files);
        $this->assertSame('rejected', $empty['status']);

        $this->assertSame(array(), $this->CI->newsletter_reviews_model->get_for_newsletter($this->nl['id']));
    }

    // ── Révision ──────────────────────────────────────────────────────────

    public function test_revision_prompt_contains_retained_texts_only(): void
    {
        $sugg = array(
            array('decision' => 'acceptee', 'admin_text' => NULL, 'content' => 'Corriger le titre', 'kind' => 'remarque',
                  'reviewer_name' => 'Alice', 'reviewer_email' => 'a@x'),
            array('decision' => 'modifiee', 'admin_text' => 'Texte reformulé par l\'admin', 'content' => 'texte brut', 'kind' => 'article',
                  'reviewer_name' => '', 'reviewer_email' => 'b@x'),
        );
        $p = Newsletter_review_cycle::build_revision_prompt("# Brouillon\n", $sugg, 'RÈGLES DE RÉDACTION');
        $this->assertStringContainsString('RÈGLES DE RÉDACTION', $p['system']);
        $this->assertStringContainsString('Corriger le titre', $p['user']);
        $this->assertStringContainsString("Article à intégrer (b@x)", $p['user']);
        $this->assertStringContainsString("Texte reformulé par l'admin", $p['user']);
        $this->assertStringNotContainsString('texte brut', $p['user']);
        $this->assertStringEndsWith("# Brouillon\n", $p['user']);
    }

    public function test_clean_ai_output_removes_code_fence(): void
    {
        $this->assertSame("# Titre\n\nTexte\n", Newsletter_review_cycle::clean_ai_output("```markdown\n# Titre\n\nTexte\n```"));
        $this->assertSame("# Titre\n", Newsletter_review_cycle::clean_ai_output("# Titre"));
    }

    public function test_diff_lines(): void
    {
        $diff = Newsletter_files::diff_lines("a\nb\nc\nd", "a\nB\nc\nd\ne");
        $ops = array_map(function ($d) { return $d['op'] . $d['line']; }, $diff);
        $this->assertSame(array(' a', '-b', '+B', ' c', ' d', '+e'), $ops);
        $this->assertSame(array(' x'), array_map(function ($d) { return $d['op'] . $d['line']; }, Newsletter_files::diff_lines('x', 'x')));
    }
}
