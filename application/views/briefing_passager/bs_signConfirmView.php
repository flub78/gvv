<!-- VIEW: application/views/briefing_passager/bs_signConfirmView.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->lang->line('briefing_passager_sign_title') ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/408316024a.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" type="text/css" href="<?= base_url() ?>assets/css/bs_styles.css">
</head>
<body class="bg-light">

<header class="container-fluid p-3 bg-success text-white text-center">
    <h1 class="text-center header"><?= $this->config->item('nom_club') ?></h1>
</header>

<div class="container-fluid py-4 px-4">

    <div class="alert alert-success d-flex align-items-center gap-3 mb-4">
        <i class="fas fa-check-circle fa-2x"></i>
        <div>
            <strong><?= $this->lang->line('briefing_passager_sign_success') ?></strong>
            <?php if (!empty($nom)): ?>
            — <?= htmlspecialchars($nom) ?>
            <?php endif; ?>
            <?php if (!empty($vld['date_vol'])): ?>
            <span class="text-muted ms-2 small"><?= date('d/m/Y', strtotime($vld['date_vol'])) ?></span>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($pdf_base64)): ?>
    <div class="card">
        <div class="card-header"><i class="fas fa-file-pdf"></i> <?= $this->lang->line('briefing_passager_pdf_title') ?></div>
        <div class="card-body p-0">
            <object data="data:application/pdf;base64,<?= $pdf_base64 ?>"
                    type="application/pdf" width="100%" style="height:85vh; display:block;">
                <p class="p-3"><?= $this->lang->line('briefing_passager_consignes_download') ?></p>
            </object>
        </div>
    </div>
    <?php endif; ?>

</div>

<footer class="container-fluid p-3 mt-3 bg-success text-white text-center">
    <p><?= $this->lang->line('gvv_copyright') ?></p>
</footer>

</body>
</html>
