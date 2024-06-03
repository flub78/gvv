<?php

/**
 * @group Helper
 */

class HelperValidationTest extends CIUnit_TestCase
{
	/**
	 * Constructor
	 *
	 * Load the libraries
	 *
	 */
	function __construct() {
	
		CIUnit_TestCase::__construct();
	
		$this->CI->load->helper('validation');
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_date_db2ht()
	{
		$this->assertSame("01/01/2013", date_db2ht("2013-01-01"), "");		
		$this->assertSame("31/12/2013", date_db2ht("2013-12-31"), "");		
		$this->assertSame("29/02/2013", date_db2ht("2013-02-29"), "");		
		$this->assertSame("30/02/2013", date_db2ht("2013-02-30"), "");		

		$this->assertSame("30/02/2013", date_db2ht("30/02/2013"), "");		
		$this->assertSame("", date_db2ht(""), "");		
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_date_ht2db()
	{
		$this->assertSame("2013-01-01", date_ht2db("01/01/2013"), "");		
		$this->assertSame("2013-12-31", date_ht2db("31/12/2013"), "");		
		$this->assertSame("2013-02-29", date_ht2db("29/02/2013"), "");
				
		$this->assertSame("2013-02-30", date_ht2db("2013-02-30"), "");		
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_french_date_compare()
	{
		$this->assertTrue(french_date_compare("01/01/2013", "01/01/2013", "=="), "");
		$this->assertTrue(french_date_compare("01/01/2013", "01/01/2013", "<="), "");
		$this->assertTrue(french_date_compare("01/01/2013", "01/01/2013", ">="), "");
		
		$this->assertTrue(french_date_compare("01/01/2013", "02/01/2013", "<="), "");
		$this->assertTrue(french_date_compare("01/01/2013", "02/01/2013", "<"), "");
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_minute_to_time()
	{
		$this->assertSame(minute_to_time(0), " 0h00", "minute_to_time(0)");
		$this->assertSame(minute_to_time(35), " 0h35", "minute_to_time(35)");
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_mysql_date()
	{
		$this->assertSame(mysql_date(''), '', "mysql_date()");
		$this->assertSame(mysql_date('31-12-2012'), '2012-12-31', "mysql_date()");
		$this->assertSame(mysql_date('31/12/2012'), '2012-12-31', "mysql_date()");
		$this->assertSame(mysql_date('31 12 2012'), '2012-12-31', "mysql_date()");
		$this->assertSame(mysql_date(''), '', "mysql_date()");
		$this->assertSame(mysql_date(''), '', "mysql_date()");
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_mysql_minutes()
	{
		$this->assertSame(mysql_minutes(''), 0, "mysql_minutes");
		$this->assertSame(mysql_minutes('2h35'), 155, "mysql_minutes");
		$this->assertSame(mysql_minutes('2.35'), 155, "mysql_minutes");
	}
	
	/**
	 * Just a few assertions
	 */
	public function test_mysql_time()
	{
		$this->assertSame(mysql_time(''), '00:00', "mysql_time");
		$this->assertSame(mysql_time('2h35'), '2:35', "mysql_time");
		$this->assertSame(mysql_time('2.35'), '2:35', "mysql_time");
	}

	/**
	 * Just a few assertions
	 */
	public function test_euro()
	{
		$this->assertSame(euro(0), '0.00', "euro");
		$this->assertSame(euro(12), '12.00', "euro");
		$this->assertSame(euro(14.953), '14.95', "euro");
	}
}
