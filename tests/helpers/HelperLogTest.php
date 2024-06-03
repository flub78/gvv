<?php

/**
 * @group Helper
 */

class HelperLogTest extends CIUnit_TestCase
{
	public function setUp()
	{
		$this->CI->load->helper('log');
	}
	
	/**
	 * Just a few assertions
	 */
	public function testAsserts()
	{
		$this->assertEquals(3, 3, "3 == 3");
		$this->assertTrue(true, "true == true");
	}
	
	private function check_log($level = "info") {
		$pattern = "phpunit log $level level";
		$initial_count = occurences($pattern);
		$msg = "found $initial_count occurences of \"$pattern\" in log file";
// 		echo $msg . "\n";
		
		if ($level == "info") {
			gvv_info($pattern);
		} elseif ($level == "debug") {
			gvv_debug($pattern);
		} elseif ($level == "error") {
			gvv_error($pattern);
		} 
		
		$count = occurences($pattern);
		$msg = "found $count occurences of \"$pattern\" in log file";
		$this->assertTrue($count > 0, $msg);
		$this->assertTrue($count > $initial_count, "number of \"$pattern\" has been incremented");
// 		echo $msg . "\n";
	}
	
	/**
	 * Test log helper
	 * The size of the log file cannot be used for testing, for some reason the size is
	 * not updated synchronously.
	 */
	public function testLogging()
	{

		$logpath = current_logfile();
		echo "logfile = $logpath\n";
		
		$this->assertTrue(file_exists($logpath), "log file $logpath exists");
		$initial_filesize = filesize($logpath);
		$this->assertTrue($initial_filesize > 0, "log file $logpath is not empty");
		
		$this->check_log("info");
		$this->check_log("error");
		$this->check_log("debug");
		
	}
}
