<!-- VIEW: application/views/coverage.php -->
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
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

echo '<div id="body" class="body ui-widget-content">';
echo heading("Gestion de la mesure de couverture", 3);


$list = array(
		anchor(controller_url("coverage/reset_coverage") , "Initialisation (remise à 0 des données de couverture)"),
		anchor(controller_url("coverage/disable_coverage") , "Désactivation de la couverture"),
		anchor(controller_url("coverage/coverage_result") , "Génération des résulats de couverture (à exécuter après les tests)"),
		anchor('http://localhost/gvv2/code-coverage-report/index.html' , "Résultats de couverture en HTML")
		);
echo ul ($list);

echo '</div">';

?>