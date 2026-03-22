<!-- VIEW: application/views/briefing_passager/bs_signView.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->lang->line('briefing_passager_sign_title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/408316024a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>assets/css/bs_styles.css">
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>assets/css/gvv.css">
    <style>
        #signature-pad { border: 1px solid #dee2e6; border-radius: 4px; background: #fff; touch-action: none; }
        .section-title { border-left: 4px solid #0d6efd; padding-left: 0.75rem; margin: 1.5rem 0 1rem; }
    </style>
</head>
<body class="bg-light">

<header class="container-fluid p-3 bg-success text-white text-center">
    <h1 class="text-center header"><?= $this->config->item('nom_club') ?></h1>
</header>

<div class="container-fluid py-4 px-4">

<?= $message ?>

<!-- 1. QR code + scan invitation -->
<div class="text-center mb-4">
    <img src="data:image/png;base64,<?= $qr_base64 ?>" alt="QR Code" style="max-width:180px;">
    <p class="text-muted small mt-1"><i class="fas fa-qrcode"></i> <?= $this->lang->line('briefing_passager_sign_scan_qr') ?></p>
</div>

<h4 class="text-center mb-4"><?= $this->lang->line('briefing_passager_sign_title') ?></h4>

<!-- 2. Flight info (read-only) -->
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

<!-- 3. Safety instructions PDF -->
<h5 class="section-title"><i class="fas fa-file-pdf"></i> <?= $this->lang->line('briefing_passager_sign_instructions') ?></h5>
<div id="scroll-notice" class="alert alert-warning">
    <i class="fas fa-arrow-down"></i> <?= $this->lang->line('briefing_passager_sign_scroll_required') ?>
</div>
<?php if ($consignes && !empty($consignes['file_path'])): ?>
    <div class="mb-3">
        <object id="pdf-object" data="<?= base_url($consignes['file_path']) ?>" type="application/pdf" width="100%" height="450">
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

<!-- 4. Passenger form + 5/6. Signature -->
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

    <!-- Declaration text -->
    <div class="card mb-3">
        <div class="card-body">
            <p class="mb-0"><?= $this->lang->line('briefing_passager_sign_checkbox') ?></p>
        </div>
    </div>

    <!-- Mandatory checkbox -->
    <div class="mb-3">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="accept" value="1" id="accept_checkbox" required>
            <label class="form-check-label fw-bold" for="accept_checkbox">
                <?= $this->lang->line('briefing_passager_sign_i_accept') ?>
            </label>
        </div>
    </div>

    <!-- Optional signature pad -->
    <div class="mb-3">
        <label class="form-label">
            <?= $this->lang->line('briefing_passager_sign_draw_pad') ?>
            <span class="text-muted fw-normal small">(<?= $this->lang->line('briefing_passager_sign_optional') ?>)</span>
        </label>
        <div>
            <canvas id="signature-pad" width="100%" height="150" class="w-100"></canvas>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mt-1" onclick="clearPad()">
            <i class="fas fa-eraser"></i> <?= $this->lang->line('briefing_passager_sign_clear') ?>
        </button>
        <input type="hidden" name="signature_data" id="signature_data">
    </div>

    <div class="d-grid mt-4">
        <button type="submit" id="submit-btn" class="btn btn-primary btn-lg" onclick="return prepareSig()" disabled>
            <i class="fas fa-check-circle"></i> <?= $this->lang->line('briefing_passager_sign_submit') ?>
        </button>
    </div>
</form>

</div><!-- /container -->

<footer class="container-fluid p-3 mt-3 bg-success text-white text-center">
    <p><?= $this->lang->line('gvv_copyright') ?></p>
</footer>

<script src="<?= base_url('assets/js/signature_pad.umd.min.js') ?>"></script>
<script>
var canvas = document.getElementById('signature-pad');
// Resize canvas to match display size
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
        document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
    }
    return true;
}

// Enable submit button only after user has scrolled past the PDF
var pdfScrolled = false;
function checkPdfScrolled() {
    if (pdfScrolled) return;
    var pdf = document.getElementById('pdf-object');
    if (!pdf) { unlockSubmit(); return; }
    var rect = pdf.getBoundingClientRect();
    if (rect.bottom <= window.innerHeight) {
        unlockSubmit();
    }
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
unlockSubmit(); // No PDF to read, enable immediately
<?php endif; ?>
window.addEventListener('scroll', checkPdfScrolled);
// Check on load in case page is short enough
checkPdfScrolled();
</script>
</body>
</html>
