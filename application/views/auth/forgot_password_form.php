<?php
$this->load->view('header');
$this->load->view('banner');

$this->lang->load('auth');

echo '<div id="body" class="body ui-widget-content">';

$login = array(
	'name'	=> 'login',
	'id'	=> 'login',
	'maxlength'	=> 80,
	'size'	=> 30,
	'value' => set_value('login')
);

?>

<fieldset><legend accesskey="D" tabindex="1"><?=$this->lang->line("auth_forgoten_password") ?></legend>
<?php echo form_open($this->uri->uri_string()); ?>

<?php echo $this->dx_auth->get_auth_error(); ?>

<dl>
	<dt><?php echo form_label($this->lang->line("auth_enter_id"), $login['id']);?></dt>
	<dd>
		<?php echo form_input($login); ?> 
		<?php echo form_error($login['name']); ?>
		<?php echo form_submit('reset', $this->lang->line("gvv_button_validate")); ?>
	</dd>
</dl>

<?php echo form_close()?>
</fieldset>
</div>
