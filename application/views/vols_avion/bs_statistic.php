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
 * Vue statistiques sur les vols avion
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->load->library('DataTable');
$this->lang->load('vols_avion');

$controller = "vols_avion";

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line("gvv_vols_avion_title_statistic"), 3);

echo year_selector($controller, $year, $year_selector);

echo br(2);
?>
<div id="tabs">
<ul>
<li><a href="#tabs-1"><?php echo ($this->lang->line("gvv_vols_avion_tab_monthly"));?></a></li>
<li><a href="#tabs-2"><?php echo ($this->lang->line("gvv_vols_avion_tab_per_machine"));?></a></li>
</ul>

<?php
$title_row = array_merge(
					array( $this->lang->line("gvv_total")),
					$this->lang->line("gvv_months")
				);

$first_col = $this->lang->line("gvv_vols_avion_stats_col");

$pm_first_row = array_merge(array($this->lang->line("gvv_vue_vols_avion_short_field_type"),
		$this->lang->line("gvv_vue_vols_avion_short_field_vamacid")), $title_row);

################################################################################
$header = $this->lang->line("gvv_vols_avion_header_airplane_activity") . ' '
		. $this->lang->line("gvv_vols_avion_header_per_month") . ' '
		.  $this->lang->line("gvv_vols_avion_header_in") . ' ' . $year;
echo '<div id="tabs-1">' . heading($header, 4);;

add_first_row($per_month, $title_row);
add_first_col($per_month, $first_col);

$table = new DataTable(array(
	'title' => "",
	'values' => $per_month,
	'controller' => '',
	'class' => "datatable_style fixed_datatable table",
	'create' => "",
    'first' => 0,
	'align' => array('left', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right')
));

$table->display();
echo br() . hr();

#-------------------------------------------------------------------------------
echo heading($header, 4);

$filename = image_dir() . "avion_mois_$year.png";
if (file_exists($filename)) echo img($filename);

$bar = array(
	array('label' => "Excel", 'url' =>"$controller/csv_month/$year"),
	array('label' => "Pdf", 'url' =>"$controller/pdf_month/$year"),
	array('label' => "Génération", 'url' =>"$controller/statistic/true", "role" => 'ca'),
	);
echo br() . button_bar4($bar);
echo '</div>';

################################################################################
$header = $this->lang->line("gvv_vols_avion_header_airplane_activity") . ' '
		. $this->lang->line("gvv_vols_avion_header_per_aircraft") . ' '
		.  $this->lang->line("gvv_vols_avion_header_in") . ' ' . $year;
echo '<div id="tabs-2">' . heading($header, 4);
add_first_row($per_machine, $pm_first_row);

$table = new DataTable(array(
	'title' => "",
	'values' => $per_machine,
	'controller' => '',
	'class' => "datatable_style fixed_datatable table",
	'create' => "",
    'first' => 0,
	'align' => array('left', 'left', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right')
));

$table->display();

#-------------------------------------------------------------------------------
echo br();
echo br() . hr();
echo heading($header, 4);

$filename = image_dir() . "avion_machine_$year.png";
if (file_exists($filename)) echo img($filename);

$bar = array(
	array('label' => "Excel", 'url' =>"$controller/csv_machine/$year"),
	array('label' => "Pdf", 'url' =>"$controller/pdf_machine/$year"),
	array('label' => "Génération", 'url' =>"$controller/statistic/true", "role" => 'ca'),
	);
echo br() . button_bar4($bar);

echo '</div>';		// par machine
echo '</div>';      // tabs
echo '</div>';		// body
?>
