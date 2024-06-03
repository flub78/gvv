<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');
/**
 * CodeIgniter
 *
 * An open source application development framework for PHP 5.1.6 or newer
 *
 * @package		CodeIgniter
 * @author		ExpressionEngine Dev Team
 * @copyright	Copyright (c) 2008 - 2011, EllisLab, Inc.
 * @license		http://codeigniter.com/user_guide/license.html
 * @link		http://codeigniter.com
 * @since		Version 1.0
 * @filesource
 */

// ------------------------------------------------------------------------

/**
 * Logging Class
 *
 * @package		CodeIgniter
 * @subpackage	Libraries
 * @category	Logging
 * @author		ExpressionEngine Dev Team
 * @link		http://codeigniter.com/user_guide/general/errors.html
 * 
 * Additionals services mostly test oriented.
 * 
 * It is convenient for a test to check the latest log entries.
 * 
 */
class MY_Log extends CI_Log {


	// --------------------------------------------------------------------

	/**
	 * Name of the current log file
	 *
	 */
	public function log_file()
	{
		$filepath = $this->_log_path.'log-'.date('Y-m-d').'.php';

		if ( ! file_exists($filepath))
		{
			return false;
		} else {
			return $filepath;
		}
	}
	
	/**
	 * 
	 * @return the current size of the log file
	 */
	public function log_file_size () {
		clearstatcache();
		return filesize ($this->log_file());
	}
	
	/**
	 * Return the last lines that match a pattern in the log
	 * @param unknown $pattern
	 * @param $level = "DEBUG" | "INFO" | "ERROR"
	 */
	public function last_lines ($pattern = "", $level = "") {
		
		$log = file_get_contents($this->log_file());
		
		$lines = explode("\n", $log);
		$matching = preg_grep("/{$pattern}/", $lines);
		if ($level != "") {
			$matching = preg_grep("/^{$level}/", $matching);
		}
		
		return $matching;		
	}
	
	/**
	 * Return the last lines that match a pattern in the log
	 * @param unknown $pattern
	 * @param $level = "DEBUG" | "INFO" | "ERROR"
	 */
	public function count_lines ($pattern = "", $level = "") {
	
		return count($this->last_lines($pattern, $level));
	}
	
	
}
// END Log Class

/* End of file MY_Log.php */
/* Location: ./application/libraries/MY_Log.php */