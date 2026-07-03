<!-- VIEW: application/views/vols_avion/bs_tableView.php -->
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
 * Vue planche (table) pour les vols avions
 * 
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('vols_avion');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session);

$title = $this->lang->line("gvv_vols_avion_title_list");
if ($section) {
    $title .= " section " . $section['nom'];
}
?>
<h3><?php echo $title ?></h3>
<?php

$categories = array_merge(array('-1' => $this->lang->line("gvv_toutes")), $this->config->item('categories_vol_avion'));

?>
<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />
<div class='mb-3'>
    <?= year_selector($controller, $year, $year_selector) ?>
</div>

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
                    <form action="<?= controller_url($controller) . "/filterValidation/" . $action ?>" method="post" accept-charset="utf-8" name="saisie">

                        <div class="d-md-flex flex-row mb-2">
                            <!-- date, jusqua, compte-->
                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_date") . ": " ?>
                                <input type="text" name="filter_date" value="<?= $filter_date ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
                            </div>

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_until") . ": " ?>
                                <input type="text" name="date_end" value="<?= $date_end ?>" size="15" title="JJ/MM/AAA" class="datepicker" />
                            </div>

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_pilot") . ": " ?>
                                <?= dropdown_field('filter_pilote', $filter_pilote, $pilote_selector, "") ?>
                            </div>

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_machine") . ": " ?>
                                <?= dropdown_field('filter_machine', $filter_machine, $machine_selector, "") ?>
                            </div>

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_site") . ": " ?>
                                <?= dropdown_field('filter_aero', $filter_aero, $aero_selector, "") ?>
                            </div>
                        </div>

                        <div class="d-md-flex flex-row  mb-2">

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_dual") . ": " ?>
                                <?= form_checkbox(array('name' => 'filter_dc', 'value' => 1, 'checked' => (0 != $filter_dc))) ?>
                            </div>

                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_age") . ": " ?>
                                <?= enumerate_radio_fields($this->lang->line("gvv_age_select"), 'filter_25', $filter_25) ?>
                            </div>

                        </div>

                        <div class="d-md-flex flex-row  mb-2">
                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_categories") . ": " ?>
                                <?= enumerate_radio_fields($categories, 'filter_vi', $filter_vi) ?>
                            </div>

                        </div>

                        <div class="d-md-flex flex-row  mb-2">
                            <div class="me-3 mb-2">
                                <?= $this->lang->line("gvv_airplanes") . ": " ?>
                                <?= enumerate_radio_fields($this->lang->line("gvv_owner_select"), 'filter_prive', $filter_prive) ?>
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
    <div class="accordion-item">
        <h2 class="accordion-header" id="panelsStayOpen-headingTwo">
            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#panelsStayOpen-collapseTwo" aria-expanded="false" aria-controls="panelsStayOpen-collapseTwo">
                <?= $this->lang->line("gvv_vols_avion_fieldset_totals") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
            <div class="accordion-body">
