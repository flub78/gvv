<?php

/**
 * Experimentation on CLI
 * @author Frederic
 *
 */
class Tools extends CI_Controller {

	public function index($to = 'World') {
		echo "Hello from tools {$to}!" . PHP_EOL;
	}

	public function bye($to = 'World') {
		echo "Goodbye from tools {$to}!" . PHP_EOL;
	}
}
