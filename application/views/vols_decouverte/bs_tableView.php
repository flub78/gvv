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
 * Vue table pour les vols de découverte avec filtres
 * 
 * @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_decouverte');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_vols_decouverte_title", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Display filter error if exists
if (isset($filter_error)) {
    echo '<div class="alert alert-danger">' . $filter_error . '</div>';
}

// Year selector
echo '<div class="mb-3">';
if (isset($year_selector) && isset($year) && isset($controller)) {
    echo year_selector($controller, $year, $year_selector);
}
echo '</div>';

?>

<!-- Filter accordion -->
<div class="accordion accordion-flush collapsed mb-3" id="accordionPanelsStayOpenExample">
    <div class="accordion-item">
        <h2 class="accordion-header" id="panelsStayOpen-headingOne">
            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseOne" aria-expanded="true" aria-controls="panelsStayOpen-collapseOne">
                <?= $this->lang->line("gvv_str_filter") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseOne" class="accordion-collapse collapse <?= isset($filter_active) && $filter_active ? 'show' : '' ?>" aria-labelledby="panelsStayOpen-headingOne">
            <div class="accordion-body">
                <div>
                    <form action="<?= controller_url($controller) . "/filter" ?>" method="post" accept-charset="utf-8" name="saisie">
                        <input type="hidden" name="return_url" value="<?= current_url() ?>" />
                        
                        <div>
                            <div class="row mb-3">
                                <div class="col">
                                    <label for="startDate" class="form-label">Date début</label>
                                    <input type="date" class="form-control" id="startDate" name="startDate" value="<?= isset($startDate) ? $startDate : '' ?>">
                                </div>
                                <div class="col">
                                    <label for="endDate" class="form-label">Date fin</label>
                                    <input type="date" class="form-control" id="endDate" name="endDate" value="<?= isset($endDate) ? $endDate : '' ?>">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <div class="col-12">
                                <label class="form-label">Afficher</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_all" value="all" <?= (!isset($filter_type) || $filter_type == 'all') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_all"><?= $this->lang->line("gvv_vols_decouverte_filter_all") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_done" value="done" <?= (isset($filter_type) && $filter_type == 'done') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_done"><?= $this->lang->line("gvv_vols_decouverte_filter_done") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_todo" value="todo" <?= (isset($filter_type) && $filter_type == 'todo') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_todo"><?= $this->lang->line("gvv_vols_decouverte_filter_todo") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_cancelled" value="cancelled" <?= (isset($filter_type) && $filter_type == 'cancelled') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_cancelled"><?= $this->lang->line("gvv_vols_decouverte_filter_cancelled") ?></label>
                                </div>
                                
                                <div class="form-check">
                                    <input class="form-check-input" type="radio" name="filter_type" id="filter_expired" value="expired" <?= (isset($filter_type) && $filter_type == 'expired') ? 'checked' : '' ?>>
                                    <label class="form-check-label" for="filter_expired"><?= $this->lang->line("gvv_vols_decouverte_filter_expired") ?></label>
                                </div>
                            </div>
                        </div>
                        
                        <div>
                            <div class="mb-2 mt-2">
                                <?= filter_buttons() ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete', 'print_vd', 'email_vd', 'action'),
    'fields' => array('id', 'validite', 'product', 'beneficiaire', 'urgence', 'date_vol',  'pilote', 'airplane_immat', 'cancelled', 'paiement', 'participation', 'prix'),
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_vols_decouverte", $attrs, "");


$bar = array(
    array('label' => "Excel", 'url' => "$controller/export/csv", 'role' => 'ca'),
    array('label' => "Pdf", 'url' => "$controller/export/pdf", 'role' => 'ca'),
);
// echo button_bar4($bar);

echo '</div>';

?>

<script>
function new_year() {
    var year = document.getElementById('year_selector').value;
    var url = document.querySelector('input[name="controller_url"]').value + '/page/' + year;
    window.location.href = url;
}
</script>
