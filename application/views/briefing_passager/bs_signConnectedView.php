<!-- VIEW: application/views/briefing_passager/bs_signConnectedView.php -->
<?php
$this->load->view('bs_header');
$this->load->view('bs_menu');
$this->load->view('bs_banner');
$this->lang->load('briefing_passager');
?>

<style>
    #signature-pad { border: 1px solid #dee2e6; border-radius: 4px; background: #fff; touch-action: none; aspect-ratio: 2 / 1; width: 100%; height: auto; }
    .section-title { border-left: 4px solid #0d6efd; padding-left: 0.75rem; margin: 1.5rem 0 1rem; }
</style>

<div id="body" class="body container-fluid">

<?= $message ?>

<h3><?= $this->lang->line('briefing_passager_sign_title') ?></h3>

<div class="row g-4">

    <!-- ── Colonne gauche : QR code pour transfert sur téléphone ──────────── -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body text-center d-flex flex-column align-items-center justify-content-center p-4">
                <p class="fw-bold mb-3"><?= $this->lang->line('briefing_passager_sign_scan_qr') ?></p>
                <a href="<?= htmlspecialchars($sign_url) ?>" target="_blank" class="d-inline-block text-decoration-none">
                    <img src="data:image/png;base64,<?= $qr_base64 ?>" alt="QR Code" class="img-fluid" style="max-width:220px;">
                </a>
                <p class="text-muted small mt-3 mb-0"><?= $this->lang->line('briefing_passager_sign_qr_help') ?></p>
                <div class="input-group input-group-sm mt-3">
                    <input type="text" id="sign_url_field" class="form-control form-control-sm"
                           value="<?= htmlspecialchars($sign_url) ?>" readonly>
                    <button class="btn btn-outline-secondary" type="button" onclick="copySignUrl()" title="Copier">
                        <i class="fas fa-copy"></i>
                    </button>
                </div>

                <?php if ($vld): ?>
                <div class="mt-4 text-start w-100">
                    <small class="text-muted d-block"><i class="fas fa-plane"></i>
                        <?= $this->lang->line('briefing_passager_field_vld') ?> #<?= htmlspecialchars($vld['id']) ?>
                    </small>
                    <small class="text-muted d-block">
                        <?= htmlspecialchars($vld['beneficiaire'] ?? '—') ?>
                        <?= $vld['date_vol'] ? ' — ' . date('d/m/Y', strtotime($vld['date_vol'])) : '' ?>
                    </small>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- ── Colonne droite : formulaire de signature ───────────────────────── -->
    <div class="col-md-8">

        <!-- Infos du vol -->
        <h5 class="section-title"><i class="fas fa-plane"></i> <?= $this->lang->line('briefing_passager_sign_flight_info') ?></h5>
        <div class="row mb-3">
            <div class="col-6 col-md-4">
                <small class="text-muted"><?= $this->lang->line('briefing_passager_field_date_vol') ?></small><br>
                <strong><?= $vld['date_vol'] ? date('d/m/Y', strtotime($vld['date_vol'])) : '—' ?></strong>
            </div>
            <div class="col-6 col-md-4">
                <small class="text-muted"><?= $this->lang->line('briefing_passager_field_aerodrome') ?></small><br>
                <strong><?= htmlspecialchars(!empty($vld['aerodrome_nom']) ? $vld['aerodrome_nom'] : ($vld['aerodrome'] ?? '—')) ?></strong>
            </div>
            <div class="col-6 col-md-4">
                <small class="text-muted"><?= $this->lang->line('briefing_passager_field_appareil') ?></small><br>
                <strong><?= htmlspecialchars($vld['airplane_immat'] ?? '—') ?></strong>
            </div>
        </div>

        <!-- Consignes de sécurité -->
        <h5 class="section-title"><i class="fas fa-file-pdf"></i> <?= $this->lang->line('briefing_passager_sign_instructions') ?></h5>
        <div id="scroll-notice" class="alert alert-warning">
            <i class="fas fa-arrow-down"></i> <?= $this->lang->line('briefing_passager_sign_scroll_required') ?>
        </div>
        <?php if ($consignes && !empty($consignes['file_path'])): ?>
            <div class="mb-3">
                <object id="pdf-object" data="<?= base_url($consignes['file_path']) ?>" type="application/pdf" width="100%" height="400">
                    <p><?= $this->lang->line('briefing_passager_consignes_download') ?> :
                        <a href="<?= base_url($consignes['file_path']) ?>" target="_blank">
                            <i class="fas fa-download"></i> PDF
                        </a>
                    </p>
                </object>
                <div class="mt-1">
                    <a href="<?= base_url($consignes['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary">
                        <i class="fas fa-download"></i> <?= $this->lang->line('briefing_passager_consignes_download') ?>
                    </a>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-info mb-3"><?= $this->lang->line('briefing_passager_no_consignes') ?></div>
        <?php endif; ?>

        <!-- Formulaire passager + signature -->
        <form method="post" action="<?= site_url('briefing_sign/submit/' . $token) ?>">
            <h5 class="section-title"><i class="fas fa-user"></i> <?= $this->lang->line('briefing_passager_sign_passenger') ?></h5>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><?= $this->lang->line('briefing_passager_field_nom') ?> <span class="text-danger">*</span></label>
                    <?php
                        $parts = explode(' ', $vld['beneficiaire'] ?? '', 2);
                        $default_nom    = $parts[0] ?? '';
                        $default_prenom = $parts[1] ?? '';
                    ?>
                    <input type="text" name="nom" class="form-control" value="<?= htmlspecialchars($default_nom) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= $this->lang->line('briefing_passager_field_prenom') ?></label>
                    <input type="text" name="prenom" class="form-control" value="<?= htmlspecialchars($default_prenom) ?>">
                </div>
            </div>

            <div class="row mb-3">
                <div class="col-md-6">
                    <label class="form-label"><?= $this->lang->line('briefing_passager_field_ddn') ?></label>
                    <input type="date" name="ddn" class="form-control" value="">
                </div>
                <div class="col-md-6">
                    <label class="form-label"><?= $this->lang->line('briefing_passager_field_poids') ?></label>
                    <input type="number" name="poids" class="form-control" min="20" max="200"
                           value="<?= (int)($vld['participation'] ?? 0) ?: '' ?>">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label"><?= $this->lang->line('briefing_passager_field_urgence') ?></label>
                <input type="text" name="urgence" class="form-control" value="<?= htmlspecialchars($vld['urgence'] ?? '') ?>">
            </div>

            <h5 class="section-title"><i class="fas fa-pen"></i> <?= $this->lang->line('briefing_passager_sign_acceptance') ?></h5>

            <div class="mb-3">
                <div class="form-check">
                    <input class="form-check-input" type="checkbox" name="accept" value="1" id="accept_checkbox" required>
                    <label class="form-check-label fw-bold" for="accept_checkbox">
                        <?= $this->lang->line('briefing_passager_sign_checkbox') ?>
                    </label>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label">
                    <?= $this->lang->line('briefing_passager_sign_draw_pad') ?>
                    <span class="text-muted fw-normal small">(<?= $this->lang->line('briefing_passager_sign_optional') ?>)</span>
                </label>
                <div>
                    <canvas id="signature-pad"></canvas>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPad()">
                    <i class="fas fa-eraser"></i> <?= $this->lang->line('briefing_passager_sign_clear') ?>
                </button>
                <input type="hidden" name="signature_data" id="signature_data">
            </div>

            <div class="d-flex gap-2 mt-4">
                <button type="submit" id="submit-btn" class="btn btn-primary btn-lg flex-grow-1" onclick="return prepareSig()" disabled>
                    <i class="fas fa-check-circle"></i> <?= $this->lang->line('briefing_passager_sign_submit') ?>
                </button>
                <a href="<?= site_url('briefing_passager/upload/' . $vld_id) ?>" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> <?= $this->lang->line('gvv_button_cancel') ?>
                </a>
            </div>
        </form>

    </div><!-- /col right -->

</div><!-- /row -->

</div><!-- /body -->

<script src="<?= base_url('assets/js/signature_pad.umd.min.js') ?>"></script>
<script>
var canvas = document.getElementById('signature-pad');
function resizeCanvas() {
    var ratio = Math.max(window.devicePixelRatio || 1, 1);
    canvas.width  = canvas.offsetWidth  * ratio;
    canvas.height = canvas.offsetHeight * ratio;
    canvas.getContext('2d').scale(ratio, ratio);
    if (typeof signaturePad !== 'undefined') signaturePad.clear();
}
var signaturePad = new SignaturePad(canvas, { backgroundColor: 'rgb(255,255,255)' });
window.addEventListener('resize', resizeCanvas);
resizeCanvas();

function clearPad() {
    signaturePad.clear();
    document.getElementById('signature_data').value = '';
}

function prepareSig() {
    if (!signaturePad.isEmpty()) {
        var norm = document.createElement('canvas');
        norm.width  = 600;
        norm.height = 300;
        var ctx = norm.getContext('2d');
        ctx.fillStyle = 'rgb(255,255,255)';
        ctx.fillRect(0, 0, norm.width, norm.height);
        ctx.drawImage(canvas, 0, 0, norm.width, norm.height);
        var dataUrl = norm.toDataURL('image/png');
        var prefix = 'data:image/png;base64,';
        document.getElementById('signature_data').value = dataUrl.substring(prefix.length);
    }
    return true;
}

// Unlock submit once PDF is scrolled past
var pdfScrolled = false;
function checkPdfScrolled() {
    if (pdfScrolled) return;
    var pdf = document.getElementById('pdf-object');
    if (!pdf) { unlockSubmit(); return; }
    var rect = pdf.getBoundingClientRect();
    if (rect.bottom <= window.innerHeight) { unlockSubmit(); }
}
function unlockSubmit() {
    if (pdfScrolled) return;
    pdfScrolled = true;
    document.getElementById('submit-btn').disabled = false;
    var notice = document.getElementById('scroll-notice');
    if (notice) notice.remove();
    window.removeEventListener('scroll', checkPdfScrolled);
}
<?php if (!($consignes && !empty($consignes['file_path']))): ?>
unlockSubmit();
<?php endif; ?>
window.addEventListener('scroll', checkPdfScrolled);
checkPdfScrolled();

function copySignUrl() {
    var input = document.getElementById('sign_url_field');
    input.select();
    document.execCommand('copy');
    input.blur();
}
</script>
