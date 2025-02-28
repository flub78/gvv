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
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->load->library('ButtonNew');
$this->load->library('ButtonView');
$this->lang->load('tickets');

$controller = 'tickets';

echo '<div id="body" class="body ui-widget-content">';

echo heading("gvv_tickets_title_balance", 3);
echo form_hidden('controller_url', controller_url($controller) . "/solde", '"id"="controller_url"');

$attrs = array(
	'fields' => array('pilote', 'nom', 'solde'),
	'controller' => $controller,
	'mode' => "ro",
	'class' => "datatable"
);

echo $this->gvvmetadata->table("vue_solde_tickets", $attrs, "");

$bar = array(
	array('label' => "Excel", 'url' => "$controller/solde/csv"),
	array('label' => "Pdf", 'url' => "$controller/solde/pdf"),
);
echo button_bar4($bar);

echo '</div>';
