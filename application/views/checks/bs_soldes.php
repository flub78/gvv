<!-- VIEW: application/views/checks/bs_soldes.php -->
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

echo p("Cette page effectue des vérifications sur la cohérence de la base de données. Elle calcule la différence entre la somme des crédits et la somme des débits de chaque compte et la compare au solde réel (calculé à partir des écritures). Si la différence est non nulle, le compte est affiché dans la liste ci-dessous.");

echo p("Comme les crédits et débits des comptes ne sont pas systématiquement mis à jour, la vérification n'est pas forcément judicieuse. Il faudrait le remplacer par une vérification que le solde de la dernière écriture est bien égal au solde du compte.");

echo heading($title . " Vérification des soldes", 4);
echo table_from_array ($soldes, array(
    'fields' => array('Compte', 'Nom', 'Description', 'Débit', 'Crédit', 'Différence', 'Solde'),
    'align' => array('left', 'left', 'left', 'left', 'left', 'left', 'left'),
    'class' => 'datatable table'
));

echo br();

echo '</div">';

?>