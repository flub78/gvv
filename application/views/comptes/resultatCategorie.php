<!-- VIEW: application/views/comptes/resultatCategorie.php -->
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
 * Résultats par catégorie
 * 
 * @packages vues
 */

$this->load->library('DataTable');
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->load->library('ButtonView');
echo '<div id="body" class="body ui-widget-content">';

echo heading("Dépenses $annee_exercise par catégories", 2, "");
echo br();

$tab = nbs(6);
$table = array();
$table[0] = array("Catégorie",  "Code", "Compte", "Montant", "Total", "");

$current = "";
$subtable = array();
foreach ($results as $row) {
    $annee_exercise = $row['annee_exercise'];
    $categorie = $row['categorie'];
    $nom_compte1 = $row['nom_compte1'];
    $compte1 = $row['compte1'];
    $code1 = $row['code1'];
    $total = $row['total'];
    // echo "$annee_exercise $categorie $nom_compte1 $total" . br();
    if ($current != $categorie) {
        // nouvelle catégorie
        $table = array_merge($table, $subtable);
        $current = $categorie;
        $full_total = $total;
        $subtable = array(array($categorie, '', '', '', euro($full_total), ''));
    } else {
        $full_total += $total;
    }
    
    $button = new ButtonView(array(
				'label' => 'Voir',
				'action' => 'balance',
				'controller' => 'comptes',
			    'param' => $code1));
    $subtable[] = array('', $code1, $nom_compte1,  euro($total), '',$button->image());
    $subtable[0][4] = euro($full_total);
    
}
$table = array_merge($table, $subtable);

$table = new DataTable(array(
	'title' => "",
	'values' => $table,
	'controller' => $controller,
	'class' => "sql_table fixed_datatable",
	'create' => '',
	'count' => '',
	'first' => '',
	'align' => array('left', 'right', 'left',  'right')
));

$table->display();

echo button_bar(array('Excel' => "$controller/csv_resultat_categories", 'Pdf' => "rapports/pdf_resultats_par_categories"));

echo '</div>';
?>
