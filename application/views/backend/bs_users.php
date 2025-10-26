<!-- VIEW: application/views/backend/bs_users.php -->
<html>
<head>
<title>Manage users</title>
</head>
<body>
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');

$this->load->view('bs_banner');
$this->lang->load('backend');

$this->load->library('ButtonNew');
$this->load->library('ButtonDelete');

echo '<div id="body" class="body container-fluid">';

// Show success message
if ($this->session->flashdata('success')) {
    echo '<div class="alert alert-success alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-check-circle"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('success')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show error message
if ($this->session->flashdata('error')) {
    echo '<div class="alert alert-danger alert-dismissible fade show" role="alert">';
    echo '<strong><i class="bi bi-exclamation-triangle-fill"></i></strong> ';
    echo nl2br(htmlspecialchars($this->session->flashdata('error')));
    echo '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
    echo '</div>';
}

// Show reset password message if exist
if (isset($reset_message))
echo $reset_message;

// Show error
echo validation_errors();

$create = new ButtonNew(array(
	'controller' => 'backend',
	'param' => ''));

$header = $this->lang->line("gvv_backend_header");
$header[] = '';
$header[] = $create->image();
$this->table->set_heading($header);

foreach ($users as $user)
{
	$delete_button = new ButtonDelete(array(
				'controller' => 'backend',
				'confirmMsg' => $this->lang->line("gvv_backend_delete_confirm") . " " . $user->username . " ",
				'param' => $user->id));

	$edit_button = new ButtonEdit(array(
				'controller' => 'backend',
			    'param' => $user->id));
	
	$banned = ($user->banned == 1) ? $this->lang->line("gvv_backend_yes") : $this->lang->line("gvv_backend_no");

	$this->table->add_row(
	form_checkbox('checkbox_'.$user->id, 'accept', $user->id),
	$user->username,
	$user->email,
	$user->role_name,
	$banned,
	$user->last_ip,
	date('Y-m-d', strtotime($user->last_login)),
	date('Y-m-d', strtotime($user->created)),
	$edit_button->image(),
	$delete_button->image());
}

echo form_open($this->uri->uri_string());        // backend/users

// echo form_submit('ban', 'Désactive');
// echo form_submit('unban', 'Réactive');
// echo form_submit('reset_pass', 'Reset password');

// echo '<hr/>';

$datatable = ($this->config->item('ajax')) ? "datatable" : "fixed_datatable";
$datatable .= " table table-striped";
$tmpl = array (
	'table_open'=> '<table border="1" cellpadding="4" cellspacing="0" class="' . $datatable . '">',
	'row_start'           => '<tr style="color:black">',
	'row_alt_start'       => '<tr style="color:black">'
);
$this->table->set_template($tmpl);
if (isset($pagination)) echo $pagination;
echo $this->table->generate();



echo form_close();

echo '</div>';
?>
</body>
</html>
