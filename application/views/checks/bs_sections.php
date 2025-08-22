<?php
// ----------------------------------------------------------------------------------------
//    GVV Gestion vol à voile
//    Copyright (C) 2011  Philippe Boissel & Frédéric Peignot
//
//    This program is free software: you can redistribute it and/or modify
//    it under the terms of the GNU General Public License as published by
//    the Free Software Foundation, either version 3 of the License, or
//    (at your option) any later version.
//
//    This program is distributed in the hope that it will be useful,
//    but WITHOUT ANY WARRANTY; without even the implied warranty of
//    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
//    GNU General Public License for more details.
//
//    You should have received a copy of the GNU General Public License
//    along with this program.  If not, see <http://www.gnu.org/licenses/>.
// ----------------------------------------------------------------------------------------
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

echo '<div id="body" class="body container-fluid">';
echo heading("Database checks", 3);

echo p("Cette page effectue des vérifications sur la cohérence de la base de données. Elle vérifie qu'il n'y a pas d'écritures entre sections différentes.");


echo heading($title . " Vérification des sections", 4);
echo table_from_array ($sections, array(
    'fields' => array('', 'Compte', 'Date', 'Montant', 'Description', 'Référence', 'Club', 'Compte1', 'Codec1', 'Club1', 'Compte2', 'Codec2', 'Club2'),
    'align' => array('left', 'left', 'left', 'left', 'left', 'left', 'left'),
    'class' => 'datatable table'
));

echo br();

?>
    <div class="actions mb-3">
        <button class="btn btn-primary" onclick="selectAll()">Sélectionnez tout</button>
        <button class="btn btn-primary" onclick="deselectAll()">Dé-sélectionnez tout</button>
    </div>
<?php

echo '</div">';

?>