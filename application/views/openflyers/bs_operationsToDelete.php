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
 * Vue table pour les terrains
 * 
 * @package vues
 * @file bs_tableOperations.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_title_confirm_delete", 3);

if ($status) {
    echo '<div class="border border-primary border-3 rounded p-2">';
    echo $status;
    echo '</div>';
}
?>

    <h5><?=$titre?></h5>
	<p><?=$date_edition?></p>

    <div class="actions mb-3">
        <button class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
        <button class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
    </div>
<?php

echo form_open_multipart('openflyers/delete_operations');

// Utilisé pour les comptes clients
echo table_from_array ($to_delete, array(
    'fields' => array('', 'Id', 'Date', 'Codec1',  'Compte1', 'Codec2', 'Compte2', 'Description', 'Référence', 'Montant'),
    'align' => array('center', 'right', 'right', 'left',  'left', 'right', 'left', 'left', 'left', 'right'),
    'class' => 'datatable table'
));

if ($section) echo form_input(array(
	'type' => 'submit',
	'name' => 'button',
	'value' => $this->lang->line("gvv_of_delete_opérations"),
	'class' => 'btn btn-primary mb-4'
));
echo form_close('</div>');

echo '</div>';

?>

<script>
</script>
<?php