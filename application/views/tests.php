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
echo heading("Tests", 3);

echo p("Cette page permet l'activation des tests unitaires. Note: elle doit être activable par tout les utilisateurs, pas seulement les administrateurs.");

echo heading("Tests des librairies et helpers", 4);
$list = array(
	anchor(controller_url("tests/test_helpers"), "Helpers"),
	anchor(controller_url("tests/test_libraries"), "Libraries")
);
echo ul($list);

echo heading("Tests controleurs et models", 4);
echo p("Tests unitaires des fonctions du controleur et du model");


$list = array(
	anchor(controller_url("achats/test"), "Achats"),
	anchor(controller_url("admin/test"), "Admin"),
	anchor(controller_url("categorie/test"), "Categories"),
	anchor(controller_url("compta/test"), "Compta"),
	anchor(controller_url("comptes/test"), "Comptes"),
	anchor(controller_url("event/test"), "Events"),
	anchor(controller_url("licences/test"), "Licences"),
	anchor(controller_url("membre/test"), "Membres"),
	anchor(controller_url("plan_comptable/test"), "Plan comptable"),
	anchor(controller_url("planeur/test"), "Planeur"),
	anchor(controller_url("pompes/test"), "Pompes"),
	anchor(controller_url("presences/test"), "Présences"),
	anchor(controller_url("rapports/test"), "Rapports"),
	anchor(controller_url("tarifs/test"), "Tarifs"),
	anchor(controller_url("terrains/test"), "Terrains"),
	anchor(controller_url("tickets/test"), "Tickets"),
	anchor(controller_url("types_ticket/test"), "Type tickets"),
);
echo ul($list);

echo heading("Tests fonctionels", 4);
echo p("Ces tests mettent en jeux plusieurs models.");
echo p("Attention ils chargent une base de données de test et efface vos données.", 'class="error"');
//echo p("(Note: il doivent laisser la base de donnée dans l'état ou ils l'ont trouvée).");

$list = array(
	anchor(controller_url("facturation/test"), "Facturation"),
	anchor(controller_url("vols_avion/test"), "Vols avion"),
	anchor(controller_url("vols_planeur/test"), "Vols planeur")
);
echo ul($list);

$list = array(
	'statistiques planeur',
	'statistiques avion',
	'facturation planeur',
	'facturation avion',
	'bilan'
);
echo ul($list);

echo '</div">';

echo '<div>';
echo '<form action="/upload_article_image" method="POST" enctype="multipart/form-data">
    <input type="file" name="article_image" accept="image/*" capture="camera">
    <button type="submit">Upload</button>
</form>';
echo '</div">';
