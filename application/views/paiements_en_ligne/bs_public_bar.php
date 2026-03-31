<!-- VIEW: application/views/paiements_en_ligne/bs_public_bar.php -->
<?php
/**
 * Page publique — règlement consommations bar par QR Code (UC2).
 * Accessible sans connexion.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<?php if (!$section): ?>
    <div class="alert alert-warning">
        <?= $this->lang->line('gvv_public_bar_error_club') ?>
    </div>
<?php else: ?>

<h3><?= $this->lang->line('gvv_public_bar_title') ?></h3>
<p class="text-muted">
    <?= $this->lang->line('gvv_public_bar_intro') ?>
    &nbsp;<strong><?= htmlspecialchars($section['nom']) ?></strong>
</p>

<div style="max-width: 520px;">

<form method="post" action="<?= current_url() ?>">

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_public_bar_prenom') ?> <span class="text-danger">*</span></label>
        <input type="text" name="prenom" class="form-control"
               value="<?= htmlspecialchars($prenom) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_public_bar_nom') ?> <span class="text-danger">*</span></label>
        <input type="text" name="nom" class="form-control"
               value="<?= htmlspecialchars($nom) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_public_bar_email') ?></label>
        <input type="email" name="email" class="form-control"
               value="<?= htmlspecialchars($email) ?>"
               placeholder="<?= $this->lang->line('gvv_public_bar_email_placeholder') ?>">
        <div class="form-text text-muted"><?= $this->lang->line('gvv_public_bar_email_help') ?></div>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_bar_description') ?> <span class="text-danger">*</span></label>
        <input type="text" name="description" class="form-control"
               value="<?= htmlspecialchars($description) ?>"
               placeholder="<?= $this->lang->line('gvv_bar_description_placeholder') ?>" required>
        <div class="form-text text-muted"><?= $this->lang->line('gvv_bar_description_help') ?></div>
    </div>

    <div class="mb-4">
        <label class="form-label"><?= $this->lang->line('gvv_bar_montant') ?> <span class="text-danger">*</span></label>
        <div class="input-group" style="max-width: 180px;">
            <input type="number" name="montant" class="form-control"
                   value="<?= htmlspecialchars($montant) ?>"
                   min="2" step="0.01" required>
            <span class="input-group-text">€</span>
        </div>
        <div class="form-text text-muted"><?= $this->lang->line('gvv_public_bar_montant_help') ?></div>
    </div>

    <div class="alert alert-info small mb-3">
        <i class="fas fa-info-circle"></i>
        <?= $this->lang->line('gvv_public_bar_helloasso_notice') ?>
    </div>

    <button type="submit" name="button" value="valider" class="btn btn-warning btn-lg">
        <i class="fas fa-credit-card"></i> <?= $this->lang->line('gvv_public_bar_button_valider') ?>
    </button>

</form>

</div><!-- max-width -->

<?php endif; ?>

</div>
