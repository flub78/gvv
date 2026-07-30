<!-- VIEW: application/views/motd/bs_formView.php -->
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
 * Formulaire de saisie d'un message du jour
 * @package vues
 */
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('motd');

echo '<div id="body" class="body container-fluid">';

if (isset($message)) {
    echo p($message) . br();
}
echo checkalert($this->session, isset($popup) ? $popup : "");

echo heading("motd_title", 3);

echo form_open(controller_url($controller) . "/formValidation/" . $action, array('name' => 'saisie'));

echo form_hidden('controller_url', controller_url($controller), '"id"="controller_url"');

if (isset($kid) && isset($$kid)) {
    echo form_hidden('original_' . $kid, $$kid);
}
if ($action != CREATION) {
    echo form_hidden('id', $id);
}

$title = isset($title) ? $title : '';
$content = isset($content) ? $content : '';
$level = isset($level) ? $level : 'info';
$start_date = isset($start_date) ? $start_date : '';
$end_date = isset($end_date) ? $end_date : '';
$target_type = isset($target_type) ? $target_type : 'all';
$target_list_id = isset($target_list_id) ? $target_list_id : '';
$target_user_login = isset($target_user_login) ? $target_user_login : '';
?>

<div class="mb-3">
    <?= $this->gvvmetadata->label('motd_messages', 'title') ?>
    <?= $this->gvvmetadata->input_field('motd_messages', 'title', $title) ?>
    <?= form_error('title') ?>
</div>

<div class="mb-3">
    <?= $this->gvvmetadata->label('motd_messages', 'content') ?>
    <div class="mb-1">
        <input type="file" id="motd_image_file" accept="image/png,image/jpeg,image/webp" class="form-control form-control-sm d-inline-block w-auto">
        <button type="button" id="motd_image_insert" class="btn btn-sm btn-outline-secondary">
            <?= $this->lang->line('motd_image_insert') ?>
        </button>
        <span id="motd_image_upload_message" class="text-danger"></span>
    </div>
    <?= $this->gvvmetadata->input_field('motd_messages', 'content', $content, "rw", array('rows' => 8, 'class' => 'form-control')) ?>
    <?= form_error('content') ?>
</div>

<div class="d-md-flex flex-row mb-3">
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->label('motd_messages', 'level') ?>
        <?= $this->gvvmetadata->input_field('motd_messages', 'level', $level) ?>
    </div>
</div>

<div class="d-md-flex flex-row mb-3">
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->label('motd_messages', 'start_date') ?>
        <?= $this->gvvmetadata->input_field('motd_messages', 'start_date', $start_date) ?>
        <?= form_error('start_date') ?>
    </div>
    <div class="me-3 mb-2">
        <?= $this->gvvmetadata->label('motd_messages', 'end_date') ?>
        <?= $this->gvvmetadata->input_field('motd_messages', 'end_date', $end_date) ?>
        <?= form_error('end_date') ?>
    </div>
</div>

<div class="mb-2">
    <?= $this->gvvmetadata->label('motd_messages', 'target_type') ?>
    <?= $this->gvvmetadata->input_field('motd_messages', 'target_type', $target_type) ?>
    <?= form_error('target_type') ?>
</div>

<div class="d-md-flex flex-row mb-3">
    <div class="me-3 mb-2" id="motd_target_list_wrapper">
        <?= $this->gvvmetadata->label('motd_messages', 'target_list_id') ?>
        <?= $this->gvvmetadata->input_field('motd_messages', 'target_list_id', $target_list_id) ?>
    </div>
    <div class="me-3 mb-2" id="motd_target_user_wrapper">
        <?= $this->gvvmetadata->label('motd_messages', 'target_user_login') ?>
        <?= $this->gvvmetadata->input_field('motd_messages', 'target_user_login', $target_user_login) ?>
    </div>
</div>

<?php
echo validation_button($action);
echo form_close();

echo '</div>';

echo html_script(array('type' => "text/javascript", 'src' => js_url('motd')));
?>
<script>
$(function() {
    motd_target_changed();
    $('input[name=target_type]').change(motd_target_changed);
    motd_init_image_upload('<?= controller_url('motd') ?>/upload_image', <?= json_encode($this->lang->line('motd_error_action_failed')) ?>);
});
</script>
