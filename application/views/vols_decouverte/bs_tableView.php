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
 * Vue table pour les terrains
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_decouverte');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_vols_decouverte_title", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete', 'print_vd', 'action'),
    'fields' => array('id', 'validite', 'product', 'beneficiaire', 'date_vol', 'urgence', 'cancelled', 'paiement', 'participation'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_vols_decouverte", $attrs, "");


$bar = array(
    array('label' => "Excel", 'url' => "$controller/export/csv", 'role' => 'ca'),
    array('label' => "Pdf", 'url' => "$controller/export/pdf", 'role' => 'ca'),
);
// echo button_bar4($bar);

echo '</div>';
