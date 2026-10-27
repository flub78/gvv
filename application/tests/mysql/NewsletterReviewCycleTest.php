<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests du cycle de relecture de la veille : analyse des réponses email,
 * tri et enregistrement des remarques, décisions, report et reprise,
 * email de transmission, commentaires de l'administrateur et brouillon
 * annoté, consignes de modification pour l'agent, différences entre versions.
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
        if (!$this->CI->db->table_exists('newsletter_annotations')) {
            $this->markTestSkipped('Migration 182 non appliquée');
        }
        require_once APPPATH . 'libraries/Newsletter_files.php';
        require_once APPPATH . 'libraries/Newsletter_review_cycle.php';
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_reviews_model');
        $this->CI->load->model('newsletter_annotations_model');
        $this->dir   = sys_get_temp_dir() . '/gvv_nl_cycle_' . uniqid();
        $this->files = new Newsletter_files(array('base_path' => $this->dir));
        $this->cycle = new Newsletter_review_cycle();

        $id = $this->CI->newsletter_model->create(array(
            'title' => 'ZZTest cycle relecture', 'period_start' => '1990-02-01', 'period_end' => '1990-02-28',
        ));
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $this->nl = $this->CI->newsletter_model->get($id);
    }

    protected function tearDown(): void
    {
        if ($this->nl) {
            $this->CI->db->query("DELETE FROM newsletter_reviews WHERE reviewer_email LIKE 'zztest_%@example.invalid'");
            $this->CI->db->query('DELETE FROM newsletter_annotations WHERE newsletter_id = ' . (int) $this->nl['id']);
            $this->CI->db->query("DELETE FROM newsletters WHERE title LIKE 'ZZTest cycle relecture%'");
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
        $this->assertSame('Brouillon 01/02/1990 – 28/02/1990 v2 ' . Newsletter_review_cycle::reference_tag($this->nl['id'], 2), $mail['subject']);
        $this->assertSame("Réponse avant le 26/10/2026\nMerci", $mail['body']);
        $default = Newsletter_review_cycle::build_review_email(array(), $this->nl, 2, '2026-10-26');
        $this->assertStringContainsString('information qui aurait logiquement dû figurer', $default['body']);
        $this->assertStringContainsString('sa source', $default['body']);
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

    // ── Décisions ─────────────────────────────────────────────────────────

    private function remark(string $content, string $msgid): int
    {
        $raw = $this->reply('zztest_a@example.invalid', 'RE: ' . $this->tag(), $content, $msgid);
        $r = $this->cycle->process_message($this->nl, $raw, array('zztest_a@example.invalid'), $this->files);
        $this->assertSame('retained', $r['status']);
        return (int) $r['review_id'];
    }

    public function test_decisions_postpone_and_take_back(): void
    {
        $m = $this->CI->newsletter_reviews_model;
        $id = $this->remark('Corriger le titre', '<d1@example.invalid>');

        $this->assertSame('modified_text_required', $m->decide($id, 'modifiee', '  '));
        $this->assertSame('invalid_decision', $m->decide($id, 'inconnue'));
        $this->assertTrue($m->decide($id, 'modifiee', 'Titre corrigé par l\'admin'));
        $this->assertSame(array($id), array_map('intval', array_column($m->get_accepted($this->nl['id'], $this->nl['review_iteration']), 'id')));

        // Toute remarque peut être reportée : elle reste rattachée à son édition
        // et elle est proposée à la reprise dans les autres éditions
        $this->assertTrue($m->decide($id, 'reportee'));
        $this->assertSame((int) $this->nl['id'], (int) $m->get($id)['newsletter_id']);
        $this->assertSame(array($id), array_map('intval', array_column($m->get_postponed_in_round($this->nl['id'], $this->nl['review_iteration']), 'id')));
        $this->assertContains($id, array_map('intval', array_column($m->get_postponed(), 'id')));
        $this->assertNotContains($id, array_map('intval', array_column($m->get_postponed($this->nl['id']), 'id')));
        $this->assertFalse($m->attach($id, $this->nl['id'], 1), 'pas de reprise dans sa propre édition');

        $other = $this->CI->newsletter_model->create(array(
            'title' => 'ZZTest cycle relecture suivante', 'period_start' => '1990-03-01', 'period_end' => '1990-03-31'));
        $this->assertTrue($m->attach($id, $other, 0));
        $this->assertSame('en_attente', $m->get($id)['decision']);
        $this->assertSame((int) $other, (int) $m->get($id)['newsletter_id']);
        $this->assertFalse($m->attach($id, $this->nl['id'], 1), 'une remarque reprise n\'est plus reportée');
    }

    // ── Commentaires et consignes de modification ─────────────────────────

    public function test_split_blocks_and_annotate(): void
    {
        $md = "# Titre\n\nPremier paragraphe\nsuite\n\n```\ncode\n\nencore du code\n```\n\n- a\n- b\n";
        $blocks = Newsletter_files::split_blocks($md);
        $this->assertSame(array("# Titre", "Premier paragraphe\nsuite", "```\ncode\n\nencore du code\n```", "- a\n- b"), $blocks);

        $annotated = Newsletter_files::annotate($md, array(
            array('block_index' => 1, 'label' => 'Commentaire du DTO', 'content' => "Le plafond à 400 ft\nsuffit"),
            array('block_index' => 9, 'label' => 'Note', 'content' => 'fin'),
        ));
        $this->assertStringContainsString("Premier paragraphe\nsuite\n\n> **Commentaire du DTO :** Le plafond à 400 ft\n> suffit\n\n```", $annotated);
        $this->assertStringEndsWith("- a\n- b\n\n> **Note :** fin\n", $annotated);
    }

    public function test_directives_contain_comments_and_retained_remarks_with_images(): void
    {
        $att = "--zzb" . md5('<i1@example.invalid>') . "\r\nContent-Type: image/png; name=\"piste.png\"\r\n"
             . "Content-Disposition: attachment; filename=\"piste.png\"\r\nContent-Transfer-Encoding: base64\r\n\r\n"
             . base64_encode('PNGDATA') . "\r\n";
        $raw = $this->reply('zztest_a@example.invalid', 'RE: ' . $this->tag(), 'Ajouter la photo de la piste', '<i1@example.invalid>', $att);
        $kept = $this->cycle->process_message($this->nl, $raw, array('zztest_a@example.invalid'), $this->files)['review_id'];
        $rejected = $this->remark('Remarque à rejeter', '<i2@example.invalid>');
        $modified = $this->remark('Texte du relecteur', '<i3@example.invalid>');
        $postponed = $this->remark('Article pour plus tard', '<i4@example.invalid>');
        $m = $this->CI->newsletter_reviews_model;
        $m->decide($kept, 'acceptee');
        $m->decide($rejected, 'rejetee');
        $m->decide($modified, 'modifiee', 'Texte de l\'administrateur');
        $m->decide($postponed, 'reportee');
        $remarks = array_merge($m->get_accepted($this->nl['id'], $this->nl['review_iteration']),
                               $m->get_postponed_in_round($this->nl['id'], $this->nl['review_iteration']));

        $draft = "# Lettre\n\nParagraphe sur les zones drones\n\nAutre paragraphe\n";
        $a = $this->CI->newsletter_annotations_model;
        $aid = $a->add(array('newsletter_id' => $this->nl['id'], 'version' => 2, 'block_index' => 1,
                             'anchor_text' => 'Paragraphe sur les zones drones', 'label' => 'Commentaire du DTO',
                             'content' => 'Le plafond à 400 ft'));
        $this->assertNotFalse($aid);
        $annotations = $a->get_for_version($this->nl['id'], 2);
        $this->assertCount(1, $annotations);
        $this->assertSame(array(), $a->get_for_version($this->nl['id'], 3));

        $d = Newsletter_review_cycle::build_directives($this->nl, 2, $draft, $annotations, $remarks, $this->files);
        $this->assertStringContainsString('version à produire : v3', $d['markdown']);
        $this->assertStringContainsString('« Paragraphe sur les zones drones »', $d['markdown']);
        $this->assertStringContainsString('> **Commentaire du DTO :** Le plafond à 400 ft', $d['markdown']);
        $this->assertStringContainsString('Ajouter la photo de la piste', $d['markdown']);
        $this->assertStringNotContainsString('Remarque à rejeter', $d['markdown']);
        $this->assertStringContainsString('### C1. APPLIQUER', $d['markdown']);
        $this->assertSame(3, substr_count($d['markdown'], '- Statut : **APPLIQUER**'), 'commentaire, remarque acceptée, remarque modifiée');
        $this->assertSame(1, substr_count($d['markdown'], '- Statut : **REPORTER**'));
        $this->assertRegExp("/REPORTER.*\\n\\nArticle pour plus tard/s", $d['markdown']);
        $this->assertRegExp("/Texte à appliquer[^\\n]*:\\n\\nTexte de l'administrateur\\n\\n[^\\n]*pour mémoire :\\n\\n> Texte du relecteur/", $d['markdown']);
        $img = 'remarque' . $kept . '_piste.png';
        $this->assertStringContainsString('](img/' . $img . ')', $d['markdown']);
        $this->assertSame(array($img), array_keys($d['images']));

        $zip = Newsletter_files::make_zip('consignes.md', $d['markdown'], $d['images']);
        $tmp = tempnam(sys_get_temp_dir(), 'zzt');
        file_put_contents($tmp, $zip);
        $z = new ZipArchive();
        $this->assertTrue($z->open($tmp));
        $this->assertSame('PNGDATA', $z->getFromName('img/' . $img));
        $this->assertSame($d['markdown'], $z->getFromName('consignes.md'));
        $z->close();
        unlink($tmp);

        $this->assertTrue($a->delete($aid));
    }

    public function test_diff_lines(): void
    {
        $diff = Newsletter_files::diff_lines("a\nb\nc\nd", "a\nB\nc\nd\ne");
        $ops = array_map(function ($d) { return $d['op'] . $d['line']; }, $diff);
        $this->assertSame(array(' a', '-b', '+B', ' c', ' d', '+e'), $ops);
        $this->assertSame(array(' x'), array_map(function ($d) { return $d['op'] . $d['line']; }, Newsletter_files::diff_lines('x', 'x')));
    }
}
