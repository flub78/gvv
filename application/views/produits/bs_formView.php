<!-- VIEW: application/views/produits/bs_formView.php -->
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
 * Formulaire de saisie produits
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');

$this->load->view('bs_banner');
$this->lang->load('produits');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
	echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_produits_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('id', $id);

echo '<div class="row">';
echo '<div class="col-lg-7">';

$fields = array(
	'reference' => $reference,
	'description' => $description,
	'compte' => $compte,
	'public' => $public,
	'is_cotisation' => $is_cotisation,
	'nb_personnes_max' => isset($nb_personnes_max) ? $nb_personnes_max : 1,
);

if ($this->config->item('gestion_tickets')) {
	$fields['type_ticket'] = $type_ticket;
}

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

echo ($this->gvvmetadata->form('produits', $fields));

echo '</div>'; // col-lg-7

$gestion_tickets = $this->config->item('gestion_tickets');
echo '<div class="col-lg-5">';
echo '<div class="card">';
echo '<div class="card-header">' . $this->lang->line('gvv_produits_tarifs_card_title') . '</div>';
echo '<div class="card-body">';

echo '<div id="tarifs_error" class="alert alert-danger py-2 px-2" style="display:none;"></div>';

echo '<table class="table table-sm align-middle" id="tarifs_table">';
echo '<thead><tr>';
echo '<th>' . $this->gvvmetadata->field_long_name('tarifs', 'date') . '</th>';
echo '<th>' . $this->gvvmetadata->field_long_name('tarifs', 'prix') . '</th>';
if ($gestion_tickets) {
	echo '<th>' . $this->gvvmetadata->field_long_name('tarifs', 'nb_tickets') . '</th>';
}
echo '<th></th>';
echo '</tr></thead>';
echo '<tbody id="tarifs_tbody"></tbody>';
echo '</table>';

echo '<div class="row g-2 align-items-end">';
echo '<div class="col">';
echo '<label class="form-label" for="tarif_date">' . $this->gvvmetadata->field_long_name('tarifs', 'date') . '</label>';
echo '<input type="date" id="tarif_date" class="form-control form-control-sm">';
echo '</div>';
echo '<div class="col">';
echo '<label class="form-label" for="tarif_prix">' . $this->gvvmetadata->field_long_name('tarifs', 'prix') . '</label>';
echo '<input type="text" id="tarif_prix" class="form-control form-control-sm" placeholder="0.00">';
echo '</div>';
if ($gestion_tickets) {
	echo '<div class="col">';
	echo '<label class="form-label" for="tarif_nb_tickets">' . $this->gvvmetadata->field_long_name('tarifs', 'nb_tickets') . '</label>';
	echo '<input type="text" id="tarif_nb_tickets" class="form-control form-control-sm" placeholder="0">';
	echo '</div>';
}
echo '<div class="col-auto">';
echo '<button type="button" id="tarif_add_btn" class="btn btn-sm btn-primary">' . $this->lang->line('gvv_produits_tarif_add_btn') . '</button> ';
echo '<button type="button" id="tarif_cancel_btn" class="btn btn-sm btn-secondary" style="display:none;">' . $this->lang->line('gvv_produits_tarif_cancel_btn') . '</button>';
echo '</div>';
echo '</div>'; // row mini-form

echo '</div>'; // card-body
echo '</div>'; // card
echo '</div>'; // col-lg-5
echo '</div>'; // row

echo '<input type="hidden" name="tarifs_json" id="tarifs_json" value="">';

echo validation_button($action);
echo form_close();

echo '</div>';

echo '<script>var GVV_PRODUITS_INITIAL_TARIFS = ' . json_encode(isset($tarifs) ? $tarifs : array()) . ';';
echo 'var GVV_PRODUITS_GESTION_TICKETS = ' . ($gestion_tickets ? 'true' : 'false') . ';';
echo 'var GVV_PRODUITS_LANG = ' . json_encode(array(
	'last_one' => $this->lang->line('gvv_produits_tarif_last_one'),
	'invalid' => $this->lang->line('gvv_produits_tarif_invalide'),
	'add' => $this->lang->line('gvv_produits_tarif_add_btn'),
	'update' => $this->lang->line('gvv_produits_tarif_update_btn'),
)) . ';</script>';
echo '<script src="' . base_url('assets/js/produits_tarifs.js') . '"></script>';
