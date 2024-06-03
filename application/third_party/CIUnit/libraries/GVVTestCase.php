<?php

/*
* fooStack, CIUnit for CodeIgniter
* Copyright (c) 2008-2009 Clemens Gruenberger
* Released under the MIT license, see:
* http://www.opensource.org/licenses/mit-license.php
*/
include_once ("CIUnit_TestCase.php");
/**
 * Extending the default phpUnit Framework_TestCase Class
 * providing eg. fixtures, custom assertions, utilities etc.
 */
class GVVTestCase extends CIUnit_TestCase
{
	/**
	 * Simple test for function that return a string
	 * @param unknown $name
	 * @param string $arg
	 */
	protected function tst_function ($name, $arg = "") {
	
		if ($arg) {
			$str = $name($arg);
		} else {
			$str = $name();
		}
	
		echo "$name() : $str\n";
	
		$this->assertNotEquals($str, "", "$name not empty");
	
	}
	
}

/* End of file GVVTestCase.php */
