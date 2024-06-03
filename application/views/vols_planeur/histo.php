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
 * Vue cumuls pour les vols planeurs
 * 
 * @packages vues
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');

$this->lang->load('vols_planeur');

echo '<div id="body" class="body ui-widget-content">';

echo form_hidden('jsonurl', $jsonurl, '"id"="jsonurl"');
echo form_hidden('machines', $machines, '"id"="machines"');
echo form_hidden('title', $this->lang->line($title_key), '"id"="title"');
echo form_hidden('year', $year, '"id"="year"');
echo form_hidden('first_year', $first_year, '"id"="first_year"');

echo checkalert($this->session);

// echo heading($title, 3);


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
  

<div id="chartdiv" style="height:500px;width:800px; "></div>

<?php

// -----------------------------------------------------------------------------------------

echo '</div>';

?>
<script type="text/javascript" src="<?php echo js_url('jquery.jqplot'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.logAxisRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.barRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.categoryAxisRenderer'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.pointLabels'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.highlighter'); ?>"></script>
<script type="text/javascript" src="<?php echo js_url('plugins/jqplot.cursor'); ?>"></script>


<script type="text/javascript" src="<?php echo js_url('histo'); ?>"></script>

<link rel="stylesheet" type="text/css" href="<?php echo base_url() . 'assets/css/jquery.jqplot.css'; ?>"  />

