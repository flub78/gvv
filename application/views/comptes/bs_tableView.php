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
 * Vue balance des comptes
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '<div id="body" class="body container-fluid">';
echo checkalert($this->session);
$title = $this->lang->line($title_key);
if ($codec) {
	$title .= nbs() . $this->lang->line('comptes_label_class') . nbs() . $codec;
}
if ($codec2) {
	$title .= nbs() . $this->lang->line('comptes_label_to') . nbs() . $codec2;
}
echo heading($title, 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
$values = $this->lang->line("comptes_balance_general");
echo $this->lang->line("comptes_label_date") . ': <input type="text" name="balance_date" id="balance_date" value="'. $balance_date .'" size="15" title="JJ/MM/AAAA" class="datepicker" onchange=new_balance_date(); />';
echo br(2);

// --------------------------------------------------------------------------------------------------
// Filtre
echo form_hidden('filter_active', $filter_active);

$tab = 3;
echo form_fieldset($this->lang->line("gvv_str_filter"), array('class' => 'coolfieldset filtre mb-3 mt-3',
    'title' => $this->lang->line("gvv_str_filter_tooltip")));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" , array('name' => 'saisie') );

echo "<table><tr><td>\n";
echo $this->lang->line("comptes_label_balance") . " . " . radio_field('general', $general, $values, 'onchange=balance_general();');
echo "</td></tr><tr><td>";
echo $this->lang->line("comptes_label_soldes") . ": " . enumerate_radio_fields($this->lang->line("comptes_filter_active_select"), 'filter_solde', $filter_solde);
echo "</td></tr><tr><td>";

echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
echo nbs();
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));

echo "</td></tr></table>\n";
echo form_close();
echo "</div>";
echo form_fieldset_close();

// --------------------------------------------------------------------------------------------------
// Data

$solde_deb = '';
$solde_cred = '';
$total_debit = $total['debit'];
$total_credit = $total['credit'];
if ($total_debit > $total_credit) {
    $solde_deb = euro($total_debit - $total_credit);
} else {
    $solde_cred = euro($total_credit - $total_debit);
}

$footer = [];


if ($detail) {
	$footer[] = ['', $this->lang->line("comptes_label_totals"), euro($total['debit']), euro($total['credit']), euro($total['solde_debit']), euro($total['solde_credit']), '', ''];
	$footer[] = ['', $this->lang->line("comptes_label_totals_balance"), euro($total['solde_debit']), euro($total['solde_credit']), '', ''];
}
$footer[] = ['', $this->lang->line("comptes_label_balance"), $solde_deb, $solde_cred, '', ''];

if ($general) {
	$fields = array ( 'codec', 'nom', 'solde_debit', 'solde_credit' );
	$res = array();
	foreach ($select_result as $row) {
		//var_dump($row);
		$row['nom'] = anchor(base_url() . 'index.php' . '/comptes/page/' . $row['codec'], $row['nom']);
		$res[] = $row;
	}
	$select_result = $res;
} else {
	$fields = array ( 'codec', 'id', 'solde_debit', 'solde_credit' );
}
$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'mode' => ($has_modification_rights && ! $general) ? "rw" : "ro",
    'footer' => $footer,
	'fields' => $fields,
    'class' => "datatable  table table-striped");
echo $this->gvvmetadata->table("vue_comptes", $attrs, $select_result, "");

$csv_url = "$controller/balance_csv";
$pdf_url = "$controller/balance_pdf";
if (isset($codec)) {
	$csv_url .= "/$codec";
	$pdf_url .= "/$codec";
	if (isset($codec2)) {
		$csv_url .= "/$codec2";
		$pdf_url .= "/$codec2";
	}
}
$bar = array(
	array('label' => "Excel", 'url' => $csv_url, 'role' => 'ca'),
	array('label' => "Pdf", 'url' => $pdf_url, 'role' => 'ca'),
	);
echo button_bar4($bar);

echo br(2);
echo p($this->lang->line("comptes_warning"));
//echo "? " . $this->session->userdata('return_url');
echo '</div>';

?>

<script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
