<!-- VIEW: application/views/auth/bs_forgot_password_form.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_banner');

$this->lang->load('auth');

$forgot_state = isset($forgot_state) ? $forgot_state : NULL;
$wait_seconds = isset($wait_seconds) ? (int)$wait_seconds : 0;
?>

<div id="body" class="body container-fluid d-flex justify-content-center p-5">
<div class="col-md-5">

<h3><?= $this->lang->line("auth_forgoten_password") ?></h3>

<?php if (!empty($auth_error)): ?>
    <div id="wait-alert" class="alert alert-<?= $forgot_state === 'too_soon' ? 'warning' : 'info' ?>">
        <?= htmlspecialchars($auth_error) ?>
    </div>
<?php endif; ?>

<?php echo form_open($this->uri->uri_string()); ?>

<div class="mb-3">
    <label class="form-label" for="login"><?= $this->lang->line("auth_enter_id") ?></label>
    <?php echo form_input(array(
        'name'      => 'login',
        'id'        => 'login',
        'class'     => 'form-control',
        'maxlength' => 80,
        'value'     => set_value('login')
    )); ?>
    <?php echo form_error('login', '<div class="text-danger">', '</div>'); ?>
</div>

<?php if ($forgot_state === 'resend_available'): ?>
    <?php echo form_submit(array('name' => 'resend_email', 'value' => $this->lang->line('auth_resend_email'), 'class' => 'btn btn-warning')); ?>
<?php elseif ($forgot_state === 'too_soon'): ?>
    <?php echo form_submit(array('name' => 'reset', 'id' => 'btn-validate', 'value' => $this->lang->line('gvv_button_validate'), 'class' => 'btn btn-primary', 'disabled' => 'disabled')); ?>
<?php else: ?>
    <?php echo form_submit(array('name' => 'reset', 'value' => $this->lang->line('gvv_button_validate'), 'class' => 'btn btn-primary')); ?>
<?php endif; ?>

<?php echo form_close() ?>

</div>
</div>

<?php if ($forgot_state === 'too_soon' && $wait_seconds > 0): ?>
<script>
(function() {
    var remaining  = <?= $wait_seconds ?>;
    var msgEl      = document.getElementById('wait-alert');
    var btnEl      = document.getElementById('btn-validate');
    var template   = <?= json_encode($this->lang->line('auth_request_too_soon')) ?>;

    function tick() {
        if (remaining <= 0) {
            btnEl.disabled = false;
            msgEl.className = 'alert alert-success';
            msgEl.textContent = <?= json_encode($this->lang->line('auth_request_ready')) ?>;
            return;
        }
        msgEl.textContent = template.replace('%d', remaining);
        remaining--;
        setTimeout(tick, 1000);
    }

    tick();
})();
</script>
<?php endif; ?>
