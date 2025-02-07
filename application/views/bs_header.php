<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
 *
 *    This program is free software: you can redistribute it and/or modify
 *    it under the terms of the GNU General Public License as published by
 *    the Free Software Foundation, either version 3 of the License, or
 *    (at your option) any later version.
 *
 *    This program is distributed in the hope that it will be useful,
 *    but WITHOUT ANY WARRANTY; without even the implied warranty of
 *    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *    GNU General Public License for more details.
 *
 *    You should have received a copy of the GNU General Public License
 *    along with this program.  If not, see <http://www.gnu.org/licenses/>.
 *    
 */
?>
<!DOCTYPE html>
<html>

<head>
	<title><?php echo $this->config->item("program_title"); ?></title>
	<meta name="generator" content="Bluefish 2.0.2" />
	<meta name="author" content="F.Peignot" />
	<meta name="copyright" content="" />
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta name="ROBOTS" content="NOINDEX, NOFOLLOW" />
	<meta http-equiv="content-type" content="text/html; charset=UTF-8" />
	<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8" />
	<meta http-equiv="content-style-type" content="text/css" />
	<meta http-equiv="expires" content="0" />
	<meta name="viewport" content="width=device-width, initial-scale=1.0">

	<!-- All javascript -->
	<!-- Bootstrap 5 -->
	<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
	<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

	<!-- Font awsome -->
	<!-- script src="https://kit.fontawesome.com/408316024a.js" crossorigin="anonymous"></script -->

	<?php
	$CI = &get_instance();
	$CI->lang->load('gvv');
	$lang = $this->lang->line("gvv_language");

	echo html_script(array('type' => "text/javascript", 'src' => js_url('408316024a')));

	echo html_link(array('rel' => "icon", 'type' => "image/png", 'href' => base_url() . "favicon.png"));

	// CSS
	echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/datatable_jui.css'));
	echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/jquery-ui.css'));

	echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/fullcalendar.css'));

	echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/gvv.css'));

	/// echo html_link(array('rel' => "stylesheet", 'media' => "screen", 'title' => "Design", 'type' => "text/css", 'href' => css_url('styles')));

	// Javascript
	echo html_script(array('type' => "text/javascript", 'src' => js_url('gvv')));
	echo html_script(array('type' => "text/javascript", 'src' => js_url('moment.min')));
	if (ENVIRONMENT == "production") {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.min')));
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery-ui.min')));
	} else {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery')));
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery-ui')));
	}

	$lang = $CI->config->item('language');
	if ($lang == "french") {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.ui.datepicker-fr')));
	} elseif ($lang == "dutch") {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.ui.datepicker-nl')));
	}
	echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.coolfieldset')));

	echo html_script(array('type' => "text/javascript", 'src' => js_url($lang . "_lang")));

	if (ENVIRONMENT == "production") {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.dataTables.min')));
	} else {
		echo html_script(array('type' => "text/javascript", 'src' => js_url('jquery.dataTables')));
	}
	?>

	<script type="text/javascript" src="<?= base_url() ?>assets/javascript/multilevel.js"></script>
	<link rel="stylesheet" type="text/css" href="<?= base_url() ?>assets/css/multilevel.css">
	</link>
	<link rel="stylesheet" type="text/css" href="<?= base_url() ?>assets/css/bs_styles.css">
	</link>

	<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
	<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>


	<!-- Version locale -->


</head>