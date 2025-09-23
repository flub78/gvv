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
 * Formulaire de resultat
 *
 * @packages vues
 */

$this->load->library('DataTable');
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('comptes');

echo '';

$title = $this->lang->line("gvv_comptes_title_dashboard");

$url = controller_url($controller);

?>
<div id="body" class="body container-fluid">
    <h2><?= $title ?></h2>

    <input type="hidden" name="controller_url" value="<?= $url ?>" />

    <?= $this->lang->line("comptes_label_date") . ': <input type="text" name="balance_date" id="balance_date" value="' . $balance_date . '" size="15" title="JJ/MM/AAAA" class="datepicker" onchange=new_balance_date(); />';  ?>

    <div class="accordion mt-3 mb-3" id="accordionPanelsStayOpenExample">
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                    <h3><?= "Charges" ?></h3>
                </button>
            </h2>
            <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <?php
                    $table = new DataTable(array(
                        'title' => "",
                        'values' => $charges,
                        'controller' => $controller,
                        'class' => "sql_table fixed_datatable table",
                        // 'create' => '',
                        // 'count' => '',
                        // 'first' => '',
                        'align' => array(
                            'center',
                            'left',
                            'right',
                            'right',
                            'right',
                            'right',
                            'right'
                        )
                    ));

                    $table->display();
                    ?>
                </div>
            </div>
        </div>
        <div class="accordion-item">
            <h2 class="accordion-header">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                    <h3><?= "Produits" ?></h3>
                </button>
            </h2>
            <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse">
                <div class="accordion-body">
                    <?php
                    $table = new DataTable(array(
                        'title' => "",
                        'values' => $produits,
                        'controller' => $controller,
                        'class' => "sql_table fixed_datatable table",
                        'align' => array(
                            'center',
                            'left',
                            'right',
                            'right',
                            'right',
                            'right',
                            'right'
                        )
                    ));

                    $table->display();
                    ?>

                </div>
            </div>
        </div>

    </div>



    <h3><?= "Résultat avant répartition" ?></h3>

    <?php
    $table = new DataTable(array(
        'title' => "",
        'values' => $resultat,
        'controller' => $controller,
        'class' => "sql_table fixed_datatable table",
        'align' => array(
            'left',
            'right',
            'right',
            'right',
            'right',
            'right'
        )
    ));

    $table->display();
    ?>

    <h3 class="mt-3"><?= "Actifs financiers" ?></h3>
    <?php
    $table = new DataTable(array(
        'title' => "",
        'values' => $disponible,
        'controller' => $controller,
        'class' => "sql_table fixed_datatable table",
        'align' => array(
            'left',
            'right',
            'right',
            'right',
            'right',
            'right'
        )
    ));

    $table->display();
    ?>

    <h3 class="mt-3"><?= "Dettes" ?></h3>
    <?php
    $table = new DataTable(array(
        'title' => "",
        'values' => $dettes,
        'controller' => $controller,
        'class' => "sql_table fixed_datatable table",
        'align' => array(
            'left',
            'right',
            'right',
            'right',
            'right',
            'right'
        )
    ));

    $table->display();
    ?>

    <h3 class="mt-3"><?= "Immobilisations" ?></h3>
    <?php
    $table = new DataTable(array(
        'title' => "",
        'values' => $immos,
        'controller' => $controller,
        'class' => "sql_table fixed_datatable table",
        'align' => array(
            'left',
            'right',
            'right',
            'right',
            'right',
            'right'
        )
    ));

    $table->display();
    ?>

    <?php

    echo br(2);

    $table = new DataTable(array(
        'title' => "",
        'values' => $resultat_table,
        'controller' => $controller,
        'class' => "sql_table fixed_datatable table",
        // 'create' => '',
        // 'count' => '',
        // 'first' => '',
        // 'align' => array(
        // 	'left',
        // 	'left',
        // 	'right',
        // 	'right',
        // 	'center',
        // 	'right',
        // 	'left',
        // 	'right',
        // 	'right'
        // )
    ));

    $table->display();

    $bar = array(
        array('label' => "Excel", 'url' => "comptes/dashboard/csv", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "comptes/dashboard/pdf", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    echo '</div>';
    ?>
    <script type="text/javascript" src="<?php echo js_url('balance'); ?>"></script>