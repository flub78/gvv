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
 * Vue table pour les vols planeurs
 * 
 * @packages vues
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body ui-widget-content">';

echo checkalert($this->session);

echo heading($this->lang->line("gvv_vols_planeur_title"), 3);

echo year_selector($controller, $year, $year_selector);

// -----------------------------------------------------------------------------------------
// Filtre
echo form_hidden('filter_active', $filter_active);

$tab = 3;
echo form_fieldset($this->lang->line("gvv_str_filter"), array('class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_str_filter_tooltip")));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" . $action, array('name' => 'saisie') );
echo "<table><tr><td>\n";
echo $this->lang->line("gvv_date") . ": " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker'));
echo nbs($tab); // "</td><td>";
echo $this->lang->line("gvv_until") . ": ". input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker'));
echo nbs($tab); // "</td><td>";
echo "</td></tr><tr><td>";
echo $this->lang->line("gvv_pilot") . ": ". dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "");
echo nbs($tab); // "</td><td>";
echo $this->lang->line("gvv_machine") .": ". dropdown_field('filter_machine', $filter_machine, $machine_selector, "");
echo nbs($tab); // "</td><td>";
echo $this->lang->line("gvv_site") . ": ". dropdown_field('filter_aero', $filter_aero, $aero_selector, "");

echo "</td></tr><tr><td>";
echo $this->lang->line("gvv_age") . ": " . enumerate_radio_fields($this->lang->line("gvv_age_select"), 'filter_25', $filter_25);

echo nbs($tab * 4);
echo $this->lang->line("gvv_dual") . " " . form_checkbox(array ('name' => 'filter_dc',
                'value' => 1,
                'checked' => (0 != $filter_dc)));
echo nbs ($tab * 4); // "</td><td>";
echo $this->lang->line("gvv_gliders") . ": " . enumerate_radio_fields($this->lang->line("gvv_owner_select"), 'filter_prive', $filter_prive);
echo "</td></tr><tr><td>";

$categories = array_merge(array('-1' => $this->lang->line("gvv_toutes")), $this->config->item('categories_vol_planeur'));
echo $this->lang->line("gvv_categories") . ": " . enumerate_radio_fields($categories, 'filter_vi', $filter_vi);

echo "</td></tr><tr><td>";

echo $this->lang->line("gvv_launch") . ":";

echo enumerate_radio_fields($this->lang->line("gvv_launch_select"), 'filter_lanc', $filter_lanc);
echo "</td></tr><tr><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
echo nbs();
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));
echo "</td></tr></table>\n";
echo form_close();
echo "</div>";
echo form_fieldset_close();

// -----------------------------------------------------------------------------------------
// Totaux
echo form_fieldset($this->lang->line("gvv_vols_planeur_fieldset_totals"), array('class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_vols_planeur_fieldset_totals_tooltip")));
echo "<div>";
echo "<table>\n"; // <tr><td align=\"right\">\n";

$launch = $this->lang->line("gvv_launch_type");
$towing = $launch[3];
$winch = $launch[1];
$auto = $launch[2];
$ext = $launch[4];
echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_flight_number") . " = " 
		. "</td><td> $count </td><td> $towing=$rems, $winch=$treuils, $auto=$autonomes, $ext=$exts </td></tr>\n";
echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_hours") . " = "   . "</td><td>" . minute_to_time($total) . " </td></tr>\n";
echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_junior") . " = "  . "</td><td> " . minute_to_time($m25ans) . "</td></tr>\n";
if ($by_pilote) {
    echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_dual") . " = " . "</td><td> " . minute_to_time($dc) . "</td></tr>\n";
    echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_captain") . " = " . "</td><td> " . minute_to_time($cdb) . "</td></tr>\n";
    echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_instruction") . " = " . "</td><td> " . minute_to_time($inst) . "</td></tr>\n";
} else {
    echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_dual") . " = " . "</td><td> " . minute_to_time($dc) . "</td></tr>\n";
}
echo "<tr><td align=\"right\"> " . $this->lang->line("gvv_vols_planeur_label_total_distance") . " = " . "</td><td> " . $kms . "</td></tr>\n";

echo "</table>\n";
echo "</div>";
echo form_fieldset_close();

// -----------------------------------------------------------------------------------------
// Elements table

$ajax = $this->config->item('ajax');
if ($ajax) {
	if ($this->dx_auth->is_role('planchiste')) {
		$classes = "datatable_style datatable_server";
	} else {
		$classes = "datatable_style datatable_server_ro";
	}
} else {
	$classes = "sql_table fixed_datatable";
}

$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete'),
    'count' => $count,
	'first' => $premier,
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => $classes,
    'datatable' => 'server_side');

if ($ajax) {
	echo $this->gvvmetadata->empty_table("vue_vols_planeur", $attrs);	
} else {
	echo $this->gvvmetadata->table("vue_vols_planeur", $attrs, "");
}

// -----------------------------------------------------------------------------------------
echo p($this->lang->line("gvv_vols_planeur_tip_unit"));

$bar = array(
    array('label' => "Excel", 'url' =>"$controller/csv/$year"),
    array('label' => "Pdf", 'url' =>"$controller/pdf/$year"),
    );
echo br() . button_bar4($bar);

echo '</div>';

?>
<script type="text/javascript" src="<?php echo js_url('french_dates'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('table_vols_planeur'); ?>"></script>

