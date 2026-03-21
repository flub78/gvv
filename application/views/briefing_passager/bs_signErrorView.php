<!-- VIEW: application/views/briefing_passager/bs_signErrorView.php -->
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $this->lang->line('briefing_passager_sign_title') ?></title>
    <link rel="stylesheet" href="<?= base_url('assets/bootstrap/css/bootstrap.min.css') ?>">
    <link rel="stylesheet" href="<?= base_url('assets/fontawesome/css/all.min.css') ?>">
</head>
<body class="bg-light">
<div class="container py-5" style="max-width:600px; text-align:center;">
    <div class="card shadow-sm">
        <div class="card-body py-5">
            <i class="fas fa-exclamation-triangle text-warning" style="font-size:4rem;"></i>
            <h3 class="mt-4"><?= htmlspecialchars($error_message) ?></h3>
        </div>
    </div>
</div>
</body>
</html>
