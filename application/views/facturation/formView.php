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
 * Vue de configuration de la facturation
 * 
 */

$this->load->view('header');
$this->load->view('banner');
$this->load->view('sidebar');
$this->load->view('menu');
$this->lang->load('facturation');

echo '<div id="body" class="body ui-widget-content">';

echo heading($this->lang->line("gvv_facturation_title"), 3);

if (isset ($popup))
    echo checkalert($this->session, $popup);

if (isset ($text)) {
    // echo "<center>\n";
    echo (p($text));
    // echo "</center>";
}

echo form_open(controller_url($controller) . "/formValidation/" . $action, array (
    'name' => 'saisie'
));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// echo validation_errors();
echo ($this->gvvmetadata->form('facturation', array (
    'payeur_non_pilote' => $payeur_non_pilote,
    'partage' => $partage,
    'gestion_pompes' => $gestion_pompes,
	'remorque_100eme' => $remorque_100eme,
    'date_gel' => $date_gel
)));

echo validation_button($action);
echo form_close();
?>
</div>