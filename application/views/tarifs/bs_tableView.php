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
 * Vue (table) pour les tarifs et les produits
 * 
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');
$this->lang->load('tarifs');
?>
<div id="body" class="body container-fluid">
    <h3><?= $this->lang->line("gvv_tarifs_title_list") ?></h3>

    <input type="hidden" name="controller_url" id="controller_url" value="<?= controller_url($controller) ?>" />

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
                        <form action="<?= controller_url($controller) . "/filterValidation/" ?>" method="post" accept-charset="utf-8" name="saisie">

                            <div class="d-md-flex flex-row mb-2">
                                <!-- date, jusqua, compte-->
                                <div class="me-3 mb-2">
                                    <?= $this->lang->line("gvv_tarifs_label_todate") . ": " . input_field('filter_tarif_date', $filter_tarif_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= $this->lang->line("gvv_tarifs_label_public") . ": "
                                        . enumerate_radio_fields($this->lang->line("gvv_tarifs_filter_public_select"), 'filter_tarif_public', $filter_tarif_public) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "" ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "" ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "" ?>
                                </div>
                            </div>

                            <div class="d-md-flex flex-row  mb-2">
                                <div class="me-3 mb-2">
                                    <?= "" ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "" ?>
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
    $tarifs = "Tarifs";
    if ($filter_tarif_date) $tarifs .= " au $filter_tarif_date";

    $attrs = array(
        'controller' => $controller,
        'actions' => array('edit', 'delete', 'clone_elt'),
        'title' => $tarifs,
        'fields' => array('reference', 'description', 'date', 'section_name', 'date_fin', 'prix', 'nom_compte', 'public'),
        //    'count' => $count,
        'first' => $premier,
        'mode' => ($has_modification_rights && $section) ? "rw" : "ro",
        'class' => "datatable table table-striped"
    );

    echo $this->gvvmetadata->table("vue_tarifs", $attrs, "");

    echo p($this->lang->line("gvv_tarifs_clone_tooltip"));
    echo br();
    echo p($this->lang->line("gvv_tarifs_warning"));

    echo '</div>';
