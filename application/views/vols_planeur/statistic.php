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
 * Vue statistiques sur les vols planeur
 *
 * @package vues
 */
$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('vols_planeur');

$this->load->library('DataTable');

$controller = "vols_planeur";

echo '<div id="body" class="body ui-widget-content">';

echo heading($this->lang->line("gvv_vols_planeur_title_statistic"), 3);
echo year_selector($controller, $year, $year_selector);
echo br(2);
?>
<div id="tabs">
	<ul>
		<li><a href="#tabs-1"><?php echo ($this->lang->line("gvv_vols_planeur_tab_monthly"));?></a></li>
		<li><a href="#tabs-2"><?php echo ($this->lang->line("gvv_vols_planeur_tab_launch"));?></a></li>
		<li><a href="#tabs-3"><?php echo ($this->lang->line("gvv_vols_planeur_tab_training"));?></a></li>
		<li><a href="#tabs-4"><?php echo ($this->lang->line("gvv_vols_planeur_tab_age"));?></a></li>
		<li><a href="#tabs-5"><?php echo ($this->lang->line("gvv_vols_planeur_tab_gender"));?></a></li>
		<li><a href="#tabs-6"><?php echo ($this->lang->line("gvv_vols_planeur_tab_owner"));?></a></li>
		<li><a href="#tabs-7"><?php echo ($this->lang->line("gvv_vols_planeur_tab_glider"));?></a></li>
	</ul>

<?php
$title_row = array_merge(array (
        $this->lang->line("gvv_total")
), $this->lang->line("gvv_months"));

$cat_first_col = array (
        '',
        $this->lang->line("gvv_hdv"),
        $this->lang->line("gvv_flights"),
        $this->lang->line("gvv_towing"),
        $this->lang->line("gvv_winch"),
        $this->lang->line("gvv_self"),
        $this->lang->line("gvv_external"),
        $this->lang->line("gvv_kms")
);

$first_col = $this->lang->line("gvv_vols_planeur_stats_col");

$pm_first_row = array_merge(array (
        $this->lang->line("gvv_vue_vols_planeur_short_field_type"),
        $this->lang->line("gvv_vue_vols_planeur_short_field_vpmacid")
), $title_row);

// ###############################################################################
$header = $this->lang->line("gvv_vols_planeur_header_glider_activity") . ' ' . $this->lang->line("gvv_vols_planeur_header_per_month") . ' ' . $this->lang->line("gvv_vols_planeur_header_in") . ' ' . $year;
echo '<div id="tabs-1">' . heading($header, 4);

add_first_row($per_month, $title_row);
add_first_col($per_month, $first_col);

$table = new DataTable(array (
        'title' => "",
        'values' => $per_month,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

echo br() . hr();

echo heading($header, 4);
$filename = image_dir() . "planeur_mois_$year.png";
if (file_exists($filename))
    echo img($filename);

$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/export_per/$year/month"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/pdf_month/$year"
        ),
        array (
                'label' => "Génération",
                'url' => "$controller/statistic/true",
                "role" => 'ca'
        )
);
echo br() . button_bar4($bar);

// ###############################################################################

$header = $this->lang->line("gvv_vols_planeur_header_glider_activity") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_per_launch");

add_first_row($total, $title_row);
add_first_col($total, $cat_first_col);
echo '</div>';
echo '<div id="tabs-2">' . heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $total,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################

$header = $this->lang->line("gvv_vols_planeur_header_glider_activity") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_student");
add_first_row($totaldc, $title_row);
add_first_col($totaldc, $cat_first_col);
echo '</div>';
echo '<div id="tabs-3">' . heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $totaldc,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################

$header = $this->lang->line("gvv_vols_planeur_header_m25") . ' ' . $year;
add_first_row($total25, $title_row);
add_first_col($total25, $cat_first_col);
echo '</div>';
echo '<div id="tabs-4">' . heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $total25,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################

$header = $this->lang->line("gvv_vols_planeur_header_p25") . ' ' . $year;
add_first_row($total_plus, $title_row);
add_first_col($total_plus, $cat_first_col);
echo br();
echo heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $total_plus,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################
$header = $this->lang->line("gvv_vols_planeur_header_male") . ' ' . $year;
echo '</div>';
echo '<div id="tabs-5">' . heading($header, 4);
add_first_row($totalhommes, $title_row);
add_first_col($totalhommes, $cat_first_col);
$table = new DataTable(array (
        'title' => "",
        'values' => $totalhommes,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################
$header = $this->lang->line("gvv_vols_planeur_header_female") . ' ' . $year;
add_first_row($totalfem, $title_row);
add_first_col($totalfem, $cat_first_col);
echo heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $totalfem,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################
$header = $this->lang->line("gvv_vols_planeur_header_club") . ' ' . $year;
add_first_row($totalclub, $title_row);
add_first_col($totalclub, $cat_first_col);
echo '</div>';
echo '<div id="tabs-6">' . heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $totalclub,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

// ###############################################################################
$header = $this->lang->line("gvv_vols_planeur_header_private") . ' ' . $year;
add_first_row($totalpriv, $title_row);
add_first_col($totalpriv, $cat_first_col);
echo br();
echo heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $totalpriv,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

echo '</div>';

// ###############################################################################
// ====================================================================================
$header = $this->lang->line("gvv_vols_planeur_header_hours") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_per_glider");
add_first_row($per_machine, $pm_first_row);
echo '<div id="tabs-7">' . heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $per_machine,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

add_first_row($flights_per_machine, $pm_first_row);
echo br();
echo br() . hr();
$header = $this->lang->line("gvv_vols_planeur_header_flights") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_per_glider");
echo heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $flights_per_machine,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

echo br();
echo br() . hr();

$header = $this->lang->line("gvv_vols_planeur_header_engine") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_per_glider");
add_first_row($motor_per_machine, $pm_first_row);
echo heading($header, 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $motor_per_machine,
        'controller' => '',
        'class' => "datatable_style fixed_datatable",
        'create' => "",
        'first' => 0,
        'align' => array (
                'left',
                'left',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right',
                'right'
        )
));
$table->display();

echo br();
echo br() . hr();
$header = $this->lang->line("gvv_vols_planeur_header_hours") . ' ' . $year . ' ' . $this->lang->line("gvv_vols_planeur_header_per_glider");
echo heading($header, 4);
$filename = image_dir() . "planeur_machine_$year.png";
if (file_exists($filename))
    echo img($filename);

$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/export_per/$year/machine"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/pdf_machine/$year"
        ),
        array (
                'label' => "Génération",
                'url' => "$controller/statistic/true",
                "role" => 'ca'
        )
);
echo br() . button_bar4($bar);

echo '</div></div>';
echo '</div>'; // body
?>
