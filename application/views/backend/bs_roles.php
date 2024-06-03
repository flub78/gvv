	<?php
$this->load->view('bs_header');
$this->load->view('bs_banner');
$this->load->view('bs_menu');

echo "\n" . '<div id="body" class="body container-fluid">';

echo heading("Roles pour la gestion des autorisations", 3);
echo br ();

// Show reset password message if exist
if (isset ($reset_message))
    echo $reset_message;

// Show error
echo validation_errors();

// Build drop down menu
$options[0] = 'None';
foreach ($roles as $role) {
    $options[$role->id] = $role->name;
}

// Build table
$this->table->set_heading('', 'ID', 'Nom', 'ID Parent');

foreach ($roles as $role) {
    $this->table->add_row(form_checkbox('checkbox_' . $role->id, $role->id), $role->id, $role->name, $role->parent_id);
}

// Build form
echo form_open($this->uri->uri_string());

echo form_label('Role parent', 'role_parent_label') . nbs();
echo form_dropdown('role_parent', $options) . nbs();

echo form_label('Nom du Role', 'role_name_label') . nbs();
echo form_input('role_name', '') . nbs();

echo form_submit('add', 'Ajout role') . nbs();
echo form_submit('delete', 'Supprime role selectioné');

echo '<hr/>' . "\n";
echo "\n";

// Show table
$tmpl = array ( 'table_open'  => '<table cellpadding="2" cellspacing="1" class="datatable table">' );

$this->table->set_template($tmpl); 
echo $this->table->generate();

echo form_close();
echo '</div>';
?>