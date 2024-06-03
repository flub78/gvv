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
 *    http: *ie7-js.googlecode.com/svn/trunk/lib/IE7.js
 *    http://ie7-js.googlecode.com/svn/version/2.0(beta3)/IE7.js
 *    HTML header
 */
echo doctype('xhtml11'); ?>

<html>
<head>
<title><?php echo $this->config->item("program_title");?></title>
<meta name="generator" content="Bluefish 2.0.2" />
<meta name="author" content="F.Peignot" />
<meta name="copyright" content=""/>
<meta name="keywords" content=""/>
<meta name="description" content=""/>
<meta name="ROBOTS" content="NOINDEX, NOFOLLOW"/>
<meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
<meta http-equiv="content-type" content="application/xhtml+xml; charset=UTF-8"/>
<meta http-equiv="content-style-type" content="text/css"/>
<meta http-equiv="expires" content="0"/>

<!-- All javascript -->
<!--[if lt IE 7]>
<script src="http://ie7-js.googlecode.com/svn/version/2.1(beta3)/IE7.js" type="text/javascript"></script>
<![endif]-->

<?php
$CI =& get_instance();
$CI->lang->load('gvv');
$lang = $this->lang->line("gvv_language");

echo html_link(array('rel' => "icon", 'type' => "image/png", 'href' => base_url() . "favicon.png"));

// CSS
echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/datatable_jui.css'));
echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/jquery.coolfieldset.css'));

echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/jquery-ui.css'));
$href = "https://ajax.googleapis.com/ajax/libs/jqueryui/1.8/themes/" . jqueryui_theme() . "/jquery-ui.css";
echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => $href));

echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/fullcalendar.css'));

echo html_link(array('rel' => "stylesheet", 'media' => "screen", 'title' => "Design", 'type' => "text/css", 'href' => css_url('styles')));

	if (isset($new_layout)) {
		echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/menu.css'));
		echo html_link(array('rel' => "stylesheet", 'type' => "text/css", 'href' => base_url() . 'assets/css/layout.css'));
	} else {
		echo html_link(array('rel' => "stylesheet", 'media' => "screen", 'type' => "text/css", 'href' => css_url('menu')));
		echo html_link(array('rel' => "stylesheet", 'media' => "screen", 'type' => "text/css", 'href' => css_url('layout')));
	}


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


<!-- Version locale -->


<!--  All CSS -->




</head>

