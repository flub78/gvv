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
 * Vue table pour les achats
 * 
 * @package vues
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('rapports');

echo '<div id="body" class="body ui-widget-content">';

echo heading("gvv_rapports_title", 3);

echo year_selector($controller, $year, $year_selector);
echo br(2);

$list = array(
	$this->lang->line("gvv_rapports_label_assoc") . " = $association",
	$this->lang->line("gvv_rapports_label_code") . " = $code",
);
echo ul($list);

$list = array(
	$this->lang->line("gvv_rapports_label_heures_rem") . " = $total_towing",
);
echo ul($list);

echo table_from_array($activity, array(
	'fields' => $this->lang->line("gvv_rapports_headers"),
	'align' => array('left', 'right', 'right', 'right', 'right', 'right', 'right', 'right', 'right'),
	'class' => 'datatable_style'
));

$list = array(
	$this->lang->line("gvv_rapports_label_heures_rem") . " = $total_towing",
	$this->lang->line("gvv_rapports_label_heures_totales") . " = $total_glider",
);
echo ul($list);

echo table_from_array(
	$machine_activity,
	array(
		'fields' => $this->lang->line("gvv_rapports_machines"),
		'align' => array('left', 'right', 'left', 'right', 'right'),
		'class' => 'datatable_style '
	)
);

$bar = array(
	array('label' => $this->lang->line("gvv_button_print"), 'url' => "$controller/pdf_ffvv", 'role' => 'admin'),
);
if (ENVIRONMENT == 'development') echo button_bar4($bar);

echo '</div>';
