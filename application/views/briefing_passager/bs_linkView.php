<!-- VIEW: application/views/briefing_passager/bs_linkView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');

// Generate QR code to a temp file
$qr_file = sys_get_temp_dir() . '/bp_qr_' . md5($sign_url) . '.png';
if (!file_exists($qr_file)) {
    include_once(APPPATH . '/third_party/phpqrcode/qrlib.php');
    QRcode::png($sign_url, $qr_file, QR_ECLEVEL_L, 8, 2);
}
$qr_base64 = base64_encode(file_get_contents($qr_file));
?>

<div id="body" class="body container-fluid">

<h3><i class="fas fa-qrcode"></i> <?= $this->lang->line('briefing_passager_link_title') ?></h3>

<?= $message ?>

<div class="alert alert-success">
    <i class="fas fa-check-circle"></i> <?= $this->lang->line('briefing_passager_link_generated') ?>
</div>

<div class="row">
    <div class="col-md-4 text-center mb-4">
        <a href="<?= htmlspecialchars($sign_url) ?>" target="_blank" class="d-inline-block text-decoration-none">
            <img src="data:image/png;base64,<?= $qr_base64 ?>" alt="QR Code" class="img-fluid" style="max-width:250px;">
            <p class="mt-2 text-muted small"><?= $this->lang->line('briefing_passager_sign_scan_qr') ?><br><i class="fas fa-mouse-pointer"></i> <?= $this->lang->line('briefing_passager_link_open') ?></p>
        </a>
    </div>
    <div class="col-md-8">
        <?php if ($vld): ?>
        <div class="card mb-3">
            <div class="card-header"><i class="fas fa-plane"></i> <?= $this->lang->line('briefing_passager_field_vld') ?> #<?= htmlspecialchars($vld['id']) ?></div>
            <div class="card-body row">
                <div class="col-md-4">
                    <strong><?= $this->lang->line('briefing_passager_field_nom') ?> :</strong>
                    <?= htmlspecialchars($vld['beneficiaire'] ?? '—') ?>
                </div>
                <div class="col-md-4">
                    <strong><?= $this->lang->line('briefing_passager_field_date_vol') ?> :</strong>
                    <?= $vld['date_vol'] ? date_db2ht($vld['date_vol']) : '—' ?>
                </div>
                <div class="col-md-4">
                    <strong><?= $this->lang->line('briefing_passager_field_appareil') ?> :</strong>
                    <?= htmlspecialchars($vld['airplane_immat'] ?? '—') ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <div class="mb-3">
            <label class="form-label fw-bold"><?= $this->lang->line('briefing_passager_link_title') ?></label>
            <div class="input-group">
                <input type="text" id="sign_url" class="form-control" value="<?= htmlspecialchars($sign_url) ?>" readonly>
                <button class="btn btn-outline-secondary" type="button" onclick="copyLink()">
                    <i class="fas fa-copy"></i> <?= $this->lang->line('briefing_passager_link_copy') ?>
                </button>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="<?= site_url('briefing_passager/upload/' . $vld_id) ?>" class="btn btn-outline-secondary">
                <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_button_cancel') ?>
            </a>
        </div>
    </div>
</div>

</div><!-- /body -->

<script>
function copyLink() {
    var input = document.getElementById('sign_url');
    input.select();
    document.execCommand('copy');
    input.blur();
}
</script>
