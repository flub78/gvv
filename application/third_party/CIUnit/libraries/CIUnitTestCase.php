<?php

/*
* fooStack, CIUnit for CodeIgniter
* Copyright (c) 2008-2009 Clemens Gruenberger
* Released under the MIT license, see:
* http://www.opensource.org/licenses/mit-license.php
*/

/**
 * Extending the default phpUnit Framework_TestCase Class
 * providing eg. fixtures, custom assertions, utilities etc.
 */
class CIUnit_TestCase extends PHPUnit_Framework_TestCase
{
	// ------------------------------------------------------------------------
	
	/**
	 * An associative array of table names. The order of the fixtures
	 * determines the loading and unloading sequence of the fixtures. This is 
	 * to help account for foreign key restraints in databases.
	 *
	 * For example:
	 * $tables = array(
	 *				'group' => 'group',
	 *				'user' => 'user',
	 *				'user_group' => 'user_group'
	 *				'table_a' => 'table_a_01'
	 * 			);
	 *
	 * Note: To test different data scenarios for a single database, create
	 * different fixtures.
	 *
	 * For example:
	 * $tables = array(
	 *				'table_a' => 'table_a_02'
	 *			);
	 *
	 * @var array
	 */
	protected $tables = array();
	
	// ------------------------------------------------------------------------
	
	/**
	 * The CodeIgniter Framework Instance
	 *
	 * @var object
	 */
	public $CI;
	
	// ------------------------------------------------------------------------
	
	/**
	 * Constructor
	 *
	 * @param	string	$name 
	 * @param	array	$data 
	 * @param	string	$dataName 
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->CI =& get_instance();
		
		$msg = get_class($this).' CIUnit_TestCase initialized';
		log_message('debug', $msg);
		// echo $msg . "\n";
	}
	
	/**
	 * Set Up
	 *
	 * This method will run before every test.
	 *
	 * @return void
	 *
	 * @author Eric Jones
	 */
	protected function setUp()
	{
		// Only run if the $tables attribute is set.
		if ( ! empty($this->tables))
		{
			$this->dbfixt($this->tables);
		}
	}
	
	/**
	 * Tear Down
	 * 
	 * This method will run after every test.
	 * 
	 * @return void
	 *
	 * @author Eric Jones
	 */
	protected function tearDown()
	{
		// Only run if the $tables attribute is set.
		if ( ! empty($this->tables))
		{
			$this->dbfixt_unload($this->tables);
		}
	}
	
	/**
	 * loads a database fixture
	 * for each given fixture, we look up the yaml file and insert that into the corresponding table
	 * names are by convention
	 * 'users' -> look for 'users_fixt.yml' fixture: 'fixtures/users_fixt.yml'
	 * table is assumed to be named 'users'
	 * dbfixt can have multiple strings as arguments, like so:
	 * $this->dbfixt('users', 'items', 'prices');
	 */
	protected function dbfixt($table_fixtures)
	{
		if (is_array($table_fixtures))
		{
			$this->load_fixt($table_fixtures);
		}
		else
		{
			$table_fixtures = func_get_args();
			$this->load_fixt($table_fixtures);
		}
		
		/**
		 * This is to allow the Unit Tester to specifiy different fixutre files for
		 * a given table. An example would be the testing of two different senarios
		 * of data in the database.
		 *
		 * @see CIUnitTestCase::tables
		 */
		foreach($table_fixtures as $table => $fixt )
		{
			$fixt_name = $fixt . '_fixt';
			$table = is_int($table) ? $fixt : $table;
			
			if (!empty($this->$fixt_name))
			{
				CIUnit::$fixture->load($table, $this->$fixt_name);
			}
			else
			{
				die("The fixture {$fixt_name} failed to load properly\n");
			}
			
		}
		
		log_message('debug', 'Table fixtures "' . join('", "', $table_fixtures) . '" loaded');
	}
	
