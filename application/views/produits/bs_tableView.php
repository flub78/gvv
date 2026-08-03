<!-- VIEW: application/views/produits/bs_tableView.php -->
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
 * Vue (table) pour les produits
 *
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('produits');
?>
<div id="body" class="body container-fluid">
    <?= checkalert($this->session) ?>
    <h3><?= $this->lang->line("gvv_produits_title_list") ?></h3>

    <input type="hidden" name="controller_url" id="controller_url" value="<?= controller_url($controller) ?>" />

    <?php
    $attrs = array(
        'controller' => $controller,
        'actions' => array('tarifs', 'edit', 'delete'),
        'title' => $this->lang->line("gvv_produits_title_list"),
        'fields' => array('reference', 'description', 'section_name', 'nom_compte', 'public', 'is_cotisation'),
        'first' => $premier,
        'mode' => ($has_modification_rights && $section) ? "rw" : "ro",
        'class' => "datatable table table-striped"
    );

    // Create button above the table
    echo '<div class="mb-3">'
        . '<a href="' . site_url('produits/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a>'
        . '</div>';
    echo $this->gvvmetadata->table("vue_produits", $attrs, "");

    echo p($this->lang->line("gvv_produits_tarifs_tooltip"));
    echo br();
    echo p($this->lang->line("gvv_produits_warning"));

    echo '</div>';
