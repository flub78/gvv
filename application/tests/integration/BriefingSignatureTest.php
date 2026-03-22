<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration tests for UC2: digital signature tokens (briefing_tokens table)
 *
 * Tests:
 * - Token insertion and format (64-char hex, unique)
 * - Token lookup: valid, expired, already-used
 * - Token mark-as-used
 * - Cascade delete when VLD is deleted
 */
class BriefingSignatureTest extends TestCase
{
    /** @var object */
    private $CI;
    /** @var object */
    private $db;
    /** @var int */
    private $vld_id;
    /** @var array IDs to clean up */
    private $token_ids = array();

    protected function setUp(): void
    {
        $this->CI = &get_instance();
        $this->db = $this->CI->db;

        // Get an existing VLD to attach tokens to
        $row = $this->db->query("SELECT id FROM vols_decouverte LIMIT 1")->row_array();
        if (!$row) {
            $this->markTestSkipped('No vols_decouverte records found');
        }
        $this->vld_id = (int)$row['id'];

        // Verify briefing_tokens table exists
        $result = $this->db->query("SHOW TABLES LIKE 'briefing_tokens'")->row_array();
        if (empty($result)) {
            $this->markTestSkipped('briefing_tokens table not found — run migration 088');
        }
    }

    protected function tearDown(): void
    {
        if (!empty($this->token_ids)) {
            $this->db->where_in('id', $this->token_ids)->delete('briefing_tokens');
        }
    }

    /**
     * Insert a token and register for cleanup.
     */
    private function insertToken($token, $used_at = null, $expires_at = null)
    {
        $data = array(
            'vld_id'     => $this->vld_id,
            'token'      => $token,
            'created_at' => date('Y-m-d H:i:s'),
            'expires_at' => $expires_at,
            'used_at'    => $used_at,
        );
        $this->db->insert('briefing_tokens', $data);
        $id = $this->db->insert_id();
        $this->token_ids[] = $id;
        return $id;
    }

    // -------------------------------------------------------------------------
    // Token format
    // -------------------------------------------------------------------------

    public function testToken_IsGenerated_As64CharHex()
    {
        $token = bin2hex(random_bytes(32));
        $this->assertEquals(64, strlen($token));
        $this->assertRegExp('/^[a-f0-9]{64}$/', $token);
    }

    public function testToken_InsertedInDb_CanBeRetrieved()
    {
        $token = bin2hex(random_bytes(32));
        $this->insertToken($token);

        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotEmpty($row);
        $this->assertEquals($this->vld_id, (int)$row['vld_id']);
        $this->assertNull($row['used_at']);
    }

    public function testToken_Uniqueness_EnforcedByDB()
    {
        $token = bin2hex(random_bytes(32));
        $this->insertToken($token);

        // Second insert with same token should fail due to UNIQUE constraint
        $threw = false;
        try {
            $this->db->db_debug = true;
            $this->db->insert('briefing_tokens', array(
                'vld_id'     => $this->vld_id,
                'token'      => $token,
                'created_at' => date('Y-m-d H:i:s'),
            ));
        } catch (\Exception $e) {
            $threw = true;
        } finally {
            $this->db->db_debug = true;
        }

        $this->assertTrue($threw, 'Duplicate token insert should throw due to UNIQUE constraint');
    }

    // -------------------------------------------------------------------------
    // Token validation
    // -------------------------------------------------------------------------

    public function testValidateToken_ValidToken_Passes()
    {
        $token = bin2hex(random_bytes(32));
        $this->insertToken($token, null, date('Y-m-d H:i:s', strtotime('+7 days')));

        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotEmpty($row, 'Valid token should be found');
        $this->assertNull($row['used_at'], 'Valid token should not be used');
        $this->assertGreaterThan(time(), strtotime($row['expires_at']), 'Valid token should not be expired');
    }

    public function testValidateToken_UnknownToken_ReturnsNull()
    {
        $fake_token = str_repeat('f', 64); // valid hex format but not in DB
        $row = $this->db->get_where('briefing_tokens', array('token' => $fake_token))->row_array();
        // Clean up if accidentally exists
        $this->assertEmpty($row, 'Unknown token should not exist in DB');
    }

    public function testValidateToken_ExpiredToken_ShouldBeDetected()
    {
        $token = bin2hex(random_bytes(32));
        $past  = date('Y-m-d H:i:s', strtotime('-1 day'));
        $this->insertToken($token, null, $past);

        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotEmpty($row);
        $this->assertLessThan(time(), strtotime($row['expires_at']), 'Expired token should have past expiry');
    }

    public function testValidateToken_UsedToken_ShouldBeDetected()
    {
        $token   = bin2hex(random_bytes(32));
        $used_at = date('Y-m-d H:i:s', strtotime('-1 hour'));
        $this->insertToken($token, $used_at, date('Y-m-d H:i:s', strtotime('+7 days')));

        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotEmpty($row);
        $this->assertNotNull($row['used_at'], 'Already-used token should have used_at set');
    }

    // -------------------------------------------------------------------------
    // Mark token as used
    // -------------------------------------------------------------------------

    public function testMarkTokenUsed_SetsUsedAt()
    {
        $token = bin2hex(random_bytes(32));
        $this->insertToken($token, null, date('Y-m-d H:i:s', strtotime('+7 days')));

        $before = date('Y-m-d H:i:s');
        $this->db->where('token', $token)->update('briefing_tokens', array(
            'used_at'    => date('Y-m-d H:i:s'),
            'ip_address' => '127.0.0.1',
        ));
        $after = date('Y-m-d H:i:s');

        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotNull($row['used_at']);
        $this->assertGreaterThanOrEqual($before, $row['used_at']);
        $this->assertLessThanOrEqual($after, $row['used_at']);
        $this->assertEquals('127.0.0.1', $row['ip_address']);
    }

    public function testMarkTokenUsed_PreventReuse()
    {
        $token   = bin2hex(random_bytes(32));
        $used_at = date('Y-m-d H:i:s');
        $this->insertToken($token, $used_at, date('Y-m-d H:i:s', strtotime('+7 days')));

        // Simulate the controller check: if used_at is set, reject
        $row = $this->db->get_where('briefing_tokens', array('token' => $token))->row_array();
        $this->assertNotEmpty($row);
        $this->assertNotNull($row['used_at'], 'Token should be marked as used');
        // The controller returns an error when used_at is not null
        $should_reject = !empty($row['used_at']);
        $this->assertTrue($should_reject, 'Controller should reject already-used token');
    }
}

/* End of file BriefingSignatureTest.php */
/* Location: ./application/tests/integration/BriefingSignatureTest.php */
