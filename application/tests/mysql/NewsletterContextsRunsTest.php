<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests des modèles de la veille ajoutés en P2.1 : consignes versionnées,
 * exécutions, suggestions de relecture, transitions du cycle v1.
 *
 * Les données créées sont supprimées ; la version active des consignes
 * existante est réactivée à la fin, même en cas d'échec.
 */
class NewsletterContextsRunsTest extends TestCase
{
    private $CI;
    private $active_before;
    private $newsletter_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->CI->load->database();
        $this->CI->load->model('newsletter_model');
        $this->CI->load->model('newsletter_contexts_model');
        $this->CI->load->model('newsletter_runs_model');
        $this->CI->load->model('newsletter_reviews_model');
        if (!$this->CI->db->table_exists('newsletter_runs')) {
            $this->markTestSkipped('Migration 182 non appliquée');
        }
        $active = $this->CI->newsletter_contexts_model->get_active();
        $this->active_before = $active ? (int) $active['id'] : NULL;
    }

    protected function tearDown(): void
    {
        $db = $this->CI->db;
        $db->query("DELETE FROM newsletter_reviews WHERE reviewer_email LIKE 'zztest_%@example.invalid'");
        $db->query("DELETE FROM newsletter_runs WHERE model = 'zztest-model'");
        $db->query("DELETE FROM newsletter_contexts WHERE content LIKE 'ZZTest %'");
        foreach ($this->newsletter_ids as $id) {
            $db->query('DELETE FROM newsletters WHERE id = ' . (int) $id);
        }
        $db->query('UPDATE newsletter_contexts SET is_active = 0');
        if ($this->active_before) {
            $db->query('UPDATE newsletter_contexts SET is_active = 1 WHERE id = ' . $this->active_before);
        }
    }

    private function newsletter(): int
    {
        $id = $this->CI->newsletter_model->create(array(
            'title' => 'ZZTest cycle', 'period_start' => '2026-10-01', 'period_end' => '2026-10-31',
        ));
        $this->newsletter_ids[] = $id;
        return (int) $id;
    }

    // ── Consignes ─────────────────────────────────────────────────────────

    public function test_create_version_activates_only_the_new_one(): void
    {
        $m = $this->CI->newsletter_contexts_model;
        $id1 = $m->create_version('ZZTest v1', 'premier');
        $id2 = $m->create_version('ZZTest v2', 'second');

        $active = $m->get_active();
        $this->assertSame((int) $id2, (int) $active['id']);
        $this->assertSame('ZZTest v2', $active['content']);
        $this->assertSame((int) $m->get($id1)['version'] + 1, (int) $active['version']);
        $this->assertSame(1, $this->CI->db->where('is_active', 1)->count_all_results('newsletter_contexts'));
    }

    public function test_restore_creates_a_new_active_version(): void
    {
        $m = $this->CI->newsletter_contexts_model;
        $id1 = $m->create_version('ZZTest original');
        $m->create_version('ZZTest modifiée');
        $id3 = $m->restore($id1);

        $restored = $m->get($id3);
        $this->assertSame('ZZTest original', $restored['content']);
        $this->assertSame(1, (int) $restored['is_active']);
        $this->assertStringContainsString('Restauration de la version ' . $m->get($id1)['version'], $restored['comment']);
        $this->assertFalse($m->restore(999999999));
    }

    // ── Exécutions ────────────────────────────────────────────────────────

    public function test_run_ids_are_unique_for_the_same_second(): void
    {
        $m = $this->CI->newsletter_runs_model;
        $ts = mktime(10, 0, 0, 10, 24, 2026);
        $first = $m->generate_run_id($ts);
        $m->create(array('run_id' => $first, 'origin' => 'desktop', 'model' => 'zztest-model'));
        $second = $m->generate_run_id($ts);
        $this->assertSame('RUN-20261024-100000', $first);
        $this->assertSame('RUN-20261024-100000-2', $second);
    }

    public function test_run_is_listed_with_context_and_newsletter(): void
    {
        $nid = $this->newsletter();
        $cid = $this->CI->newsletter_contexts_model->create_version('ZZTest consignes');
        $rid = $this->CI->newsletter_runs_model->create(array(
            'origin' => 'desktop', 'status' => 'succeeded', 'context_id' => $cid,
            'newsletter_id' => $nid, 'model' => 'zztest-model', 'cost_usd' => '1.25',
        ));
        $this->assertNotFalse($rid);

        $found = NULL;
        foreach ($this->CI->newsletter_runs_model->get_all() as $row) {
            if ((int) $row['id'] === (int) $rid) $found = $row;
        }
        $this->assertNotNull($found);
        $this->assertSame('ZZTest cycle', $found['newsletter_title']);
        $this->assertSame((int) $this->CI->newsletter_contexts_model->get($cid)['version'], (int) $found['context_version']);
        $this->assertSame((int) $rid, (int) $this->CI->newsletter_runs_model->get_by_newsletter($nid)['id']);
    }

    // ── Suggestions ───────────────────────────────────────────────────────

    public function test_message_id_prevents_duplicates(): void
    {
        $nid = $this->newsletter();
        $m = $this->CI->newsletter_reviews_model;
        $data = array('newsletter_id' => $nid, 'iteration' => 1, 'reviewer_email' => 'zztest_a@example.invalid',
                      'content' => 'Corriger le titre', 'message_id' => '<zztest-1@example.invalid>');
        $this->assertNotFalse($m->add($data));
        $this->assertFalse($m->add($data));
        $this->assertTrue($m->message_exists('<zztest-1@example.invalid>'));
    }

    public function test_decisions_rules(): void
    {
        $nid = $this->newsletter();
        $m = $this->CI->newsletter_reviews_model;
        $remark = $m->add(array('newsletter_id' => $nid, 'iteration' => 1,
                                'reviewer_email' => 'zztest_b@example.invalid', 'content' => 'Remarque'));

        $this->assertSame('postpone_article_only', $m->decide($remark, 'reportee'));
        $this->assertSame('modified_text_required', $m->decide($remark, 'modifiee', '  '));
        $this->assertSame('invalid_decision', $m->decide($remark, 'bof'));
        $this->assertTrue($m->decide($remark, 'modifiee', 'Texte corrigé'));
        $row = $m->get($remark);
        $this->assertSame('Texte corrigé', $row['admin_text']);
        $this->assertNotNull($row['decided_at']);

        $this->assertTrue($m->decide($remark, 'rejetee'));
        $this->assertNull($m->get($remark)['admin_text']);
    }

    public function test_postponed_article_is_detached_then_reattached(): void
    {
        $n1 = $this->newsletter();
        $n2 = $this->newsletter();
        $m = $this->CI->newsletter_reviews_model;
        $art = $m->add(array('newsletter_id' => $n1, 'iteration' => 1, 'kind' => 'article',
                             'reviewer_email' => 'zztest_c@example.invalid', 'content' => 'Article REX'));

        $this->assertTrue($m->decide($art, 'reportee'));
        $this->assertNull($m->get($art)['newsletter_id']);
        $ids = array_map('intval', array_column($m->get_postponed(), 'id'));
        $this->assertContains((int) $art, $ids);

        $this->assertTrue($m->attach($art, $n2, 3));
        $row = $m->get($art);
        $this->assertSame($n2, (int) $row['newsletter_id']);
        $this->assertSame('en_attente', $row['decision']);
        $this->assertSame(3, (int) $row['iteration']);
        $this->assertFalse($m->attach($art, $n1, 1), 'une suggestion rattachée ne se reprend pas');
    }

    public function test_get_accepted_is_limited_to_the_current_round(): void
    {
        $nid = $this->newsletter();
        $m = $this->CI->newsletter_reviews_model;
        $old = $m->add(array('newsletter_id' => $nid, 'iteration' => 1, 'reviewer_email' => 'zztest_d@example.invalid', 'content' => 'tour 1'));
        $new = $m->add(array('newsletter_id' => $nid, 'iteration' => 2, 'reviewer_email' => 'zztest_d@example.invalid', 'content' => 'tour 2'));
        $rej = $m->add(array('newsletter_id' => $nid, 'iteration' => 2, 'reviewer_email' => 'zztest_d@example.invalid', 'content' => 'rejet'));
        $m->decide($old, 'acceptee');
        $m->decide($new, 'acceptee');
        $m->decide($rej, 'rejetee');

        $this->assertSame(array((int) $new), array_map('intval', array_column($m->get_accepted($nid, 2), 'id')));
        $this->assertSame(0, $m->count_pending($nid));
    }

    // ── Transitions du cycle v1 ───────────────────────────────────────────

    public function test_v1_cycle_transitions(): void
    {
        $nid = $this->newsletter();
        $m = $this->CI->newsletter_model;
        $this->assertTrue($m->transition($nid, 'reviewing'));
        $this->assertTrue($m->transition($nid, 'draft'), 'nouvelle version');
        $this->assertTrue($m->transition($nid, 'reviewing'));
        $this->assertSame(2, (int) $m->get($nid)['review_iteration']);
        $this->assertTrue($m->transition($nid, 'draft'));
        $this->assertTrue($m->transition($nid, 'approved'), 'validation directe depuis draft');
        $this->assertNotNull($m->get($nid)['approved_at']);
        $this->assertFalse($m->transition($nid, 'draft'));
    }
}
