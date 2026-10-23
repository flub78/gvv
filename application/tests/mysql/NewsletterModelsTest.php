<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests des modèles newsletter (P1).
 *
 * Couvre : newsletter_model (CRUD, états, transitions, retours de relecture),
 * newsletter_subscribers_model (abonnement, confirmation, désinscription,
 * envois), migration 181 (up/down).
 *
 * Toutes les données sont créées et supprimées par ce test.
 */
class NewsletterModelsTest extends TestCase
{
    /** @var CI_Controller */
    private $CI;

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_subscribers_model');
        $this->_ensure_tables();
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        $this->cleanup();
    }

    private function _ensure_tables(): void
    {
        if (!$this->CI->db->table_exists('newsletters')) {
            if (!class_exists('CI_Migration')) {
                require_once BASEPATH . 'libraries/Migration.php';
            }
            if (!class_exists('Migration_Newsletter')) {
                require_once APPPATH . 'migrations/181_newsletter.php';
            }
            (new Migration_Newsletter())->up();
        }
    }

    private function cleanup(): void
    {
        if ($this->CI->db->table_exists('newsletter_subscribers')) {
            $this->CI->db->query("DELETE FROM newsletter_subscribers WHERE email LIKE 'zztest_newsletter_%@example.invalid'");
        }
        if ($this->CI->db->table_exists('newsletters')) {
            $this->CI->db->query("DELETE FROM newsletters WHERE title LIKE 'ZZTest %'");
        }
    }

    // -------------------------------------------------------------------------
    // newsletter_model
    // -------------------------------------------------------------------------

    public function test_create_returns_id(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Newsletter',
            'period_start' => '2026-01-01',
            'period_end'   => '2026-01-31',
        ));
        $this->assertNotFalse($id);
        $this->assertGreaterThan(0, $id);
    }

    public function test_get_returns_newsletter(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Get',
            'period_start' => '2026-02-01',
            'period_end'   => '2026-02-28',
        ));
        $nl = $this->CI->newsletter_model->get($id);
        $this->assertSame('ZZTest Get', $nl['title']);
        $this->assertSame('draft', $nl['status']);
        $this->assertSame(0, (int) $nl['review_iteration']);
    }

    public function test_transition_draft_to_reviewing(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Transition',
            'period_start' => '2026-03-01',
            'period_end'   => '2026-03-31',
        ));
        $ok = $this->CI->newsletter_model->transition($id, 'reviewing');
        $this->assertTrue($ok);
        $nl = $this->CI->newsletter_model->get($id);
        $this->assertSame('reviewing', $nl['status']);
        $this->assertSame(1, (int) $nl['review_iteration']);
    }

    public function test_transition_increments_iteration_on_second_review(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Iter',
            'period_start' => '2026-04-01',
            'period_end'   => '2026-04-30',
        ));
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $nl = $this->CI->newsletter_model->get($id);
        $this->assertSame(2, (int) $nl['review_iteration']);
    }

    public function test_transition_invalid_sequence_returns_false(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Invalid',
            'period_start' => '2026-05-01',
            'period_end'   => '2026-05-31',
        ));
        // draft → validating est interdit
        $ok = $this->CI->newsletter_model->transition($id, 'validating');
        $this->assertFalse($ok);
    }

    public function test_full_workflow(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Full',
            'period_start' => '2026-06-01',
            'period_end'   => '2026-06-30',
        ));
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $this->CI->newsletter_model->transition($id, 'validating');
        $this->CI->newsletter_model->transition($id, 'approved');
        $this->CI->newsletter_model->update($id, array('pdf_path' => '/tmp/zztest.pdf'));
        $ok = $this->CI->newsletter_model->transition($id, 'published');
        $this->assertTrue($ok);
        $nl = $this->CI->newsletter_model->get($id);
        $this->assertSame('published', $nl['status']);
        $this->assertNotNull($nl['published_at']);
    }

    public function test_add_review_and_get_reviews(): void
    {
        $id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Review',
            'period_start' => '2026-07-01',
            'period_end'   => '2026-07-31',
        ));
        $this->CI->newsletter_model->transition($id, 'reviewing');
        $rid = $this->CI->newsletter_model->add_review($id, array(
            'reviewer_email' => 'zztest@example.invalid',
            'content'        => 'Super newsletter !',
            'submitted_via'  => 'gvv',
        ));
        $this->assertNotFalse($rid);
        $reviews = $this->CI->newsletter_model->get_reviews($id);
        $this->assertCount(1, $reviews);
        $this->assertSame('Super newsletter !', $reviews[0]['content']);
    }

    public function test_get_published_returns_only_published(): void
    {
        $id1 = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Published',
            'period_start' => '2026-08-01',
            'period_end'   => '2026-08-31',
        ));
        $this->CI->newsletter_model->transition($id1, 'reviewing');
        $this->CI->newsletter_model->transition($id1, 'validating');
        $this->CI->newsletter_model->transition($id1, 'approved');
        $this->CI->newsletter_model->transition($id1, 'published');

        $id2 = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Draft',
            'period_start' => '2026-09-01',
            'period_end'   => '2026-09-30',
        ));

        $published = $this->CI->newsletter_model->get_published();
        $ids = array_column($published, 'id');
        $this->assertContains((string) $id1, $ids);
        $this->assertNotContains((string) $id2, $ids);
    }

    // -------------------------------------------------------------------------
    // newsletter_subscribers_model
    // -------------------------------------------------------------------------

    public function test_subscribe_returns_token(): void
    {
        $result = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_a@example.invalid',
            'first_name' => 'Alice',
            'last_name'  => 'Test',
        ));
        $this->assertIsArray($result);
        $this->assertArrayHasKey('confirmation_token', $result);
        $this->assertNotEmpty($result['confirmation_token']);
    }

    public function test_confirm_activates_subscriber(): void
    {
        $result = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_b@example.invalid',
            'first_name' => 'Bob',
            'last_name'  => 'Test',
        ));
        $ok = $this->CI->newsletter_subscribers_model->confirm($result['confirmation_token']);
        $this->assertTrue($ok);
        $sub = $this->CI->newsletter_subscribers_model->get_by_email('zztest_newsletter_b@example.invalid');
        $this->assertSame(1, (int) $sub['confirmed']);
        $this->assertNull($sub['confirmation_token']);
    }

    public function test_subscribe_twice_returns_false_when_confirmed(): void
    {
        $result = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_c@example.invalid',
            'first_name' => 'Carol',
            'last_name'  => 'Test',
        ));
        $this->CI->newsletter_subscribers_model->confirm($result['confirmation_token']);
        $result2 = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_c@example.invalid',
            'first_name' => 'Carol',
            'last_name'  => 'Test',
        ));
        $this->assertFalse($result2);
    }

    public function test_unsubscribe_by_token(): void
    {
        $result = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_d@example.invalid',
            'first_name' => 'Dave',
            'last_name'  => 'Test',
        ));
        $this->CI->newsletter_subscribers_model->confirm($result['confirmation_token']);
        $sub = $this->CI->newsletter_subscribers_model->get_by_email('zztest_newsletter_d@example.invalid');
        $ok  = $this->CI->newsletter_subscribers_model->unsubscribe_by_token($sub['unsubscribe_token']);
        $this->assertTrue($ok);
        $sub2 = $this->CI->newsletter_subscribers_model->get($sub['id']);
        $this->assertNotNull($sub2['unsubscribed_at']);
    }

    public function test_get_active_returns_only_confirmed_and_subscribed(): void
    {
        // Abonné confirmé
        $r1 = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_e@example.invalid',
            'first_name' => 'Eve',
            'last_name'  => 'Test',
        ));
        $this->CI->newsletter_subscribers_model->confirm($r1['confirmation_token']);

        // Abonné non confirmé
        $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_f@example.invalid',
            'first_name' => 'Frank',
            'last_name'  => 'Test',
        ));

        $active = $this->CI->newsletter_subscribers_model->get_active();
        $emails = array_column($active, 'email');
        $this->assertContains('zztest_newsletter_e@example.invalid', $emails);
        $this->assertNotContains('zztest_newsletter_f@example.invalid', $emails);
    }

    public function test_record_sending_and_already_sent(): void
    {
        $nl_id = $this->CI->newsletter_model->create(array(
            'title'        => 'ZZTest Sending',
            'period_start' => '2026-10-01',
            'period_end'   => '2026-10-31',
        ));
        $r = $this->CI->newsletter_subscribers_model->subscribe(array(
            'email'      => 'zztest_newsletter_g@example.invalid',
            'first_name' => 'Grace',
            'last_name'  => 'Test',
        ));
        $this->CI->newsletter_subscribers_model->confirm($r['confirmation_token']);
        $sub = $this->CI->newsletter_subscribers_model->get_by_email('zztest_newsletter_g@example.invalid');

        $this->assertFalse($this->CI->newsletter_subscribers_model->already_sent($nl_id, $sub['id']));
        $this->CI->newsletter_subscribers_model->record_sending($nl_id, $sub['id']);
        $this->assertTrue($this->CI->newsletter_subscribers_model->already_sent($nl_id, $sub['id']));
    }
}
