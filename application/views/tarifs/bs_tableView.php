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
 * Vue (table) pour les tarifs et les produits
 * 
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('tarifs');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line("gvv_tarifs_title_list"), 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

echo form_fieldset($this->lang->line("gvv_str_filter"), array('class' => 'coolfieldset filtre mb-3 mt-3',
    'title' => $this->lang->line("gvv_str_filter_tooltip")));
echo "<div>";

echo form_open(controller_url($controller) . "/filterValidation/", array('name' => 'saisie') );

echo $this->lang->line("gvv_tarifs_label_todate") . ": " . input_field('filter_tarif_date', $filter_tarif_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker'));
echo br();
echo $this->lang->line("gvv_tarifs_label_public") . ": "
. enumerate_radio_fields($this->lang->line("gvv_tarifs_filter_public_select"), 'filter_tarif_public', $filter_tarif_public);

echo br(2);
echo "<table><tr><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
echo nbs();
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_display")));
echo "</td></tr></table>\n";

echo form_close();
echo "</div>";
echo form_fieldset_close();

$tarifs = "Tarifs";
if ($filter_tarif_date) $tarifs .= " au $filter_tarif_date";

$attrs = array(
	'controller' => $controller,
    'actions' => array ('edit', 'delete', 'clone_elt'),
	'title' => $tarifs,
    'fields' => array('reference', 'description', 'date', 'date_fin', 'prix', 'nom_compte', 'public'), 
//    'count' => $count,
	'first' => $premier,
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped");

echo $this->gvvmetadata->table("vue_tarifs", $attrs, "");

echo p($this->lang->line("gvv_tarifs_clone_tooltip"));
echo br();
echo p($this->lang->line("gvv_tarifs_warning"));

echo '</div>';

?>
