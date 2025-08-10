<?php

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('openflyers');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("gvv_associations_ecriture_title_association", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie')); 

// hidden controller url for javascript access
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

// Form fields
echo ($this->gvvmetadata->form('associations_ecriture', array(
    'string_releve' => $string_releve,
    'id_compte_gvv' => $id_compte_gvv
)));

echo validation_button($action);
echo form_close();

echo '</div>';