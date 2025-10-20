<?php

/**
 *    GVV Gestion vol à voile
 *    Copyright (C) 2011-2024  Philippe Boissel & Frédéric Peignot
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
 * Bootstrap-based responsive view for glider flights management
 * Uses Bootstrap accordion components and flex layouts
 * 
 * @packages views
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body container-fluid">';

echo checkalert($this->session);

echo heading("gvv_vols_planeur_title", 3);

$categories = array_merge(array('-1' => $this->lang->line("gvv_toutes")), $this->config->item('categories_vol_planeur'));
$launch = $this->lang->line("gvv_launch_type");
$towing = $launch[3];
$winch = $launch[1];
$auto = $launch[2];
$ext = $launch[4];
?>

<input type="hidden" name="filter_active" value="<?= $filter_active ?>" />
<div class='mb-3'>
    <?= year_selector($controller, $year, $year_selector) ?>
</div>

<div class="accordion accordion-flush collapsed mb-4" id="accordionPanelsStayOpenExample">
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
                                <?= $this->lang->line("gvv_launch") . ": " ?>
                                <?= enumerate_radio_fields($this->lang->line("gvv_launch_select"), 'filter_lanc', $filter_lanc) ?>
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
                <?= $this->lang->line("gvv_vols_planeur_fieldset_totals") ?>
            </button>
        </h2>
        <div id="panelsStayOpen-collapseTwo" class="accordion-collapse collapse" aria-labelledby="panelsStayOpen-headingTwo">
            <div class="accordion-body">
                <div class="d-md-flex flex-row">
                    <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_flight_number") . " = " . $count ?></div>
                    <div class="me-3 mb-3"><?= "$towing=$rems, $winch=$treuils, $auto=$autonomes, $ext=$exts" ?></div>
                    <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_hours") . " = "   . minute_to_time($total) ?></div>
                    <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_junior") . " = "  . minute_to_time($m25ans) ?></div>
                </div>

                <div class="d-md-flex flex-row">
                    <?php if ($by_pilote) : ?>
                        <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_dual") . " = " . minute_to_time($dc) ?></div>
                        <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_captain") . " = " . minute_to_time($cdb) ?></div>
                        <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_instruction") . " = " . minute_to_time($inst) ?></div>
                    <?php else : ?>
                        <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_dual") . " = " . minute_to_time($dc) ?></div>
                    <?php endif; ?>
                    <div class="me-3 mb-3"><?= $this->lang->line("gvv_vols_planeur_label_total_distance") . " = " . $kms  ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php

// -----------------------------------------------------------------------------------------
// Elements table

$ajax = $this->config->item('ajax');
if ($ajax) {
    if ($this->dx_auth->is_role('planchiste')) {
        $classes = "datatable_style datatable_server table table-striped";
    } else {
        $classes = "datatable_style datatable_server_ro table table-striped";
    }
} else {
    $classes = "sql_table fixed_datatable  table table-striped";
}

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'count' => $count,
    'first' => $premier,
    'mode' => ($has_modification_rights) ? "rw" : "ro",
    'class' => $classes,
    'datatable' => 'server_side'
);

// Create button above the table
echo '<div class="mb-3">'
    . '<a href="' . site_url('vols_planeur/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

if ($ajax) {
    echo $this->gvvmetadata->empty_table("vue_vols_planeur", $attrs);
} else {
    echo $this->gvvmetadata->table("vue_vols_planeur", $attrs, "");
}

// -----------------------------------------------------------------------------------------
echo p($this->lang->line("gvv_vols_planeur_tip_unit"));

$bar = array(
    array('label' => "Excel", 'url' => "$controller/csv/$year"),
    array('label' => "Pdf", 'url' => "$controller/pdf/$year"),
);
echo br() . button_bar4($bar);

echo '</div>';

?>
<script type="text/javascript" src="<?php echo js_url('french_dates'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('table_vols_planeur'); ?>"></script>