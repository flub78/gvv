<!-- VIEW: application/views/pompes/bs_formView.php -->
<?php

/**
 * 
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
 *    Formulaire de saisie d'un mouvement à la pompe
 *    @package vues
 */

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';
$tabpomp= array (0=> "<B>100LL</B>", 1=>"<B>ULM</B>");

if ($action == CREATION) {
    echo heading("Saisie d'un mouvement à la pompe ".$tabpomp[$pnum], 3);
}
elseif ($action == MODIFICATION) {
    echo heading("Modification d'un mouvement à la pompe ".$tabpomp[$pnum], 3);
} else {
    echo heading("Visualisation d'un mouvement à la pompe ".$tabpomp[$pnum], 3);
}

if (isset ($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset ($popup) ? $popup : "");
echo validation_errors();

echo form_open(controller_url($controller) . "/formValidation/" . $action, array (
    'name' => 'saisie'
));

// hidden contrller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('pid', $pid); 
echo form_hidden('pnum', $pnum); // 0: 100LL (défaut), 1: 98SP
echo form_hidden('pdatesaisie', date('d/m/Y')); // date de la saisie
echo form_hidden('psaisipar', $saisie_par); // date de la saisie

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

$tabs = nbs(3);
$pompes_fields = array(
    array($this->gvvmetadata->field_long_name("pompes", "pdatemvt"), $this->gvvmetadata->input_field("pompes", 'pdatemvt', $pdatemvt)),
    array($this->gvvmetadata->field_name("pompes", "ppilid"), $this->gvvmetadata->input_field("pompes", 'ppilid', $ppilid)),
    array($this->gvvmetadata->field_name("pompes", "pmacid"), $this->gvvmetadata->input_field("pompes", 'pmacid', $pmacid)),
    array($this->gvvmetadata->field_name("pompes", "ptype"), $this->gvvmetadata->input_field("pompes", 'ptype', $ptype)),
    array($this->gvvmetadata->field_name("pompes", "ppu"), $this->gvvmetadata->input_field("pompes", 'ppu', $ppu) . $tabs . "(Euros/Litre)"),
    array($this->gvvmetadata->field_long_name("pompes", "pqte"), $this->gvvmetadata->input_field("pompes", 'pqte', $pqte) . $tabs . "(en Litres)"),
    array($this->gvvmetadata->field_name("pompes", "pprix"), $this->gvvmetadata->input_field("pompes", 'pprix', $pprix) . $tabs . "(euros)"),
    array($this->gvvmetadata->field_long_name("pompes", "pdesc"), $this->gvvmetadata->input_field("pompes", 'pdesc', $pdesc)),
);
foreach ($pompes_fields as $field) {
    echo '<div class="form-group row mb-3">';
    echo '<label class="col-sm-3 col-form-label">' . $field[0] . ':</label>';
    echo '<div class="col-sm-9">' . $field[1] . '</div>';
    echo '</div>';
}



/*
echo ($this->gvvmetadata->form('pompes', array (
    'pdatemvt' => $pdatemvt,
    'ppilid' => $ppilid,
    'pmacid' => $pmacid,
    'ptype' => $ptype,
    'pqte' => $pqte,
    'ppu' => $ppu,
    'pprix' => $pprix,
    'pdesc' => $pdesc
)));
*/
echo validation_button($action);
echo form_close();

echo '</div>';
?>
