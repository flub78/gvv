<!-- VIEW: application/views/parametres/bs_message.php -->
<div id="body" class="body container-fluid">
    <h3><i class="fas fa-sliders-h text-primary me-2"></i><?= htmlspecialchars($title) ?></h3>
    <div class="alert alert-warning" role="alert" id="parametres-message">
        <i class="fas fa-exclamation-triangle me-1"></i> <?= $message ?>
    </div>
</div>
