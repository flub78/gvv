<!-- VIEW: application/views/comptes/bs_bilanView.php -->
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
 * Bilan
 * 
 */
$this->load->library('table');
$this->load->library('DataTable');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '<div id="body" class="body container-fluid">';

$title = $this->lang->line('gvv_comptes_title_bilan');
if ($section) {
	$title .= " section " . $section['nom'];
}

echo heading($title, 2, "");
echo year_selector($controller, $year, $year_selector);
echo br(2);
echo heading("date: $date", 3, "");
echo br();

$table = new DataTable(array(
		'title' => "",
		'values' => $bilan_table,
		'controller' => $controller,
		'class' => "sql_table fixed_datatable table",
		'create' => '',
		'count' => '',
		'first' => '',
		'align' => array('left', 'right', 'right', 'right', 'right', 'center', 'left', 'right', 'right')
));
$table->display();

$bar = array(
	array('label' => "Excel", 'url' =>"comptes/export_bilan/csv", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => "comptes/export_bilan/pdf", 'role' => 'ca'),
	array('label' => $this->lang->line('comptes_button_cloture'), 'url' => "comptes/cloture", 'role' => 'tresorier'),	
	);
echo button_bar4($bar);

echo '</div>';
?>
