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
 * Vue de la page d'administration
 * @package vues
 * @filesource admin.php
 */
// ----------------------------------------------------------------------------------------

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('admin');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_admin_title", 3);

$this->load->helper('html');
$attributes = array(
	'class' => 'boldlist',
	'id'    => 'mylist'
);

echo heading("gvv_admin_title_config", 4);

$list = array(
	anchor(controller_url('config'), $this->lang->line("gvv_admin_menu_config"), array("class" => "jbutton")),
	anchor(controller_url('events_types'), $this->lang->line("gvv_admin_menu_certificates"), array("class" => "jbutton")),
);
echo ul($list, $attributes);

echo heading("gvv_admin_title_admin", 4);
$list = array(
	anchor(controller_url('admin/backup'), $this->lang->line("gvv_admin_menu_backup"), array("class" => "jbutton")),
	anchor(controller_url('admin/restore'), $this->lang->line("gvv_admin_menu_restore"), array("class" => "jbutton")),
	anchor(controller_url('migration'), $this->lang->line("gvv_admin_menu_migrate"), array("class" => "jbutton"))
);
if (ENVIRONMENT == 'development') {
	$list[] = anchor(controller_url('admin/backup/structure'), $this->lang->line("gvv_admin_menu_structure"), array("class" => "jbutton"));
	$list[] = anchor(controller_url('admin/backup/defaut'), $this->lang->line("gvv_admin_menu_default"), array("class" => "jbutton"));
	$list[] = anchor(controller_url('welcome/nyi'), $this->lang->line("gvv_admin_menu_lock"), array("class" => "jbutton"));
}
echo ul($list, $attributes);

echo heading("gvv_admin_title_rights", 4);
$list = array(
	anchor(controller_url('backend/users'), $this->lang->line("gvv_admin_menu_users"), array("class" => "jbutton")),
	anchor(controller_url('backend/roles'), $this->lang->line("gvv_admin_menu_roles"), array("class" => "jbutton")),
	anchor(controller_url('backend/uri_permissions'), $this->lang->line("gvv_admin_menu_permissions"), array("class" => "jbutton")),

);
if (ENVIRONMENT == 'development')
	$list[] = anchor(controller_url('auth/custom_permissions'), $this->lang->line("gvv_admin_menu_custom_permissions"), array("class" => "jbutton"));
echo ul($list, $attributes);

if (ENVIRONMENT == 'development') {

	echo heading("gvv_admin_title_tests", 4);

	$list = array(
		anchor(controller_url("tests"), $this->lang->line("gvv_admin_menu_ut"), array("class" => "jbutton")),
		anchor(controller_url("coverage"), $this->lang->line("gvv_admin_menu_coverage"), array("class" => "jbutton")),
		anchor(controller_url('admin/info'), "phpinfo()", array("class" => "jbutton")),
		anchor("http://localhost/gvv2/phpdoc/", "phpdoc", array("class" => "jbutton")),
	);
	echo ul($list, $attributes);
}

echo heading("Cohérence de la base de données", 4);
$list = array(
	anchor(controller_url('dbchecks'), "Ecritures", array("class" => "jbutton")),
	anchor(controller_url('dbchecks/volsp'), "Vols planeur", array("class" => "jbutton")),
	anchor(controller_url('dbchecks/volsa'), "Vols avion", array("class" => "jbutton")),
	anchor(controller_url('dbchecks/achats'), "Achats", array("class" => "jbutton")),
	anchor(controller_url('dbchecks/soldes'), "Solde des comptes", array("class" => "jbutton")),
	anchor(controller_url('dbchecks/sections'), "Sections", array("class" => "jbutton")),
);
echo ul($list, $attributes);


echo '</div>';
