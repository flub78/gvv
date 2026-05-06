<!-- VIEW: application/views/auth/bs_reset_password_form.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('auth');
?>

<div id="body" class="body container-fluid d-flex justify-content-center p-5">
<div class="col-md-6">

<h3><?php echo $this->lang->line('auth_password_reset') ?: 'Réinitialisation du mot de passe'; ?></h3>

<?php echo $this->dx_auth->get_auth_error(); ?>
<?php echo validation_errors('<div class="text-danger">', '</div>'); ?>

<?php echo form_open($this->uri->uri_string()); ?>
<?php echo form_hidden('username', $username); ?>
<?php echo form_hidden('key', $key); ?>

<div class="mb-3">
    <label class="form-label" for="new_password"><?php echo $this->lang->line('auth_new_password'); ?></label>
    <?php echo form_password(['name' => 'new_password', 'id' => 'new_password', 'class' => 'form-control', 'value' => '']); ?>
    <?php echo form_error('new_password', '<div class="text-danger">', '</div>'); ?>
</div>

<div class="mb-3">
    <label class="form-label" for="confirm_new_password"><?php echo $this->lang->line('auth_confirm_password'); ?></label>
    <?php echo form_password(['name' => 'confirm_new_password', 'id' => 'confirm_new_password', 'class' => 'form-control', 'value' => '']); ?>
    <?php echo form_error('confirm_new_password', '<div class="text-danger">', '</div>'); ?>
</div>

<?php echo form_submit(['name' => 'submit_new_password', 'value' => $this->lang->line('gvv_button_validate'), 'class' => 'btn btn-primary']); ?>

<?php echo form_close(); ?>

</div>
</div>
