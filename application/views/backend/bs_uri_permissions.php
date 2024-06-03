<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');

$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid m-3">';

// Build drop down menu
foreach ($roles as $role) {
    $options[$role->id] = $role->name;
}

// Change allowed uri to string to be inserted in text area
if (!empty ($allowed_uris)) {
    $allowed_uris = implode("\n", $allowed_uris);
}

// Build form
echo form_open($this->uri->uri_string());

echo form_label('Role', 'role_name_label') . nbs();
echo form_dropdown('role', $options) . nbs();
echo form_submit('show', "Afficher les permissions d'URI") .nbs();

echo form_label('', 'uri_label');

echo '<hr/>';

echo 'URI autorisées (Une URI par ligne) :<br/><br/>';

echo "Entrez '/' pour permettre l'accès à toutes les URIs.<br/>";
echo "Entrez '/controller/' pour permettre l'accès au controleur et à ses fonctions.<br/>";
echo "Entrez '/controller/function/' pour permettre l'accès à la fonction seulement.<br/><br/>";
echo "Ces règles n'ont d'effet que si vous utilisez check_uri_permissions() dans vos controleurs. C'est la cas pour GVV.<br/><br/>";

echo form_textarea('allowed_uris', $allowed_uris);

echo '<br/>';
echo form_submit('save', 'Sauvegarde des permissions URI');

echo form_close();
echo '</div>';
?>