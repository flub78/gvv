<!-- VIEW: application/views/associations_releve/bs_tableView.php -->
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
 * Vue table pour les associations relevé
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('associations_releve');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_associations_releve_title_associations", 3);

echo p("Cette table associe des chaines de caractères des relevés bancaires avec des comptes GVV. Elle permet d'associer un IBAN à un compte GVV, un émetteur de virement à un pilote ou un destinataire à un compte de dépense. Une fois ces associations faites, GVV saura vous faire des suggestion pour rapprocher les écritures. C'est manuel parce que les informations des relevés ne sont pas standardisées, un utilisateur peut apparaître différemment suivant la banque qu'il utilise pour le virement, etc..");

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('string_releve', 'type', 'id_compte_gvv'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

// Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('associations_releve/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

echo $this->gvvmetadata->table("vue_associations_releve", $attrs, "");

echo '</div>';