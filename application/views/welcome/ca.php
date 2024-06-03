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
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('welcome');

$attributes = array(
                    'class' => 'boldlist',
                    'id'    => 'mylist'
                    );

echo '<div id="body" class="body ui-widget-content">';

echo heading($this->lang->line("welcome_admin_title"), 4);
$list = array(
	anchor (controller_url('terrains/page'), $this->lang->line("welcome_airfield_title"), array("class" => "jbutton")),
	anchor (controller_url('historique'), $this->lang->line("welcome_history_title"), array("class" => "jbutton")),
	);
echo ul($list, $attributes);

// echo year_selector($controller, $year, $year_selector);

echo heading($this->lang->line("welcome_reports_title"), 4);
$list = array(
	// anchor (controller_url('rapports/annuel'), "Rapport d'activité annuel", array("class" => "jbutton")),
	anchor (controller_url('rapports/financier'), $this->lang->line("welcome_financial_title"), array("class" => "jbutton")),
	anchor (controller_url('rapports/comptes'), $this->lang->line("welcome_accounts_title"), array("class" => "jbutton"))
	);
if ($this->config->item('gestion_avion'))
	$list[] = anchor (controller_url('vols_avion/pdf'), $this->lang->line("welcome_airplane_flightlog"), array("class" => "jbutton"));
if ($this->config->item('gestion_planeur'))
	$list[] = anchor (controller_url('vols_planeur/pdf'), $this->lang->line("welcome_glider_flightlog"), array("class" => "jbutton"));

echo year_selector($controller, $year, $year_selector);
echo ul($list, $attributes);

echo heading($this->lang->line("welcome_dates"), 4);
$list = array(
	anchor (controller_url('event/page'), $this->lang->line("welcome_certificates"), array("class" => "jbutton")),
            );
echo ul($list, $attributes);

?>
</div>
