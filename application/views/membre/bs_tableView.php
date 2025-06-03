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
 * Vue table des membres
 *
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('membre');

?>
<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("membre_title_list") ?></h3>

    <input type="hidden" name="controller_url" id="controller_url" value="<?= controller_url($controller) ?>" />
    <input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

    <div class="accordion accordion-flush collapsed mb-4" id="panels">

        <!-- Filtre -->
        <div class="accordion-item">
            <h3 class="accordion-header" id="panel-filtre">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panel_filter_id" aria-expanded="true" aria-controls="panel_filter_id">
                    <?= $this->lang->line("gvv_str_filter") ?>
                </button>
            </h3>
            <div id="panel_filter_id" class="accordion-collapse collapse <?= $filter_active ? 'show' : '' ?>" aria-labelledby="panel-filtre">
                <div class="accordion-body">
                    <form action="<?= controller_url($controller) . "/filterValidation/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">

                        <!-- Actifs-->
                        <div class="d-md-flex flex-row mb-2">
                            <div class="me-3 mb-2">
                                <?= $this->lang->line("membre_filter_active") . ": " . enumerate_radio_fields($this->lang->line("membres_filter_active_select"), 'filter_membre_actif', $filter_membre_actif) ?>
                            </div>
                        </div>

                        <!-- Age -->
                        <div class="d-md-flex flex-row  mb-2">
                            <?= $this->lang->line("membre_filter_age") . ": " .  enumerate_radio_fields($this->lang->line("membres_filter_age"), 'filter_25', $filter_25) ?>
                        </div>

                        <!-- Categorie -->
                        <div class="d-md-flex flex-row  mb-2">

                            <div class="me-3 mb-2">
                                <?php
                                $my_categories = array(0 => $this->lang->line("membre_filter_all"));
                                foreach ($this->config->item('categories_pilote') as $k => $v) {
                                    $my_categories[$k + 1] = $v;
                                }
                                echo $this->lang->line("membre_filter_category") . ": " .  enumerate_radio_fields($my_categories, 'filter_categorie', $filter_categorie);
                                ?>
                            </div>

                        </div>

                        <div class="d-md-flex flex-row">
                            <?= filter_buttons() ?>
                        </div>

                    </form>
                </div>
            </div>
        </div>
    </div>
    <?php

    // 'liens',
    $table_style = ($has_modification_rights) ? "table_membre" : "table_membre_ro";
    $attrs = array(
        'controller' => $controller,
        'actions' => array('edit', 'delete'),
        'fields' => array('photo', 'mnom', 'mprenom', 'ville', 'mtelf', 'mtelm', 'memail', 'mdaten', 'm25ans', 'msexe', 'actif'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'class' => "datatable_style $table_style table table-striped"
    );

    echo $this->gvvmetadata->table("membres", $attrs, "");

    $bar = array(
        array('label' => "Excel", 'url' => "membre/export/csv", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "membre/export/pdf", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    echo '</div>';
    ?>

    <script language="JavaScript">
        <!--
        $(document).ready(function() {
            // notre code ici

            $('.table_membre').dataTable({
                "bFilter": true,
                "bPaginate": true,
                "iDisplayLength": 25,
                "bSort": true,
                "bJQueryUI": true,
                "bStateSave": false,
                "aaSorting": [
                    [0, "asc"]
                ],
                "aoColumns": [{
                        "bSortable": true
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    }
                ],
                "bInfo": true,
                "bAutoWidth": true,
                "sPaginationType": "full_numbers",
                "oLanguage": olanguage
            });

            $('.table_membre_ro').dataTable({
                "bFilter": true,
                "bPaginate": true,
                "iDisplayLength": 25,
                "bSort": true,
                "bJQueryUI": true,
                "bStateSave": false,
                "aaSorting": [
                    [0, "asc"]
                ],
                "aoColumns": [{
                        "bSortable": true
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    },
                    {
                        "bSortable": false
                    },
                    {
                        "bSortable": true
                    }
                ],
                "bInfo": true,
                "bAutoWidth": true,
                "sPaginationType": "full_numbers",
                "oLanguage": olanguage
            });

        });
        //
        -->
    </script>