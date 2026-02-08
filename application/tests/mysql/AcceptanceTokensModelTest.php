<?php

use PHPUnit\Framework\TestCase;

/**
 * MySQL integration tests for Acceptance_tokens_model
 *
 * @package tests
 * @see application/models/acceptance_tokens_model.php
 */
class AcceptanceTokensModelTest extends TestCase
{
    protected $CI;
    protected $db;
    protected $model;
    protected $items_model;
    protected $test_item_ids = array();
    protected $test_token_ids = array();

    protected function setUp(): void
    {
        $this->CI =& get_instance();
        $this->db = $this->CI->db;

        $this->CI->load->model('acceptance_items_model');
        $this->CI->load->model('acceptance_tokens_model');
        $this->model = $this->CI->acceptance_tokens_model;
        $this->items_model = $this->CI->acceptance_items_model;
    }

    protected function tearDown(): void
    {
        foreach (array_reverse($this->test_token_ids) as $id) {
            $this->db->delete('acceptance_tokens', array('id' => $id));
        }
        foreach (array_reverse($this->test_item_ids) as $id) {
            $this->db->delete('acceptance_items', array('id' => $id));
        }
    }

    protected function getTestLogin()
    {
        $query = $this->db->query("SELECT mlogin FROM membres LIMIT 1");
        $row = $query->row_array();
        return $row ? $row['mlogin'] : null;
    }

    protected function createTestItem()
    {
        $login = $this->getTestLogin();
        if (!$login) {
            $this->markTestSkipped('No member in database for testing');
        }

        $id = $this->items_model->create(array(
            'title' => 'Token Test Item ' . uniqid(),
            'category' => 'document',
            'target_type' => 'external',
            'mandatory' => 0,
            'dual_validation' => 0,
            'active' => 1,
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s')
        ));
        $this->test_item_ids[] = $id;
        return $id;
    }

    /**
     * Helper: generate a token and track its DB id for cleanup
     */
    protected function generateAndTrack($item_id, $mode = 'link', $expires_hours = 24)
    {
        $login = $this->getTestLogin();
        $token = $this->model->generate_token($item_id, $mode, $login, $expires_hours);
        $this->assertNotFalse($token);

        // Find the ID for cleanup
        $this->db->where('token', $token);
        $row = $this->db->get('acceptance_tokens')->row_array();
        if ($row) {
            $this->test_token_ids[] = $row['id'];
        }
        return $token;
    }

    // ==================== generate_token tests ====================

