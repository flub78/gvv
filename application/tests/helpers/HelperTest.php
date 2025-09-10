<?php

use PHPUnit\Framework\TestCase;

class EmailHelperTest extends TestCase
{
	private $CI;
	
	public static function setUpBeforeClass(): void
	{
		// Email validation function should already be available
		// from the minimal bootstrap
	}
	
	public function testEmailValidation()
	{
		$this->assertTrue(valid_email('test@test.com'));
		$this->assertFalse(valid_email('test#test.com'));
	}
}

?>