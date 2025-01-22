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
 * Vue table des tickets (c'est la liste des achats / consomation par pilote).
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('tickets');
?>
<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("gvv_tickets_title_list") ?></h3>
    <input type="hidden" name="controller_url" id="controller_url" value="<?= controller_url($controller) ?>" />
    <input type="hidden" name="filter_active" value="<?= $filter_active ?>" />

    <div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
        <div class="accordion-item">
            <h2 class="accordion-header" id="panelsStayOpen-headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                    <?= $this->lang->line("gvv_str_filter") ?>
                </button>
            </h2>
            <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingOne">
                <div class="accordion-body">
                    <div>
                        <form action="<?= controller_url($controller) . "/filterValidation/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">

                            <div class="d-md-flex flex-row mb-2">
                                <!-- date, jusqua, compte-->
                                <div class="me-3 mb-2">
                                    <?= $this->lang->line("gvv_date") . ": " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= $this->lang->line("gvv_until") . ": " . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= $this->lang->line("gvv_pilot") . ": " . dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "") ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row">
                                <!-- Bouttons filtrer, afficher tout -->
                                <input type="submit" name="button" value="<?= $this->lang->line("gvv_str_select") ?>" />
                                <input type="submit" name="button" value="<?= $this->lang->line("gvv_str_display") ?>" />
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php

    if ($nom)
        echo $this->lang->line("gvv_tickets_label_account") . " =" . nbs() . $nom . br();

    if (isset($solde_pilote))
        echo $this->lang->line("gvv_tickets_label_balance") . " =" . nbs() . $solde_pilote . nbs() . "remorqués" . br(2);

    // Elements table
    $attrs = array(
        'controller' => $controller,
        'actions' => array('edit', 'delete'),
        'fields' => array('date', 'pilote', 'quantite', 'nom', 'description', 'vol'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'class' => "datatable table table-striped"
    );

    echo $this->gvvmetadata->table("vue_tickets", $attrs, "");

    $bar = array(
        array('label' => "Excel", 'url' => "$controller/export/csv/$filter_pilote", 'role' => 'ca'),
        array('label' => "Pdf", 'url' => "$controller/export/pdf/$filter_pilote", 'role' => 'ca'),
    );
    echo button_bar4($bar);

    if ($has_modification_rights) {
        echo br();
        echo p($this->lang->line("gvv_tickets_warning"));
    }
    echo '</div>';
