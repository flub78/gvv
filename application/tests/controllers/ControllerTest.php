<?php

use PHPUnit\Framework\TestCase;

class ControllerTest extends TestCase
{
	private $CI;
	
	public function setUp(): void
	{
		$this->CI = get_instance();
        // Load common helpers required by controllers
        $this->CI->load->helper('url');
	}
	
	/**
	 * This test will create a controller subfolder with a stub file.
	 * It skips the test if the environment doesn't grant enough permissions
	 * to create folder and file.
	 * @group ignore
	 */
	public function testLoadControllerFromSubfolder()
	{
		$folder = APPPATH.'controllers/testsubfolder';
		
		// check if we can run the test
		if (!is_dir($folder)) {
			// create subfolder
			$success = mkdir(APPPATH.'controllers/testsubfolder');
			if (!$success)
				$this->markTestSkipped('Cannot create subfolder');
		}
		if (!is_writable($folder))
			$this->markTestSkipped('Cannot write in subfolder');
		
		// create a test controller
		if (!is_writable($folder.'/stub.php')) {
			// create stub file
			$success = file_put_contents($folder.'/stub.php',
									'<?php class Stub extends CI_Controller { public function index(){} } ?>');
			if (!$success)
				$this->markTestSkipped('Cannot create test controller file');
		}
		
		// Stub is there, let's autoload it
		$this->assertTrue(class_exists('Stub'), 'Stub is loadable');
		$this->CI = new Stub();
		$this->CI->index();
		
		// remove stub
		unlink($folder.'/stub.php');
		rmdir($folder);
	}
	
	/**
	 * This test will check if our bootstrap autoload won't make an
	 * inexistent class suddenly be loadable
	 * @group ignore
	 */
	public function testLoadInexistentControllerFromSubfolder()
	{
		$this->assertFalse(class_exists('InexistentStub'), 'Inexistent class is not loadable');
	}
}

?>