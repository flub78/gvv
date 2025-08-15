<?php

/**
 * @group Model
 */

class ModelComptesTest extends CIUnit_TestCase
{
	
//  List of tables that are initialized with fixture
// 	protected $tables = array(
// 		'machinesa' => 'machinesa'
// 	);
	
	private $_pcm;
	
	protected $initial_count;
	
	// For some reasons it seems that affectation of member variables done from inside a test method
	// are not seen by other methods. A little bit like if several instances of the test object
	// where used to run the test methods. So I use a class variable even if it not too clean.
	static $latest_id;
	
	/**
	 * Constructor, called once for the tests
	 * @param string $name
	 * @param array $data
	 * @param string $dataName
	 */
	public function __construct($name = NULL, array $data = array(), $dataName = '')
	{
		parent::__construct($name, $data, $dataName);
		$this->CI->load->model('comptes_model');
		$this->CI->load->helper('validation');
		
		$this->_pcm = $this->CI->comptes_model;

		// initial count
		$date = date("d/m/Y");
		$this->initial_count = count($this->_pcm->select_page(array(), $date));
	}
	
	/**
	 * Setup is called once before each test
	 * (non-PHPdoc)
	 * @see CIUnit_TestCase::setUp()
	 */
	public function setUp()
	{
		parent::setUp();		
	}
	
	/**
	 * tearDown is called once after each test
	 * (non-PHPdoc)
	 * @see CIUnit_TestCase::tearDown()
	 */
	public function tearDown()
	{
		parent::tearDown();
	}
	
	// ------------------------------------------------------------------------
	
	/**
	 * Test creation
	 */
	public function testCreate()
	{

		return;		
		$elt = array(
			//	'id' => 200,
				'nom' => 'jdupont',
				'pilote' => '',
				'desc' => 'Jean Dupont',
				'codec' => '411',
				'actif' => '1',
				'debit' => '0.00',
				'credit' => '0.00',
				'saisie_par' => 'fpeignot',
				'club' => false,
		);
				
		$update = array('desc' => 'Jean-Marc Dupont');
		
		$model = $this->_pcm;
			
		// test create
		self::$latest_id = $model->create($elt);
		
		$this->assertEquals($this->initial_count + 1, count($model->select_page(array(), date("d/m/Y"))), "one additional element");
	
		// update
		foreach ($update as $key => $value) {
			$elt[$key] = $value;
		}
		$model->update($model->primary_key(), $elt, self::$latest_id);
	
		// test machine list
		$list = $model->select_all();
		$this->assertEquals($this->initial_count + 1, count($list), "correct size for machine_list");
		$this->assertTrue(in_array(self::$latest_id, $list), "last inserted element " . self::$latest_id . " found in list");
		var_dump($list);
		
		$list_of = $model->list_of(array(), 'id');
		$this->assertEquals($this->initial_count + 1, count($list_of), "correct size for list_of");
	
		$list1 = $model->machine_list(array(), false);
		$list2 = $model->select_columns('macimmat, horametre_en_minutes');
		$this->assertEquals(count($list1), count($list2), "correct size for machine_list 2");
	
		$inserted = $model->get_by_id($model->primary_key(), self::$latest_id);
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
		$select_all = $model->select_all(array($key => self::$latest_id));
		$this->assertEquals(1, count($select_all), "correct size for select_all (1)");
	
	}
	
	/**
	 * Unit test of model common methods
	 */
	public function testCommon()
	{
		return;
		$model = $this->_pcm;
		$latest_id = self::$latest_id;
		$table = "comptes";

		// test table
		$this->assertEquals($table, $model->table(), "table name");
	
		// first object
		$key = $model->primary_key();
		$first = $model->get_first();
		$current = $model->get_first(array($key => $latest_id));
		if ($this->initial_count == 0) {
			$this->assertEquals($first[$key], $current[$key]);
		} else {
			var_dump($current);
			
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
	 * test delete function
	 */
	public function testDelete()
	{
		return;
		$model = $this->_pcm; 
		$latest_id = self::$latest_id;
		
		// Test delete
		$model->delete(array($model->primary_key() => $latest_id));
		$count = count($model->select_page());
		$this->assertEquals($this->initial_count, $count, "back to initial number of elements: " . $this->initial_count);
	}
	
	
	// ------------------------------------------------------------------------
	
}
