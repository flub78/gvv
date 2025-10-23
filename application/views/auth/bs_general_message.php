<!-- VIEW: application/views/auth/bs_general_message.php -->
<?php 
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');

echo '<div id="body" class="body container-fluid">';

echo $auth_message;
echo "</div>";
?>
