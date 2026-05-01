<!-- VIEW: application/views/vols_decouverte/bs_tableView.php -->
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
$this->lang->load('briefing_passager');

echo '<div id="body" class="body container-fluid">';

echo heading("gvv_vols_decouverte_title", 3);
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Display flash message if exists
if ($this->session->flashdata('message')) {
    echo $this->session->flashdata('message');
}

// Display filter error if exists
if (isset($filter_error)) {
    echo '<div class="alert alert-danger">' . $filter_error . '</div>';
}

// Statistiques par section
if (!empty($vd_stats_per_section)) {
    $total_sold = array_sum(array_column($vd_stats_per_section, 'sold_this_year'));
    $total_done = array_sum(array_column($vd_stats_per_section, 'done_this_year'));
    $total_todo = array_sum(array_column($vd_stats_per_section, 'todo_valid'));
    $multi      = count($vd_stats_per_section) > 1;
    $year       = isset($year) ? $year : date('Y');

    echo '<div class="card mb-3 border-primary">';
    echo '<div class="card-header bg-primary text-white py-2">Vols de découverte vendus à effectuer</div>';
    echo '<div class="card-body py-2">';
    echo '<table class="table table-sm table-borderless mb-0">';
    echo '<thead><tr>'
        . '<th></th>'
        . '<th class="text-center">Vendus en ' . (int)$year . '</th>'
        . '<th class="text-center">Effectués en ' . (int)$year . '</th>'
        . '<th class="text-center">À effectuer (valides)</th>'
        . '</tr></thead><tbody>';
    foreach ($vd_stats_per_section as $row) {
        if ((int)$row['sold_this_year'] === 0 && (int)$row['done_this_year'] === 0 && (int)$row['todo_valid'] === 0) {
            continue;
        }
        echo '<tr>'
            . '<td class="fw-semibold">' . htmlspecialchars($row['nom']) . '</td>'
            . '<td class="text-center"><span class="badge bg-primary">' . (int)$row['sold_this_year'] . '</span></td>'
            . '<td class="text-center"><span class="badge bg-success">'  . (int)$row['done_this_year'] . '</span></td>'
            . '<td class="text-center"><span class="badge bg-warning text-dark">' . (int)$row['todo_valid'] . '</span></td>'
            . '</tr>';
    }
    if ($multi) {
        echo '<tr class="border-top fw-semibold">'
            . '<td>Total</td>'
            . '<td class="text-center"><span class="badge bg-primary">'          . $total_sold . '</span></td>'
            . '<td class="text-center"><span class="badge bg-success">'           . $total_done . '</span></td>'
            . '<td class="text-center"><span class="badge bg-warning text-dark">' . $total_todo . '</span></td>'
            . '</tr>';
    }
    echo '</tbody></table>';
    echo '</div>';
    echo '</div>';
}

// Year selector
echo '<div class="mb-3">';
if (isset($year_selector) && isset($year) && isset($controller)) {
    echo year_selector($controller, $year, $year_selector);
}
echo '</div>';

?>

<?php
// Create button above the filter
if ($has_modification_rights) {
    $btn_bar = '<div class="d-flex gap-2 mb-3">';
    $btn_bar .= '<a href="' . site_url('vols_decouverte/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a>';

    $btn_bar .= '</div>';
    echo $btn_bar;
}
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

$is_pilot_only = !$has_modification_rights && (isset($has_pilot_rights) && $has_pilot_rights);
if ($has_modification_rights) {
    $table_actions = array('edit', 'delete', 'print_vd', 'email_vd', 'action', 'briefing_vd');
} elseif ($is_pilot_only) {
    $table_actions = array('print_vd', 'email_vd', 'action', 'briefing_vd');
} else {
    $table_actions = array();
}
$attrs = array(
    'controller' => $controller,
    'actions' => $table_actions,
    'fields' => array('id', 'validite', 'product', 'beneficiaire', 'urgence', 'date_vol',  'pilote', 'airplane_immat', 'cancelled', 'paiement', 'participation'),
    'mode' => ($has_modification_rights || $is_pilot_only) ? "rw" : "ro",
    'class' => "datatable table table-striped"
);

echo $this->gvvmetadata->table("vue_vols_decouverte", $attrs, "");


if ($has_modification_rights) {
    $bar = array(
        array('label' => "Excel", 'url' => "$controller/export/csv"),
        array('label' => "Pdf",   'url' => "$controller/export/pdf", 'target' => '_blank'),
    );
    echo button_bar4($bar);
}

echo '</div>';

?>

<script>
function new_year() {
    var year = document.getElementById('year_selector').value;
    var url = document.querySelector('input[name="controller_url"]').value + '/page/' + year;
    window.location.href = url;
}
</script>
