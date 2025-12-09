<?php
/**
 * Base class for integration tests that use database transactions
 * 
 * This class provides automatic transaction rollback in tearDown() to ensure
 * database isolation between tests, even when using CodeIgniter models that
 * use their own trans_start()/trans_complete() calls (nested transactions).
 */

use PHPUnit\Framework\TestCase;

abstract class TransactionalTestCase extends TestCase {
    
    protected $CI;
    
    protected function setUp(): void {
        // Get CodeIgniter instance
        $this->CI =& get_instance();
        
        // Start transaction for test isolation
        if ($this->CI && $this->CI->db) {
            $this->CI->db->trans_start();
        }
        
        parent::setUp();
    }
    
    protected function tearDown(): void {
        // Rollback transaction to restore database state
        if ($this->CI && $this->CI->db) {
            // Force rollback by resetting transaction depth
            // CodeIgniter only rollbacks when _trans_depth == 0
            $this->CI->db->_trans_depth = 0;
            $this->CI->db->trans_rollback();
        }
        parent::tearDown();
    }
}
