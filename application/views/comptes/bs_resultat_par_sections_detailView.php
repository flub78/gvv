<!-- VIEW: application/views/comptes/bs_resultat_par_sections_detailView.php -->
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
 * Vue du détail d'un codec par sections pour deux années consécutives
 *
 * @packages vues
 */

$this->load->library('DataTable');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '';

$title = sprintf($this->lang->line("gvv_comptes_title_resultat_par_sections_detail"), $codec . ' - ' . $codec_nom);

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

    <!-- Lien de retour -->
    <div class="mb-3">
        <a href="<?= base_url('comptes/resultat_par_sections') ?>" class="btn btn-secondary btn-sm">
            &laquo; <?= $this->lang->line("gvv_comptes_title_resultat_par_sections") ?>
        </a>
    </div>

    <!-- Tableau de détail -->
    <h3 class="mt-4">
        <?= $is_charge ? $this->lang->line("comptes_label_charges") : $this->lang->line("comptes_label_produits") ?>
        - <?= $codec ?> <?= $codec_nom ?>
    </h3>
    <?php
    if (!empty($detail)) {
        // Calcul du nombre de colonnes pour l'alignement
        $nb_cols = count($detail[0]);
        $align = array('center', 'left'); // Code et Libellé à gauche
        for ($i = 2; $i < $nb_cols; $i++) {
            $align[] = 'right'; // Montants à droite
        }

        $table = new DataTable(array(
            'title' => "",
            'values' => $detail,
            'controller' => $controller,
            'class' => "sql_table fixed_datatable table",
            'align' => $align
        ));

        $table->display();
    } else {
        echo '<p class="text-muted">Aucun compte trouvé pour ce codec.</p>';
    }
    ?>

    <?php
    echo br(2);

    // Boutons d'export
    $bar = array(
        array('label' => "Excel", 'url' => "comptes/resultat_par_sections_detail/$codec/csv", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "comptes/resultat_par_sections_detail/$codec/pdf", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    echo '</div>';
    ?>
    <script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>
