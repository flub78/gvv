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

echo heading($this->lang->line("gvv_vols_planeur_title_per_pilot"), 3);

// echo year_selector($controller, $year, $year_selector);
// echo br(2);
?>
<div id="tabs">
	<ul>
		<li><a href="#tabs-1"><?php echo $this->lang->line("gvv_vols_planeur_tab_per_glider")?></a></li>
		<li><a href="#tabs-2"><?php echo $this->lang->line("gvv_vols_planeur_tab_solo_per_glider")?></a></li>
		<li><a href="#tabs-3"><?php echo $this->lang->line("gvv_vols_planeur_tab_yearly_hours")?></a></li>
		<li><a href="#tabs-4"><?php echo $this->lang->line("gvv_vols_planeur_tab_yearly_flights")?></a></li>
		<li><a href="#tabs-5"><?php echo $this->lang->line("gvv_vols_planeur_tab_dual")?></a></li>
		<li><a href="#tabs-6"><?php echo $this->lang->line("gvv_vols_planeur_tab_solo")?></a></li>
	</ul>

<?php
function align_array($data) {
    $count = count($data [0]);
    $res = array (
            'left'
    );
    for($i = 0; $i < $count; $i ++) {
        $res [] = 'right';
    }
    return $res;
}

echo '<div id="tabs-1">' . heading($this->lang->line("gvv_vols_planeur_title_yearly_machine"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $total,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($total)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/total"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/total"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

echo '<div id="tabs-2">' . heading($this->lang->line("gvv_vols_planeur_title_solo_machine"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $total_solo,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($total)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/total_solo"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/total_solo"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

echo '<div id="tabs-3">' . heading($this->lang->line("gvv_vols_planeur_title_yearly_hours"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $hours_per_year,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($hours_per_year)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/hours_per_year"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/hours_per_year"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

echo '<div id="tabs-4">' . heading($this->lang->line("gvv_vols_planeur_title_yearly_flights"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $flights_per_year,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($flights_per_year)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/flights_per_year"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/flights_per_year"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

echo '<div id="tabs-5">' . heading($this->lang->line("gvv_vols_planeur_title_yearly_dual"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $double_per_year,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($double_per_year)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/double_per_year"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/double_per_year"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

echo '<div id="tabs-6">' . heading($this->lang->line("gvv_vols_planeur_title_yearly_solo"), 4);
$table = new DataTable(array (
        'title' => "",
        'values' => $solo_per_year,
        'controller' => '',
        'class' => "datatable",
        'create' => "",
        'first' => 0,
        'align' => align_array($solo_per_year)
));
$table->display();
$bar = array (
        array (
                'label' => "Excel",
                'url' => "$controller/par_pilote_machine/csv/solo_per_year"
        ),
        array (
                'label' => "Pdf",
                'url' => "$controller/par_pilote_machine/pdf/solo_per_year"
        )
);
echo br() . button_bar4($bar);
echo '</div>';

// ====================================================================================

echo '</div></div>';
echo '</div>'; // body

?>
