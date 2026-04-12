<?php

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('tableaux_de_bord');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : '');

echo heading($this->lang->line('db_title_freeze_date_edit'), 3);

if (!empty($active_section['nom'])) {
    echo '<div class="alert alert-info py-2">'
        . 'Section active : <strong>' . htmlspecialchars($active_section['nom']) . '</strong>'
        . '</div>';
}

echo form_open(controller_url($controller) . '/formValidation/' . $action, array('name' => 'saisie'));
echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}

if (isset($section)) {
    echo form_hidden('section', $section);
}

echo $this->gvvmetadata->form('clotures', array(
    'date' => isset($date) ? $date : '',
    'description' => isset($description) ? $description : ''
));

echo validation_button($action);
echo form_close();

echo '</div>';
