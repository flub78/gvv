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
 * Formulaire de resultat
 *
 * @packages vues
 */

$this->load->library('DataTable');
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('comptes');

echo '<div id="body" class="body ui-widget-content">';

echo heading( $this->lang->line("gvv_comptes_title_resultat"), 2, "");

$url = controller_url($controller);
echo '<input type="hidden" name="controller_url" value="' . $url . '" />';

echo year_selector($controller, $year, $year_selector);

echo br(2);
	
$table = new DataTable(array(
	'title' => "",
	'values' => $resultat_table,
	'controller' => $controller,
	'class' => "sql_table fixed_datatable",
	'create' => '',
	'count' => '',
	'first' => '',
	'align' => array('left', 'left', 'right', 'right',
        'center',
		'right', 'left', 'right' , 'right')
));

$table->display();

$bar = array(
	array('label' => "Excel", 'url' =>"comptes/export_resultat/csv", 'role' => 'ca'),
	array('label' => "Pdf", 'url' => "comptes/export_resultat/pdf", 'role' => 'ca'),
	array('label' =>  $this->lang->line("comptes_button_cloture"), 'url' => "comptes/cloture", 'role' => 'tresorier'),
	);
echo button_bar4($bar);

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>