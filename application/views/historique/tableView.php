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
 * Vue table pour l'historique des heures
 * 
 * @package vues
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('historique');

echo '<div id="body" class="body ui-widget-content">';

echo heading("gvv_historique_title_list", 3);

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// --------------------------------------------------------------------------------------------------
// Data
$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('machine', 'annee', 'heures'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable"
);

echo $this->gvvmetadata->table("vue_historique", $attrs, "");
br();
echo p($this->lang->line("gvv_historique_txt"));
echo '</div>';
