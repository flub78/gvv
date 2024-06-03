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
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line("gvv_comptes_title_cloture") . " $year", 2, "");

echo form_open(controller_url($controller) . "/cloture/" . VALIDATION, array (
    'name' => 'saisie'
));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

echo br();
if ($error) {
    echo p($error, 'class="error"') . br();
}

echo $this->lang->line("comptes_cloture_date_fin") . " = $date_fin" . br(); 
echo $this->lang->line("comptes_cloture_date_gel") . " = $date_gel" . br();
 
echo br();
echo heading($this->lang->line("comptes_cloture_title_result"), 4, "");
echo dropdown_field('capital', $capital, $capital_selector, "id='selector' ");

echo heading($this->lang->line("comptes_cloture_title_previous"), 4, "");
$attrs = array(
		'align' => array('left', 'left', 'right', 'right', 'right', 'right'),
        'class' => ' table table-striped'
		);

echo br();
echo table_from_array($a_integrer, $attrs);

echo br();
echo heading($this->lang->line("comptes_cloture_title_charges_a_integrer"), 4, "");
echo table_from_array($charges, $attrs);

echo br();
echo heading($this->lang->line("comptes_cloture_title_produits_a_integrer"), 4, "");
echo table_from_array($produits, $attrs);

echo br();
if ($action == MODIFICATION && !$error) {
	echo validation_button($action);
}
echo form_close();

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>