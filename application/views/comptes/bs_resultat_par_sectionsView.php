<!-- VIEW: application/views/comptes/bs_resultat_par_sectionsView.php -->
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
 * Vue du résultat d'exploitation par sections pour deux années consécutives
 *
 * @packages vues
 */

$this->load->library('DataTable');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '';

$title = $this->lang->line("gvv_comptes_title_resultat_par_sections");

$url = controller_url($controller);

?>
<div id="body" class="body container-fluid">
    <h2><?= $title ?></h2>

    <input type="hidden" name="controller_url" value="<?= $url ?>" />

    <!-- Sélecteur d'année -->
    <div class="mb-3">
        <?= year_selector($controller, $year, $year_selector) ?>
    </div>

    <!-- Vue par exercice: pas de sélecteur de date -->

    <!-- Tableau des charges -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_charges") ?></h3>
    <?php
    if (!empty($charges)) {
        // Calcul du nombre de colonnes pour l'alignement
        $nb_cols = count($charges[0]);
        $align = array('center', 'left'); // Code et Nom à gauche
        for ($i = 2; $i < $nb_cols; $i++) {
            $align[] = 'right'; // Montants à droite
        }

        $table = new DataTable(array(
            'title' => "",
            'values' => $charges,
            'controller' => $controller,
            'class' => "sql_table fixed_datatable table",
            'align' => $align
        ));

        $table->display();
    }
    ?>

    <!-- Tableau des produits -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_produits") ?></h3>
    <?php
    if (!empty($produits)) {
        $nb_cols = count($produits[0]);
        $align = array('center', 'left');
        for ($i = 2; $i < $nb_cols; $i++) {
            $align[] = 'right';
        }

        $table = new DataTable(array(
            'title' => "",
            'values' => $produits,
            'controller' => $controller,
            'class' => "sql_table fixed_datatable table",
            'align' => $align
        ));

        $table->display();
    }
    ?>

    <!-- Tableau du résultat -->
    <h3 class="mt-4"><?= $this->lang->line("comptes_label_total") ?></h3>
    <?php
    if (!empty($resultat)) {
        $nb_cols = count($resultat[0]);
        $align = array('center', 'left'); // Code (vide) et Libellé à gauche
        for ($i = 2; $i < $nb_cols; $i++) {
            $align[] = 'right'; // Montants à droite
        }

        $table = new DataTable(array(
            'title' => "",
            'values' => $resultat,
            'controller' => $controller,
            'class' => "sql_table fixed_datatable table",
            'align' => $align
        ));

        $table->display();
    }
    ?>

    <?php
    echo br(2);

    // Boutons d'export
    $bar = array(
        array('label' => "Excel", 'url' => "comptes/resultat_par_sections/csv", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "comptes/resultat_par_sections/pdf", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    echo '</div>';
    ?>
    <script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
