<!-- VIEW: application/views/auth/bs_change_password_form.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

$this->lang->load('auth');

echo '<div id="body" class="body container-fluid d-flex justify-content-center p-5">';

// echo heading("Changement de mot de passe", 3);

$old_password = array(
	'name'	=> 'old_password',
	'id'		=> 'old_password',
	'size' 	=> 30,
	'value' => set_value('old_password')
);

$new_password = array(
	'name'	=> 'new_password',
	'id'		=> 'new_password',
	'size'	=> 30
);

$confirm_new_password = array(
	'name'	=> 'confirm_new_password',
	'id'		=> 'confirm_new_password',
	'size' 	=> 30
);

?>

<fieldset>
<legend><?php echo $this->lang->line("auth_password_change"); ?></legend>
<?php echo form_open($this->uri->uri_string()); ?>

<?php echo $this->dx_auth->get_auth_error();
if ($duplicate) {
	echo '<div class="error">' . $this->lang->line("gvv_error_weak_password") . '</div>';
}
?>

<dl>
	<dt><?php echo form_label($this->lang->line("auth_previous_password"), $old_password['id']); ?></dt>
	<dd>
		<?php echo form_password($old_password); ?>
		<?php echo form_error($old_password['name']); ?>
	</dd>

	<dt><?php echo form_label($this->lang->line("auth_new_password"), $new_password['id']); ?></dt>
	<dd>
		<?php echo form_password($new_password); ?>
		<?php echo form_error($new_password['name']); ?>
	</dd>

	<dt><?php echo form_label($this->lang->line("auth_confirm_password"), $confirm_new_password['id']); ?></dt>
	<dd>
		<?php echo form_password($confirm_new_password); ?>
		<?php echo form_error($confirm_new_password['name']); ?>
	</dd>

	<dt></dt>
	<dd><?php echo form_submit('change', $this->lang->line("gvv_button_validate")); ?></dd>
</dl>

<?php echo form_close(); ?>
</fieldset>
</div>

