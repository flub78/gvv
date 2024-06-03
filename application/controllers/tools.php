<?php
/**
 * Experimentation on CLI
 * @author frede
 *
 */
class Tools extends CI_Controller {

	public function index($to = 'World')
	{
		echo "Hello {$to}!".PHP_EOL;
	}
}
?>