	/**
	 * DBFixt Unload
	 *
	 * Since there may be foreign key dependencies in the database, we can't just
	 * truncate tables in random order. This method attempts to truncate the
	 * tables by reversing the order of the $table attribute.
	 *
	 * @param	array	$table_fixtures	Typically this will be the class attribute $table.
	 * @param	boolean	$reverse		Should the method reverse the $table_fixtures array
	 * before the truncating the tables?
	 *
	 * @return void
	 *
	 * @see CIUnitTestCase::table
	 *
	 * @uses CIUnit::fixture
	 * @uses Fixture::unload()
	 *
	 * @author Eric Jones <eric.web.email@gmail.com>
	 */
	protected function dbfixt_unload(array $table_fixtures, $reverse = true)
	{
		// Should we reverse the order of loading?
		// Helps with truncating tables with foreign key dependencies.
		if ($reverse)
		{
			// Since the loading of tables took into account foreign key
			// dependencies we should be able to just reverse the order
			// of the database load. Right??
			$table_fixtures = array_reverse($table_fixtures, true);
		}
	
		// Iterate over the array unloading the tables
		foreach ($table_fixtures as $table => $fixture)
		{
			CIUnit::$fixture->unload($table);
			log_message('debug', 'Table fixture "' . $fixture . '" unloaded');
		}
	}

	/**
	* fixture wrapper, for arbitrary number of arguments
	*/
	function fixt()
	{
		$fixts = func_get_args();
		$this->load_fixt($fixts);
	}

