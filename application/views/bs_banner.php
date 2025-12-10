<!-- VIEW: application/views/bs_banner.php -->
<?php
// Check RAN mode status (config + admin rights)
$CI =& get_instance();
$CI->config->load('program');
$ran_mode_active = $CI->config->item('ran_mode_enabled') && $CI->dx_auth->is_role('admin');
?>

<header class="container-fluid p-3 bg-success text-white text-center">
    <!-- Ici on mettra la bannière -->
    <div id="header_left"></div>
    <h1 class="text-center header"><?= $this->config->item('nom_club') ?></h1>
    <div id="header_right"></div>
</header>

<?php if ($ran_mode_active): ?>
<div class="container-fluid p-2 bg-danger text-white text-center" style="position: sticky; top: 0; z-index: 1030; border-bottom: 3px solid #8b0000; box-shadow: 0 2px 4px rgba(0,0,0,0.2);">
    <strong style="font-size: 1.1em;">
        <i class="bi bi-exclamation-triangle-fill"></i>
        MODE RAN ACTIVÉ - SAISIE RÉTROSPECTIVE AVEC COMPENSATION AUTOMATIQUE
        <i class="bi bi-exclamation-triangle-fill"></i>
    </strong>
</div>
<?php endif; ?>