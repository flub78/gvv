<!-- VIEW: application/views/paiements_en_ligne/bs_decouverte_manager_form.php -->
<?php
/**
 * UC4 - Formulaire gestionnaire pour generer un paiement bon decouverte.
 */
?>

<div id="body" class="body container-fluid">

<?= checkalert($this->session) ?>

<?php if (!empty($error)): ?>
<div class="alert alert-danger"><?= $error ?></div>
<?php endif; ?>

<h3><?= $this->lang->line('gvv_decouverte_manager_title') ?></h3>
<p class="text-muted">
    <?= $this->lang->line('gvv_decouverte_manager_intro') ?>
    &nbsp;<strong><?= htmlspecialchars($section['nom']) ?></strong>
</p>

<div style="max-width: 640px;">

<form method="post" action="<?= current_url() ?>">

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_decouverte_product') ?> <span class="text-danger">*</span></label>
        <select name="product" class="form-select" required>
            <option value=""><?= $this->lang->line('gvv_decouverte_product_choose') ?></option>
            <?php foreach ($produits as $p): ?>
                <?php $selected = ((string) $selected_product === (string) $p['reference']) ? 'selected' : ''; ?>
                <option value="<?= htmlspecialchars($p['reference']) ?>" <?= $selected ?>>
                    <?= htmlspecialchars($p['description']) ?> - <?= euros((float) $p['prix']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_decouverte_beneficiaire') ?> <span class="text-danger">*</span></label>
        <input type="text" name="beneficiaire" class="form-control"
               value="<?= htmlspecialchars($beneficiaire) ?>" required>
    </div>

    <div class="mb-3">
        <label class="form-label"><?= $this->lang->line('gvv_decouverte_de_la_part') ?></label>
        <input type="text" name="de_la_part" class="form-control"
               value="<?= htmlspecialchars($de_la_part) ?>">
    </div>

    <div class="mb-4">
        <label class="form-label"><?= $this->lang->line('gvv_decouverte_email') ?></label>
        <input type="email" name="beneficiaire_email" class="form-control"
               value="<?= htmlspecialchars($beneficiaire_email) ?>"
               placeholder="<?= $this->lang->line('gvv_public_bar_email_placeholder') ?>">
        <div class="form-text text-muted"><?= $this->lang->line('gvv_decouverte_email_help') ?></div>
    </div>

    <div class="alert alert-info small mb-3">
        <i class="fas fa-info-circle"></i>
        <?= $this->lang->line('gvv_decouverte_helloasso_notice') ?>
    </div>

    <button type="submit" name="button" value="generer" class="btn btn-warning btn-lg">
        <i class="fas fa-qrcode"></i> <?= $this->lang->line('gvv_decouverte_generate_button') ?>
    </button>

</form>

</div>

</div>
