<!-- VIEW: application/views/welcome/bs_ca.php -->
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
 * Page d'administration
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('welcome');

$attributes = array(
	'class' => 'boldlist',
	'id'    => 'mylist'
);

echo '<div id="body" class="body container-fluid">';


// echo year_selector($controller, $year, $year_selector);

echo heading("welcome_reports_title", 4);
?>
<?php
$list = array(
	// anchor (controller_url('rapports/annuel'), "Rapport d'activité annuel", array("class" => "jbutton")),
	anchor(controller_url('rapports/financier'), $this->lang->line("welcome_financial_title"), array("class" => "jbutton")),
	anchor(controller_url('rapports/comptes'), $this->lang->line("welcome_accounts_title"), array("class" => "jbutton"))
);
if ($this->config->item('gestion_avion'))
	$list[] = anchor(controller_url('vols_avion/pdf'), $this->lang->line("welcome_airplane_flightlog"), array("class" => "jbutton"));
if ($this->config->item('gestion_planeur'))
	$list[] = anchor(controller_url('vols_planeur/pdf'), $this->lang->line("welcome_glider_flightlog"), array("class" => "jbutton"));

echo year_selector($controller, $year, $year_selector);
echo ul($list, $attributes);



?>
</div>