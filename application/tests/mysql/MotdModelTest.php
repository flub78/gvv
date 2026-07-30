<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for the Messages du jour (MOTD) models.
 *
 * Covers CRUD, target resolution (all/list/user), active message
 * visibility (period, priority sort), replies and user actions
 * (hide, hide all, acknowledge, prefs).
 *
 * @package tests
 * @see application/models/motd_model.php
 * @see application/models/motd_replies_model.php
 * @see application/models/motd_user_state_model.php
 * @see application/models/motd_user_prefs_model.php
 */
class MotdModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $motd_model;
    protected $motd_replies_model;
    protected $motd_user_state_model;
    protected $motd_user_prefs_model;
    protected $email_lists_model;

    protected $pilot_a;
    protected $pilot_b;
    protected $test_user_id;

    protected $test_message_ids = array();
    protected $test_reply_ids = array();
    protected $test_list_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('motd_model');
        $this->CI->load->model('motd_replies_model');
        $this->CI->load->model('motd_user_state_model');
        $this->CI->load->model('motd_user_prefs_model');
        $this->CI->load->model('email_lists_model');

        $this->motd_model = $this->CI->motd_model;
        $this->motd_replies_model = $this->CI->motd_replies_model;
        $this->motd_user_state_model = $this->CI->motd_user_state_model;
        $this->motd_user_prefs_model = $this->CI->motd_user_prefs_model;
        $this->email_lists_model = $this->CI->email_lists_model;

        $pilots = $this->db->query("SELECT mlogin FROM membres LIMIT 2")->result_array();
        if (count($pilots) < 2) {
            $this->markTestSkipped('Need at least 2 members in database for MOTD tests');
        }
        $this->pilot_a = $pilots[0]['mlogin'];
        $this->pilot_b = $pilots[1]['mlogin'];

        $user = $this->db->query("SELECT id FROM users LIMIT 1")->row_array();
        $this->test_user_id = $user ? $user['id'] : 1;

        $this->cleanupTestData();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestData();
    }

    private function cleanupTestData()
    {
        foreach ($this->test_reply_ids as $id) {
            $this->db->delete('motd_replies', array('id' => $id));
        }
        $this->test_reply_ids = array();

        foreach ($this->test_message_ids as $id) {
            $this->db->delete('motd_user_message_state', array('message_id' => $id));
            $this->db->delete('motd_replies', array('message_id' => $id));
            $this->db->delete('motd_messages', array('id' => $id));
        }
        $this->test_message_ids = array();

        foreach ($this->test_list_ids as $id) {
            $this->email_lists_model->delete_list($id);
        }
        $this->test_list_ids = array();

        $this->db->delete('motd_user_prefs', array('user_login' => $this->pilot_a));
        $this->db->delete('motd_user_prefs', array('user_login' => $this->pilot_b));
        $this->db->query("DELETE FROM email_lists WHERE name LIKE 'TEST_motd_%'");
    }

    private function base_message($overrides = array())
    {
        return array_merge(array(
            'title' => 'Test message',
            'content' => 'Contenu de test',
            'level' => 'info',
            'start_date' => date('Y-m-d H:i:s', strtotime('-1 hour')),
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 hour')),
            'target_type' => 'all',
            'created_by' => $this->pilot_a,
            'updated_by' => $this->pilot_a,
        ), $overrides);
    }

    // ==================== CRUD ====================

    public function testCreateMessage_TargetAll()
    {
        $id = $this->motd_model->create_message($this->base_message());
        $this->assertNotFalse($id);
        $this->test_message_ids[] = $id;

        $message = $this->motd_model->get_message($id);
        $this->assertEquals('Test message', $message['title']);
        $this->assertEquals('all', $message['target_type']);
        $this->assertEquals('admin', $message['origin']);
    }

    public function testCreateMessage_CreatedByNonMemberAdmin()
    {
        // Regression test: an admin account with no matching membres row
        // (e.g. legacy DX_Auth admins like "testadmin") must still be able
        // to create a message - created_by/updated_by have no FK to membres
        // (see migration 143 comment).
        $id = $this->motd_model->create_message($this->base_message(array(
            'created_by' => 'no_membres_row_admin_xyz',
            'updated_by' => 'no_membres_row_admin_xyz',
        )));
        $this->assertNotFalse($id);
        $this->test_message_ids[] = $id;

        $message = $this->motd_model->get_message($id);
        $this->assertEquals('no_membres_row_admin_xyz', $message['created_by']);
    }

    public function testCreateMessage_TargetUser_Valid()
    {
        $id = $this->motd_model->create_message($this->base_message(array(
            'target_type' => 'user',
            'target_user_login' => $this->pilot_b,
        )));
        $this->assertNotFalse($id);
        $this->test_message_ids[] = $id;
    }

    public function testCreateMessage_TargetUser_UnknownLogin_Rejected()
    {
        $id = $this->motd_model->create_message($this->base_message(array(
            'target_type' => 'user',
            'target_user_login' => 'no_such_login_xyz',
        )));
        $this->assertFalse($id, 'Message with no valid recipient must be rejected');
    }

    public function testCreateMessage_TargetList_UnknownList_Rejected()
    {
        $id = $this->motd_model->create_message($this->base_message(array(
            'target_type' => 'list',
            'target_list_id' => 999999,
        )));
        $this->assertFalse($id, 'Message targeting a non-existent list must be rejected');
    }

    public function testUpdateMessage()
    {
        $id = $this->motd_model->create_message($this->base_message());
        $this->test_message_ids[] = $id;

        $this->motd_model->update_message($id, array('title' => 'Updated title', 'updated_by' => $this->pilot_a));

        $updated = $this->motd_model->get_message($id);
        $this->assertEquals('Updated title', $updated['title']);
    }

    public function testDeleteMessage()
    {
        $id = $this->motd_model->create_message($this->base_message());

        $this->motd_model->delete_message($id);

        $deleted = $this->motd_model->get_message($id);
        $this->assertEmpty($deleted);
    }

    // ==================== Active messages / period ====================

    public function testActiveMessagesForUser_ExcludesOutOfPeriodMessages()
    {
        $active_id = $this->motd_model->create_message($this->base_message(array('title' => 'Active')));
        $this->test_message_ids[] = $active_id;

        $expired_id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'Expired',
            'start_date' => date('Y-m-d H:i:s', strtotime('-3 days')),
            'end_date' => date('Y-m-d H:i:s', strtotime('-1 day')),
        )));
        $this->test_message_ids[] = $expired_id;

        $future_id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'Future',
            'start_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'end_date' => date('Y-m-d H:i:s', strtotime('+3 days')),
        )));
        $this->test_message_ids[] = $future_id;

        $titles = array_column(
            $this->motd_model->active_messages_for_user($this->pilot_a),
            'title'
        );

        $this->assertContains('Active', $titles);
        $this->assertNotContains('Expired', $titles);
        $this->assertNotContains('Future', $titles);
    }

    public function testActiveMessagesForUser_TargetUser_OnlyVisibleToTarget()
    {
        $id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'Private message',
            'target_type' => 'user',
            'target_user_login' => $this->pilot_b,
        )));
        $this->test_message_ids[] = $id;

        $titles_b = array_column($this->motd_model->active_messages_for_user($this->pilot_b), 'title');
        $titles_a = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');

        $this->assertContains('Private message', $titles_b);
        $this->assertNotContains('Private message', $titles_a);
    }

    public function testActiveMessagesForUser_TargetList_VisibleToListMember()
    {
        $list_id = $this->email_lists_model->create_list(array(
            'name' => 'TEST_motd_list1',
            'created_by' => $this->test_user_id,
        ));
        $this->assertNotFalse($list_id);
        $this->test_list_ids[] = $list_id;

        $this->email_lists_model->add_manual_member($list_id, $this->pilot_b);

        $id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'List message',
            'target_type' => 'list',
            'target_list_id' => $list_id,
        )));
        $this->test_message_ids[] = $id;

        $titles_member = array_column($this->motd_model->active_messages_for_user($this->pilot_b), 'title');
        $titles_non_member = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');

        $this->assertContains('List message', $titles_member);
        $this->assertNotContains('List message', $titles_non_member);
    }

    public function testActiveMessagesForUser_SortByPriority()
    {
        $info_id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'Info msg', 'level' => 'info',
        )));
        $this->test_message_ids[] = $info_id;

        $urgent_id = $this->motd_model->create_message($this->base_message(array(
            'title' => 'Urgent msg', 'level' => 'urgent',
        )));
        $this->test_message_ids[] = $urgent_id;

        $messages = $this->motd_model->active_messages_for_user($this->pilot_a, 'priority');
        $titles = array_column($messages, 'title');

        $pos_urgent = array_search('Urgent msg', $titles);
        $pos_info = array_search('Info msg', $titles);

        $this->assertNotFalse($pos_urgent);
        $this->assertNotFalse($pos_info);
        $this->assertLessThan($pos_info, $pos_urgent, 'Urgent message should sort before an info message');
    }

    // ==================== Alarm entry point ====================

    public function testGenerateSystemMessage()
    {
        $id = $this->motd_model->generate_system_message(array(
            'content' => 'Alarme generee automatiquement',
            'end_date' => date('Y-m-d H:i:s', strtotime('+1 day')),
            'target_type' => 'user',
            'target_user_login' => $this->pilot_a,
            'source_type' => 'alarm_medical',
            'source_ref' => 'doc-123',
            'created_by' => $this->pilot_a,
            'updated_by' => $this->pilot_a,
        ));
        $this->assertNotFalse($id);
        $this->test_message_ids[] = $id;

        $message = $this->motd_model->get_message($id);
        $this->assertEquals('system', $message['origin']);
        $this->assertEquals('alarm_medical', $message['source_type']);
        $this->assertEquals('alerte', $message['level'], 'Default level for system messages should be alerte');
    }

    // ==================== Replies ====================

    public function testReplyCrud()
    {
        $message_id = $this->motd_model->create_message($this->base_message());
        $this->test_message_ids[] = $message_id;

        $reply_id = $this->motd_replies_model->create_reply(array(
            'message_id' => $message_id,
            'author_login' => $this->pilot_a,
            'content' => 'Une reponse',
            'created_by' => $this->pilot_a,
            'updated_by' => $this->pilot_a,
        ));
        $this->assertNotFalse($reply_id);
        $this->test_reply_ids[] = $reply_id;

        $replies = $this->motd_replies_model->replies_for_message($message_id);
        $this->assertCount(1, $replies);
        $this->assertEquals('Une reponse', $replies[0]['content']);

        $this->motd_replies_model->update_reply($reply_id, array(
            'content' => 'Reponse modifiee',
            'updated_by' => $this->pilot_a,
        ));
        $updated = $this->motd_replies_model->get_reply($reply_id);
        $this->assertEquals('Reponse modifiee', $updated['content']);

        $this->motd_replies_model->delete_reply($reply_id);
        $this->assertEmpty($this->motd_replies_model->get_reply($reply_id));
        $this->test_reply_ids = array_diff($this->test_reply_ids, array($reply_id));
    }

    public function testRepliesForMessages_BatchesAcrossSeveralMessages()
    {
        $message_id_a = $this->motd_model->create_message($this->base_message(array('title' => 'Batch A')));
        $this->test_message_ids[] = $message_id_a;
        $message_id_b = $this->motd_model->create_message($this->base_message(array('title' => 'Batch B')));
        $this->test_message_ids[] = $message_id_b;
        $message_id_c = $this->motd_model->create_message($this->base_message(array('title' => 'Batch C (no reply)')));
        $this->test_message_ids[] = $message_id_c;

        $reply_id_a = $this->motd_replies_model->create_reply(array(
            'message_id' => $message_id_a,
            'author_login' => $this->pilot_a,
            'content' => 'Reponse A',
            'created_by' => $this->pilot_a,
            'updated_by' => $this->pilot_a,
        ));
        $this->test_reply_ids[] = $reply_id_a;
        $reply_id_b = $this->motd_replies_model->create_reply(array(
            'message_id' => $message_id_b,
            'author_login' => $this->pilot_b,
            'content' => 'Reponse B',
            'created_by' => $this->pilot_b,
            'updated_by' => $this->pilot_b,
        ));
        $this->test_reply_ids[] = $reply_id_b;

        $grouped = $this->motd_replies_model->replies_for_messages(array($message_id_a, $message_id_b, $message_id_c));

        $this->assertCount(1, $grouped[$message_id_a]);
        $this->assertEquals('Reponse A', $grouped[$message_id_a][0]['content']);
        $this->assertCount(1, $grouped[$message_id_b]);
        $this->assertEquals('Reponse B', $grouped[$message_id_b][0]['content']);
        $this->assertArrayNotHasKey($message_id_c, $grouped);

        $this->assertEquals(array(), $this->motd_replies_model->replies_for_messages(array()));
    }

    public function testUserCanAccessMessage()
    {
        $message = array(
            'created_by' => $this->pilot_a,
            'target_type' => 'user',
            'target_user_login' => $this->pilot_b,
        );

        $this->assertTrue($this->motd_model->user_can_access_message($message, $this->pilot_a, false), 'Author can access');
        $this->assertTrue($this->motd_model->user_can_access_message($message, $this->pilot_b, false), 'Direct target can access');
        $this->assertFalse($this->motd_model->user_can_access_message($message, 'some_other_login', false), 'Unrelated user cannot access');
        $this->assertTrue($this->motd_model->user_can_access_message($message, 'some_other_login', true), 'Admin can always access');
    }

    // ==================== User state (hide / hide all / acknowledge) ====================

    public function testHideMessage_RemovesFromActiveList()
    {
        $id = $this->motd_model->create_message($this->base_message(array('title' => 'To hide')));
        $this->test_message_ids[] = $id;

        $this->motd_user_state_model->hide_message($id, $this->pilot_a);

        $titles = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');
        $this->assertNotContains('To hide', $titles);

        // Still present when hidden messages are not excluded
        $titles_all = array_column(
            $this->motd_model->active_messages_for_user($this->pilot_a, 'priority', false),
            'title'
        );
        $this->assertContains('To hide', $titles_all);
    }

    public function testHideAllMessages()
    {
        $id1 = $this->motd_model->create_message($this->base_message(array('title' => 'Msg1')));
        $this->test_message_ids[] = $id1;
        $id2 = $this->motd_model->create_message($this->base_message(array('title' => 'Msg2')));
        $this->test_message_ids[] = $id2;

        // id1 already has a state row (acknowledged, not yet hidden): the
        // bulk upsert must UPDATE it (and preserve "acknowledged") rather
        // than fail on the unique key. id2 has no state row yet: it must be
        // INSERTed. Both go through the same single query.
        $this->motd_user_state_model->acknowledge_message($id1, $this->pilot_a);

        $hidden_count = $this->motd_user_state_model->hide_all_messages($this->pilot_a);
        $this->assertGreaterThanOrEqual(2, $hidden_count);

        $titles = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');
        $this->assertNotContains('Msg1', $titles);
        $this->assertNotContains('Msg2', $titles);

        $state1 = $this->motd_user_state_model->get_state($id1, $this->pilot_a);
        $this->assertEquals(1, $state1['hidden']);
        $this->assertEquals(1, $state1['acknowledged'], 'Hiding all must not clobber a pre-existing acknowledged state');

        $state2 = $this->motd_user_state_model->get_state($id2, $this->pilot_a);
        $this->assertEquals(1, $state2['hidden']);
    }

    public function testAcknowledgeMessage()
    {
        $id = $this->motd_model->create_message($this->base_message());
        $this->test_message_ids[] = $id;

        $this->motd_user_state_model->acknowledge_message($id, $this->pilot_a);

        $state = $this->motd_user_state_model->get_state($id, $this->pilot_a);
        $this->assertEquals(1, $state['acknowledged']);
        $this->assertNotNull($state['acknowledged_at']);
    }

    /**
     * "Afficher les messages masqués" : reverses hide_all_messages(), and
     * leaves an unrelated acknowledged state untouched.
     */
    public function testUnhideAllMessages()
    {
        // active_messages_for_user() is scoped to pilot_a globally, not to this
        // test's own fixtures (hide_all_messages()/unhide_all_messages() act on
        // every message visible to the user). Snapshot any message already
        // hidden coming into this test so it can be restored afterwards,
        // keeping the test independent of execution order.
        $previously_hidden_ids = array();
        foreach ($this->motd_model->active_messages_for_user($this->pilot_a, 'priority', FALSE) as $row) {
            if (!empty($row['hidden'])) {
                $previously_hidden_ids[] = $row['id'];
            }
        }

        $id1 = $this->motd_model->create_message($this->base_message(array('title' => 'Msg1')));
        $this->test_message_ids[] = $id1;
        $id2 = $this->motd_model->create_message($this->base_message(array('title' => 'Msg2')));
        $this->test_message_ids[] = $id2;

        $this->motd_user_state_model->acknowledge_message($id1, $this->pilot_a);
        $this->motd_user_state_model->hide_message($id1, $this->pilot_a);
        $this->motd_user_state_model->hide_message($id2, $this->pilot_a);

        $titles_hidden = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');
        $this->assertNotContains('Msg1', $titles_hidden);
        $this->assertNotContains('Msg2', $titles_hidden);

        $unhidden_count = $this->motd_user_state_model->unhide_all_messages($this->pilot_a);
        $this->assertGreaterThanOrEqual(2, $unhidden_count);

        $titles_visible = array_column($this->motd_model->active_messages_for_user($this->pilot_a), 'title');
        $this->assertContains('Msg1', $titles_visible);
        $this->assertContains('Msg2', $titles_visible);

        // acknowledged state on Msg1 must survive the unhide.
        $state = $this->motd_user_state_model->get_state($id1, $this->pilot_a);
        $this->assertEquals(1, $state['acknowledged']);

        foreach ($previously_hidden_ids as $message_id) {
            $this->motd_user_state_model->hide_message($message_id, $this->pilot_a);
        }
    }

    /**
     * The "Tous mes messages" badge counts, among the rows returned by
     * active_messages_for_user(), those with an empty 'acknowledged' field.
     * This confirms that data source is sufficient to derive the unread count.
     */
    public function testActiveMessagesForUser_UnreadCountReflectsAcknowledgedState()
    {
        $id1 = $this->motd_model->create_message($this->base_message(array('title' => 'Unread1')));
        $this->test_message_ids[] = $id1;
        $id2 = $this->motd_model->create_message($this->base_message(array('title' => 'Unread2')));
        $this->test_message_ids[] = $id2;
        $id3 = $this->motd_model->create_message($this->base_message(array('title' => 'Acked')));
        $this->test_message_ids[] = $id3;

        $this->motd_user_state_model->acknowledge_message($id3, $this->pilot_a);

        $messages = $this->motd_model->active_messages_for_user($this->pilot_a);
        $unread_count = 0;
        foreach ($messages as $message) {
            if (empty($message['acknowledged'])) {
                $unread_count++;
            }
        }

        $this->assertEquals(2, $unread_count);
    }

    // ==================== User prefs ====================

    public function testGetPrefs_DefaultsWhenNoneStored()
    {
        $prefs = $this->motd_user_prefs_model->get_prefs($this->pilot_a);
        $this->assertEquals(1, $prefs['section_collapsed']);
        $this->assertEquals('priority', $prefs['sort_by']);
    }

    public function testSavePrefs_CreatesThenUpdates()
    {
        $this->motd_user_prefs_model->save_prefs($this->pilot_a, array(
            'section_collapsed' => 0,
            'sort_by' => 'date',
        ));

        $prefs = $this->motd_user_prefs_model->get_prefs($this->pilot_a);
        $this->assertEquals(0, $prefs['section_collapsed']);
        $this->assertEquals('date', $prefs['sort_by']);

        $this->motd_user_prefs_model->save_prefs($this->pilot_a, array(
            'section_collapsed' => 1,
        ));
        $prefs_updated = $this->motd_user_prefs_model->get_prefs($this->pilot_a);
        $this->assertEquals(1, $prefs_updated['section_collapsed']);
    }
}
