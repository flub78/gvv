<!-- VIEW: application/views/event/bs_formView.php -->
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
 * Formulaire de saisie d'un événement
 * ----------------------------------------------------------------------------------------
 */
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('events');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");
echo validation_errors();

echo heading("gvv_events_title", 3);

// ------------------------------------------------------------------------
echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));
// hidden controller url for java script access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');
echo form_hidden('emlogin', $emlogin, '');
echo form_hidden('id', $id, '');

// Add hidden field for original ID (required for MODIFICATION to work with race condition fix)
if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

if (($evaid != 0) && ($evaid != null)) {
    $event_type_flight = "avion";
    $display_plane = "block";
    $display_glider = "none";
} else if (($evpid != 0) && ($evpid != null)) {
    $event_type_flight = "planeur";
    $display_plane = "none";
    $display_glider = "block";
} else {
    $event_type_flight = "aucun";
    $display_plane = "none";
    $display_glider = "none";
}

$data = array(
    'name' => 'ecomment',
    'cols' => 64,
    'rows' => 2,
    'value' => $ecomment,
    'maxlength' => 128
);
?>

<div class="d-md-flex flex-row">
    <div class="me-3 mb-3">
        <h4><?= $this->lang->line("gvv_events_field_emlogin") . " "  . $mimage ?><h4>
    </div>
</div>
<div class="d-md-flex flex-row">
    <div class="me-3 mb-3">
        <?= $this->lang->line("gvv_events_title_event") . ": "  ?>
        <?= dropdown_field('etype', $etype, $event_type_selector, "") ?>
    </div>

    <div class="me-3 mb-3">
        <?= $this->lang->line("gvv_events_short_field_date") . ": "  ?>
        <?= $this->gvvmetadata->input_field('events', 'edate', $edate) ?>
    </div>

    <div class="me-3 mb-3 d-md-flex flex-row">
        <div class="me-3 mb-3">
            <?= $this->lang->line("gvv_events_field_event_type") . ": "  ?>
        </div>
        <div class="me-3 mb-3">
            <?= dropdown_field(
                'event_type_flight',
                $event_type_flight,
                $this->lang->line("gvv_events_type_selector"),
                "onchange=get_plane_selector(this);"
            ) ?>
        </div>

        <div class="me-3 mb-3">
            <?= dropdown_field('evpid', $evpid, $planeurs_selector, "id='dropdown_planeurs' style='display:" . $display_glider . "'") .
                dropdown_field('evaid', $evaid, $avions_selector, "id='dropdown_avions' style='display:" . $display_plane . "'") ?>
        </div>
    </div>
</div>
<div class="d-md-flex flex-row">

    <div class="me-3 mb-3">
        <?= $this->lang->line("gvv_events_field_date_expiration") . ": "  ?>
        <?= $this->gvvmetadata->input_field('events', 'date_expiration', $date_expiration) ?>
    </div>
</div>
<div class="d-md-flex flex-row">

    <div class="me-3 mb-3">
        <?= $this->lang->line("gvv_events_short_field_comment") . ": "  ?>
        <?= form_textarea($data) ?>
    </div>

</div>

<?php


echo validation_button($action);
echo form_close();

echo '</div>';

?>