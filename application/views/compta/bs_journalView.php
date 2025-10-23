<!-- VIEW: application/views/compta/bs_journalView.php -->
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
 * @package vues
 * 
 * Grand journal
 */
$this->load->library('ButtonNew');

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('compta');

?>
<div id="body" class="body container-fluid">
    <?php
    if (isset($message)) {
        echo p($message) . br();
    }
    echo checkalert($this->session);

    $title = $this->lang->line("gvv_comptes_title_journal");
    if ($section) {
        $title .= " section " . $section['nom'];
    }
    ?>

    <h3><?= $title ?></h3>
    <div class="mb-3">
        <?= year_selector($controller, $year, $year_selector) ?>
    </div>
    <input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

    <div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
        <div class="accordion-item">
            <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                    <?= $this->lang->line("gvv_str_filter") ?>
                </button>
            </h2>
            <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse <?= $filter_active ? 'show' : '' ?>" aria-labelledby="panelsStayOpen-headingOne">
                <div class="accordion-body">
                    <div>
                        <form action="<?= controller_url($controller) . "/JournalFilterValidation/" . $compte ?>" method="post" accept-charset="utf-8" name="saisie">

                            <div class="d-md-flex flex-row mb-2">
                                <!-- date, jusqua, compte-->
                                <div class="me-3 mb-2" style='min-width: 400px'>
                                    <?= $this->lang->line('gvv_compta_comptes') . ": " . dropdown_field('id', $id, $compte_selector, "id='selector' class='big_select' style='min-width: 300px' onchange='compte_selection();'") ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row  mb-2">
                                <div class="me-3 mb-2">
                                    <?= $this->lang->line('gvv_compta_date') . ": "
                                        . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= $this->lang->line('gvv_compta_jusqua') . ": "
                                        . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?php if ($this->dx_auth->is_role('tresorier')) {
                                        echo enumerate_radio_fields($this->lang->line('gvv_compta_type_ecriture'), 'filter_checked', $filter_checked);
                                    } ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row  mb-2">
                                <div class="me-3 mb-2">
                                    <?php $query_selector = $this->lang->line('gvv_vue_journal_selector');
                                    echo dropdown_field('query', $query, $query_selector, "id='query_selector' onchange='query_selection();'") ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row  mb-2">
                                <div class="me-3 mb-2">
                                    <?= $compte ?>
                                </div>
                                <div class="me-3 mb-2">
                                    <?= $compte ?>
                                </div>
                                <div class="me-3 mb-2">
                                    <?= $compte ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row  mb-2">
                                <div class="me-3 mb-2">
                                    <?= $this->lang->line('gvv_compta_montant_min') . ": " ?>
                                </div>
                                <div class="me-3 mb-2">
                                    <?= input_field('montant_min', $montant_min, array('type'  => 'text', 'size' => '8', 'title' => 'Montant minimal')) ?>
                                </div>
                                <div class="me-3 mb-2">
                                    <?= $this->lang->line('gvv_compta_montant_max') . ": " ?>
                                </div>
                                <div class="me-3 mb-2">
                                    <?= input_field('montant_max', $montant_max, array('type'  => 'text', 'size' => '8', 'title' => 'Montant maximal')) ?>
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
    </div>

    <?php

    // Lignes d'écritures
    echo br();
    $ajax = $this->config->item('ajax');
    if ($ajax) {
        $classes = "datatable_style datatable_mini_serverside  table table-striped";
    } else {
        $classes = "datatable_style datatable_mini  table table-striped";
    }

    $attrs = array(
        'controller' => $controller,
        'actions' => array('edit', 'delete'),
        'count' => $count,
        'first' => $premier,
        'mode' => ($has_modification_rights && ($section)) ? "rw" : "ro",
        'class' => $classes
    );

    if ($ajax) {
        echo $this->gvvmetadata->empty_table("vue_journal", $attrs);
    } else {
        echo $this->gvvmetadata->table("vue_journal", $attrs, "");
    }

    echo button_bar2("$controller/export_journal", array('Excel' => "button", 'Pdf' => "button", $this->lang->line("gvv_compta_button_freeze") => 'button'));

    echo '</div>';
    ?>
    <script language="JavaScript">
        <!--
        $(document).ready(function() {

            $('.datatable_mini').dataTable({
                "bFilter": false,
                "bPaginate": false,
                "bStateSave": false,
                "bJQueryUI": true,
                "bSort": true,
                "bInfo": true,
                "bAutoWidth": true,
                "sPaginationType": "full_numbers",
                "oLanguage": olanguage
            });

            $('.datatable_mini_serverside').dataTable({
                "bServerSide": true,
                "sAjaxSource": "ajax_page",
                "bFilter": true,
                "iDisplayLength": 100,
                "bPaginate": true,
                "bStateSave": false,
                "bJQueryUI": true,
                "bSort": true,
                "bInfo": true,
                "bAutoWidth": true,
                "sPaginationType": "full_numbers",
                "oLanguage": olanguage,

                // Add the page length menu options
                "aLengthMenu": [
                    [10, 25, 50, 100, 500, 1000, -1],
                    [10, 25, 50, 100, 500, 1000, "Tous les"]
                ],

                "aoColumnDefs": [{
                        "aTargets": [10],
                        "sClass": "text-end"
                    },
                    {
                        "aTargets": [4, 6, 11, 12],
                        "sClass": "text-center"
                    }
                ]
            });

        });


        //
        -->
    </script>
    <?php
    ?>