<!-- VIEW: application/views/simpleDirectoryView.php -->
<?php
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
//
// Simple vue pour afficher un répèrtoire
// On aurait pu adapter la simpleListView mais il y a quand même des aspects 
// spécifique à l'affichage des fichiers. Si on voulait fournir un service 
// complet de partage de fichier, on ferait miex d'en trouver un plutôt 
// que de le refaire.

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->load->library('TableViewer');

echo '<div id="body" class="body ui-widget-content">';

echo heading($title, 3);

echo p("Vous pouvez ajouter des fichiers à la liste des documents partagés.");
echo form_open_multipart("partage/do_upload/$dir");
echo '<input type="file" name="userfile" size="35" /><br>';
echo form_input(array('type' => 'submit', 'name' => 'button', 'value' => 'Validation'));

/*
$table = new TableViewer(array(
	'title' => "",
	'title_row'	=> $title_row,
	'col_list' => $col_list,
	'values' => $list,
	'controller' => $controller,
	'idColumn' => $primary_key,
	'class' => "sql_table",
	'create' => '',
	'count' => $count,
	'first' => $premier
));
*/
$table->addAction(new Button(array(
	'label' => 'Supprime',
	'controller' => $controller,
	'action' => "delete/$dir",
	'confirm' => TRUE)));

$table->display();
echo '</div>';
?>
