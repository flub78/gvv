<!-- VIEW: application/views/openflyers/bs_tableSoldes.php -->
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
 * @file bs_tableSoldes.php
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_of_title_soldes", 3);

if ($error) {
    echo '<p class="text-danger">' . $error . "</p>\n";
}

?>
	<p>Seuls les comptes au solde non null sont affichés.</p>

<?php

echo form_open_multipart('openflyers/create_soldes');

// Utilisé pour les comptes clients
echo table_from_array ($soldes, array(
    'fields' => array('', 'Compte OF', 'Nom', 'Profil',  'Compte GVV', 'Solde OF', 'Solde GVV'),
    'align' => array('center', 'right', 'left', 'left', 'center', 'right', 'right'),
    'class' => 'datatable table'
));

?>
    <div class="actions mt-3">
        <button type="button"  class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
        <button type="button"  class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
        <!-- button onclick="getSelectedRows()">Get Selected Rows</!-->
    </div>
<?php
echo 'Date d\'import ou comparaison des soldes: <input type="date" name="import_date" size="50" class="mt-4" value="' . set_value('import_date', isset($import_date) ? $import_date : '') . '"><br><br>';

echo '<div class="d-flex gap-3 mb-4">';
echo form_input(array(
    'type' => 'submit',
    'name' => 'init_soldes',
    'value' => $this->lang->line("gvv_of_init_soldes"),
    'class' => 'btn btn-primary'
));
echo form_input(array(
    'type' => 'submit',
    'name' => 'compare_soldes',
    'value' => $this->lang->line("gvv_of_submit_compare"),
    'class' => 'btn btn-primary'
));
echo '</div>';

echo form_close('</div>');

echo '</div>';

?>

<?php