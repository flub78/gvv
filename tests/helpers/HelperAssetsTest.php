<?php

/**
 * @group Helper
 */

class HelperAssetsTest extends CIUnit_TestCase
{
	public function setUp()
	{
		$this->CI->load->helper('assets');
	}
	
	/**
	 * Just a few assertions
	 */
	public function testFnctions()
	{	
		$theme = theme();
		$this->tst_function_args("theme", array(), base_url() . "themes/binary-news");
		$css = $theme . "/css/menu.css";
		$this->tst_function_args("css_url", array("menu"), $css);
		$this->tst_function_args("js_url", array("jquery"));
		$this->tst_function_args("image_dir");
		$this->tst_function_args("img_url", array("picture.png"));
		$this->tst_function_args("asset_url", array("picture.png"));
		$this->tst_function_args("controller_url", array("avion"));
		$this->tst_function_args("jqueryui_theme");
	}
	
}
