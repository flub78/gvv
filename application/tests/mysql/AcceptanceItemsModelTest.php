<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Acceptance_items_model
 *
 * @package tests
 * @see application/models/acceptance_items_model.php
 */
class AcceptanceItemsModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $test_ids = array();
    protected $temp_role_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('acceptance_items_model');
        $this->model = $this->CI->acceptance_items_model;
    }

    protected function tearDown(): void
    {
        // Clean up any messages du jour generated for test items (soft link
        // via source_type/source_ref, no FK) before removing the items.
        foreach ($this->test_ids as $id) {
            $this->db->where('source_type', Acceptance_items_model::MOTD_SOURCE_TYPE);
            $this->db->where('source_ref', $id);
            $this->db->delete('motd_messages');
        }
        // Clean up test data in reverse order
        foreach (array_reverse($this->test_ids) as $id) {
            $this->db->delete('acceptance_items', array('id' => $id));
        }
        foreach ($this->temp_role_ids as $id) {
            $this->db->delete('user_roles_per_section', array('id' => $id));
        }
    }

    /**
     * Helper to get a valid, currently active member login from the
     * database (holds the 'user' role, unrevoked, in some section — cf.
     * Membres_model::actif_dans_au_moins_une_section()). Targeting
     * resolution now requires this, so an arbitrary/inactive membres row
     * would silently fail to be picked up by resolve_targets().
     */
    protected function getTestLogin()
    {
        $query = $this->db->query(
            "SELECT m.mlogin FROM membres m
             JOIN users u ON u.username = m.mlogin
             JOIN user_roles_per_section urps ON urps.user_id = u.id
             WHERE urps.types_roles_id = 1 AND urps.revoked_at IS NULL
             LIMIT 1"
        );
        $row = $query->row_array();
        return $row ? $row['mlogin'] : null;
    }

    /**
     * Helper to get two distinct, currently active member logins (see
     * getTestLogin() for why activity is required).
     */
    protected function getTwoTestLogins()
    {
        $query = $this->db->query(
            "SELECT DISTINCT m.mlogin FROM membres m
             JOIN users u ON u.username = m.mlogin
             JOIN user_roles_per_section urps ON urps.user_id = u.id
             WHERE urps.types_roles_id = 1 AND urps.revoked_at IS NULL
             LIMIT 2"
        );
        $rows = $query->result_array();
        if (count($rows) < 2) {
            $this->markTestSkipped('At least two active members are required for this test');
        }
        return array($rows[0]['mlogin'], $rows[1]['mlogin']);
    }

    /**
     * Helper: an active member (holds the base 'user' role somewhere, so
     * resolve_targets() picks them up) who does NOT already hold
     * $types_roles_id/$section_id — needed by grant/revoke lifecycle tests,
     * since granting a role the member already has would be a no-op for
     * eligibility once the temporary grant is revoked.
     */
    protected function getTestLoginWithoutRole($types_roles_id, $section_id)
    {
        $row = $this->db->query(
            "SELECT m.mlogin FROM membres m
             JOIN users u ON u.username = m.mlogin
             JOIN user_roles_per_section urps_active ON urps_active.user_id = u.id
                 AND urps_active.types_roles_id = 1 AND urps_active.revoked_at IS NULL
             WHERE NOT EXISTS (
                 SELECT 1 FROM user_roles_per_section urps_target
                 WHERE urps_target.user_id = u.id
                   AND urps_target.types_roles_id = ?
                   AND urps_target.section_id = ?
                   AND urps_target.revoked_at IS NULL
             )
             LIMIT 1",
            array($types_roles_id, $section_id)
        )->row_array();
        if (!$row) {
            $this->markTestSkipped('No active member without the sampled role/section available for this test');
        }
        return $row['mlogin'];
    }

    /**
     * Helper to create a test item
     */
    protected function createTestItem($overrides = array())
    {
        $login = $this->getTestLogin();
        if (!$login) {
            $this->markTestSkipped('No member in database for testing');
        }

        $data = array_merge(array(
            'title' => 'Test Item ' . uniqid(),
            'category' => 'document',
            'target_type' => 'internal',
            'mandatory_level' => 'optional',
            'dual_validation' => 0,
            'active' => 1,
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s')
        ), $overrides);

        $id = $this->model->create($data);
        $this->assertNotFalse($id, 'Failed to create test item');
        $this->test_ids[] = $id;
        return $id;
    }

    // ==================== CRUD tests ====================

    public function testCreate_ReturnsId()
    {
        $id = $this->createTestItem();
        $this->assertGreaterThan(0, $id);
    }

    public function testGetById_ReturnsCorrectItem()
    {
        $id = $this->createTestItem(array('title' => 'Test GetById'));
        $item = $this->model->get_by_id('id', $id);

        $this->assertNotEmpty($item);
        $this->assertEquals('Test GetById', $item['title']);
        $this->assertEquals('document', $item['category']);
    }

    public function testUpdate_ModifiesItem()
    {
        $id = $this->createTestItem();
        $this->model->update('id', array(
            'id' => $id,
            'title' => 'Updated Title',
            'updated_at' => date('Y-m-d H:i:s')
        ));

        $item = $this->model->get_by_id('id', $id);
        $this->assertEquals('Updated Title', $item['title']);
    }

    public function testDelete_RemovesItem()
    {
        $id = $this->createTestItem();
        $this->model->delete(array('id' => $id));

        $item = $this->model->get_by_id('id', $id);
        $this->assertEmpty($item);

        // Remove from cleanup list since already deleted
        $this->test_ids = array_diff($this->test_ids, array($id));
    }

    // ==================== select_page tests ====================

    public function testSelectPage_ReturnsArray()
    {
        $this->createTestItem();
        $results = $this->model->select_page();

        $this->assertIsArray($results);
        $this->assertGreaterThan(0, count($results));
    }

    public function testSelectPage_ContainsCreatorName()
    {
        $id = $this->createTestItem();
        $results = $this->model->select_page();

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) {
                $this->assertArrayHasKey('created_by_name', $row);
                $found = true;
                break;
            }
        }
        $this->assertTrue($found, 'Test item should appear in select_page results');
    }

    public function testSelectPage_WithFilter()
    {
        $id = $this->createTestItem(array('category' => 'briefing'));
        $results = $this->model->select_page(0, 0, array('acceptance_items.category' => 'briefing'));

        $this->assertIsArray($results);
        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) {
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
    }

    // ==================== get_active_items tests ====================

    public function testGetActiveItems_ReturnsOnlyActive()
    {
        $active_id = $this->createTestItem(array('active' => 1));
        $inactive_id = $this->createTestItem(array('active' => 0));

        $results = $this->model->get_active_items();

        $active_found = false;
        $inactive_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $active_id) $active_found = true;
            if ($row['id'] == $inactive_id) $inactive_found = true;
        }

        $this->assertTrue($active_found, 'Active item should be returned');
        $this->assertFalse($inactive_found, 'Inactive item should not be returned');
    }

    public function testGetActiveItems_FilterByCategory()
    {
        $doc_id = $this->createTestItem(array('category' => 'document'));
        $formation_id = $this->createTestItem(array('category' => 'formation'));

        $results = $this->model->get_active_items('formation');

        $doc_found = false;
        $formation_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $doc_id) $doc_found = true;
            if ($row['id'] == $formation_id) $formation_found = true;
        }

        $this->assertTrue($formation_found, 'Formation item should be returned');
        $this->assertFalse($doc_found, 'Document item should not be returned when filtering by formation');
    }

    // ==================== get_overdue_items tests ====================

    public function testGetOverdueItems_ReturnsExpiredDeadlines()
    {
        $overdue_id = $this->createTestItem(array(
            'deadline' => '2020-01-01',
            'active' => 1
        ));
        $future_id = $this->createTestItem(array(
            'deadline' => '2030-12-31',
            'active' => 1
        ));

        $results = $this->model->get_overdue_items();

        $overdue_found = false;
        $future_found = false;
        foreach ($results as $row) {
            if ($row['id'] == $overdue_id) $overdue_found = true;
            if ($row['id'] == $future_id) $future_found = true;
        }

        $this->assertTrue($overdue_found, 'Overdue item should be returned');
        $this->assertFalse($future_found, 'Future deadline item should not be returned');
    }

    // ==================== image & selector tests ====================

    public function testImage_ReturnsTitle()
    {
        $id = $this->createTestItem(array('title' => 'My Test Document'));
        $this->assertEquals('My Test Document', $this->model->image($id));
    }

    public function testImage_EmptyKey()
    {
        $this->assertEquals('', $this->model->image(''));
    }

    public function testSelector_ReturnsArray()
    {
        $id = $this->createTestItem(array('title' => 'Selector Test'));
        $result = $this->model->selector();

        $this->assertIsArray($result);
        $this->assertArrayHasKey('', $result, 'Selector should have empty option');
        $this->assertArrayHasKey($id, $result);
        $this->assertEquals('Selector Test', $result[$id]);
    }

    // ==================== Category enum tests ====================

    public function testCreate_AllCategories()
    {
        $categories = array('document', 'formation', 'controle', 'briefing', 'autorisation');
        foreach ($categories as $cat) {
            $id = $this->createTestItem(array('category' => $cat));
            $item = $this->model->get_by_id('id', $id);
            $this->assertEquals($cat, $item['category']);
        }
    }

    public function testCreate_BothTargetTypes()
    {
        $internal_id = $this->createTestItem(array('target_type' => 'internal'));
        $external_id = $this->createTestItem(array('target_type' => 'external'));

        $internal = $this->model->get_by_id('id', $internal_id);
        $external = $this->model->get_by_id('id', $external_id);

        $this->assertEquals('internal', $internal['target_type']);
        $this->assertEquals('external', $external['target_type']);
    }

    // ==================== target_user_login tests (Lot 3c) ====================

    public function testCreate_WithTargetUserLogin()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array('target_user_login' => $login));

        $item = $this->model->get_by_id('id', $id);
        $this->assertEquals($login, $item['target_user_login']);
    }

    public function testGetItemsForUser_IncludesUntargetedItem()
    {
        list($login_a, $login_b) = $this->getTwoTestLogins();
        $id = $this->createTestItem(array('title' => 'Untargeted ' . uniqid()));

        $results = $this->model->get_items_for_user($login_a);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertTrue($found, 'An item with no targeting restriction should be returned for any user');
    }

    public function testGetItemsForUser_IncludesIndividuallyTargetedItem()
    {
        list($login_a, $login_b) = $this->getTwoTestLogins();
        $id = $this->createTestItem(array(
            'title' => 'Targeted at A ' . uniqid(),
            'target_user_login' => $login_a,
        ));

        $results = $this->model->get_items_for_user($login_a);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertTrue($found, 'An item individually targeting this user should be returned');
    }

    public function testGetItemsForUser_ExcludesItemTargetedAtAnotherUser()
    {
        list($login_a, $login_b) = $this->getTwoTestLogins();
        $id = $this->createTestItem(array(
            'title' => 'Targeted at B ' . uniqid(),
            'target_user_login' => $login_b,
        ));

        $results = $this->model->get_items_for_user($login_a);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertFalse($found, 'An item individually targeting another user should not be returned');
    }

    // ==================== Role x section targeting (Lot 4) ====================

    /**
     * Helper: an active (non-revoked) user_roles_per_section row, joined to
     * its membre login, so tests exercise the real role-resolution join.
     * The holder must also independently hold the base 'user' role
     * somewhere (cf. Membres_model::actif_dans_au_moins_une_section()),
     * otherwise resolve_targets() would silently drop them regardless of
     * the specific role/section under test.
     */
    protected function getUserWithRole()
    {
        $row = $this->db->query(
            "SELECT m.mlogin, urps.types_roles_id, urps.section_id
             FROM user_roles_per_section urps
             JOIN users u ON u.id = urps.user_id
             JOIN membres m ON m.mlogin = u.username
             WHERE urps.revoked_at IS NULL
               AND EXISTS (
                   SELECT 1 FROM user_roles_per_section urps_active
                   WHERE urps_active.user_id = urps.user_id
                     AND urps_active.types_roles_id = 1
                     AND urps_active.revoked_at IS NULL
               )
             LIMIT 1"
        )->row_array();
        if (!$row) {
            $this->markTestSkipped('No active user_roles_per_section row available for this test');
        }
        return $row;
    }

    protected function addItemRole($item_id, $role_id, $section_id, $login)
    {
        $this->db->insert('acceptance_item_roles', array(
            'item_id' => $item_id,
            'types_roles_id' => $role_id,
            'section_id' => $section_id,
            'created_at' => date('Y-m-d H:i:s'),
            'created_by' => $login,
        ));
    }

    public function testGetItemsForUser_IncludesItemTargetedByHeldRoleAndSection()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Role targeted ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);

        $results = $this->model->get_items_for_user($role['mlogin']);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertTrue($found, 'An item targeting a role/section held by the user should be returned');
    }

    public function testGetItemsForUser_IncludesItemTargetedByRoleAllSections()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Role all sections ' . uniqid()));
        // section_id NULL = "all sections" for that role.
        $this->addItemRole($id, $role['types_roles_id'], null, $role['mlogin']);

        $results = $this->model->get_items_for_user($role['mlogin']);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertTrue($found, 'An item targeting a role in all sections should be returned for a user holding it in any section');
    }

    public function testGetItemsForUser_ExcludesItemTargetedByRoleUserDoesNotHold()
    {
        list($login_a, $login_b) = $this->getTwoTestLogins();
        $role = $this->getUserWithRole();
        // Make sure the user under test truly does not hold this role/section.
        if ($role['mlogin'] === $login_a) {
            $this->markTestSkipped('Test login already holds the sampled role, cannot build a negative case');
        }

        $id = $this->createTestItem(array('title' => 'Role not held ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);

        $results = $this->model->get_items_for_user($login_a);

        $found = false;
        foreach ($results as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertFalse($found, 'An item restricted to roles must not be returned to a user without any of them');
    }

    // ==================== get_pending_items_for_user (Lot 4) ====================

    public function testGetPendingItemsForUser_ExcludesAcceptedItem()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array('title' => 'Pending then accepted ' . uniqid()));

        $this->CI->load->model('acceptance_records_model');
        $record_id = $this->CI->acceptance_records_model->create(array(
            'item_id' => $id,
            'user_login' => $login,
            'status' => 'accepted',
            'acted_at' => date('Y-m-d H:i:s'),
        ));
        $this->assertNotFalse($record_id);

        $pending = $this->model->get_pending_items_for_user($login);
        $found = false;
        foreach ($pending as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertFalse($found, 'An already-accepted item must not appear in the pending list');

        $this->db->where('id', $record_id)->delete('acceptance_records');
    }

    public function testGetPendingItemsForUser_IncludesUnhandledItem()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array('title' => 'Still pending ' . uniqid()));

        $pending = $this->model->get_pending_items_for_user($login);
        $found = false;
        foreach ($pending as $row) {
            if ($row['id'] == $id) $found = true;
        }
        $this->assertTrue($found, 'An item with no acceptance record yet must appear in the pending list');
    }

    // ==================== sync_target_motd (Lot 3d.4) ====================

    protected function motdMessagesForItem($item_id)
    {
        $this->db->where('source_type', Acceptance_items_model::MOTD_SOURCE_TYPE);
        $this->db->where('source_ref', $item_id);
        return $this->db->get('motd_messages')->result_array();
    }

    public function testSyncTargetMotd_IndividualTarget()
    {
        list($login_a, $login_b) = $this->getTwoTestLogins();
        $id = $this->createTestItem(array(
            'title' => 'Individual target ' . uniqid(),
            'target_user_login' => $login_a,
            'mandatory_level' => 'mandatory_hard',
        ));

        $this->model->sync_target_motd($id);

        $messages = $this->motdMessagesForItem($id);
        $this->assertCount(1, $messages);
        $this->assertEquals('user', $messages[0]['target_type']);
        $this->assertEquals($login_a, $messages[0]['target_user_login']);
        $this->assertEquals(0, (int) $messages[0]['dismissible'], 'mandatory_hard must not be dismissible');
        $this->assertEquals('urgent', $messages[0]['level']);
        // Note: MockLang::line() (application/tests/integration_bootstrap.php)
        // returns the key itself rather than a real translation, so the
        // generated content cannot be checked for interpolated text here.
        $this->assertNotEmpty($messages[0]['content']);
    }

    public function testSyncTargetMotd_OptionalIsDismissible()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array(
            'title' => 'Optional target ' . uniqid(),
            'target_user_login' => $login,
            'mandatory_level' => 'optional',
        ));

        $this->model->sync_target_motd($id);

        $messages = $this->motdMessagesForItem($id);
        $this->assertCount(1, $messages);
        $this->assertEquals(1, (int) $messages[0]['dismissible']);
    }

    public function testSyncTargetMotd_RoleAndSectionTarget()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Role target ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);

        $this->model->sync_target_motd($id);

        $messages = $this->motdMessagesForItem($id);
        $this->assertNotEmpty($messages, 'At least the known role holder must be targeted');
        $logins = array_column($messages, 'target_user_login');
        $this->assertContains($role['mlogin'], $logins);
        foreach ($messages as $message) {
            $this->assertEquals('user', $message['target_type'], 'Never a single target_type=all broadcast');
        }
    }

    public function testSyncTargetMotd_UnrestrictedTargetsEveryActiveMember()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array('title' => 'Unrestricted target ' . uniqid()));
        // No target_user_login, no acceptance_item_roles rows.

        $this->model->sync_target_motd($id);

        $messages = $this->motdMessagesForItem($id);
        $this->assertGreaterThan(0, count($messages));
        $logins = array_column($messages, 'target_user_login');
        $this->assertContains($login, $logins, 'An unrestricted item must reach every active member, including this one');
    }

    public function testSyncTargetMotd_SkipsAlreadyHandledUser()
    {
        $login_a = $this->getTestLogin();
        // No target_user_login, no acceptance_item_roles rows: unrestricted,
        // targets every active member — including login_a, who already acted.
        $id = $this->createTestItem(array('title' => 'Skip handled ' . uniqid()));

        $this->db->insert('acceptance_records', array(
            'item_id' => $id,
            'user_login' => $login_a,
            'status' => 'accepted',
            'acted_at' => date('Y-m-d H:i:s'),
            'created_at' => date('Y-m-d H:i:s'),
        ));

        $this->model->sync_target_motd($id);

        $messages = $this->motdMessagesForItem($id);
        $logins = array_column($messages, 'target_user_login');
        $this->assertNotContains($login_a, $logins, 'A user who already accepted must not get a new message');

        $this->db->where('item_id', $id)->where('user_login', $login_a)->delete('acceptance_records');
    }

    public function testSyncTargetMotd_ClearsOnResync()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array(
            'title' => 'Resync target ' . uniqid(),
            'target_user_login' => $login,
        ));

        $this->model->sync_target_motd($id);
        $this->assertCount(1, $this->motdMessagesForItem($id));

        $this->model->sync_target_motd($id);
        $this->assertCount(1, $this->motdMessagesForItem($id), 'Re-syncing must not duplicate the message');
    }

    public function testSyncTargetMotd_InactiveItemHasNoMessage()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array(
            'title' => 'Inactive target ' . uniqid(),
            'target_user_login' => $login,
            'active' => 0,
        ));

        $this->model->sync_target_motd($id);

        $this->assertCount(0, $this->motdMessagesForItem($id));
    }

    public function testClearTargetMotd_RemovesAllMessages()
    {
        $login = $this->getTestLogin();
        $id = $this->createTestItem(array(
            'title' => 'Clear all ' . uniqid(),
            'target_user_login' => $login,
        ));
        $this->model->sync_target_motd($id);
        $this->assertCount(1, $this->motdMessagesForItem($id));

        $this->model->clear_target_motd($id);

        $this->assertCount(0, $this->motdMessagesForItem($id));
    }

    public function testClearTargetMotdForUser_RemovesOnlyThatUsersMessage()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Clear one user ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);
        $this->model->sync_target_motd($id);

        $before = count($this->motdMessagesForItem($id));
        $this->assertGreaterThan(0, $before);

        $this->model->clear_target_motd_for_user($id, $role['mlogin']);

        $after = $this->motdMessagesForItem($id);
        $this->assertCount($before - 1, $after);
        $this->assertNotContains($role['mlogin'], array_column($after, 'target_user_login'));
    }

    // ==================== clear_dangling_motd_for_user ====================

    /**
     * Grants $login a temporary, synthetic role/section assignment — by
     * default any existing role and section row (the pairing itself doesn't
     * matter unless the caller passes explicit ids to match a specific
     * item's targeting) — so a test can simulate gaining/losing it
     * afterwards without touching a real club member's actual roles.
     * Tracked for teardown.
     * @param string $login
     * @param int|null $types_roles_id Explicit role id, or null to pick any
     * @param int|null $section_id Explicit section id, or null to pick any
     * @return array ['types_roles_id' => int, 'section_id' => int, 'urps_id' => int]
     */
    protected function grantTempRole($login, $types_roles_id = null, $section_id = null)
    {
        $user = $this->db->where('username', $login)->get('users')->row_array();
        if (!$user) {
            $this->markTestSkipped("No users row for login $login");
        }
        if ($types_roles_id === null) {
            $role = $this->db->limit(1)->get('types_roles')->row_array();
            if (!$role) {
                $this->markTestSkipped('No role row available for this test');
            }
            $types_roles_id = $role['id'];
        }
        if ($section_id === null) {
            $section = $this->db->limit(1)->get('sections')->row_array();
            if (!$section) {
                $this->markTestSkipped('No section row available for this test');
            }
            $section_id = $section['id'];
        }

        $urps_id = $this->db->insert('user_roles_per_section', array(
            'user_id' => $user['id'],
            'types_roles_id' => $types_roles_id,
            'section_id' => $section_id,
        )) ? $this->db->insert_id() : null;
        $this->assertNotNull($urps_id, 'Failed to grant temporary test role');
        $this->temp_role_ids[] = $urps_id;

        return array('types_roles_id' => $types_roles_id, 'section_id' => $section_id, 'urps_id' => $urps_id);
    }

    public function testClearDanglingMotdForUser_RemovesNotificationForLostRole()
    {
        $role_row = $this->db->limit(1)->get('types_roles')->row_array();
        $section_row = $this->db->limit(1)->get('sections')->row_array();
        if (!$role_row || !$section_row) {
            $this->markTestSkipped('No role/section row available for this test');
        }
        $login = $this->getTestLoginWithoutRole($role_row['id'], $section_row['id']);
        $role = $this->grantTempRole($login, $role_row['id'], $section_row['id']);

        $id = $this->createTestItem(array('title' => 'Dangling after revoke ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $login);
        $this->model->sync_target_motd($id);
        $this->assertContains($login, array_column($this->motdMessagesForItem($id), 'target_user_login'),
            'Precondition: the role holder must have been notified');

        // Simulate the role being revoked (cf. Gvv_Authorization::revoke_role()).
        $this->db->where('id', $role['urps_id'])->update('user_roles_per_section', array(
            'revoked_at' => date('Y-m-d H:i:s'),
        ));

        $this->model->clear_dangling_motd_for_user($login);

        $this->assertNotContains($login, array_column($this->motdMessagesForItem($id), 'target_user_login'),
            'A notification for an item the user is no longer eligible for must not keep linking to it');
    }

    public function testClearDanglingMotdForUser_KeepsNotificationForStillEligibleItem()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Still eligible ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);
        $this->model->sync_target_motd($id);
        $before = $this->motdMessagesForItem($id);
        $this->assertContains($role['mlogin'], array_column($before, 'target_user_login'));

        $this->model->clear_dangling_motd_for_user($role['mlogin']);

        $after = $this->motdMessagesForItem($id);
        $this->assertContains($role['mlogin'], array_column($after, 'target_user_login'),
            'A notification the user is still eligible for must not be removed');
    }

    // ==================== Message actually displayed to the role holder ====================
    //
    // The tests above only check that a motd_messages row was created for
    // the right login (motdMessagesForItem()). None of them go through
    // Motd_model::active_messages_for_user() — the query that actually
    // decides what a member sees on their dashboard. The two below close
    // that gap.

    public function testActiveMessagesForUser_DisplaysNotificationToRoleHolder()
    {
        $role = $this->getUserWithRole();
        $id = $this->createTestItem(array('title' => 'Displayed to role holder ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);
        $this->model->sync_target_motd($id);

        $this->CI->load->model('motd_model');
        $messages = $this->CI->motd_model->active_messages_for_user($role['mlogin']);

        // Note: content is not checked for the interpolated read link here —
        // MockLang::line() (application/tests/integration_bootstrap.php)
        // returns the key itself rather than the real translation in this
        // test environment, same limitation as testSyncTargetMotd_IndividualTarget().
        $found = false;
        foreach ($messages as $m) {
            if ($m['source_type'] === Acceptance_items_model::MOTD_SOURCE_TYPE && (int) $m['source_ref'] === (int) $id) {
                $found = true;
            }
        }
        $this->assertTrue($found, 'The role holder must actually see the generated notification, not just have a row created for them');
    }

    public function testActiveMessagesForUser_HidesNotificationFromUserWithoutTheRole()
    {
        list($login_a, ) = $this->getTwoTestLogins();
        $role = $this->getUserWithRole();
        if ($role['mlogin'] === $login_a) {
            $this->markTestSkipped('Test login already holds the sampled role, cannot build a negative case');
        }

        $id = $this->createTestItem(array('title' => 'Not displayed without role ' . uniqid()));
        $this->addItemRole($id, $role['types_roles_id'], $role['section_id'], $role['mlogin']);
        $this->model->sync_target_motd($id);

        $this->CI->load->model('motd_model');
        $messages = $this->CI->motd_model->active_messages_for_user($login_a);

        foreach ($messages as $m) {
            $this->assertFalse(
                $m['source_type'] === Acceptance_items_model::MOTD_SOURCE_TYPE && (int) $m['source_ref'] === (int) $id,
                'A user who does not hold the targeted role must not see the notification'
            );
        }
    }

    // ==================== Role granted or revoked after the item exists ====================
    //
    // Full lifecycle for a single user/item pair, matching how roles are
    // actually granted/revoked in production (Gvv_Authorization::grant_role()
    // / revoke_role()): before holding the role the item must be invisible
    // everywhere; once granted, the dashboard must reflect it immediately
    // (dynamic query) even though no motd is generated retroactively (an
    // accepted limitation); once revoked, both the dashboard and any
    // leftover motd link must go away (the dead-link fix under test above).

    public function testRoleLifecycle_ItemVisibilityAndNotificationTrackGrantAndRevoke()
    {
        $role_row = $this->db->limit(1)->get('types_roles')->row_array();
        $section_row = $this->db->limit(1)->get('sections')->row_array();
        if (!$role_row || !$section_row) {
            $this->markTestSkipped('No role/section row available for this test');
        }
        $login = $this->getTestLoginWithoutRole($role_row['id'], $section_row['id']);

        $id = $this->createTestItem(array('title' => 'Role lifecycle ' . uniqid()));
        $this->addItemRole($id, $role_row['id'], $section_row['id'], $login);
        $this->model->sync_target_motd($id);
        $this->CI->load->model('motd_model');

        // --- Before the role is granted: invisible everywhere ---
        $pending = $this->model->get_pending_items_for_user($login);
        $this->assertNotContains($id, array_column($pending, 'id'),
            'Before the role is granted, the item must not appear on the dashboard');
        $messages = $this->CI->motd_model->active_messages_for_user($login);
        $this->assertNotContains($id, $this->acceptanceSourceRefs($messages),
            'Before the role is granted, no notification should target this user');

        // --- Role granted (mirrors Gvv_Authorization::grant_role()) ---
        $role = $this->grantTempRole($login, $role_row['id'], $section_row['id']);

        $pending = $this->model->get_pending_items_for_user($login);
        $this->assertContains($id, array_column($pending, 'id'),
            'Once the role is granted, the item must appear on the dashboard immediately (dynamic query)');
        $messages = $this->CI->motd_model->active_messages_for_user($login);
        $this->assertNotContains($id, $this->acceptanceSourceRefs($messages),
            'Gaining a role does not retroactively generate a notification (accepted limitation, cf. sync_target_motd() is only triggered by admin edits)');

        // --- Role revoked (mirrors Gvv_Authorization::revoke_role(), including the dead-link fix) ---
        $this->db->where('id', $role['urps_id'])->update('user_roles_per_section', array(
            'revoked_at' => date('Y-m-d H:i:s'),
        ));
        $this->model->clear_dangling_motd_for_user($login);

        $pending = $this->model->get_pending_items_for_user($login);
        $this->assertNotContains($id, array_column($pending, 'id'),
            'Once the role is revoked, the item must disappear from the dashboard immediately');
        $messages = $this->CI->motd_model->active_messages_for_user($login);
        $this->assertNotContains($id, $this->acceptanceSourceRefs($messages),
            'Once the role is revoked, no dangling notification/dead link must remain displayed');
    }

    /**
     * @param array $messages Rows from Motd_model::active_messages_for_user()
     * @return int[] source_ref of this item's acceptance-sourced messages, as ints
     */
    protected function acceptanceSourceRefs($messages)
    {
        $refs = array();
        foreach ($messages as $m) {
            if ($m['source_type'] === Acceptance_items_model::MOTD_SOURCE_TYPE) {
                $refs[] = (int) $m['source_ref'];
            }
        }
        return $refs;
    }
}
