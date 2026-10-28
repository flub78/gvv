<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests de Newsletter_mailer : texte de l'email d'accueil, choix du dernier
 * numéro paru et envoi d'accueil à la confirmation d'un abonnement (cas
 * sans envoi SMTP : déjà reçu, PDF absent), recherche d'un abonné par token
 * de confirmation, abonnés non atteints (reprise d'un accueil en échec).
 *
 * Chaque test s'exécute dans une transaction annulée à la fin : les
 * éditions de test, publiées en 2099 donc « dernier numéro », ne sont
 * jamais visibles de gvv.net.
 */
class NewsletterMailerTest extends TestCase
{
    private $CI;
    private $mailer;
    private $lang;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        if (!$this->CI->db->table_exists('newsletter_sendings')) {
            $this->markTestSkipped('Migration 181 non appliquée');
        }
        $this->CI->load->helper('url');
        require_once APPPATH . 'libraries/Newsletter_mailer.php';
        $this->mailer = new Newsletter_mailer();
        $this->CI->db->trans_begin();
        // Le banc de test ne traduit pas : textes réels du fichier français
        $lang = array();
        include APPPATH . 'language/french/newsletter_lang.php';
        $this->lang = $this->CI->lang;
        $this->CI->lang = new class($lang) {
            private $t;
            public function __construct($t) { $this->t = $t; }
            public function load($file) { return TRUE; }
            public function line($key) { return $this->t[$key] ?? $key; }
        };
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->CI->lang = $this->lang;
        $this->CI->db->trans_rollback();
        $this->cleanup();
    }

    private function cleanup(): void
    {
        $this->CI->db->query("DELETE FROM newsletter_sendings WHERE subscriber_id IN "
            . "(SELECT id FROM newsletter_subscribers WHERE email LIKE 'zztest_mailer_%@example.invalid')");
        $this->CI->db->query("DELETE FROM newsletter_subscribers WHERE email LIKE 'zztest_mailer_%@example.invalid'");
        $this->CI->db->query("DELETE FROM newsletters WHERE title LIKE 'ZZTest mailer%'");
    }

    /** Abonné confirmé */
    private function subscriber(): array
    {
        $res = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email' => 'zztest_mailer_' . uniqid() . '@example.invalid', 'first_name' => 'Zoé', 'last_name' => 'Test',
        ));
        $this->CI->newsletter_subscribers_model->confirm($res['confirmation_token']);
        return $this->CI->newsletter_subscribers_model->get($res['subscriber_id']);
    }

    /** Édition publiée dans le futur, donc la plus récente par défaut */
    private function published($pdf_path, $published_at = '2099-02-01 10:00:00'): array
    {
        $id = $this->CI->newsletter_model->create(array(
            'title' => 'ZZTest mailer', 'period_start' => '2099-01-01', 'period_end' => '2099-01-31',
        ));
        $this->CI->newsletter_model->update($id, array(
            'status' => 'published', 'published_at' => $published_at, 'pdf_path' => $pdf_path,
        ));
        return $this->CI->newsletter_model->get($id);
    }

    public function test_welcome_message_announces_latest_issue(): void
    {
        $sub = $this->subscriber();
        $nl  = $this->published(NULL);
        $msg = $this->mailer->message($nl, $sub, array('newsletter_smtp_from' => 'veille@example.invalid'), 'Club test', TRUE);

        $welcome = sprintf($this->CI->lang->line('nl_mail_welcome_intro'),
                           $this->CI->config->item('nom_club') ?: 'Club test', '01/01/2099', '31/01/2099');
        $this->assertStringContainsString($welcome, $msg['body']);
        $this->assertStringContainsString('veille@example.invalid', $msg['body']);
        $this->assertStringContainsString('newsletter_public/unsubscribe/' . $sub['unsubscribe_token'], $msg['body']);
        $this->assertStringContainsString('01/01/2099', $msg['subject']);
    }

    public function test_regular_message_keeps_standard_intro(): void
    {
        $msg = $this->mailer->message($this->published(NULL), $this->subscriber(), array(), 'Club test');
        $this->assertStringNotContainsString($this->CI->lang->line('nl_mail_welcome_intro'), $msg['body']);
    }

    public function test_latest_published_is_most_recent(): void
    {
        $nl = $this->published(NULL);
        $this->assertSame((int) $nl['id'], (int) $this->mailer->latest_published()['id']);
    }

    public function test_send_latest_skips_issue_already_received(): void
    {
        $sub = $this->subscriber();
        $nl  = $this->published(NULL);
        $this->CI->newsletter_subscribers_model->record_sending($nl['id'], $sub['id']);
        $this->assertSame(Newsletter_mailer::LATEST_ALREADY, $this->mailer->send_latest($sub));
    }

    public function test_send_latest_fails_without_pdf_and_records_nothing(): void
    {
        $sub = $this->subscriber();
        $nl  = $this->published('/nonexistent/zztest_mailer.pdf');
        $this->assertSame(Newsletter_mailer::LATEST_FAILED, $this->mailer->send_latest($sub));
        $this->assertFalse($this->CI->newsletter_subscribers_model->already_sent($nl['id'], $sub['id']));
    }

    public function test_send_refuses_newsletter_without_pdf(): void
    {
        $sub = $this->subscriber();
        $nl  = $this->published(NULL);
        $this->assertFalse($this->mailer->send($nl, $sub, array(), array('from' => 'veille@example.invalid', 'from_name' => 'Club test')));
        $this->assertFalse($this->CI->newsletter_subscribers_model->already_sent($nl['id'], $sub['id']));
    }

    public function test_not_sent_includes_later_subscribers_for_latest_issue_only(): void
    {
        // Numéro antérieur, puis dernier numéro, tous deux publiés avant la confirmation de l'abonné
        $older  = $this->published(NULL, '2099-01-15 10:00:00');
        $latest = $this->published(NULL, '2099-02-01 10:00:00');
        $sub    = $this->subscriber();
        $this->CI->db->where('id', $sub['id'])->update('newsletter_subscribers', array('confirmed_at' => '2099-03-01 10:00:00'));

        $ids = function ($rows) { return array_map('intval', array_column($rows, 'id')); };
        $this->assertContains((int) $sub['id'], $ids($this->mailer->not_sent($latest)), 'accueil en échec à reprendre');
        $this->assertNotContains((int) $sub['id'], $ids($this->mailer->not_sent($older)), 'numéro antérieur : pas d\'envoi rétroactif');

        $this->CI->newsletter_subscribers_model->record_sending($latest['id'], $sub['id']);
        $this->assertNotContains((int) $sub['id'], $ids($this->mailer->not_sent($latest)), 'déjà reçu');
    }

    public function test_get_by_confirmation_token(): void
    {
        $res = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email' => 'zztest_mailer_token@example.invalid', 'first_name' => 'Zoé', 'last_name' => 'Test',
        ));
        $sub = $this->CI->newsletter_subscribers_model->get_by_confirmation_token($res['confirmation_token']);
        $this->assertSame((int) $res['subscriber_id'], (int) $sub['id']);

        $this->CI->newsletter_subscribers_model->confirm($res['confirmation_token']);
        $this->assertNull($this->CI->newsletter_subscribers_model->get_by_confirmation_token($res['confirmation_token']));
        $this->assertNull($this->CI->newsletter_subscribers_model->get_by_confirmation_token(''));
    }
}
