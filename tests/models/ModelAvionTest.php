<?php

/**
 * @group Model
 */

class ModelAvionTest extends CIUnit_TestCase
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
		$this->CI->load->model('avions_model');
		$this->_pcm = $this->CI->avions_model;

		// initial count
		$this->initial_count = count($this->_pcm->select_page());
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

		self::$latest_id = 'F-B' . $this->initial_count;
		
		$elt = array (
				'macconstruc' => 'Mon zavion',
				'macmodele' => 'Stamp',
				'macimmat' => self::$latest_id,
				'macnbhdv' => '0.00',
				'macplaces' => '2',
				'macrem' => false,
				'maprive' => false,
				'club' => false,
				'actif' => '1',
				'comment' => '',
				'maprix' => 'Boisson',
				'maprixdc' => 'Boisson',
				'horametre_en_minutes' => false,
				'fabrication' => '',
		);
		
		$update = array('fabrication' => 1984);
		
		$this->db_create($this->_pcm, self::$latest_id, "machinesa", $elt, $update);
	}

	/**
	 * Unit test of model common methods
	 */
	public function testCommon()
	{
		$this->db_common($this->_pcm, self::$latest_id, "machinesa");		
	}
	
	
	/**
	 * test delete function
	 */
	public function testDelete()
	{
		$this->db_delete_latest($this->_pcm, self::$latest_id);	
	}
	
	
	// ------------------------------------------------------------------------
	
}
