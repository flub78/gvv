<?php

/**
 * 
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

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('welcome');

echo '<div id="body" class="body ui-widget-content">';

echo heading("welcome_treasurer_title", 3);

$this->load->helper('html');
$attributes = array(
    'class' => 'boldlist',
    'id'    => 'mylist'
);

echo heading("welcome_admin_title", 4);
$list = array(
    anchor(controller_url('admin/backup'), $this->lang->line("welcome_database_backup_title"), array("class" => "jbutton")),
    anchor(controller_url('comptes/cloture'), $this->lang->line("welcome_database_endofyear_title"), array("class" => "jbutton"))
);
echo ul($list, $attributes);

echo heading("welcome_special_entries_title", 4);
$list = array(
    anchor(controller_url('facturation/config'), $this->lang->line("welcome_billing_config_title"), array("class" => "jbutton"))
);
echo ul($list, $attributes);

$list = array(
    anchor(controller_url('plan_comptable/page'), $this->lang->line("welcome_chart_of_account_title"), array("class" => "jbutton")),
    anchor(controller_url('tarifs/page'), $this->lang->line("welcome_price_list_title"), array("class" => "jbutton")),
);
if ($this->config->item('gestion_tickets'))
    $list[] = anchor(controller_url('types_ticket/page'), $this->lang->line("welcome_ticket_types_title"), array("class" => "jbutton"));
echo ul($list, $attributes);


echo heading("welcome_special_entries_title", 4);
$list = array(
    anchor(controller_url('compta/create'), $this->lang->line("welcome_global_entries_title"), array("class" => "jbutton")),
    // anchor (controller_url('compta/page'), "Edition manuelle des écritures."), 
);
echo ul($list, $attributes);
echo p($this->lang->line("welcome_special_entries_warning"));

/*
 * Le controle des soldes vérifie que les soldes enregistrés dans les comptes
 * sont cohérents comparés aux cumuls des écritures.
 * 
 * Cependant depuis que les soldes des comptes 600 et 700 ne prennent plus en compte
 * que les opérations de l'année, le controle rélève systématiquement des erreurs.
 * 
 * De plus c'est devenu inutile depuis que les soldes des comptes ne sont plus utilisés.
 * 
echo heading("Administration", 4);
$list = array(
    anchor (controller_url('comptes/check'), "Control des soldes"),
);
echo ul($list, $attributes);
*/

echo "</div>";
