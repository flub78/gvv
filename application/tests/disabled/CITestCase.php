<?php

use PHPUnit\Framework\TestCase;

/**
 * Base class for CodeIgniter tests that need database access
 * 
 * This is a simplified replacement for the old PHPUnit_Extensions_Database_TestCase
 * that worked with older PHPUnit versions. For PHPUnit 8.x, we use a simpler approach.
 * 
 * @author		Updated for PHPUnit 8.x compatibility
 */
abstract class CITestCase extends TestCase
{
	/**
	 * Reference to CodeIgniter
	 * 
	 * @var resource
	 */
	protected $CI;
	
	/**
	 * Call parent constructor and initialize reference to CodeIgniter
	 */
	public function setUp(): void
	{
		parent::setUp();
		$this->CI =& get_instance();
		
		// Initialize database if needed
		if (!isset($this->CI->db)) {
			$this->CI->load->database();
		}
	}
	
	/**
	 * Clean up after each test
	 */
	public function tearDown(): void
	{
		// You can add database cleanup here if needed
		// For example: $this->CI->db->trans_rollback();
		parent::tearDown();
	}
	
	/**
	 * Helper method to get database connection
	 * 
	 * @return object Database connection
	 */
	protected function getDatabase()
	{
		return $this->CI->db;
	}
	
	/**
	 * Helper method to assert database record exists
	 * 
	 * @param string $table Table name
	 * @param array $conditions Where conditions
	 * @param string $message Optional message
	 */
	protected function assertDatabaseHas($table, $conditions, $message = '')
	{
		$query = $this->CI->db->get_where($table, $conditions);
		$this->assertGreaterThan(0, $query->num_rows(), 
			$message ?: "Failed asserting that table '$table' contains matching record");
	}
	
	/**
	 * Helper method to assert database record does not exist
	 * 
	 * @param string $table Table name
	 * @param array $conditions Where conditions  
	 * @param string $message Optional message
	 */
	protected function assertDatabaseMissing($table, $conditions, $message = '')
	{
		$query = $this->CI->db->get_where($table, $conditions);
		$this->assertEquals(0, $query->num_rows(),
			$message ?: "Failed asserting that table '$table' does not contain matching record");
	}
}