    public function testGenerateToken_ReturnsHexString()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);

        $this->assertIsString($token);
        $this->assertEquals(64, strlen($token), 'Token should be 64 hex chars');
        $this->assertRegExp('/^[0-9a-f]+$/', $token, 'Token should be hexadecimal');
    }

    public function testGenerateToken_StoresInDatabase()
    {
        $item_id = $this->createTestItem();
        $login = $this->getTestLogin();
        $token = $this->generateAndTrack($item_id, 'qrcode');

        $this->db->where('token', $token);
        $row = $this->db->get('acceptance_tokens')->row_array();

        $this->assertNotEmpty($row);
        $this->assertEquals($item_id, $row['item_id']);
        $this->assertEquals('qrcode', $row['mode']);
        $this->assertEquals($login, $row['created_by']);
        $this->assertEquals(0, $row['used']);
    }

    public function testGenerateToken_UniqueTokens()
    {
        $item_id = $this->createTestItem();
        $token1 = $this->generateAndTrack($item_id);
        $token2 = $this->generateAndTrack($item_id);

        $this->assertNotEquals($token1, $token2, 'Each token should be unique');
    }

    public function testGenerateToken_CustomExpiration()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id, 'link', 48);

        $this->db->where('token', $token);
        $row = $this->db->get('acceptance_tokens')->row_array();

        $expires = strtotime($row['expires_at']);
        $created = strtotime($row['created_at']);
        $diff_hours = ($expires - $created) / 3600;

        // Allow small tolerance for execution time
        $this->assertGreaterThan(47, $diff_hours);
        $this->assertLessThan(49, $diff_hours);
    }

    // ==================== validate_token tests ====================

    public function testValidateToken_ValidToken()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);

        $result = $this->model->validate_token($token);
        $this->assertIsArray($result);
        $this->assertEquals($token, $result['token']);
        $this->assertEquals($item_id, $result['item_id']);
        $this->assertArrayHasKey('item_title', $result);
    }

    public function testValidateToken_ExpiredToken()
    {
        $item_id = $this->createTestItem();
        $login = $this->getTestLogin();

        // Create a token that expired 1 hour ago
        $token = bin2hex(random_bytes(32));
        $this->db->insert('acceptance_tokens', array(
            'token' => $token,
            'item_id' => $item_id,
            'mode' => 'link',
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s', time() - 7200),
            'expires_at' => date('Y-m-d H:i:s', time() - 3600),
            'used' => 0
        ));
        $this->test_token_ids[] = $this->db->insert_id();

        $result = $this->model->validate_token($token);
        $this->assertFalse($result, 'Expired token should be invalid');
    }

    public function testValidateToken_UsedToken()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);

        // Mark as used
        $this->db->where('token', $token);
        $this->db->update('acceptance_tokens', array('used' => 1));

        $result = $this->model->validate_token($token);
        $this->assertFalse($result, 'Used token should be invalid');
    }

    public function testValidateToken_NonExistentToken()
    {
        $result = $this->model->validate_token('nonexistent_token_12345');
        $this->assertFalse($result);
    }

    // ==================== mark_used tests ====================

    public function testMarkUsed_SetsUsedFields()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);

        $result = $this->model->mark_used($token, 999);
        $this->assertTrue($result);

        $this->db->where('token', $token);
        $row = $this->db->get('acceptance_tokens')->row_array();

        $this->assertEquals(1, $row['used']);
        $this->assertNotNull($row['used_at']);
        $this->assertEquals(999, $row['record_id']);
    }

    public function testMarkUsed_TokenNoLongerValid()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);

        $this->model->mark_used($token, 999);
        $result = $this->model->validate_token($token);
        $this->assertFalse($result, 'Used token should no longer validate');
    }

    // ==================== cleanup_expired tests ====================

    public function testCleanupExpired_RemovesOldTokens()
    {
        $item_id = $this->createTestItem();
        $login = $this->getTestLogin();

        // Create a token that expired 10 days ago
        $old_token = bin2hex(random_bytes(32));
        $this->db->insert('acceptance_tokens', array(
            'token' => $old_token,
            'item_id' => $item_id,
            'mode' => 'link',
            'created_by' => $login,
            'created_at' => date('Y-m-d H:i:s', time() - 864000),
            'expires_at' => date('Y-m-d H:i:s', time() - 864000),
            'used' => 0
        ));
        $old_id = $this->db->insert_id();

        // Create a valid token (should NOT be deleted)
        $valid_token = $this->generateAndTrack($item_id);

        $deleted = $this->model->cleanup_expired(7);
        $this->assertGreaterThanOrEqual(1, $deleted);

        // Old token should be gone
        $this->db->where('id', $old_id);
        $row = $this->db->get('acceptance_tokens')->row_array();
        $this->assertEmpty($row, 'Expired token should be deleted');

        // Valid token should still exist
        $result = $this->model->validate_token($valid_token);
        $this->assertNotFalse($result, 'Valid token should survive cleanup');
    }

    // ==================== get_by_item tests ====================

    public function testGetByItem_ReturnsActiveTokens()
    {
        $item_id = $this->createTestItem();
        $token1 = $this->generateAndTrack($item_id, 'link');
        $token2 = $this->generateAndTrack($item_id, 'qrcode');

        $results = $this->model->get_by_item($item_id);
        $this->assertIsArray($results);
        $this->assertGreaterThanOrEqual(2, count($results));
    }

    public function testGetByItem_ExcludesUsedTokens()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id);
        $this->model->mark_used($token, 999);

        $active_token = $this->generateAndTrack($item_id);

        $results = $this->model->get_by_item($item_id);
        $used_found = false;
        $active_found = false;
        foreach ($results as $row) {
            if ($row['token'] == $token) $used_found = true;
            if ($row['token'] == $active_token) $active_found = true;
        }

        $this->assertFalse($used_found, 'Used token should not appear');
        $this->assertTrue($active_found, 'Active token should appear');
    }

    // ==================== image tests ====================

    public function testImage_ReturnsTruncatedToken()
    {
        $item_id = $this->createTestItem();
        $token = $this->generateAndTrack($item_id, 'qrcode');

        $this->db->where('token', $token);
        $row = $this->db->get('acceptance_tokens')->row_array();

        $result = $this->model->image($row['id']);
        $this->assertStringContainsString('...', $result);
        $this->assertStringContainsString('qrcode', $result);
    }

    public function testImage_EmptyKey()
    {
        $this->assertEquals('', $this->model->image(''));
    }

    // ==================== All modes tests ====================

    public function testGenerateToken_AllModes()
    {
        $item_id = $this->createTestItem();

        foreach (array('direct', 'link', 'qrcode') as $mode) {
            $token = $this->generateAndTrack($item_id, $mode);
            $this->db->where('token', $token);
            $row = $this->db->get('acceptance_tokens')->row_array();
            $this->assertEquals($mode, $row['mode']);
        }
    }
}