<style>
.gvv-totaux-table thead th { font-size: .8rem; }
.gvv-totaux-table td, .gvv-totaux-table th { padding: .3rem .5rem; white-space: nowrap; }
.gvv-totaux-table tfoot td { border-top: 2px solid #dee2e6; }
.gvv-totaux-title { font-size: .85rem; font-weight: 600; color: #495057; margin-bottom: .35rem; text-transform: uppercase; letter-spacing: .04em; }
</style>
<?php
$vol_categories = $this->config->item('categories_vol_avion');
$_total   = floatval($total);
$_count   = intval($count);
$_dc      = floatval($dc);
$_count_dc = intval($count_dc);
$_m25     = floatval($m25ans);
$_cnt_m25 = intval($count_m25ans);
$_pct = function($part, $total) { return ($total > 0) ? round(100 * $part / $total, 1) : 0; };
?>
<div class="row g-3">

    <!-- Tableau 1 : Par type de vol -->
    <div class="col-12 col-sm-6 col-xl-3">
        <p class="gvv-totaux-title"><?= $this->lang->line('gvv_vols_avion_totaux_by_type') ?></p>
        <table class="table table-sm table-bordered table-hover gvv-totaux-table mb-0">
            <thead class="table-dark">
                <tr>
                    <th><?= $this->lang->line('gvv_vols_avion_col_type') ?></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_flights') ?></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_hours') ?></th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($vol_categories as $cat_id => $cat_name): ?>
                    <?php if (!isset($by_category[$cat_id])) continue; ?>
                    <?php $c = $by_category[$cat_id]; ?>
                    <tr>
                        <td><?= htmlspecialchars($cat_name) ?></td>
                        <td class="text-end"><?= $c['flights'] ?></td>
                        <td class="text-end"><?= centieme_to_hhmm($c['hours']) ?></td>
                        <td class="text-end"><?= $_pct($c['hours'], $_total) ?>%</td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot>
                <tr class="table-secondary fw-semibold">
                    <td><?= $this->lang->line('gvv_vols_avion_row_total') ?></td>
                    <td class="text-end"><?= $_count ?></td>
                    <td class="text-end"><?= centieme_to_hhmm($_total) ?></td>
                    <td class="text-end">100%</td>
                </tr>
            </tfoot>
        </table>
    </div>

    <!-- Tableau 2 : Double commande -->
    <div class="col-12 col-sm-6 col-xl-3">
        <p class="gvv-totaux-title"><?= $this->lang->line('gvv_vols_avion_totaux_dc') ?></p>
        <table class="table table-sm table-bordered table-hover gvv-totaux-table mb-0">
            <thead class="table-dark">
                <tr>
                    <th></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_flights') ?></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_hours') ?></th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $non_dc_flights = max(0, $_count - $_count_dc);
                $non_dc_hours   = max(0.0, $_total - $_dc);
                ?>
                <tr>
                    <td><?= $this->lang->line('gvv_vols_avion_row_dc') ?></td>
                    <td class="text-end"><?= $_count_dc ?></td>
                    <td class="text-end"><?= centieme_to_hhmm($_dc) ?></td>
                    <td class="text-end"><?= $_pct($_dc, $_total) ?>%</td>
                </tr>
                <tr>
                    <td><?= $this->lang->line('gvv_vols_avion_row_non_dc') ?></td>
                    <td class="text-end"><?= $non_dc_flights ?></td>
                    <td class="text-end"><?= centieme_to_hhmm($non_dc_hours) ?></td>
                    <td class="text-end"><?= $_pct($non_dc_hours, $_total) ?>%</td>
                </tr>
                <?php if ($by_pilote): ?>
                <tr class="table-light">
                    <td><?= $this->lang->line('gvv_vols_avion_label_total_captain') ?></td>
                    <td class="text-end">—</td>
                    <td class="text-end"><?= centieme_to_hhmm($cdb) ?></td>
                    <td class="text-end"><?= $_pct($cdb, $_total) ?>%</td>
                </tr>
                <tr class="table-light">
                    <td><?= $this->lang->line('gvv_vols_avion_label_total_instruction') ?></td>
                    <td class="text-end">—</td>
                    <td class="text-end"><?= centieme_to_hhmm($inst) ?></td>
                    <td class="text-end"><?= $_pct($inst, $_total) ?>%</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- Tableau 3 : Âge pilote (PG) -->
    <div class="col-12 col-sm-6 col-xl-3">
        <p class="gvv-totaux-title"><?= $this->lang->line('gvv_vols_avion_totaux_age') ?></p>
        <table class="table table-sm table-bordered table-hover gvv-totaux-table mb-0">
            <thead class="table-dark">
                <tr>
                    <th></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_flights') ?></th>
                    <th class="text-end"><?= $this->lang->line('gvv_vols_avion_col_hours') ?></th>
                    <th class="text-end">%</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $p25_flights = max(0, $_count - $_cnt_m25);
                $p25_hours   = max(0.0, $_total - $_m25);
                ?>
                <tr>
                    <td><?= $this->lang->line('gvv_vols_avion_row_m25') ?></td>
                    <td class="text-end"><?= $_cnt_m25 ?></td>
                    <td class="text-end"><?= centieme_to_hhmm($_m25) ?></td>
                    <td class="text-end"><?= $_pct($_m25, $_total) ?>%</td>
                </tr>
                <tr>
                    <td><?= $this->lang->line('gvv_vols_avion_row_p25') ?></td>
                    <td class="text-end"><?= $p25_flights ?></td>
                    <td class="text-end"><?= centieme_to_hhmm($p25_hours) ?></td>
                    <td class="text-end"><?= $_pct($p25_hours, $_total) ?>%</td>
                </tr>
            </tbody>
        </table>
    </div>

    <!-- Tableau 4 : Consommations -->
    <?php if (count($conso) > 1 && (!$by_pilote)): ?>
    <div class="col-12 col-sm-6 col-xl-3">
        <p class="gvv-totaux-title"><?= $this->lang->line('gvv_vols_avion_fieldset_conso') ?></p>
        <table class="table table-sm table-bordered table-hover gvv-totaux-table mb-0">
            <thead class="table-dark">
                <tr>
                    <?php foreach ($conso[0] as $cell): ?>
                    <th><?= htmlspecialchars($cell) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php for ($i = 1; $i < count($conso); $i++): ?>
                <tr>
                    <?php foreach ($conso[$i] as $cell): ?>
                    <td><?= htmlspecialchars($cell) ?></td>
                    <?php endforeach; ?>
                </tr>
                <?php endfor; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>

</div>
            </div>
        </div>
    </div>

</div>

<?php

// -----------------------------------------------------------------------------------------
// Liste des vols
if ($has_modification_rights) {
    $classes = "datatable_style datedtable table table-striped";
} else {
    $classes = "datatable_style datedtable_ro table table-striped";
}

$mode = $has_modification_rights ? "rw" : "ro";
$actions = ($mode == "rw") ? array('edit', 'delete') : [];
$attrs = array(
    'controller' => $controller,
    'actions' => $actions,
    'mode' => $mode,
    'class' => $classes,
);

// Create button above the table (only for planchiste role)
if ($has_modification_rights) {
    echo '<div class="mb-3">'
        . '<a href="' . site_url('vols_avion/create') . '" class="btn btn-sm btn-success">'
        . '<i class="fas fa-plus" aria-hidden="true"></i> '
        . $this->lang->line('gvv_button_create')
        . '</a>'
        . '</div>';
}
echo $this->gvvmetadata->table("vue_vols_avion", $attrs, "");


echo br();

$bar = array(
    array('label' => "Excel", 'url' => "$controller/csv/$year"),
    array('label' => "Pdf", 'url' => "$controller/pdf/$year"),
);
echo br() . button_bar4($bar);

echo '</div>';
?>
<script type="text/javascript" src="<?php echo js_url('french_dates'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('table_vols_avion'); ?>"></script>
<?php

?>