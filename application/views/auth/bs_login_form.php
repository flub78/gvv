<!-- VIEW: application/views/auth/bs_login_form.php -->
<?php
// Set defaults if variables are not defined
$remembered_username = isset($remembered_username) ? $remembered_username : '';
$remembered_section = isset($remembered_section) ? $remembered_section : 1;
$remember_me_checked = isset($remember_me_checked) ? $remember_me_checked : false;

$username = array(
	'name'	=> 'username',
	'id'	=> 'username',
	'size'	=> 30,
	'value' => set_value('username', $remembered_username)
);

$password = array(
	'name'	=> 'password',
	'id'	=> 'password',
	'size'	=> 30
);

$remember = array(
	'name'	=> 'remember',
	'id'	=> 'remember',
	'value'	=> 1,
	'checked'	=> set_value('remember', $remember_me_checked),
	'style' => 'margin:0;padding:0'
);

$confirmation_code = array(
	'name'	=> 'captcha',
	'id'	=> 'captcha',
	'maxlength'	=> 8
);

$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

$this->lang->load('auth');

echo '<div id="body" class="body container-fluid  d-flex justify-content-center p-5">';

?>


<?php echo form_open($this->uri->uri_string()) ?>

<?php
// Display dx_auth errors (incorrect password, etc.)
$auth_error = $this->dx_auth->get_auth_error();
if ($auth_error) {
	echo '<p class="error">' . $auth_error . '</p>';
}

// Display URL parameter errors (authorization denial after logout/redirect)
$error_code = $this->input->get('error');
if ($error_code === 'no_user_role') {
	echo '<p class="error">' . $this->lang->line('auth_no_user_role') . '</p>';
}
?>

<?php
if ($locked) {
	echo p($this->lang->line("auth_locked"), 'class="error"');
	echo p($this->lang->line("auth_come_back"));
}
?>
<dl>
	<dt><?php echo form_label($this->lang->line("auth_user"), $username['id']); ?></dt>
	<dd>
		<?php echo form_input($username) ?>
		<?php echo form_error($username['name']); ?>
	</dd>

	<dt><?php echo form_label($this->lang->line("auth_password"), $password['id']); ?></dt>
	<dd>
		<?php echo form_password($password) ?>
		<?php echo form_error($password['name']); ?>
	</dd>


	<?php if ($section_count > 1) : ?>
		<dt><?php echo form_label($this->lang->line("gvv_sections_element"), 'section'); ?></dt>
		<dd>
			<?= dropdown_field('section', set_value('section', $remembered_section), $sections_selector, 'class="form-control" id="section" ') ?>
			<?php echo form_error('section'); ?>
		</dd>
	<?php endif; ?>

	<?php if ($show_captcha): ?>

		<dt><?= $this->lang->line("auth_enter_code") ?></dt>
		<dd><?php echo $this->dx_auth->get_captcha_image(); ?></dd>

		<dt><?php echo form_label($this->lang->line("auth_confirmation_code"), $confirmation_code['id']); ?></dt>
		<dd>
			<?php echo form_input($confirmation_code); ?>
			<?php echo form_error($confirmation_code['name']); ?>
		</dd>

	<?php endif; ?>

	<dt></dt>
	<dd>
		<?php echo form_checkbox($remember); ?> 
		<?php echo form_label($this->lang->line("auth_remember_user_section"), $remember['id']); ?> 
		<br>
		<?php echo anchor($this->dx_auth->forgot_password_uri, $this->lang->line("auth_forgoten_password")); ?>
		<?php
		if ($this->dx_auth->allow_registration) {
			echo anchor($this->dx_auth->register_uri, 'Register');
		};
		?>
	</dd>

	<dt></dt>
	<dd><?php echo form_submit('login', $this->lang->line("auth_login")); ?></dd>
</dl>

<?php

echo br(2);
// Si il existe testadmin ou testuser afficher l'information
// Si ils existent mais qu'il y a d'autres utilisateurs afficher un warning de sécurité.
// echo "Après installation, vous pouvez vous connecter en utilisant testadmin/testadmin ou testuser/testuser";
echo form_close()
?>
</div>