	/**
	* loads a fixture from a yaml file
	*/
	protected function load_fixt($fixts)
	{
		foreach ( $fixts as $fixt )
		{
			$fixt_name = $fixt . '_fixt';
			
			if (file_exists(TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml')) {
				$this->$fixt_name = CIUnit::$spyc->loadFile(TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml');
			}
			else
			{
				die('The file '. TESTSPATH . 'fixtures/' . $fixt . '_fixt.yml doesn\'t exist.');
			}
		}
	}
	

	/**
	 * Simple test for function that return a string
	 * @param unknown $name
	 * @param string $arg
	 */
	protected function tst_function_args ($name, $args = array(), $expected = "") {
	
		if (count($args) == 0) {
			$str = $name();			
		} else if (count($args) == 1) {
			$str = $name($args[0]);
		} else if (count($args) == 2) {
			$str = $name($args[0], $args[1]);
		} else if (count($args) == 3) {
			$str = $name($args[0], $args[1], $args[2]);
		} else if (count($args) == 4) {
			$str = $name($args[0], $args[1], $args[2], $args[3]);
		}	
		echo "$name(". var_export($args, true) .") : $str\n";
		
		if ($expected) {
			$this->assertEquals($str, $expected, "$name returns $expected");
		} else {
			$this->assertNotEquals($str, "", "$name not empty");
		}
	}
	
	/**
	 * Check that there is no error on HTML
	 */
	public function no_errors_on_page($method) {
		$this->CI->$method();
	
		// Fetch the buffered output
		$out = output();
	
		// Check if the content is OK
	
		// No warnings
		$warnings = preg_match('/(notice)/i', $out);
		$errors = preg_match('/(Error|notice)/', $out);
	
		if (($warnings != 0)|| ($errors != 0)) {
			// Save the output
			$filename = getcwd() . "/output/error_" . $method . "_" . date("Ymdhns") . ".html";
				
			echo "file://$filename\n";
			if (!write_file($filename, $out)) {
			echo "error writing $filename, check that it is writable";
			}
	
			}
	
			$this->assertSame(0, $warnings, "Pas de warning, $method()");
			$this->assertSame(0, $errors, "Pas d'erreurs, $method()");
	}
	
	/**
	 * Login as a test user
	 *
	 * @param unknown $username
	 * @param unknown $password
	 */
	function login($username, $password) {
		if ($this->CI->dx_auth->is_logged_in())
		{
			print "Connected before login\n";
		} else {
			print "Not connected before login\n";
		}
	
		$this->CI->dx_auth->login($username, $password, 0);
	
		if ($this->CI->dx_auth->is_logged_in())
		{
			print "Connected after login\n";
		} else {
			print "Not connected after login\n";
		}
	}
	
	/**
	 * Logout the test user
	 *
	 */
	function logout() {
		$this->CI->dx_auth->logout();
		if ($this->CI->dx_auth->is_logged_in())
		{
			print "Connected\n";
		} else {
			print "Not connected\n";
		}
	}
	
	/**
	 * Unit test of model common methods
	 */
	public function db_common($model, $latest_id, $table)
	{
		// test table
		$this->assertEquals($table, $model->table(), "table name");
	
		// first object
		$key = $model->primary_key();
		$first = $model->get_first();
		$current = $model->get_first(array($key => $latest_id));
		if ($this->initial_count == 0) {
			$this->assertEquals($first[$key], $current[$key]);
		} else {
			$this->assertNotEquals($first[$key], $current[$key]);
		}
	
		$this->assertNotEquals($model->image($key), "", "image not empty");
	
		$selector1 = $model->selector();
		$this->assertTrue(in_array($latest_id, $selector1), "last inserted element found in selector");
	
		$selector2 = $model->selector_with_all();
		$this->assertTrue(in_array($latest_id, $selector2), "last inserted element found in selector_with_all");
		$this->assertTrue(array_key_exists('', $selector2), "empty string found in selector_with_all");
	
		$selector3 = $model->selector_with_null();
		$this->assertTrue(in_array($latest_id, $selector3), "last inserted element found in selector_with_all");
		$this->assertTrue(array_key_exists('', $selector3), "empty string found in selector_with_all");
	}
	
	/**
	 * Test creation
	 */
	public function db_create($model, $latest_id, $table, $elt, $update)
	{	
	
		// test create
		$model->create($elt);
	
		$this->assertEquals($this->initial_count + 1, count($model->select_page()), "one additional element");
	
		// update
		foreach ($update as $key => $value) {
			$elt[$key] = $value;
		}
		$model->update($model->primary_key(), $elt, $latest_id);
	
		// test machine list
		$list = $model->list_of();
		$this->assertEquals($this->initial_count + 1, count($list), "correct size for machine_list");
		$found = false;
		foreach ($list as $element) {
			if ($element->id = $latest_id) {
				$found = true;
				break;
			}
		}
		$this->assertTrue($found, "lastest inserted element found in list");
	
		$list_of = $model->list_of();
		$this->assertEquals($this->initial_count + 1, count($list_of), "correct size for list_of");
	
		$list1 = $model->machine_list(array(), false);
		$list2 = $model->select_columns('macimmat, horametre_en_minutes');
		$this->assertEquals(count($list1), count($list2), "correct size for machine_list 2");
	
		$inserted = $model->get_by_id($model->primary_key(), $latest_id);
		foreach ($elt as $key => $value) {
			if ($elt[$key] != "") {
				$equal = ($elt[$key] == $inserted[$key]);
				$this->assertTrue($equal, "correct inserted value, key=$key" . " => " . $elt[$key] . "==" . $inserted[$key]);
			}
		}
		foreach ($update as $key => $value) {
			$this->assertEquals($inserted[$key], $value, "update taken into account");
		}		
	
		$select_all = $model->select_all();
		$this->assertEquals($this->initial_count + 1, count($select_all), "correct size for select_all");
	
		$key = $model->primary_key();
		$select_all = $model->select_all(array($key => $latest_id));
		$this->assertEquals(1, count($select_all), "correct size for select_all (1)");
	
	}
	
	
	/**
	 * Delete latest element in the table
	 */
	public function db_delete_latest ($model, $latest_id) {
		// Test delete
		$model->delete(array($model->primary_key() => $latest_id));
		$count = count($model->select_page());
		$this->assertEquals($this->initial_count, $count, "back to initial number of elements: " . $this->initial_count);
	}
}

/* End of file CIUnitTestCase.php */
/* Location: ./application/third_party/CIUnit/libraries/CIUnitTestCase.php */