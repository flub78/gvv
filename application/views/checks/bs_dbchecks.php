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
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';
echo heading("Database checks", 3);

echo p("Cette page effectue des vérifications sur la cohérence de la base de données. Si votre base de données est saine elle ne devrait retourner aucun résultat. Dans le cas contraire, contactez votre administrateur.");



echo heading("Ecritures sur comptes non existants", 4);

echo table_from_array ($wrong_lines, array(
    'fields' => array('Date', 'Description', 'Montant', 'Compte1', 'Compte2'),
    'align' => array('left', 'left', 'right', 'right', 'right'),
    'class' => 'datatable table'
));

echo br();
echo heading("Comptes référencés mais non existants", 4);

echo table_from_array ($wrong_accounts, array(
    'fields' => array('Id'),
    'align' => array('left'),
    'class' => 'datatable table'
));
echo br();

echo heading("Ecritures référencant des achats non existants", 4);

echo table_from_array ($wrong_purchases, array(
    'fields' => array('Ecriture', 'Date', 'Description', 'Montant', 'achat'),
    'align' => array('left'),
    'class' => 'datatable'
));
echo br();

echo '</div">';

?>