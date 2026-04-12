<?php

$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('tableaux_de_bord');

echo '<div id="body" class="body container-fluid">';

echo heading($this->lang->line('db_title_freeze_date_edit'), 3);

if (!empty($active_section['nom'])) {
    echo '<p class="text-muted">Section active : <strong>' . htmlspecialchars($active_section['nom']) . '</strong></p>';
}

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

$attrs = array(
    'controller' => $controller,
    'actions' => array('edit', 'delete'),
    'fields' => array('date', 'description'),
    'mode' => ($has_modification_rights) ? 'rw' : 'ro',
    'class' => 'datatable table table-striped'
);

echo '<div class="mb-3">'
    . '<a href="' . site_url('dates_gel/create') . '" class="btn btn-sm btn-success">'
    . '<i class="fas fa-plus" aria-hidden="true"></i> '
    . $this->lang->line('gvv_button_create')
    . '</a>'
    . '</div>';

echo $this->gvvmetadata->table('vue_clotures', $attrs, '');

echo '</div>';
