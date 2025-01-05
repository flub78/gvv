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
 * @package vues
 * 
 * Vue table des tickets (c'est la liste des achats / consomation par pilote).
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('tickets');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_tickets_title_list", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Filtre
echo form_hidden('filter_active', $filter_active);
echo form_fieldset($this->lang->line("gvv_str_filter"), array(
    'class' => 'coolfieldset filtre',
    'title' => $this->lang->line("gvv_str_filter_tooltip")
));
echo "<div>";
echo form_open(controller_url($controller) . "/filterValidation/" . $action, array('name' => 'saisie'));
echo "<table><tr><td>\n";
echo $this->lang->line("gvv_date") . ": " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'));
echo "</td><td>";
echo $this->lang->line("gvv_until") . ": " . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker'));
echo "</td><td>";
echo $this->lang->line("gvv_pilot") . ": " . dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "");
echo "</td><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => $this->lang->line("gvv_str_select")));
echo "</td><td>";
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' =>  $this->lang->line("gvv_str_display")));
echo "</td></tr></table>\n";
echo form_close();
echo "</div>";
echo form_fieldset_close();

echo br();
if ($nom)
    echo $this->lang->line("gvv_tickets_label_account") . " =" . nbs() . $nom . br();

if (isset($solde_pilote))
    echo $this->lang->line("gvv_tickets_label_balance") . " =" . nbs() . $solde_pilote . nbs() . "remorqués" . br(2);

// Elements table
$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('date', 'pilote', 'quantite', 'nom', 'description', 'vol'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_tickets", $attrs, "");

$bar = array(
    array('label' => "Excel", 'url' => "$controller/export/csv/$filter_pilote", 'role' => 'ca'),
    array('label' => "Pdf", 'url' => "$controller/export/pdf/$filter_pilote", 'role' => 'ca'),
);
echo button_bar4($bar);

if ($has_modification_rights) {
    echo br();
    echo p($this->lang->line("gvv_tickets_warning"));
}
echo '</div>';
