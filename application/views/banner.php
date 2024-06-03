<body  class="ui-widget-content">

<header>

<div class="ui-widget">
<div id="header" class="header ui-widget-header ui-corner-top">

<!-- Ici on mettra la bannière -->
<?php
$title = $this->config->item('nom_club');
$CI =& get_instance();

echo form_open(controller_url("auth/logout")) . "\n";

echo heading($title, 1);

$gvv_user = $CI->dx_auth->get_username();
$gvv_role = $CI->dx_auth->get_role_name();

if (strlen($gvv_user) > 1) {
	// if someone is logged in
    echo '<div id="login">' . $gvv_user . ' ' . $gvv_role . nbs() .
    	form_input(array('type' => 'submit', 'value' => $this->lang->line("gvv_button_exit"))) .
    "</div>";
    echo form_hidden('gvv_user', $gvv_user);
    echo form_hidden('gvv_role', $gvv_role);
}

echo form_close();

?>

</div></div>
</header>
