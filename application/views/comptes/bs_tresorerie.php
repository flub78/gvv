<!-- VIEW: application/views/comptes/bs_tresorerie.php -->
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
 * Vue cumuls de trésorerie
 * 
 * @packages vues
 */

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

echo '<div id="body" class="body container-fluid">';


echo form_hidden('year', $year, '"id"="year"');
echo form_hidden('jsonurl', $jsonurl, '"id"="jsonurl"');

echo checkalert($this->session);

echo heading($title, 3);

echo year_selector($controller, $year, $year_selector);

// -----------------------------------------------------------------------------------------
// Elements table

$ajax = $this->config->item('ajax');

echo br(2);

?>
<style type="text/css">
    
    .note {
        font-size: 0.8em;
    }
    .jqplot-yaxis-tick {
      white-space: nowrap;
    }
  </style>
  

<div id="chartdiv" style="height:500px;width:1000px; "></div>

<?php

// -----------------------------------------------------------------------------------------

$bar = array(
    array('label' => "Excel", 'url' =>"$controller/csv/$year"),
    array('label' => "Pdf", 'url' =>"$controller/pdf/$year"),
    );
$bar = array();
echo br() . button_bar4($bar);

echo '</div>';

?>
<script type="text/javascript" src="<?php echo js_url('jquery.jqplot'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.logAxisRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.barRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.categoryAxisRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.pointLabels'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.highlighter'); ?>"></script>


<script type="text/javascript" src="<?php echo js_url('tresorerie'); ?>"></script>

<link rel="stylesheet" type="text/css" href="<?php echo base_url() . 'assets/css/jquery.jqplot.css'; ?>"  />

