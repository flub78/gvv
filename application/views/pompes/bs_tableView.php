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
 * Vue liste des utilisations des pompes
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

?>
<div id="body" class="body container-fluid">
    <h3>Utilisation de la pompe </h3>
    <input type="hidden" name="filter_active" value="<?= $filter_active ?>" />
    <?php
    // -----------------------------------------------------------------------------------------
    // Filtre
    ?>
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
                                    <?= "Date: " . input_field('filter_date', $filter_date, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "Jusqu'a: " . input_field('date_end', $date_end, array('type'  => 'text', 'size' => '15', 'title' => 'JJ/MM/AAAA', 'class' => 'datepicker')) ?>
                                </div>

                                <div class="me-3 mb-2">
                                    <?= "Utilisateur: " . dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "") ?>
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

    // ------------------------------------- fin filtre

    $totaux = $totaux['totaux'][0];

    $footer = array();
    $footer[] = array('', '', '', 'Total:', $totaux['total_qte'], $totaux['total_prix'], '', '', '');
    $attrs = array(
        'controller' => $controller,
        'actions' => array('edit', 'delete'),
        'mode' => ($has_modification_rights) ? "rw" : "ro",
        'footer' => $footer,
        'class' => 'table'
    );

    echo $this->gvvmetadata->table("vue_pompes", $attrs, "");

    echo '</div>';

    ?>